<?php
defined('ABSPATH') || exit;

function sjioc_recaptcha_site_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_site_key', '');
}

function sjioc_recaptcha_secret_key(): string {
    return (string) sjioc_get('sjioc_recaptcha_secret_key', '');
}

function sjioc_recaptcha_is_configured(): bool {
    return sjioc_recaptcha_site_key() !== '' && sjioc_recaptcha_secret_key() !== '';
}

// Returns true if the token passes Google's verification (score >= $min_score).
// Returns true when keys are not yet configured so forms still work during setup.
// Fails open (returns true) if Google is unreachable to avoid blocking legit users.
function sjioc_recaptcha_verify(string $token, string $action = '', float $min_score = 0.5): bool {
    if (!sjioc_recaptcha_is_configured()) return true;
    if (!$token) return false;

    $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body'    => [
            'secret'   => sjioc_recaptcha_secret_key(),
            'response' => $token,
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($resp)) return true; // network failure — don't punish real users

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['success'])) return false;
    if ($action && !empty($data['action']) && $data['action'] !== $action) return false;
    if (isset($data['score']) && (float) $data['score'] < $min_score) return false;

    return true;
}
