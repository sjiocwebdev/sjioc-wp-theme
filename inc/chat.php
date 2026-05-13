<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   AJAX: Chat
───────────────────────────────────── */
add_action('wp_ajax_sjioc_chat',        'sjioc_chat_ajax');
add_action('wp_ajax_nopriv_sjioc_chat', 'sjioc_chat_ajax');

function sjioc_chat_ajax() {
    check_ajax_referer('sjioc_ajax', 'nonce');

    $message = sanitize_text_field(wp_unslash($_POST['message'] ?? ''));
    if (!$message) {
        wp_send_json_error('empty');
    }

    // Normalize and check if it looks like a license plate
    $stripped      = strtoupper(preg_replace('/[\s\-]/', '', $message));
    $is_plate_like = preg_match('/^[A-Z]{1,4}[0-9]{1,4}[A-Z0-9]{0,3}$/', $stripped)
                  || preg_match('/^[0-9]{1,4}[A-Z]{1,4}[A-Z0-9]{0,3}$/', $stripped);

    if ($is_plate_like) {
        $vehicle = sjioc_lookup_plate($stripped);
        if ($vehicle) {
            wp_send_json_success(['html' => sjioc_plate_html($vehicle)]);
            return;
        }
        wp_send_json_success(['html' => sjioc_plate_not_found_html(strtoupper($message))]);
        return;
    }

    // Rate limit — 5 OpenAI requests per 3 minutes per IP (plate lookups exempt)
    $ip_key = 'sjioc_rl_chat_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits   = (int) get_transient($ip_key);
    if ($hits >= 5) {
        wp_send_json_error('Too many requests — please wait a few minutes before trying again.');
    }
    set_transient($ip_key, $hits + 1, 180);

    // General question → Azure OpenAI
    wp_send_json_success(['html' => sjioc_azure_oai($message)]);
}

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

function sjioc_azure_oai($message) {
    $endpoint = defined('SJIOC_AZURE_OAI_ENDPOINT') ? SJIOC_AZURE_OAI_ENDPOINT : '';
    $key      = defined('SJIOC_AZURE_OAI_KEY')      ? SJIOC_AZURE_OAI_KEY      : '';
    $deploy   = defined('SJIOC_AZURE_OAI_DEPLOY')   ? SJIOC_AZURE_OAI_DEPLOY   : 'gpt-4o';

    if (!$endpoint || !$key) {
        return 'The assistant is not fully configured yet. Please contact the <strong>Secretary</strong> or a <strong>Trustee</strong>.<br>&#128222; ' . esc_html(sjioc_phone());
    }

    $url  = rtrim($endpoint, '/') . '/openai/deployments/' . rawurlencode($deploy) . '/chat/completions?api-version=2024-02-01';
    $kb   = get_option('sjioc_kb_text', '');
    $body = wp_json_encode([
        'messages'    => [
            ['role' => 'system', 'content' => sjioc_chat_system_prompt($kb)],
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
        return 'Sorry, I\'m having trouble connecting. Please call us at <strong>' . esc_html(sjioc_phone()) . '</strong>.';
    }

    $data  = json_decode(wp_remote_retrieve_body($res), true);
    $reply = trim($data['choices'][0]['message']['content'] ?? '');

    if (!$reply) {
        return 'I\'m not sure about that. Please contact our <strong>Secretary</strong> or a <strong>Trustee</strong> at ' . esc_html(sjioc_phone()) . '.';
    }

    return wp_kses($reply, [
        'strong' => [], 'em' => [], 'br' => [],
        'a'      => ['href' => [], 'target' => [], 'style' => []],
    ]);
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
