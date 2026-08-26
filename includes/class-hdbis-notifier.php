<?php

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

class HDBIS_Notifier
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
        add_action('hdbis_process_restock', array($this, 'process_restock_batch'), 10, 2);
    }

    /**
     * Single send code path, used both by the Action Scheduler hook and the
     * admin "Notify next batch" bulk action.
     *
     * @return array { attempted: int, notified: int, failed: int }
     */
    public function process_restock_batch($product_id, $variation_id = 0)
    {
        $product = wc_get_product($variation_id ?: $product_id);

        if (!$product) {
            return array('attempted' => 0, 'notified' => 0, 'failed' => 0);
        }

        $limit = null;
        if ($product->managing_stock()) {
            $quantity = $product->get_stock_quantity();
            if (is_numeric($quantity) && $quantity > 0) {
                $limit = (int) $quantity;
            }
        }

        $candidates = HDBIS_Repository::get_waiting_subscribers($product_id, $variation_id, $limit);

        if (empty($candidates)) {
            return array('attempted' => 0, 'notified' => 0, 'failed' => 0);
        }

        $emails = WC()->mailer()->get_emails();
        $email  = isset($emails['hdbis_back_in_stock']) ? $emails['hdbis_back_in_stock'] : new HDBIS_Email();

        $notified_ids = array();
        $logger       = wc_get_logger();

        foreach ($candidates as $subscriber) {
            $sent = $email->trigger($subscriber, $product);

            if ($sent) {
                $notified_ids[] = (int) $subscriber->id;
                $logger->info(
                    sprintf('Restock notification sent to %s for product #%d (variation #%d).', $subscriber->email, $product_id, $variation_id),
                    array('source' => 'hdbis')
                );
            } else {
                $logger->error(
                    sprintf('Restock notification FAILED to send to %s for product #%d (variation #%d).', $subscriber->email, $product_id, $variation_id),
                    array('source' => 'hdbis')
                );
            }
        }

        HDBIS_Repository::mark_notified($notified_ids);

        return array(
            'attempted' => count($candidates),
            'notified'  => count($notified_ids),
            'failed'    => count($candidates) - count($notified_ids),
        );
    }
}
