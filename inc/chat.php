<?php
defined('ABSPATH') || exit;

/* ─ DB: daily token usage table ─ */
add_action('admin_init', function () {
    if (get_option('sjioc_chat_usage_db_ver') !== '1') {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = $wpdb->prefix . 'sjioc_chat_usage';
        dbDelta("CREATE TABLE {$t} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usage_date date NOT NULL,
            prompt_tokens int(10) unsigned NOT NULL DEFAULT 0,
            completion_tokens int(10) unsigned NOT NULL DEFAULT 0,
            total_tokens int(10) unsigned NOT NULL DEFAULT 0,
            call_count int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY usage_date (usage_date)
        ) {$wpdb->get_charset_collate()};");
        update_option('sjioc_chat_usage_db_ver', '1');
    }
});

/* ─────────────────────────────────────
   AJAX: Chat
───────────────────────────────────── */
add_action('wp_ajax_sjioc_chat',        'sjioc_chat_ajax');
add_action('wp_ajax_nopriv_sjioc_chat', 'sjioc_chat_ajax');

function sjioc_chat_ajax(): void {
    check_ajax_referer('sjioc_ajax', 'nonce');

    $message = sanitize_text_field(wp_unslash($_POST['message'] ?? ''));
    if (!$message) wp_send_json_error('empty');

    // 1. License plate — local DB only, own (looser) rate limit so it doesn't
    //    share the LLM budget, but is still capped to stop registry scraping.
    $stripped      = strtoupper(preg_replace('/[\s\-]/', '', $message));
    $is_plate_like = preg_match('/^[A-Z]{1,4}[0-9]{1,4}[A-Z0-9]{0,3}$/', $stripped)
                  || preg_match('/^[0-9]{1,4}[A-Z]{1,4}[A-Z0-9]{0,3}$/', $stripped)
                  || preg_match('/^[0-9]{5,6}$/', $stripped); // Delaware standard plates are numeric-only, no letters
    if ($is_plate_like) {
        $plate_key  = 'sjioc_rl_plate_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
        $plate_hits = (int) get_transient($plate_key);
        if ($plate_hits >= 15) {
            wp_send_json_error('Too many plate lookups — please wait a few minutes before trying again.');
        }
        set_transient($plate_key, $plate_hits + 1, 5 * MINUTE_IN_SECONDS);

        $vehicle = sjioc_lookup_plate($stripped);
        wp_send_json_success(['html' => $vehicle
            ? sjioc_plate_html($vehicle)
            : sjioc_plate_not_found_html(strtoupper($message))]);
        return;
    }

    // 2. Rate limit — only LLM-bound requests count
    $ip_key = 'sjioc_rl_chat_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits   = (int) get_transient($ip_key);
    if ($hits >= 5) {
        wp_send_json_error('Too many requests — please wait 3 minutes before trying again.');
    }
    set_transient($ip_key, $hits + 1, 180);

    if (mb_strlen($message) > 500) {
        wp_send_json_error('Please keep your message under 500 characters.');
    }

    // 4. KB excerpt — only lines relevant to this message (max 10 lines / 500 words)
    $kb_excerpt = sjioc_chat_kb_excerpt($message, get_option('sjioc_kb_text', ''));

    // 4b. Live data — only queried when the message actually asks about events
    //     or ministries, so unrelated questions don't pay the extra DB/token cost.
    $live_context = sjioc_chat_live_context($message);
    if ($live_context) {
        $kb_excerpt = trim($kb_excerpt . "\n" . $live_context);
    }

    // 5. LLM call with targeted KB excerpt
    $result = sjioc_azure_oai($message, $kb_excerpt);
    sjioc_store_token_usage($result['usage']);
    wp_send_json_success(['html' => $result['html']]);
}

/* ─────────────────────────────────────
   KB Excerpt
───────────────────────────────────── */

function sjioc_chat_kb_excerpt(string $message, string $kb): string {
    if (!$kb) return '';
    $words = array_unique(array_filter(
        preg_split('/\s+/', mb_strtolower(preg_replace('/[^\w\s]/u', '', $message))),
        fn($w) => mb_strlen($w) > 3
    ));
    if (!$words) return '';
    $lines   = preg_split('/\r?\n/', mb_substr($kb, 0, 2000));
    $matched = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        $ll = mb_strtolower($line);
        foreach ($words as $word) {
            if (str_contains($ll, $word)) { $matched[] = $line; break; }
        }
    }
    // Cap at 10 lines and 500 words
    $result = [];
    $wcount = 0;
    foreach (array_slice($matched, 0, 10) as $line) {
        $lw = str_word_count($line);
        if ($wcount + $lw > 500) break;
        $result[] = $line;
        $wcount  += $lw;
    }
    return implode("\n", $result);
}

/* ─────────────────────────────────────
   Live Context — Events / Ministries
   Pulled fresh from the DB/CPTs, only when the message is actually about
   them, so "Upcoming Events" and "Join a Ministry" (the quick-reply chips)
   always have real, current data instead of relying on the admin manually
   duplicating it into the Knowledge Base text.
───────────────────────────────────── */
function sjioc_chat_live_context(string $message): string {
    $ll      = mb_strtolower($message);
    $context = '';

    if (preg_match('/\b(event|events|upcoming|calendar|happening|schedule)\b/', $ll)) {
        $events = sjioc_get_db_events(1);
        $context .= $events
            ? "\nUpcoming events:\n" . implode("\n", array_map(
                  fn($e) => '- ' . $e['title'] . ' (' . $e['mon'] . ' ' . $e['day'] . ')',
                  array_slice($events, 0, 5)
              ))
            : "\nNo upcoming events are currently listed on the calendar.";
    }

    if (preg_match('/\b(ministry|ministries|volunteer|serve|involved)\b/', $ll)) {
        $ministries = get_posts([
            'post_type'      => 'sjioc_ministry',
            'posts_per_page' => 12,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        $titles = wp_list_pluck($ministries, 'post_title');
        $context .= $titles
            ? "\nActive ministries: " . implode(', ', $titles)
            : "\nNo ministries are currently listed.";
    }

    return $context;
}

function sjioc_store_token_usage(array $usage): void {
    global $wpdb;
    if (empty($usage['total_tokens'])) return;
    $t = $wpdb->prefix . 'sjioc_chat_usage';
    $wpdb->query($wpdb->prepare(
        "INSERT INTO `{$t}` (usage_date, prompt_tokens, completion_tokens, total_tokens, call_count)
         VALUES (%s, %d, %d, %d, 1)
         ON DUPLICATE KEY UPDATE
           prompt_tokens     = prompt_tokens     + VALUES(prompt_tokens),
           completion_tokens = completion_tokens + VALUES(completion_tokens),
           total_tokens      = total_tokens      + VALUES(total_tokens),
           call_count        = call_count + 1",
        current_time('Y-m-d'),
        (int) ($usage['prompt_tokens']     ?? 0),
        (int) ($usage['completion_tokens'] ?? 0),
        (int) ($usage['total_tokens']      ?? 0)
    ));
}

/* ─────────────────────────────────────
   Vehicle plate helpers
───────────────────────────────────── */

function sjioc_lookup_plate($normalized) {
    global $wpdb;
    $table = $wpdb->prefix . 'sjioc_vehicles';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM `{$table}` WHERE UPPER(REPLACE(REPLACE(license_plate,' ',''),'-','')) = %s",
        $normalized
    ));
}

// First name of each owner stays visible; last name(s) keep only the first
// 2 letters, rest asterisked. Handles joint owners ("A B / C D") and
// apostrophe surnames (O'Brien, D'Souza) by masking letter-runs, not raw chars.
function sjioc_mask_name(string $name): string {
    $people = preg_split('/\s*\/\s*/', trim($name));
    $masked = array_map(function ($person) {
        $person = trim($person);
        $space  = strpos($person, ' ');
        if ($space === false) return $person;
        $first = substr($person, 0, $space);
        $rest  = substr($person, $space + 1);
        $rest_masked = preg_replace_callback('/\p{L}+/u', function ($m) {
            $word = $m[0];
            $len  = mb_strlen($word);
            if ($len <= 2) return $word;
            return mb_substr($word, 0, 2) . str_repeat('*', $len - 2);
        }, $rest);
        return $first . ' ' . $rest_masked;
    }, $people);
    return implode(' / ', $masked);
}

function sjioc_plate_html($v) {
    $titles = [
        '&#128663; <strong>Vehicle Found!</strong> Sherlock would be proud.',
        '&#128269; <strong>Match Found!</strong> Case closed — here\'s the owner.',
        '&#9989; <strong>Got it!</strong> Vehicle successfully located.',
        '&#127919; <strong>Bullseye!</strong> Found the owner of this vehicle.',
        '&#128664; <strong>Vehicle Located!</strong> This car\'s a familiar face around here.',
        '&#128270; <strong>Case Closed!</strong> Here\'s the vehicle\'s owner.',
        '&#128203; <strong>Found You!</strong> This plate is on our list.',
        '&#10024; <strong>Mystery Solved!</strong> Here\'s who this belongs to.',
    ];
    $html  = $titles[ array_rand($titles) ] . '<br><br>';
    $html .= 'Owner: <strong>' . esc_html(sjioc_mask_name($v->owner_name)) . '</strong><br>';
    if (!empty($v->vehicle_desc)) {
        $html .= 'Vehicle: ' . esc_html($v->vehicle_desc) . '<br>';
    }
    $html .= '<br>Please contact the owner directly if you\'re able, or connect with the <strong>Church Office</strong> for more details. &#128591;';
    return $html;
}

function sjioc_plate_not_found_html($plate) {
    $ph  = esc_html(sjioc_phone());
    $p   = '<strong>' . esc_html($plate) . '</strong>';
    $msgs = [
        '&#128269; Plate ' . $p . ' isn\'t in our parish registry. Could be a visitor or a typo — double-check and try again. For help, contact our <strong>Secretary</strong> or <strong>Trustee</strong>.<br>&#128222; ' . $ph,
        '&#128663; ' . $p . ' drew a blank! This vehicle isn\'t registered with SJIOC. Let our <strong>Secretary</strong> or <strong>Trustee</strong> know — they can help track down the owner.<br>&#128222; ' . $ph,
        '&#128664; Hmm, ' . $p . ' doesn\'t match anyone in our records. Might be a guest today? Our <strong>Secretary</strong> or <strong>Trustee</strong> can assist.<br>&#128222; ' . $ph,
        '&#128270; No match for ' . $p . ' in the SJIOC registry. If you think this plate should be registered, speak with our <strong>Secretary</strong> or <strong>Trustee</strong>.<br>&#128222; ' . $ph,
        '&#128203; ' . $p . ' isn\'t on our list — possibly a visitor\'s vehicle. Our <strong>Secretary</strong> or <strong>Trustee</strong> can help you sort it out.<br>&#128222; ' . $ph,
        '&#9989; Double-checked — ' . $p . ' isn\'t showing up anywhere in our registry. New here, perhaps? Our <strong>Secretary</strong> or <strong>Trustee</strong> would love to help.<br>&#128222; ' . $ph,
        '&#127919; Close, but no match! ' . $p . ' isn\'t registered with us yet. Our <strong>Secretary</strong> or <strong>Trustee</strong> can look into it.<br>&#128222; ' . $ph,
        '&#10024; ' . $p . ' seems to have slipped past our records entirely. Our <strong>Secretary</strong> or <strong>Trustee</strong> can help clear up the mystery.<br>&#128222; ' . $ph,
        '&#10067; Stumped! ' . $p . ' isn\'t ringing any bells in our system. Our <strong>Secretary</strong> or <strong>Trustee</strong> should be able to help.<br>&#128222; ' . $ph,
        '&#128064; We\'ve looked twice and still no sign of ' . $p . ' in our registry. Might be a first-time visitor! Our <strong>Secretary</strong> or <strong>Trustee</strong> can assist.<br>&#128222; ' . $ph,
    ];
    return $msgs[ array_rand($msgs) ];
}

/* ─────────────────────────────────────
   Azure OpenAI
───────────────────────────────────── */

function sjioc_azure_oai(string $message, string $kb_excerpt = ''): array {
    $endpoint = defined('SJIOC_AZURE_OAI_ENDPOINT') ? SJIOC_AZURE_OAI_ENDPOINT : '';
    $key      = defined('SJIOC_AZURE_OAI_KEY')      ? SJIOC_AZURE_OAI_KEY      : '';
    $deploy   = defined('SJIOC_AZURE_OAI_DEPLOY')   ? SJIOC_AZURE_OAI_DEPLOY   : 'gpt-4o';

    if (!$endpoint || !$key) {
        return ['html' => 'The assistant is not fully configured yet. Please contact the <strong>Secretary</strong> or <strong>Trustee</strong>.<br>&#128222; ' . esc_html(sjioc_phone()), 'usage' => []];
    }

    $url  = rtrim($endpoint, '/') . '/openai/deployments/' . rawurlencode($deploy) . '/chat/completions?api-version=2024-02-01';
    $body = wp_json_encode([
        'messages'    => [
            ['role' => 'system', 'content' => sjioc_chat_system_prompt($kb_excerpt)],
            ['role' => 'user',   'content' => $message],
        ],
        'max_tokens'  => max(50, min(1000, (int) get_option('sjioc_chat_max_tokens', 250))),
        'temperature' => max(0.0, min(1.0, (float) get_option('sjioc_chat_temperature', 0.4))),
    ]);

    $usage_total = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

    // Azure OpenAI occasionally returns a successful response with empty content
    // (seen on the very first call of a session) — retry once before giving up,
    // so a normal question doesn't need a manual second click to work.
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $res = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json', 'api-key' => $key],
            'body'    => $body,
            'timeout' => 20,
        ]);

        if (is_wp_error($res)) {
            return ['html' => 'Sorry, I\'m having trouble connecting. Please call us at <strong>' . esc_html(sjioc_phone()) . '</strong>.', 'usage' => $usage_total];
        }

        $data  = json_decode(wp_remote_retrieve_body($res), true);
        $reply = trim($data['choices'][0]['message']['content'] ?? '');

        foreach (($data['usage'] ?? []) as $k => $v) {
            if (isset($usage_total[$k])) $usage_total[$k] += (int) $v;
        }

        if ($reply) {
            return [
                'html'  => wp_kses($reply, ['strong' => [], 'em' => [], 'br' => [], 'a' => ['href' => [], 'target' => [], 'style' => []]]),
                'usage' => $usage_total,
            ];
        }
    }

    return ['html' => 'I\'m not sure about that. Please contact our <strong>Secretary</strong> or <strong>Trustee</strong> at ' . esc_html(sjioc_phone()) . '.', 'usage' => $usage_total];
}

function sjioc_chat_system_prompt($kb = '') {
    $times = implode(' | ', array_map(fn($wt) => $wt['label'] . ' ' . $wt['time'], sjioc_get_worship_times()));
    $header = sprintf(
        "You are the parish assistant for %s, an Indian Orthodox Christian church.\n" .
        "Address: %s | Phone: %s | Email: %s\n" .
        "Services: %s\n\n",
        sjioc_name(), sjioc_address(), sjioc_phone(), sjioc_email(), $times
    );

    $rules  = get_option('sjioc_chat_rules', sjioc_default_chat_rules());
    $prompt = $header . $rules;

    if ($kb) {
        $prompt .= "\n\nParish info:\n" . mb_substr($kb, 0, 2000);
    }

    return $prompt;
}

function sjioc_default_chat_rules() {
    return "Your role is to warmly welcome and assist parishioners and visitors — answering questions about our services, sacraments, events, faith, and community life.\n" .
           "Speak with warmth, humility, and pastoral care.\n" .
           "Never cram multiple facts into one long sentence — when listing 2 or more items (like service times), insert <br> between each one so they appear on separate lines, and bold key details like times and names using <strong>text</strong>.\n" .
           "Do NOT use Markdown formatting — no **asterisks**, no bullet dashes, no # symbols. This is plain HTML only; asterisks will show up literally to the reader, not as bold text.\n" .
           "Correct example: <strong>Saturday</strong> — Evening Prayer at <strong>6:00 PM</strong><br><strong>Sunday</strong> — Morning Prayer at <strong>8:30 AM</strong><br><strong>Sunday</strong> — Holy Qurbana at <strong>9:30 AM</strong>\n" .
           "Otherwise, keep responses to 2–3 warm, conversational sentences. Never invent or guess — only share what is true from the details provided above.\n" .
           "When unsure about something, invite the person to reach our Secretary or Trustee using the contact details above.\n" .
           "If asked something unrelated to the parish or the Christian faith, gently redirect with a brief, fitting Bible verse and invite them to ask about our church community instead.";
}
