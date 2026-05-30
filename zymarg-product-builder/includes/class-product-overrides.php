<?php
/**
 * Per-product override read API + storage.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

use Zymarg\ProductBuilder\Admin\Attribute_Settings;
use Zymarg\ProductBuilder\Admin\Settings_Store;

defined( 'ABSPATH' ) || exit;

/**
 * Product_Overrides — third layer of configuration.
 *
 * Resolution chain for every value:
 *
 *   Per-Product Override   →   Global Setting   →   Hard-coded Default
 *      (this class)            (Settings_Store /
 *                               Attribute_Settings)
 *
 * Storage: single post meta key '_zpb_overrides' on each product.
 *
 *   [
 *     'attribute_display' => [ 'pa_color' => 'image' ],          // 'inherit' values are NOT stored
 *     'hidden_attributes' => [ 'pa_material' ],
 *     'add_to_cart' => [
 *       'button_text'           => 'Buy Mine Now',
 *       'show_buy_now'          => 'yes' | 'no' | 'inherit',     // 'inherit' is NOT stored
 *       'out_of_stock_behavior' => 'disable' | 'hide' | 'message' | 'inherit',
 *       'redirect_after'        => 'none' | 'cart' | 'checkout' | 'inherit',
 *     ],
 *     'widgets_disabled' => [ 'gallery' ],                       // empty array is NOT stored
 *   ]
 */
final class Product_Overrides {

	/** Post meta key. */
	const META_KEY = '_zpb_overrides';

	/** Sentinel value used in admin forms; never stored. */
	const INHERIT = 'inherit';

	/** Cached per-product override arrays during a single request. */
	private static $cache = array();

	/* =====================================================================
	 * Storage
	 * ===================================================================== */

	/**
	 * Get the raw override array for a product (or empty array).
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	public static function all( $product_id ) {
		$product_id = (int) $product_id;
		if ( ! $product_id ) {
			return array();
		}
		if ( array_key_exists( $product_id, self::$cache ) ) {
			return self::$cache[ $product_id ];
		}
		$raw = get_post_meta( $product_id, self::META_KEY, true );
		self::$cache[ $product_id ] = is_array( $raw ) ? $raw : array();
		return self::$cache[ $product_id ];
	}

	/**
	 * Replace the override payload for a product. Empty / inherit values
	 * are stripped before saving so the meta row stays small.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $overrides  Sanitized overrides.
	 * @return bool
	 */
	public static function update( $product_id, array $overrides ) {
		$product_id = (int) $product_id;
		if ( ! $product_id ) {
			return false;
		}
		$clean = self::strip_empty( $overrides );

		if ( empty( $clean ) ) {
			delete_post_meta( $product_id, self::META_KEY );
			unset( self::$cache[ $product_id ] );
			return true;
		}

		$result = update_post_meta( $product_id, self::META_KEY, $clean );
		unset( self::$cache[ $product_id ] );
		return false !== $result;
	}

	/**
	 * Wipe all overrides for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function clear( $product_id ) {
		$product_id = (int) $product_id;
		if ( ! $product_id ) {
			return false;
		}
		delete_post_meta( $product_id, self::META_KEY );
		unset( self::$cache[ $product_id ] );
		return true;
	}

	/**
	 * Strip 'inherit' values, empty strings, and empty arrays so the stored
	 * meta only contains real overrides. Keeps the resolution chain clean.
	 *
	 * @param array $data Sanitized overrides.
	 * @return array
	 */
	private static function strip_empty( array $data ) {
		$clean = array();

		// attribute_display: drop 'inherit' / empty values.
		if ( ! empty( $data['attribute_display'] ) && is_array( $data['attribute_display'] ) ) {
			$keep = array();
			foreach ( $data['attribute_display'] as $tax => $type ) {
				if ( '' === $type || self::INHERIT === $type ) {
					continue;
				}
				$keep[ $tax ] = $type;
			}
			if ( ! empty( $keep ) ) {
				$clean['attribute_display'] = $keep;
			}
		}

		// hidden_attributes: only store if non-empty.
		if ( ! empty( $data['hidden_attributes'] ) && is_array( $data['hidden_attributes'] ) ) {
			$clean['hidden_attributes'] = array_values( array_unique( array_map( 'strval', $data['hidden_attributes'] ) ) );
		}

		// add_to_cart: drop 'inherit' / empty values per key.
		if ( ! empty( $data['add_to_cart'] ) && is_array( $data['add_to_cart'] ) ) {
			$keep = array();
			foreach ( $data['add_to_cart'] as $key => $value ) {
				if ( '' === $value || self::INHERIT === $value || null === $value ) {
					continue;
				}
				$keep[ $key ] = $value;
			}
			if ( ! empty( $keep ) ) {
				$clean['add_to_cart'] = $keep;
			}
		}

		// widgets_disabled: only store if non-empty.
		if ( ! empty( $data['widgets_disabled'] ) && is_array( $data['widgets_disabled'] ) ) {
			$clean['widgets_disabled'] = array_values( array_unique( array_map( 'strval', $data['widgets_disabled'] ) ) );
		}

		return $clean;
	}

	/* =====================================================================
	 * Generic getter
	 * ===================================================================== */

	/**
	 * Dot-notation getter that walks the resolution chain.
	 *
	 * Examples:
	 *   Product_Overrides::get( 123, 'add_to_cart.button_text', 'Add to Cart' )
	 *
	 * @param int    $product_id Product ID.
	 * @param string $key        Dot-notation key.
	 * @param mixed  $fallback   Default if neither override nor global is set.
	 * @return mixed
	 */
	public static function get( $product_id, $key, $fallback = '' ) {
		$override = self::dig( self::all( $product_id ), $key );
		if ( null !== $override && '' !== $override && self::INHERIT !== $override ) {
			return $override;
		}
		return $fallback;
	}

	/**
	 * Walk a dot-notation path through a nested array.
	 *
	 * @param array  $data Array to walk.
	 * @param string $path Dot-notation key.
	 * @return mixed|null
	 */
	private static function dig( array $data, $path ) {
		$node = $data;
		foreach ( explode( '.', $path ) as $part ) {
			if ( is_array( $node ) && array_key_exists( $part, $node ) ) {
				$node = $node[ $part ];
			} else {
				return null;
			}
		}
		return $node;
	}

	/* =====================================================================
	 * Specific helpers — used by widgets
	 * ===================================================================== */

	/**
	 * Resolve attribute display type for a product:
	 * per-product override → global Attribute_Settings → 'default'.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $taxonomy   e.g. 'pa_color'.
	 * @return string
	 */
	public static function get_attribute_display( $product_id, $taxonomy ) {
		$override = self::dig( self::all( $product_id ), 'attribute_display.' . $taxonomy );
		if ( $override && self::INHERIT !== $override && array_key_exists( $override, Attribute_Settings::types() ) ) {
			return $override;
		}
		return Attribute_Settings::get_type( $taxonomy );
	}

	/**
	 * Whether a given attribute is marked hidden for this product.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $taxonomy   e.g. 'pa_material'.
	 * @return bool
	 */
	public static function is_attribute_hidden( $product_id, $taxonomy ) {
		$hidden = self::dig( self::all( $product_id ), 'hidden_attributes' );
		if ( ! is_array( $hidden ) ) {
			return false;
		}
		return in_array( $taxonomy, $hidden, true );
	}

	/**
	 * Get all hidden taxonomies for a product (as strings).
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	public static function get_hidden_attributes( $product_id ) {
		$hidden = self::dig( self::all( $product_id ), 'hidden_attributes' );
		return is_array( $hidden ) ? $hidden : array();
	}

	/**
	 * Whether a widget is disabled for this product.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $widget_key One of 'add_to_cart', 'swatches', 'gallery'.
	 * @return bool
	 */
	public static function is_widget_disabled( $product_id, $widget_key ) {
		$disabled = self::dig( self::all( $product_id ), 'widgets_disabled' );
		if ( ! is_array( $disabled ) ) {
			return false;
		}
		return in_array( $widget_key, $disabled, true );
	}

	/* =====================================================================
	 * Add to Cart resolver — combined per-product / global lookup
	 * ===================================================================== */

	/**
	 * Resolve an Add to Cart setting:
	 * per-product override → Settings_Store → fallback.
	 *
	 * Keys mirror Settings_Store's add_to_cart section (button_text,
	 * show_buy_now, out_of_stock_behavior, redirect_after).
	 *
	 * @param int    $product_id Product ID.
	 * @param string $key        Setting key inside add_to_cart.
	 * @param mixed  $fallback   Final fallback.
	 * @return mixed
	 */
	public static function get_add_to_cart( $product_id, $key, $fallback = '' ) {
		$override = self::dig( self::all( $product_id ), 'add_to_cart.' . $key );
		if ( null !== $override && '' !== $override && self::INHERIT !== $override ) {
			return $override;
		}
		if ( class_exists( '\Zymarg\ProductBuilder\Admin\Settings_Store' ) ) {
			$global = Settings_Store::get( 'add_to_cart.' . $key, null );
			if ( null !== $global && '' !== $global ) {
				return $global;
			}
		}
		return $fallback;
	}

	/* =====================================================================
	 * Hidden-attribute auto-resolution helper
	 * ===================================================================== */

	/**
	 * For attributes hidden by per-product config, pre-resolve a sensible
	 * default value so client-side variation matching still works.
	 *
	 * Strategy: pick the first option of each hidden attribute that
	 * belongs to at least one in-stock variation. Falls back to the first
	 * option overall if no variation is in stock.
	 *
	 * @param \WC_Product $product Product (variable).
	 * @return array map of attribute_pa_X => slug
	 */
	public static function resolve_hidden_attributes( $product ) {
		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$hidden = self::get_hidden_attributes( $product->get_id() );
		if ( empty( $hidden ) ) {
			return array();
		}

		$variation_attrs = $product->get_variation_attributes();
		$available       = $product->get_available_variations();

		$resolved = array();

		foreach ( $hidden as $taxonomy ) {
			// WC variation_attribute keys look like 'attribute_pa_color'.
			$attr_key = 'attribute_' . $taxonomy;
			if ( ! isset( $variation_attrs[ $attr_key ] ) ) {
				continue;
			}
			$options = (array) $variation_attrs[ $attr_key ];
			if ( empty( $options ) ) {
				continue;
			}

			$picked = '';
			foreach ( $options as $option ) {
				foreach ( $available as $v ) {
					if ( empty( $v['attributes'][ $attr_key ] ) ) {
						// "any" — matches all options; safe to pick first option.
						$picked = (string) $option;
						break 2;
					}
					if ( (string) $v['attributes'][ $attr_key ] === (string) $option && ! empty( $v['is_in_stock'] ) ) {
						$picked = (string) $option;
						break 2;
					}
				}
			}
			if ( '' === $picked ) {
				$picked = (string) reset( $options );
			}

			$resolved[ $attr_key ] = $picked;
		}

		return $resolved;
	}

	/** Drop the in-memory cache — useful in tests / after admin save. */
	public static function flush_cache( $product_id = null ) {
		if ( null === $product_id ) {
			self::$cache = array();
		} else {
			unset( self::$cache[ (int) $product_id ] );
		}
	}
}
