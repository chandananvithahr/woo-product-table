<?php
/**
 * Plugin Name: WooCommerce Product Table
 * Plugin URI:  https://example.com/woo-product-table
 * Description: Display WooCommerce products in a sortable, filterable table with AJAX add-to-cart support.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-product-table
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:      8.9
 *
 * @package WooProductTable
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────────

define( 'WPT_VERSION',   '1.0.0' );
define( 'WPT_PLUGIN_FILE', __FILE__ );
define( 'WPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPT_IS_PRO',    false ); // Flip to true when Pro licence is active.

// ── WooCommerce dependency check ───────────────────────────────────────────────

add_action( 'plugins_loaded', 'wpt_check_woocommerce', 1 );

function wpt_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wpt_missing_woocommerce_notice' );
        return;
    }
    wpt_init();
}

function wpt_missing_woocommerce_notice() {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html__(
            'WooCommerce Product Table requires WooCommerce to be installed and active.',
            'woo-product-table'
        )
    );
}

// ── Boot ───────────────────────────────────────────────────────────────────────

function wpt_init() {
    // Load translations.
    load_plugin_textdomain(
        'woo-product-table',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );

    // Include classes.
    require_once WPT_PLUGIN_DIR . 'includes/class-wpt-table.php';
    require_once WPT_PLUGIN_DIR . 'includes/class-wpt-ajax.php';
    require_once WPT_PLUGIN_DIR . 'includes/class-wpt-admin.php';

    // Boot each module.
    WPT_Table::instance()->register_hooks();
    WPT_Ajax::instance()->register_hooks();
    WPT_Admin::instance()->register_hooks();
}

// ── Activation / Deactivation ──────────────────────────────────────────────────

register_activation_hook( __FILE__, 'wpt_activate' );
function wpt_activate() {
    // Set default options on first install.
    $defaults = array(
        'visible_columns' => array( 'image', 'name', 'price', 'stock', 'quantity', 'add_to_cart' ),
        'default_sort'    => 'name',
        'default_order'   => 'ASC',
        'per_page'        => 10,
    );
    if ( ! get_option( 'wpt_settings' ) ) {
        update_option( 'wpt_settings', $defaults );
    }
}

register_deactivation_hook( __FILE__, 'wpt_deactivate' );
function wpt_deactivate() {
    // Nothing to clean up on deactivation (preserve settings).
}
