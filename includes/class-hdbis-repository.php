<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Only file in the plugin that touches $wpdb directly. Every query is parameterized,
 * including the table name via the %i identifier placeholder (WP 6.2+).
 *
 * Direct queries against a custom table are unavoidable here -- there is no WP API
 * for this data -- so DirectDatabaseQuery/NoCaching advisories are expected and accepted
 * for this class, matching standard practice for custom-table plugins.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
class HDBIS_Repository
{

    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'hdbis_subscribers';
    }

    public static function get_schema_sql()
    {
        global $wpdb;
        $table           = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(200) NOT NULL,
            name VARCHAR(200) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'waiting',
            created_at DATETIME NOT NULL,
            notified_at DATETIME DEFAULT NULL,
            notify_token VARCHAR(64) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY product_variation_email (product_id, variation_id, email),
            KEY product_variation_status (product_id, variation_id, status),
            KEY notify_token (notify_token)
        ) {$charset_collate};";
    }

    /**
     * @return string 'added'|'resubscribed'|'duplicate'
     */
    public static function add_subscriber($product_id, $variation_id, $email, $name)
    {
        global $wpdb;

        $existing = $wpdb->get_row($wpdb->prepare(
            'SELECT id, status FROM %i WHERE product_id = %d AND variation_id = %d AND email = %s',
            self::get_table_name(),
            $product_id,
            $variation_id,
            $email
        ));

        $token = bin2hex(random_bytes(32));
        $now   = current_time('mysql');

        if ($existing) {
            if ('waiting' === $existing->status) {
                return 'duplicate';
            }

            $wpdb->update(
                self::get_table_name(),
                array(
                    'name'         => $name,
                    'status'       => 'waiting',
                    'created_at'   => $now,
                    'notified_at'  => null,
                    'notify_token' => $token,
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            return 'resubscribed';
        }

        $wpdb->insert(
            self::get_table_name(),
            array(
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'email'        => $email,
                'name'         => $name,
                'status'       => 'waiting',
                'created_at'   => $now,
                'notify_token' => $token,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        return 'added';
    }

    public static function get_waiting_subscribers($product_id, $variation_id, $limit = null)
    {
        global $wpdb;

        if (null !== $limit) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM %i WHERE product_id = %d AND variation_id = %d AND status = 'waiting' ORDER BY created_at ASC LIMIT %d",
                self::get_table_name(),
                $product_id,
                $variation_id,
                (int) $limit
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i WHERE product_id = %d AND variation_id = %d AND status = 'waiting' ORDER BY created_at ASC",
            self::get_table_name(),
            $product_id,
            $variation_id
        ));
    }

    public static function count_waiting($product_id, $variation_id)
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM %i WHERE product_id = %d AND variation_id = %d AND status = 'waiting'",
            self::get_table_name(),
            $product_id,
            $variation_id
        ));
    }

    public static function mark_notified(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        global $wpdb;

        $ids          = array_map('absint', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        // $placeholders is a dynamically-sized run of %d built to match count($ids), which static
        // analysis can't resolve; every value still passes through prepare() via array_merge below.
        $wpdb->query($wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            "UPDATE %i SET status = 'notified', notified_at = %s WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name(), current_time('mysql')), $ids)
        ));
    }

    public static function get_subscriber_by_id($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE id = %d',
            self::get_table_name(),
            $id
        ));
    }

    public static function get_subscriber_by_token($token)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM %i WHERE notify_token = %s',
            self::get_table_name(),
            $token
        ));
    }

    public static function unsubscribe_by_token($token)
    {
        global $wpdb;

        return $wpdb->update(
            self::get_table_name(),
            array('status' => 'unsubscribed'),
            array('notify_token' => $token),
            array('%s'),
            array('%s')
        );
    }

    public static function delete_subscribers(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        global $wpdb;

        $ids          = array_map('absint', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM %i WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name()), $ids)
        ));
    }

    public static function count_by_status($status)
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM %i WHERE status = %s',
            self::get_table_name(),
            $status
        ));
    }

    /**
     * @param array $args { status, s (email search), per_page, paged, orderby, order }
     * @return array { items: array, total: int }
     */
    public static function get_subscribers_for_list_table(array $args)
    {
        global $wpdb;

        $where  = array('1=1');
        $params = array();

        if (!empty($args['status']) && 'all' !== $args['status']) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['s'])) {
            $where[]  = 'email LIKE %s';
            $params[] = '%' . $wpdb->esc_like($args['s']) . '%';
        }

        $where_sql = implode(' AND ', $where);

        $allowed_orderby = array('created_at', 'notified_at', 'status', 'email');
        $orderby         = in_array($args['orderby'] ?? '', $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order           = 'ASC' === strtoupper($args['order'] ?? '') ? 'ASC' : 'DESC';

        $per_page = max(1, (int) ($args['per_page'] ?? 20));
        $paged    = max(1, (int) ($args['paged'] ?? 1));
        $offset   = ($paged - 1) * $per_page;

        // $where_sql/$orderby/$order are built only from the hardcoded, safe fragments and
        // allow-lists above (never raw user input); all real values still go through prepare().
        $total = (int) $wpdb->get_var($wpdb->prepare( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
            "SELECT COUNT(*) FROM %i WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name()), $params)
        ));

        $items = $wpdb->get_results($wpdb->prepare( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
            "SELECT * FROM %i WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            array_merge(array(self::get_table_name()), $params, array($per_page, $offset))
        ));

        return array(
            'items' => $items,
            'total' => $total,
        );
    }
}
