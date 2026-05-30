<?php
/**
 * Plugin Name:       Zymarg Product Builder
 * Plugin URI:        https://zymarg.com/zymarg-product-builder
 * Description:       Connected Elementor widgets for WooCommerce: Product Gallery, Variation Swatches, and Add to Cart. Build rich product layouts with synchronized widgets that work across separate sections.
 * Version:           0.6.0
 * Author:            Zymarg
 * Author URI:        https://zymarg.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zymarg-product-builder
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   8.9
 * Elementor tested up to: 3.21
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'ZPB_VERSION', '0.6.0' );
define( 'ZPB_PLUGIN_FILE', __FILE__ );
define( 'ZPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZPB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ZPB_ASSETS_URL', ZPB_PLUGIN_URL . 'assets/' );
define( 'ZPB_TEMPLATES_DIR', ZPB_PLUGIN_DIR . 'templates/' );
define( 'ZPB_MIN_PHP', '7.4' );
define( 'ZPB_MIN_WP', '6.0' );
define( 'ZPB_MIN_WC', '7.0' );
define( 'ZPB_MIN_ELEMENTOR', '3.5.0' );

/**
 * Bootstrap the plugin once all plugins are loaded so we can reliably
 * detect WooCommerce and Elementor.
 */
function zpb_bootstrap() {
	// Load translations first so admin notices are localized.
	load_plugin_textdomain( 'zymarg-product-builder', false, dirname( ZPB_PLUGIN_BASENAME ) . '/languages' );

	// Hard requirement: PHP version.
	if ( version_compare( PHP_VERSION, ZPB_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'zpb_notice_php_version' );
		return;
	}

	// Hard requirement: WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, ZPB_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'zpb_notice_wp_version' );
		return;
	}

	// Hard requirement: WooCommerce.
	if ( ! zpb_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'zpb_notice_missing_woocommerce' );
		return;
	}

	// Hard requirement: Elementor.
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'zpb_notice_missing_elementor' );
		return;
	}

	// Soft check: Elementor version.
	if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, ZPB_MIN_ELEMENTOR, '<' ) ) {
		add_action( 'admin_notices', 'zpb_notice_elementor_version' );
		return;
	}

	// All good — load the plugin.
	require_once ZPB_PLUGIN_DIR . 'includes/class-plugin.php';
	\Zymarg\ProductBuilder\Plugin::instance();
}
add_action( 'plugins_loaded', 'zpb_bootstrap', 20 );

/**
 * Boot the GitHub-based auto-updater.
 *
 * Runs in admin context only — there's no reason for front-end requests to
 * load the updater. WordPress's update transient is admin-only territory.
 *
 * To configure for a fork, change ZPB_GITHUB_OWNER / ZPB_GITHUB_REPO below.
 */
if ( ! defined( 'ZPB_GITHUB_OWNER' ) ) {
	define( 'ZPB_GITHUB_OWNER', 'Aspeyash' );
}
if ( ! defined( 'ZPB_GITHUB_REPO' ) ) {
	define( 'ZPB_GITHUB_REPO', 'Add-to-cart-' );
}

add_action(
	'admin_init',
	function () {
		require_once ZPB_PLUGIN_DIR . 'includes/class-update-checker.php';
		\Zymarg\ProductBuilder\Update_Checker::instance(
			ZPB_PLUGIN_FILE,
			ZPB_GITHUB_OWNER,
			ZPB_GITHUB_REPO
		);
	},
	5
);

/**
 * Detect WooCommerce reliably (constant + class + active plugin).
 *
 * @return bool
 */
function zpb_is_woocommerce_active() {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}
	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}
	return in_array( 'woocommerce/woocommerce.php', $active, true );
}

/**
 * Declare WooCommerce HPOS (High-Performance Order Storage) compatibility.
 * The plugin only reads product data, never custom-order-table data, so we
 * are compatible by default.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ZPB_PLUGIN_FILE, true );
		}
	}
);

/* -------------------------------------------------------------------------
 * Admin notices
 * ------------------------------------------------------------------------- */

function zpb_notice_php_version() {
	zpb_render_notice(
		sprintf(
			/* translators: 1: required PHP version, 2: current PHP version */
			esc_html__( 'Zymarg Product Builder requires PHP %1$s or higher. You are running PHP %2$s.', 'zymarg-product-builder' ),
			ZPB_MIN_PHP,
			PHP_VERSION
		)
	);
}

function zpb_notice_wp_version() {
	zpb_render_notice(
		sprintf(
			/* translators: %s: required WordPress version */
			esc_html__( 'Zymarg Product Builder requires WordPress %s or higher.', 'zymarg-product-builder' ),
			ZPB_MIN_WP
		)
	);
}

function zpb_notice_missing_woocommerce() {
	zpb_render_notice(
		esc_html__( 'Zymarg Product Builder requires WooCommerce to be installed and active.', 'zymarg-product-builder' )
	);
}

function zpb_notice_missing_elementor() {
	zpb_render_notice(
		esc_html__( 'Zymarg Product Builder requires Elementor to be installed and active.', 'zymarg-product-builder' )
	);
}

function zpb_notice_elementor_version() {
	zpb_render_notice(
		sprintf(
			/* translators: %s: required Elementor version */
			esc_html__( 'Zymarg Product Builder requires Elementor %s or higher.', 'zymarg-product-builder' ),
			ZPB_MIN_ELEMENTOR
		)
	);
}

/**
 * Render a standard admin error notice.
 *
 * @param string $message Already-escaped message.
 */
function zpb_render_notice( $message ) {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> — %s</p></div>',
		esc_html__( 'Zymarg Product Builder', 'zymarg-product-builder' ),
		wp_kses_post( $message )
	);
}

/* -------------------------------------------------------------------------
 * Activation / deactivation
 * ------------------------------------------------------------------------- */

register_activation_hook(
	__FILE__,
	function () {
		// Seed default settings so widgets always have a baseline.
		require_once ZPB_PLUGIN_DIR . 'includes/admin/class-settings-store.php';
		\Zymarg\ProductBuilder\Admin\Settings_Store::seed_defaults();

		// Show one-time welcome notice.
		update_option( 'zpb_show_welcome_notice', 1 );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		// Reserved for future cleanup. We deliberately keep settings on deactivation.
	}
);
