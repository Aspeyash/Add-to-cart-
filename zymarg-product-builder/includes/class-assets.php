<?php
/**
 * Asset registration and conditional enqueue.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all front-end and editor assets. Each widget enqueues only what
 * it needs via the helpers on this class — no global "load on every page".
 */
final class Assets {

	/** Handle for the shared product-state pub/sub bus. */
	const HANDLE_STATE = 'zpb-product-state';

	/** Handle for Add to Cart widget JS. */
	const HANDLE_ADD_TO_CART_JS = 'zpb-add-to-cart';

	/** Handle for Add to Cart widget CSS. */
	const HANDLE_ADD_TO_CART_CSS = 'zpb-add-to-cart';

	/** Handle for Variation Swatches widget JS. */
	const HANDLE_SWATCHES_JS = 'zpb-swatches';

	/** Handle for Variation Swatches widget CSS. */
	const HANDLE_SWATCHES_CSS = 'zpb-swatches';

	/** Handle for Product Gallery widget JS. */
	const HANDLE_GALLERY_JS = 'zpb-gallery';

	/** Handle for Product Gallery widget CSS. */
	const HANDLE_GALLERY_CSS = 'zpb-gallery';

	/**
	 * Singleton instance.
	 *
	 * @var Assets|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Assets
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/**
	 * Register (but do not enqueue) all known assets.
	 */
	private function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'register_editor_assets' ) );
	}

	/**
	 * Register front-end assets.
	 */
	public function register_frontend_assets() {
		// Shared state bus — every widget depends on this.
		wp_register_script(
			self::HANDLE_STATE,
			ZPB_ASSETS_URL . 'js/product-state.js',
			array(),
			ZPB_VERSION,
			true
		);

		// Localize i18n strings.
		wp_localize_script(
			self::HANDLE_STATE,
			'ZPBConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'zpb_ajax' ),
				'i18n'    => array(
					'addToCart'     => __( 'Add to cart', 'zymarg-product-builder' ),
					'adding'        => __( 'Adding...', 'zymarg-product-builder' ),
					'added'         => __( 'Added!', 'zymarg-product-builder' ),
					'error'         => __( 'Could not add to cart.', 'zymarg-product-builder' ),
					'networkError'  => __( 'Network error. Could not reach the server.', 'zymarg-product-builder' ),
					'tryAgain'      => __( 'Try again', 'zymarg-product-builder' ),
					'inStock'       => __( 'In Stock', 'zymarg-product-builder' ),
					'outOfStock'    => __( 'Out of stock', 'zymarg-product-builder' ),
					'selectOptions' => __( 'Please select product options.', 'zymarg-product-builder' ),
				),
			)
		);

		// Add to Cart widget assets.
		wp_register_script(
			self::HANDLE_ADD_TO_CART_JS,
			ZPB_ASSETS_URL . 'js/add-to-cart.js',
			array( self::HANDLE_STATE ),
			ZPB_VERSION,
			true
		);

		wp_register_style(
			self::HANDLE_ADD_TO_CART_CSS,
			ZPB_ASSETS_URL . 'css/add-to-cart.css',
			array(),
			ZPB_VERSION
		);

		// Variation Swatches widget assets.
		wp_register_script(
			self::HANDLE_SWATCHES_JS,
			ZPB_ASSETS_URL . 'js/swatches.js',
			array( self::HANDLE_STATE ),
			ZPB_VERSION,
			true
		);

		wp_register_style(
			self::HANDLE_SWATCHES_CSS,
			ZPB_ASSETS_URL . 'css/swatches.css',
			array(),
			ZPB_VERSION
		);

		// Product Gallery widget assets.
		wp_register_script(
			self::HANDLE_GALLERY_JS,
			ZPB_ASSETS_URL . 'js/gallery.js',
			array( self::HANDLE_STATE ),
			ZPB_VERSION,
			true
		);

		wp_register_style(
			self::HANDLE_GALLERY_CSS,
			ZPB_ASSETS_URL . 'css/gallery.css',
			array(),
			ZPB_VERSION
		);
	}

	/**
	 * Register editor-only assets (shown in Elementor edit mode).
	 */
	public function register_editor_assets() {
		wp_enqueue_style(
			'zpb-editor',
			ZPB_ASSETS_URL . 'css/add-to-cart.css',
			array(),
			ZPB_VERSION
		);
		wp_enqueue_style(
			'zpb-editor-swatches',
			ZPB_ASSETS_URL . 'css/swatches.css',
			array(),
			ZPB_VERSION
		);
		wp_enqueue_style(
			'zpb-editor-gallery',
			ZPB_ASSETS_URL . 'css/gallery.css',
			array(),
			ZPB_VERSION
		);
	}

	/**
	 * Enqueue everything needed for the Add to Cart widget.
	 */
	public static function enqueue_add_to_cart() {
		wp_enqueue_style( self::HANDLE_ADD_TO_CART_CSS );
		wp_enqueue_script( self::HANDLE_STATE );
		wp_enqueue_script( self::HANDLE_ADD_TO_CART_JS );
	}

	/**
	 * Enqueue everything needed for the Variation Swatches widget.
	 */
	public static function enqueue_swatches() {
		wp_enqueue_style( self::HANDLE_SWATCHES_CSS );
		wp_enqueue_script( self::HANDLE_STATE );
		wp_enqueue_script( self::HANDLE_SWATCHES_JS );
	}

	/**
	 * Enqueue everything needed for the Product Gallery widget.
	 */
	public static function enqueue_gallery() {
		wp_enqueue_style( self::HANDLE_GALLERY_CSS );
		wp_enqueue_script( self::HANDLE_STATE );
		wp_enqueue_script( self::HANDLE_GALLERY_JS );
	}
}
