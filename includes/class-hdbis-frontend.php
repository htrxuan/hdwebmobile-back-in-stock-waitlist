<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Frontend
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
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
        add_action('woocommerce_simple_add_to_cart', array($this, 'render_simple_notify_form'), 35);
        add_action('woocommerce_after_single_variation', array($this, 'render_variation_notify_container'), 5);
    }

    public function maybe_enqueue_assets()
    {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'hdbis-frontend-css',
            HDBIS_PLUGIN_URL . 'assets/css/hdbis-frontend.css',
            array(),
            HDBIS_VERSION
        );

        wp_enqueue_script(
            'hdbis-frontend-js',
            HDBIS_PLUGIN_URL . 'assets/js/hdbis-frontend.js',
            array('jquery'),
            HDBIS_VERSION,
            true
        );

        wp_localize_script('hdbis-frontend-js', 'hdbisParams', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('hdbis_frontend_nonce'),
            'i18n'     => array(
                'success'   => __("You're on the list! We'll email you when it's back.", 'hdwebmobile-back-in-stock-waitlist'),
                'duplicate' => __("You're already on the waitlist for this item.", 'hdwebmobile-back-in-stock-waitlist'),
                'error'     => __('Something went wrong. Please try again.', 'hdwebmobile-back-in-stock-waitlist'),
            ),
        ));
    }

    public function render_simple_notify_form()
    {
        global $product;

        if (!$product instanceof \WC_Product || $product->is_in_stock()) {
            return;
        }

        echo $this->get_notify_form_markup($product->get_id(), 0); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function render_variation_notify_container()
    {
        global $product;

        if (!$product instanceof \WC_Product) {
            return;
        }

        echo '<div class="hdbis-notify-form-wrap" style="display:none;">';
        echo $this->get_notify_form_markup($product->get_id(), 0); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }

    private function get_notify_form_markup($product_id, $variation_id)
    {
        ob_start();
        ?>
        <form class="hdbis-notify-form">
            <p class="hdbis-notify-form__intro">
                <?php esc_html_e("Enter your email and we'll let you know when this is back in stock.", 'hdwebmobile-back-in-stock-waitlist'); ?>
            </p>
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
            <input type="hidden" name="variation_id" value="<?php echo esc_attr($variation_id); ?>" />
            <input type="email" name="email" class="hdbis-notify-form__email" placeholder="<?php esc_attr_e('Your email address', 'hdwebmobile-back-in-stock-waitlist'); ?>" required="required" />
            <input type="text" name="name" class="hdbis-notify-form__name" placeholder="<?php esc_attr_e('Your name (optional)', 'hdwebmobile-back-in-stock-waitlist'); ?>" />
            <button type="submit" class="hdbis-notify-form__submit button">
                <?php esc_html_e('Notify Me', 'hdwebmobile-back-in-stock-waitlist'); ?>
            </button>
            <p class="hdbis-notify-form__message" role="status"></p>
        </form>
        <?php
        return ob_get_clean();
    }
}
