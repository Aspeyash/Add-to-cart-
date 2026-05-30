<?php
/**
 * "Product Builder" tab in the WooCommerce Product Data panel.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

use Zymarg\ProductBuilder\Product_Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Product_Meta_Box
 *
 * Adds a new tab next to General / Inventory / etc. for per-product
 * overrides. Reads/writes via Product_Overrides.
 */
final class Product_Meta_Box {

	/** Reset action key (POST). */
	const RESET_FLAG = 'zpb_reset_overrides';

	/** Singleton. @var Product_Meta_Box|null */
	private static $instance = null;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Hook into WooCommerce. */
	private function register() {
		// Add the tab.
		add_filter( 'woocommerce_product_data_tabs',   array( $this, 'register_tab' ), 90 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );

		// Save: HPOS-compatible hook (runs once per save with the product object).
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_overrides' ), 10, 1 );

		// Asset enqueue only on product edit screens.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the new tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function register_tab( $tabs ) {
		$tabs['zpb_product_builder'] = array(
			'label'    => __( 'Product Builder', 'zymarg-product-builder' ),
			'target'   => 'zpb_product_builder_data',
			'class'    => array(),
			'priority' => 80,
		);
		return $tabs;
	}

	/**
	 * Render the tab content (panel).
	 */
	public function render_panel() {
		global $post, $product_object;

		$product = $product_object;
		if ( ! $product instanceof \WC_Product ) {
			$product = $post && function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;
		}
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		$overrides  = Product_Overrides::all( $product_id );

		$is_variable          = $product->is_type( 'variable' );
		$variation_attributes = $is_variable ? $product->get_variation_attributes() : array();

		// Build attribute list for this product (taxonomies only — custom string
		// attributes don't have a swatch concept).
		$product_attributes = array();
		foreach ( $variation_attributes as $attr_key => $_options ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_', '', $attr_key ) );
			if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
				$product_attributes[ $taxonomy ] = wc_attribute_label( $taxonomy, $product );
			}
		}

		// Pass to view.
		include ZPB_PLUGIN_DIR . 'includes/admin/views/tab-product-builder.php';
	}

	/**
	 * Save handler — HPOS-compatible (runs once with $product object).
	 *
	 * @param \WC_Product $product Product being saved.
	 */
	public function save_overrides( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		// Capability check.
		if ( ! current_user_can( 'edit_product', $product->get_id() ) ) {
			return;
		}
		// Make sure our nonce was posted (set in the view) — guards against
		// auto-saves and unrelated product saves that don't include our fields.
		if ( empty( $_POST['zpb_overrides_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['zpb_overrides_nonce'] ) ), 'zpb_save_overrides' ) ) { // phpcs:ignore
			return;
		}

		$product_id = $product->get_id();

		// Reset request short-circuits everything.
		if ( ! empty( $_POST[ self::RESET_FLAG ] ) ) {
			Product_Overrides::clear( $product_id );
			return;
		}

		$raw = isset( $_POST['zpb_overrides'] ) && is_array( $_POST['zpb_overrides'] )
			? wp_unslash( $_POST['zpb_overrides'] )    // phpcs:ignore
			: array();

		$clean = $this->sanitize( $raw );
		Product_Overrides::update( $product_id, $clean );
	}

	/**
	 * Sanitize the raw posted overrides against schemas.
	 *
	 * @param array $raw Raw POST input (already unslashed).
	 * @return array
	 */
	private function sanitize( array $raw ) {
		$clean = array();

		// Attribute display.
		if ( isset( $raw['attribute_display'] ) && is_array( $raw['attribute_display'] ) ) {
			$valid_types = array_keys( Attribute_Settings::types() );
			$valid_types[] = Product_Overrides::INHERIT;
			$out = array();
			foreach ( $raw['attribute_display'] as $taxonomy => $value ) {
				$taxonomy = sanitize_key( (string) $taxonomy );
				$value    = (string) $value;
				if ( $taxonomy && in_array( $value, $valid_types, true ) ) {
					$out[ $taxonomy ] = $value;
				}
			}
			$clean['attribute_display'] = $out;
		}

		// Hidden attributes (multi-checkbox).
		if ( isset( $raw['hidden_attributes'] ) && is_array( $raw['hidden_attributes'] ) ) {
			$clean['hidden_attributes'] = array_values( array_filter( array_map( 'sanitize_key', $raw['hidden_attributes'] ) ) );
		}

		// Add to Cart overrides.
		$atc_in  = isset( $raw['add_to_cart'] ) && is_array( $raw['add_to_cart'] ) ? $raw['add_to_cart'] : array();
		$atc_out = array();

		if ( isset( $atc_in['button_text'] ) ) {
			$atc_out['button_text'] = sanitize_text_field( (string) $atc_in['button_text'] );
		}

		$atc_out['show_buy_now'] = $this->whitelist(
			$atc_in['show_buy_now'] ?? '',
			array( Product_Overrides::INHERIT, 'yes', 'no' ),
			Product_Overrides::INHERIT
		);

		$atc_out['out_of_stock_behavior'] = $this->whitelist(
			$atc_in['out_of_stock_behavior'] ?? '',
			array( Product_Overrides::INHERIT, 'disable', 'hide', 'message' ),
			Product_Overrides::INHERIT
		);

		$atc_out['redirect_after'] = $this->whitelist(
			$atc_in['redirect_after'] ?? '',
			array( Product_Overrides::INHERIT, 'none', 'cart', 'checkout' ),
			Product_Overrides::INHERIT
		);

		$clean['add_to_cart'] = $atc_out;

		// Widgets disabled (multi-checkbox).
		if ( isset( $raw['widgets_disabled'] ) && is_array( $raw['widgets_disabled'] ) ) {
			$valid_widgets = array( 'add_to_cart', 'swatches', 'gallery' );
			$clean['widgets_disabled'] = array_values( array_intersect(
				array_map( 'sanitize_key', $raw['widgets_disabled'] ),
				$valid_widgets
			) );
		}

		return $clean;
	}

	/**
	 * Whitelist sanitizer.
	 *
	 * @param mixed  $value     Raw value.
	 * @param array  $allowed   Allowed values.
	 * @param mixed  $default   Default if not allowed.
	 * @return mixed
	 */
	private function whitelist( $value, array $allowed, $default ) {
		$value = (string) $value;
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Enqueue admin CSS only on product edit screens.
	 *
	 * @param string $hook Current admin screen.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style(
			'zpb-product-meta-box',
			ZPB_ASSETS_URL . 'css/product-meta-box.css',
			array(),
			ZPB_VERSION
		);
		wp_enqueue_script(
			'zpb-product-meta-box',
			ZPB_ASSETS_URL . 'js/product-meta-box.js',
			array(),
			ZPB_VERSION,
			true
		);
		wp_localize_script(
			'zpb-product-meta-box',
			'ZPBOverrides',
			array(
				'i18n' => array(
					'resetConfirm' => __( 'Reset all overrides for this product?', 'zymarg-product-builder' ),
				),
			)
		);
	}
}
