<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Unsubscribe
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('template_redirect', array($this, 'maybe_handle_unsubscribe'));
    }

    public static function build_url($token)
    {
        return add_query_arg('hdbis_unsubscribe', $token, home_url('/'));
    }

    public function maybe_handle_unsubscribe()
    {
        // No nonce here by design: this is a one-click unsubscribe link clicked directly from an
        // email, often days later and with no WordPress session -- a nonce would be invalid by
        // the time it's used. The 64-char random token itself is the unguessable credential.
        if (empty($_GET['hdbis_unsubscribe'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $token = sanitize_text_field(wp_unslash($_GET['hdbis_unsubscribe'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        $subscriber = HDBIS_Repository::get_subscriber_by_token($token);

        if ($subscriber) {
            HDBIS_Repository::unsubscribe_by_token($token);
            wc_add_notice(__('You will no longer receive back-in-stock notifications for this item.', 'hdwebmobile-back-in-stock-waitlist'));
        } else {
            wc_add_notice(__('This unsubscribe link is no longer valid.', 'hdwebmobile-back-in-stock-waitlist'), 'notice');
        }

        wp_safe_redirect(home_url('/'));
        exit;
    }
}
