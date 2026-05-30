<?php
/**
 * Resolves which WooCommerce product a widget instance refers to.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Product Context — given widget settings (and current page), returns a
 * WC_Product or null. This is the single source of truth used by every
 * widget to find "its" product.
 */
final class Product_Context {

	/**
	 * Resolve a product based on widget settings.
	 *
	 * Settings shape (Elementor `$widget->get_settings_for_display()`):
	 *  - product_source: 'current' | 'manual'
	 *  - product_id    : int (only used when product_source = 'manual')
	 *
	 * @param array $settings Widget settings for display.
	 * @return \WC_Product|null
	 */
	public static function resolve( $settings ) {
		$source = isset( $settings['product_source'] ) ? $settings['product_source'] : 'current';

		if ( 'manual' === $source ) {
			$product_id = isset( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;
			return $product_id ? self::get_product( $product_id ) : null;
		}

		// 'current': try the global $product, then queried object.
		global $product;
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		$queried = get_queried_object();
		if ( $queried instanceof \WP_Post && 'product' === $queried->post_type ) {
			return self::get_product( $queried->ID );
		}

		return null;
	}

	/**
	 * Safely fetch a product by ID.
	 *
	 * @param int $product_id Product ID.
	 * @return \WC_Product|null
	 */
	public static function get_product( $product_id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$product = wc_get_product( $product_id );
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Whether the product type is supported in v1 (simple + variable).
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public static function is_supported( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		$type = $product->get_type();
		return in_array( $type, array( 'simple', 'variable' ), true );
	}
}
