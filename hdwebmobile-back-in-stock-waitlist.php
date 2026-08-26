<?php

/**
 * Plugin Name: HDWebmobile Back In Stock & Waitlist
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-back-in-stock-waitlist/
 * Description: Let customers join a waitlist for out-of-stock products and variations, and reliably notify them when restocked.
 * Version: 1.0.2
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-back-in-stock-waitlist
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdbis;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('HDBIS_VERSION', '1.0.2');
define('HDBIS_DB_VERSION', '1.0.0');
define('HDBIS_PLUGIN_FILE', __FILE__);
define('HDBIS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDBIS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-activator.php';

register_activation_hook(HDBIS_PLUGIN_FILE, array(HDBIS_Activator::class, 'activate'));
add_action('before_woocommerce_init', array(HDBIS_Activator::class, 'declare_hpos_compatibility'));

add_action('plugins_loaded', function () {
    require_once HDBIS_PLUGIN_DIR . 'includes/class-hdbis-core.php';
    HDBIS_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(HDBIS_PLUGIN_FILE), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" style="color:#d54e21;font-weight:bold;">' . __('Donate', 'hdwebmobile-back-in-stock-waitlist') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
