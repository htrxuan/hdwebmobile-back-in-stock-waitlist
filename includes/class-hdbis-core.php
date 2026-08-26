<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

final class HDBIS_Core
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
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-repository.php';
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-notifier.php';
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-stock-watcher.php';
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-unsubscribe.php';
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-frontend.php';
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-ajax.php';

        if (is_admin()) {
            require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-admin.php';
        }
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));
        add_action('admin_init', array(HDBIS_Activator::class, 'maybe_upgrade_db'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        add_filter('woocommerce_email_classes', array($this, 'register_email'));

        HDBIS_Notifier::get_instance();
        HDBIS_Stock_Watcher::get_instance();
        HDBIS_Unsubscribe::get_instance();
        HDBIS_Frontend::get_instance();
        HDBIS_Ajax::get_instance();

        if (is_admin()) {
            HDBIS_Admin::get_instance();
        }
    }

    public function register_email($emails)
    {
        // WC_Emails::init() includes its own class-wc-email.php base class immediately
        // before applying this filter, so it's always safe to load our subclass here --
        // loading it any earlier (e.g. at plugins_loaded) would fatal on WC_Email not existing yet.
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-email.php';
        $emails['hdbis_back_in_stock'] = new HDBIS_Email();
        return $emails;
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdbis_wc_missing_notice')) {
            return;
        }
        delete_transient('hdbis_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Back In Stock & Waitlist requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-back-in-stock-waitlist'); ?>
            </p>
        </div>
        <?php
    }
}
