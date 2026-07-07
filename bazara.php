<?php
/**
* Plugin Name: بازارا
* Plugin URI: plugin URI: https://www.mahaksoft.com/%d8%b3%d8%a7%db%8c%d8%b1-%d8%ae%d8%af%d9%85%d8%a7%d8%aa-%d8%b4%d8%b1%da%a9%d8%aa/%d9%85%d8%b1%da%a9%d8%b2-%d8%af%d8%a7%d9%86%d9%84%d9%88%d8%af/stacker-order-and-sales-system/
* Description: افزونه ای برای همگام سازی نرم افزار حسابداری محک و وردپرس
* Author: گروه نرم افزاری محک
* Author URI: https://www.mahaksoft.com/
* Text Domain: bazara
* Domain Path: /languages
* Version: 3.1048.18
* WC requires at least: 5.0.0
* WC tested up to: 9.4.1
* Requires at least: 5.8
* Requires PHP: 7.4
* HPOS: yes
* HPOS compatible: yes
* HPOS support: yes
* @author Erfan Nazari
* @copyright Copyright (c) 2022, mahaksoft
**/

// disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if WooCommerce is active
function bazara_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'bazara_woocommerce_missing_notice');
        return false;
    }
    return true;
}

// Admin notice for missing WooCommerce
function bazara_woocommerce_missing_notice() {
    ?>
<div class="error">
    <p><?php _e('بازارا نیاز به افزونه ووکامرس دارد. لطفا ابتدا ووکامرس را نصب و فعال کنید.', 'bazara'); ?></p>
</div>
<?php
}

// Prevent plugin activation if WooCommerce is not active
register_activation_hook(__FILE__, 'bazara_activation_check');
function bazara_activation_check() {
    if (!bazara_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('بازارا نیاز به افزونه ووکامرس دارد. لطفا ابتدا ووکامرس را نصب و فعال کنید.', 'bazara'));
    }
}

// Check WooCommerce on plugin load
add_action('plugins_loaded', 'bazara_check_woocommerce');

if ( ! defined( 'BAZARA_PLUGIN_FILE' ) ) {
    define( 'BAZARA_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'BAZARA_ADDONS_PLUGIN_FILE' ) ) {
    define( 'BAZARA_ADDONS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'BAZARA_PATH' ) ) {
    define( 'BAZARA_PATH', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'BAZARA_DIR_PATH' ) ) {
    define( 'BAZARA_DIR_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BAZARA_UPDATER_PATH' ) ) {
    define( 'BAZARA_UPDATER_PATH', 'https://utils.mahaksoft.com/bazara/bazara.json' );
}

if ( ! defined( 'BAZARA_PLUGIN_BASE_NAME' ) ) {
    define( 'BAZARA_PLUGIN_BASE_NAME', 	plugin_basename(__FILE__) );
    }

//run app
require_once plugin_dir_path( __FILE__ ) . 'init.php';
// require      plugin_dir_path( __FILE__ ) . 'vendors/plugin-update-checker-4.13/plugin-update-checker.php';

// Add HPOS compatibility check
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// //plugin updater
// $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
//     BAZARA_UPDATER_PATH,
//     __FILE__, //Full path to the main plugin file or functions.php.
//     'Bazara'
// );