<?php

/**
 * Back in stock email (plain text).
 *
 * @var WC_Product $product
 * @var string $email_heading
 * @var string $additional_content
 * @var string $unsubscribe_url
 */

if (!defined('ABSPATH')) {
    exit;
}

echo esc_html(wp_strip_all_tags($email_heading)) . "\n\n";

printf(
    /* translators: %s: product name */
    esc_html__('Good news! "%s" is back in stock and ready to order.', 'hdwebmobile-back-in-stock-waitlist'),
    esc_html($product->get_name())
);

echo "\n\n";
echo esc_html($product->get_permalink()) . "\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags($additional_content)) . "\n\n";
}

esc_html_e('Unsubscribe from restock alerts for this product:', 'hdwebmobile-back-in-stock-waitlist');
echo "\n" . esc_html($unsubscribe_url) . "\n";
