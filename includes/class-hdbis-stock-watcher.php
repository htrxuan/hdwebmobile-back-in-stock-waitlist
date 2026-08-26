<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Stock_Watcher
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
        // WooCommerce fires a different hook for variations than for simple/parent products
        // (WC_Product_Data_Store_CPT::handle_updated_props() branches on product type), so both
        // must be watched -- confirmed directly against this environment's WooCommerce core.
        add_action('woocommerce_product_set_stock_status', array($this, 'maybe_schedule_notification'), 10, 3);
        add_action('woocommerce_variation_set_stock_status', array($this, 'maybe_schedule_notification'), 10, 3);
    }

    public function maybe_schedule_notification($product_id, $new_status, $product)
    {
        if ('instock' !== $new_status) {
            return;
        }

        if ($product instanceof \WC_Product_Variation) {
            $parent_id    = $product->get_parent_id();
            $variation_id = $product->get_id();
        } else {
            $parent_id    = $product->get_id();
            $variation_id = 0;
        }

        if (0 === HDBIS_Repository::count_waiting($parent_id, $variation_id)) {
            return;
        }

        if (as_has_scheduled_action('hdbis_process_restock', array($parent_id, $variation_id), 'hdbis')) {
            return;
        }

        as_schedule_single_action(time() + MINUTE_IN_SECONDS, 'hdbis_process_restock', array($parent_id, $variation_id), 'hdbis');
    }
}
