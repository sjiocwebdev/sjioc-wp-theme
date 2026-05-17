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

    // 1. License plate — local DB only, exempt from rate limit
    $stripped      = strtoupper(preg_replace('/[\s\-]/', '', $message));
    $is_plate_like = preg_match('/^[A-Z]{1,4}[0-9]{1,4}[A-Z0-9]{0,3}$/', $stripped)
                  || preg_match('/^[0-9]{1,4}[A-Z]{1,4}[A-Z0-9]{0,3}$/', $stripped);
    if ($is_plate_like) {
        $vehicle = sjioc_lookup_plate($stripped);
        wp_send_json_success(['html' => $vehicle
            ? sjioc_plate_html($vehicle)
            : sjioc_plate_not_found_html(strtoupper($message))]);
        return;
    }

    // 2. PHP intent shortcuts — free responses, exempt from rate limit
    $php_response = sjioc_chat_php_intent($message);
    if ($php_response) {
        wp_send_json_success(['html' => $php_response]);
        return;
    }

    // 3. Rate limit — only LLM-bound requests count
    $ip_key = 'sjioc_rl_chat_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits   = (int) get_transient($ip_key);
    if ($hits >= 5) {
        wp_send_json_error('Too many requests — please wait 3 minutes before trying again.');
    }
    set_transient($ip_key, $hits + 1, 180);

    if (mb_strlen($message) > 500) {
        wp_send_json_error('Please keep your message under 500 characters.');
    }

    // 4. KB excerpt — only lines relevant to this message (reduces prompt tokens)
    $kb_full    = get_option('sjioc_kb_text', '');
    $kb_excerpt = sjioc_chat_kb_excerpt($message, $kb_full);

    // 5. If KB is populated but nothing matched, and no church keyword — skip LLM
    if ($kb_full && !$kb_excerpt && !sjioc_chat_is_church_related($message)) {
        wp_send_json_success(['html' => sjioc_chat_sorry()]);
        return;
    }

    // 6. LLM call with targeted KB excerpt
    $result = sjioc_azure_oai($message, $kb_excerpt);
    sjioc_store_token_usage($result['usage']);
    wp_send_json_success(['html' => $result['html']]);
}

/* ─────────────────────────────────────
   PHP Intent Shortcuts
───────────────────────────────────── */

function sjioc_chat_php_intent(string $message): string {
    // Service timings
    if (preg_match('/\b(time|timing|when|start|hour|schedule|qurbana|holy.?qurbana|sunday.?school|saturday.?service|mass)\b/i', $message)) {
        return '&#9203; <strong>Service Times at ' . esc_html(sjioc_abbr()) . ':</strong><br>'
             . 'Holy Qurbana: <strong>' . esc_html(sjioc_qurbana()) . '</strong><br>'
             . 'Sunday School: <strong>' . esc_html(sjioc_school()) . '</strong><br>'
             . 'Saturday: <strong>' . esc_html(sjioc_get('sjioc_saturday', '5:00–7:30 PM')) . '</strong>';
    }
    // Contact / location
    if (preg_match('/\b(phone|call|email|contact|address|location|where|directions|reach)\b/i', $message)) {
        return '&#128222; <strong>' . esc_html(sjioc_abbr()) . ' Contact:</strong><br>'
             . 'Phone: <strong>' . esc_html(sjioc_phone()) . '</strong><br>'
             . 'Email: <strong>' . esc_html(sjioc_email()) . '</strong><br>'
             . 'Address: <strong>' . esc_html(sjioc_address()) . '</strong>';
    }
    return '';
}

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
    return implode("\n", array_slice($matched, 0, 15));
}

function sjioc_chat_is_church_related(string $message): bool {
    return (bool) preg_match(
        '/\b(church|parish|orthodox|christian|faith|prayer|worship|god|jesus|christ|liturgy|sacrament|vicar|priest|bible|ministry|trustee|secretary|event|qurbana|achen|malankara|baptism|marriage|funeral|lent|easter|christmas|fasting|saint|holy)\b/i',
        $message
    );
}

function sjioc_chat_sorry(): string {
    return 'I can only help with questions about our parish — services, events, faith, or church life. '
         . '<em>"I am the way, the truth, and the life." — John 14:6</em><br><br>'
         . 'For parish matters, feel free to ask again or <strong>contact us: '
         . esc_html(sjioc_phone()) . '</strong>.';
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

function sjioc_plate_html($v) {
    $html  = '&#128663; <strong>Vehicle Found</strong><br><br>';
    $html .= 'Owner: <strong>' . esc_html($v->owner_name) . '</strong><br>';
    if (!empty($v->vehicle_desc)) {
        $html .= 'Vehicle: ' . esc_html($v->vehicle_desc) . '<br>';
    }
    $html .= '<br>Please speak with a <strong>Trustee</strong> or <strong>Usher</strong> to reach the owner. &#128591;';
    return $html;
}

function sjioc_plate_not_found_html($plate) {
    $ph  = esc_html(sjioc_phone());
    $p   = '<strong>' . esc_html($plate) . '</strong>';
    $msgs = [
        '&#128269; Plate ' . $p . ' isn\'t in our parish registry. Could be a visitor or a typo — double-check and try again. For help, contact our <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . $ph,
        '&#128663; ' . $p . ' drew a blank! This vehicle isn\'t registered with SJIOC. Let our <strong>Secretary</strong> or a <strong>Trustee</strong> know — they can help track down the owner.<br>&#128222; ' . $ph,
        '&#128664; Hmm, ' . $p . ' doesn\'t match anyone in our records. Might be a guest today? Our <strong>Secretary</strong> or a <strong>Trustee</strong> can assist.<br>&#128222; ' . $ph,
        '&#128270; No match for ' . $p . ' in the SJIOC registry. If you think this plate should be registered, speak with our <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . $ph,
        '&#128203; ' . $p . ' isn\'t on our list — possibly a visitor\'s vehicle. Our <strong>Secretary</strong> or a <strong>Trustee</strong> can help you sort it out.<br>&#128222; ' . $ph,
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
        return ['html' => 'The assistant is not fully configured yet. Please contact the <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . esc_html(sjioc_phone()), 'usage' => []];
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

    $res = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json', 'api-key' => $key],
        'body'    => $body,
        'timeout' => 20,
    ]);

    if (is_wp_error($res)) {
        return ['html' => 'Sorry, I\'m having trouble connecting. Please call us at <strong>' . esc_html(sjioc_phone()) . '</strong>.', 'usage' => []];
    }

    $data  = json_decode(wp_remote_retrieve_body($res), true);
    $reply = trim($data['choices'][0]['message']['content'] ?? '');

    if (!$reply) {
        return ['html' => 'I\'m not sure about that. Please contact our <strong>Secretary</strong> or a <strong>Trustee</strong> at ' . esc_html(sjioc_phone()) . '.', 'usage' => []];
    }

    return [
        'html'  => wp_kses($reply, ['strong' => [], 'em' => [], 'br' => [], 'a' => ['href' => [], 'target' => [], 'style' => []]]),
        'usage' => $data['usage'] ?? [],
    ];
}

function sjioc_chat_system_prompt($kb = '') {
    $header = sprintf(
        "You are the parish assistant for %s, an Indian Orthodox Christian church.\n" .
        "Address: %s | Phone: %s | Email: %s\n" .
        "Services: Holy Qurbana %s | Sunday School %s | Saturday %s\n\n",
        sjioc_name(), sjioc_address(), sjioc_phone(), sjioc_email(),
        sjioc_qurbana(), sjioc_school(), sjioc_get('sjioc_saturday', '5:00–7:30 PM')
    );

    $rules  = get_option('sjioc_chat_rules', sjioc_default_chat_rules());
    $prompt = $header . $rules;

    if ($kb) {
        $prompt .= "\n\nParish info:\n" . mb_substr($kb, 0, 2000);
    }

    return $prompt;
}

function sjioc_default_chat_rules() {
    return "Only answer questions about this church, its faith, services, events, and parish life.\n" .
           "If a question is unrelated to the church, reply: \"I can only help with parish questions. Please call us for assistance.\"\n" .
           "Keep answers to 2-3 sentences. Never invent or guess information.\n" .
           "If unsure, direct the person to contact the Secretary or a Trustee using the contact details above.";
}
