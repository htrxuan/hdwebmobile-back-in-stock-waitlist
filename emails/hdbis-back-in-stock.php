<?php

/**
 * Back in stock email (HTML).
 *
 * @var WC_Product $product
 * @var string $email_heading
 * @var string $additional_content
 * @var string $unsubscribe_url
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var \htrxuan\hdbis\HDBIS_Email $email
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
?>

<p>
    <?php
    printf(
        /* translators: %s: product name */
        esc_html__('Good news! "%s" is back in stock and ready to order.', 'hdwebmobile-back-in-stock-waitlist'),
        '<strong>' . esc_html($product->get_name()) . '</strong>'
    );
    ?>
</p>

<p style="text-align: center; margin: 24px 0;">
    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="button" style="background-color:#05A67D;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
        <?php esc_html_e('View Product', 'hdwebmobile-back-in-stock-waitlist'); ?>
    </a>
</p>

<?php
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}
?>

<p style="font-size: 12px; color: #999999;">
    <a href="<?php echo esc_url($unsubscribe_url); ?>"><?php esc_html_e('Unsubscribe from restock alerts for this product', 'hdwebmobile-back-in-stock-waitlist'); ?></a>
</p>

<?php
do_action('woocommerce_email_footer', $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
