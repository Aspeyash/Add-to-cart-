<?php
/**
 * Main plugin singleton.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin class - registers Elementor widgets, assets, AJAX handlers.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Elementor widget category slug.
	 */
	const WIDGET_CATEGORY = 'zymarg-product-builder';

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initialize: load files and register hooks.
	 */
	private function init() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Require all plugin classes.
	 */
	private function load_dependencies() {
		require_once ZPB_PLUGIN_DIR . 'includes/class-assets.php';
		require_once ZPB_PLUGIN_DIR . 'includes/class-product-context.php';
		require_once ZPB_PLUGIN_DIR . 'includes/class-ajax.php';
		require_once ZPB_PLUGIN_DIR . 'includes/frontend/class-product-data.php';
	}

	/**
	 * Wire up WordPress / Elementor hooks.
	 */
	private function register_hooks() {
		// Register custom widget category in Elementor's panel.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Boot subsystems.
		Assets::instance();
		Ajax::instance();
		Frontend\Product_Data::instance();
	}

	/**
	 * Add our widget category to the Elementor panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public function register_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			self::WIDGET_CATEGORY,
			array(
				'title' => __( 'Zymarg Product Builder', 'zymarg-product-builder' ),
				'icon'  => 'eicon-woocommerce',
			)
		);
	}

	/**
	 * Register all Elementor widgets the plugin provides.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once ZPB_PLUGIN_DIR . 'includes/widgets/class-add-to-cart-widget.php';
		$widgets_manager->register( new Widgets\Add_To_Cart_Widget() );
	}
}
