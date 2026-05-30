<?php
/**
 * AJAX endpoints.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Server-side AJAX handlers — add to cart, etc.
 *
 * Endpoint: action=zpb_add_to_cart
 *   POST: nonce, product_id, quantity, variation_id?, variation? (assoc array)
 */
final class Ajax {

	/**
	 * Singleton.
	 *
	 * @var Ajax|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Ajax
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/**
	 * Register actions.
	 */
	private function register() {
		add_action( 'wp_ajax_zpb_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_zpb_add_to_cart', array( $this, 'add_to_cart' ) );
	}

	/**
	 * Add product to cart.
	 *
	 * Returns JSON: { success, message, cart_count, cart_total, fragments, redirect_url }
	 */
	public function add_to_cart() {
		// Nonce check.
		check_ajax_referer( 'zpb_ajax', 'nonce' );

		if ( ! function_exists( 'WC' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'WooCommerce is not active.', 'zymarg-product-builder' ) ),
				500
			);
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
		$variation    = isset( $_POST['variation'] ) && is_array( $_POST['variation'] ) ? $this->sanitize_variation_attributes( wp_unslash( $_POST['variation'] ) ) : array();

		if ( ! $product_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'zymarg-product-builder' ) ),
				400
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error(
				array( 'message' => __( 'This product cannot be purchased.', 'zymarg-product-builder' ) ),
				400
			);
		}

		// Variable products require variation_id.
		if ( $product->is_type( 'variable' ) && ! $variation_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Please select product options.', 'zymarg-product-builder' ) ),
				400
			);
		}

		// Stock check.
		if ( ! $product->has_enough_stock( $quantity ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Not enough stock available.', 'zymarg-product-builder' ) ),
				400
			);
		}

		// Use WC's add_to_cart so all hooks/validations fire.
		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

		if ( ! $cart_item_key ) {
			// WC populates wc_add_notice with the reason.
			$notices = wc_get_notices( 'error' );
			$message = '';
			if ( ! empty( $notices ) ) {
				$first   = reset( $notices );
				$message = is_array( $first ) && isset( $first['notice'] ) ? wp_strip_all_tags( $first['notice'] ) : '';
				wc_clear_notices();
			}
			wp_send_json_error(
				array(
					'message' => $message ? $message : __( 'Could not add to cart.', 'zymarg-product-builder' ),
				),
				400
			);
		}

		// Trigger added-to-cart hook so other plugins (and our own) can react.
		do_action( 'woocommerce_ajax_added_to_cart', $product_id );

		// Build fragments (mini-cart, count, etc.).
		ob_start();
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );
		ob_end_clean();

		$redirect_url = '';
		if ( apply_filters( 'woocommerce_cart_redirect_after_add', false, $product_id ) ) {
			$redirect_url = wc_get_cart_url();
		}

		wp_send_json_success(
			array(
				'message'      => __( 'Added to cart.', 'zymarg-product-builder' ),
				'cart_count'   => WC()->cart->get_cart_contents_count(),
				'cart_total'   => WC()->cart->get_cart_total(),
				'fragments'    => $fragments,
				'redirect_url' => $redirect_url,
				'cart_url'     => wc_get_cart_url(),
				'checkout_url' => wc_get_checkout_url(),
			)
		);
	}

	/**
	 * Sanitize an associative array of variation attributes.
	 *
	 * @param array $variation Raw input.
	 * @return array
	 */
	private function sanitize_variation_attributes( $variation ) {
		$clean = array();
		foreach ( $variation as $key => $value ) {
			$clean[ wc_clean( (string) $key ) ] = wc_clean( (string) $value );
		}
		return $clean;
	}
}
