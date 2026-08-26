<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Ajax
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
        add_action('wp_ajax_hdbis_subscribe', array($this, 'subscribe'));
        add_action('wp_ajax_nopriv_hdbis_subscribe', array($this, 'subscribe'));
    }

    public function subscribe()
    {
        check_ajax_referer('hdbis_frontend_nonce', 'nonce');

        $product_id   = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
        $email        = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $name         = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';

        if (!$product_id || !is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'hdwebmobile-back-in-stock-waitlist')));
        }

        $target = wc_get_product($variation_id ?: $product_id);

        if (!$target || $target->is_in_stock()) {
            wp_send_json_error(array('message' => __('This item is already in stock.', 'hdwebmobile-back-in-stock-waitlist')));
        }

        $result = HDBIS_Repository::add_subscriber($product_id, $variation_id, $email, $name);

        if ('duplicate' === $result) {
            wp_send_json_error(array('message' => __("You're already on the waitlist for this item.", 'hdwebmobile-back-in-stock-waitlist')));
        }

        wp_send_json_success(array('message' => __("You're on the list! We'll email you when it's back.", 'hdwebmobile-back-in-stock-waitlist')));
    }
}
