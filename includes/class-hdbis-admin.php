<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Admin
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
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'handle_bulk_actions'));
        add_action('admin_notices', array($this, 'render_batch_result_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook)
    {
        if ('woocommerce_page_hdwebmobile' !== $hook) {
            return;
        }

        // Read-only tab selector, same pattern as core's own admin tab UIs -- no state
        // change occurs from reading it, so nonce verification doesn't apply here.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('waitlist' !== $tab) {
            return;
        }

        wp_enqueue_style('hdbis-admin-css', HDBIS_PLUGIN_URL . 'assets/css/hdbis-admin.css', array(), HDBIS_VERSION);
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['waitlist'] = array(
            'label'  => __('Waitlist', 'hdwebmobile-back-in-stock-waitlist'),
            'order'  => 30,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function render_page()
    {
        require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-admin-list-table.php';

        $table = new HDBIS_Admin_List_Table();
        $table->prepare_items();
        ?>
        <p><?php esc_html_e('Let customers join a waitlist for out-of-stock products, and reliably notify them when restocked. Everyone waiting so far is listed below.', 'hdwebmobile-back-in-stock-waitlist'); ?></p>
        <form method="get">
            <input type="hidden" name="page" value="hdwebmobile" />
            <input type="hidden" name="tab" value="waitlist" />
            <?php
            $table->search_box(__('Search email', 'hdwebmobile-back-in-stock-waitlist'), 'hdbis-subscriber-search');
            $table->display();
            ?>
        </form>
        <?php
    }

    public function handle_bulk_actions()
    {
        if (empty($_GET['page']) || 'hdwebmobile' !== $_GET['page'] || empty($_GET['tab']) || 'waitlist' !== $_GET['tab']) {
            return;
        }

        $action = isset($_REQUEST['action']) && '-1' !== $_REQUEST['action']
            ? sanitize_text_field(wp_unslash($_REQUEST['action']))
            : (isset($_REQUEST['action2']) && '-1' !== $_REQUEST['action2'] ? sanitize_text_field(wp_unslash($_REQUEST['action2'])) : '');

        if (empty($action) || empty($_REQUEST['subscriber'])) {
            return;
        }

        check_admin_referer('bulk-subscribers');

        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $ids = array_map('absint', (array) $_REQUEST['subscriber']);

        if ('delete' === $action) {
            HDBIS_Repository::delete_subscribers($ids);
            $this->redirect_with_notice(array('deleted' => count($ids)));
            return;
        }

        if ('notify_batch' === $action) {
            $pairs = array();
            foreach ($ids as $id) {
                $subscriber = HDBIS_Repository::get_subscriber_by_id($id);
                if ($subscriber) {
                    $pairs[$subscriber->product_id . ':' . $subscriber->variation_id] = array((int) $subscriber->product_id, (int) $subscriber->variation_id);
                }
            }

            $totals = array('attempted' => 0, 'notified' => 0, 'failed' => 0);
            foreach ($pairs as $pair) {
                $result = HDBIS_Notifier::get_instance()->process_restock_batch($pair[0], $pair[1]);
                $totals['attempted'] += $result['attempted'];
                $totals['notified']  += $result['notified'];
                $totals['failed']    += $result['failed'];
            }

            $this->redirect_with_notice($totals);
        }
    }

    private function redirect_with_notice($data)
    {
        set_transient('hdbis_admin_notice_' . get_current_user_id(), $data, 30);

        $url = remove_query_arg(array('action', 'action2', 'subscriber', '_wpnonce'));
        wp_safe_redirect($url);
        exit;
    }

    public function render_batch_result_notice()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check, no state change.
        if (empty($_GET['page']) || 'hdwebmobile' !== $_GET['page'] || empty($_GET['tab']) || 'waitlist' !== $_GET['tab']) {
            return;
        }

        $key  = 'hdbis_admin_notice_' . get_current_user_id();
        $data = get_transient($key);

        if (!$data) {
            return;
        }

        delete_transient($key);

        if (isset($data['deleted'])) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(sprintf(
                    /* translators: %d: number of deleted subscribers */
                    _n('%d subscriber deleted.', '%d subscribers deleted.', $data['deleted'], 'hdwebmobile-back-in-stock-waitlist'),
                    $data['deleted']
                ))
            );
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: 1: attempted count, 2: notified count, 3: failed count */
                __('Notify batch complete: %1$d attempted, %2$d notified, %3$d failed.', 'hdwebmobile-back-in-stock-waitlist'),
                $data['attempted'],
                $data['notified'],
                $data['failed']
            ))
        );
    }
}
