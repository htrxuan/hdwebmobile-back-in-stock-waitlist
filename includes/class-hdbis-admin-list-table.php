<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HDBIS_Admin_List_Table extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false,
        ));
    }

    public function get_columns()
    {
        return array(
            'cb'          => '<input type="checkbox" />',
            'product'     => __('Product', 'hdwebmobile-back-in-stock-waitlist'),
            'email'       => __('Email', 'hdwebmobile-back-in-stock-waitlist'),
            'name'        => __('Name', 'hdwebmobile-back-in-stock-waitlist'),
            'status'      => __('Status', 'hdwebmobile-back-in-stock-waitlist'),
            'created_at'  => __('Signed up', 'hdwebmobile-back-in-stock-waitlist'),
            'notified_at' => __('Notified at', 'hdwebmobile-back-in-stock-waitlist'),
        );
    }

    public function get_bulk_actions()
    {
        return array(
            'notify_batch' => __('Notify next batch', 'hdwebmobile-back-in-stock-waitlist'),
            'delete'       => __('Delete', 'hdwebmobile-back-in-stock-waitlist'),
        );
    }

    protected function get_sortable_columns()
    {
        return array(
            'email'       => array('email', false),
            'status'      => array('status', false),
            'created_at'  => array('created_at', true),
            'notified_at' => array('notified_at', false),
        );
    }

    protected function extra_tablenav($which)
    {
        if ('top' !== $which) {
            return;
        }

        // Read-only filter param, same as core WP_List_Table screens (post status, etc.) -- no
        // state change occurs from reading it, so nonce verification doesn't apply here.
        $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $statuses       = array(
            'all'          => __('All statuses', 'hdwebmobile-back-in-stock-waitlist'),
            'waiting'      => __('Waiting', 'hdwebmobile-back-in-stock-waitlist'),
            'notified'     => __('Notified', 'hdwebmobile-back-in-stock-waitlist'),
            'unsubscribed' => __('Unsubscribed', 'hdwebmobile-back-in-stock-waitlist'),
        );
        ?>
        <div class="alignleft actions">
            <select name="status">
                <?php foreach ($statuses as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'hdwebmobile-back-in-stock-waitlist'), '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="subscriber[]" value="%d" />', $item->id);
    }

    public function column_product($item)
    {
        $product = wc_get_product($item->variation_id ?: $item->product_id);

        if (!$product) {
            return sprintf('#%d', $item->product_id);
        }

        $edit_link = get_edit_post_link($item->product_id);
        $name      = $product->get_name();

        if ($edit_link) {
            return sprintf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($name));
        }

        return esc_html($name);
    }

    public function column_status($item)
    {
        $labels = array(
            'waiting'      => __('Waiting', 'hdwebmobile-back-in-stock-waitlist'),
            'notified'     => __('Notified', 'hdwebmobile-back-in-stock-waitlist'),
            'unsubscribed' => __('Unsubscribed', 'hdwebmobile-back-in-stock-waitlist'),
        );

        $label = isset($labels[$item->status]) ? $labels[$item->status] : $item->status;
        $badge = sprintf('<span class="hdbis-status-%s">%s</span>', esc_attr($item->status), esc_html($label));

        $actions = array();
        if ('waiting' === $item->status) {
            $notify_url = wp_nonce_url(
                add_query_arg(array('page' => 'hdwebmobile', 'tab' => 'waitlist', 'action' => 'notify_batch', 'subscriber' => array($item->id))),
                'bulk-subscribers'
            );
            $actions['notify'] = sprintf('<a href="%s">%s</a>', esc_url($notify_url), esc_html__('Notify next batch', 'hdwebmobile-back-in-stock-waitlist'));
        }

        return $badge . $this->row_actions($actions); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'email':
                return esc_html($item->email);
            case 'name':
                return esc_html($item->name);
            case 'created_at':
                return esc_html($item->created_at);
            case 'notified_at':
                return $item->notified_at ? esc_html($item->notified_at) : '&mdash;';
            default:
                return '';
        }
    }

    public function prepare_items()
    {
        $per_page = 20;
        $paged    = $this->get_pagenum();
        // Read-only filter/search/sort params for this list table -- same pattern as core
        // WP_List_Table screens; nothing here changes state, so no nonce is needed.
        $status   = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search   = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $result = HDBIS_Repository::get_subscribers_for_list_table(array(
            'status'   => $status,
            's'        => $search,
            'per_page' => $per_page,
            'paged'    => $paged,
            'orderby'  => $orderby,
            'order'    => $order,
        ));

        $this->items = $result['items'];

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $this->set_pagination_args(array(
            'total_items' => $result['total'],
            'per_page'    => $per_page,
            'total_pages' => ceil($result['total'] / $per_page),
        ));
    }
}
