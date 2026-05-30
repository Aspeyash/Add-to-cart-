<?php
/**
 * Injects per-product JSON data into the page so client-side widgets
 * (variation matching, price/stock updates) can run without extra AJAX
 * round-trips.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Frontend;

use Zymarg\ProductBuilder\Product_Context;

defined( 'ABSPATH' ) || exit;

/**
 * Product Data — collects product IDs registered by widgets during render
 * and prints a single JSON blob in the footer.
 */
final class Product_Data {

	/**
	 * Singleton.
	 *
	 * @var Product_Data|null
	 */
	private static $instance = null;

	/**
	 * Product IDs queued for output.
	 *
	 * @var int[]
	 */
	private $queued = array();

	/**
	 * Get singleton.
	 *
	 * @return Product_Data
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			add_action( 'wp_footer', array( self::$instance, 'print_data' ), 5 );
		}
		return self::$instance;
	}

	/**
	 * Queue a product for inclusion in the footer JSON.
	 *
	 * @param int $product_id Product ID.
	 */
	public function queue( $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id && ! in_array( $product_id, $this->queued, true ) ) {
			$this->queued[] = $product_id;
		}
	}

	/**
	 * Render queued product data as a JSON island in the page footer.
	 */
	public function print_data() {
		if ( empty( $this->queued ) ) {
			return;
		}

		$payload = array();
		foreach ( $this->queued as $product_id ) {
			$data = $this->build_product_payload( $product_id );
			if ( $data ) {
				$payload[ (string) $product_id ] = $data;
			}
		}

		if ( empty( $payload ) ) {
			return;
		}

		printf(
			'<script type="application/json" id="zpb-products-data">%s</script>',
			wp_json_encode( $payload )
		);
	}

	/**
	 * Build the JSON-serializable payload for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	private function build_product_payload( $product_id ) {
		$product = Product_Context::get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		$payload = array(
			'id'          => $product->get_id(),
			'type'        => $product->get_type(),
			'name'        => $product->get_name(),
			'permalink'   => get_permalink( $product->get_id() ),
			'sku'         => $product->get_sku(),
			'priceHtml'   => $product->get_price_html(),
			'price'       => (float) $product->get_price(),
			'isInStock'   => $product->is_in_stock(),
			'isPurchasable' => $product->is_purchasable(),
			'stockQty'    => $product->get_stock_quantity(),
			'maxQty'      => $this->get_max_purchase_qty( $product ),
			'minQty'      => 1,
			'image'       => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' ),
			'attributes'  => array(),
			'variations'  => array(),
		);

		if ( $product->is_type( 'variable' ) ) {
			$payload['attributes'] = $this->get_attribute_data( $product );
			$payload['variations'] = $this->get_variations_data( $product );
		}

		/**
		 * Filter the product data payload sent to the front-end.
		 *
		 * @param array       $payload Payload array.
		 * @param \WC_Product $product Product object.
		 */
		return apply_filters( 'zpb_product_payload', $payload, $product );
	}

	/**
	 * Get max purchasable qty as a number (-1 = unlimited).
	 *
	 * @param \WC_Product $product Product.
	 * @return int
	 */
	private function get_max_purchase_qty( $product ) {
		$max = $product->get_max_purchase_quantity();
		return is_numeric( $max ) ? (int) $max : -1;
	}

	/**
	 * Variation attributes (name + label + options).
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array
	 */
	private function get_attribute_data( $product ) {
		$attributes = array();
		foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $attribute_name ) ) );
			$attributes[] = array(
				'name'    => sanitize_title( $attribute_name ),
				'label'   => wc_attribute_label( $attribute_name, $product ),
				'options' => array_values( array_map( 'strval', $options ) ),
			);
		}
		return $attributes;
	}

	/**
	 * Variation list (id, attributes map, price, stock, image).
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @return array
	 */
	private function get_variations_data( $product ) {
		$variations = array();
		$available  = $product->get_available_variations();

		foreach ( $available as $variation ) {
			$variations[] = array(
				'id'             => (int) $variation['variation_id'],
				'attributes'     => isset( $variation['attributes'] ) ? $variation['attributes'] : array(),
				'priceHtml'      => isset( $variation['price_html'] ) ? $variation['price_html'] : '',
				'displayPrice'   => isset( $variation['display_price'] ) ? (float) $variation['display_price'] : 0,
				'isInStock'      => ! empty( $variation['is_in_stock'] ),
				'isPurchasable'  => ! empty( $variation['is_purchasable'] ),
				'maxQty'         => isset( $variation['max_qty'] ) && '' !== $variation['max_qty'] ? (int) $variation['max_qty'] : -1,
				'minQty'         => isset( $variation['min_qty'] ) ? (int) $variation['min_qty'] : 1,
				'image'          => isset( $variation['image']['src'] ) ? $variation['image']['src'] : '',
				'sku'            => isset( $variation['sku'] ) ? $variation['sku'] : '',
			);
		}

		return $variations;
	}
}
