<?php
/**
 * Layer 1: per-attribute display-type configuration.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute_Settings — owns the `zpb_attribute_types` option.
 *
 * Storage shape:
 *   [
 *     'pa_color' => 'color',
 *     'pa_size'  => 'button',
 *   ]
 *
 * Anything not present in the option falls back to 'default' (native dropdown).
 */
final class Attribute_Settings {

	/** Option name. */
	const OPTION_KEY = 'zpb_attribute_types';

	/** Default (no swatch) type. */
	const TYPE_DEFAULT = 'default';

	/** Cached map. @var array|null */
	private static $cache = null;

	/**
	 * Allowed display types and their human labels.
	 *
	 * @return array
	 */
	public static function types() {
		return array(
			self::TYPE_DEFAULT => __( 'Default Dropdown', 'zymarg-product-builder' ),
			'color'            => __( 'Color', 'zymarg-product-builder' ),
			'image'            => __( 'Image', 'zymarg-product-builder' ),
			'label'            => __( 'Label', 'zymarg-product-builder' ),
			'button'           => __( 'Button', 'zymarg-product-builder' ),
		);
	}

	/**
	 * Get the full map.
	 *
	 * @return array map of taxonomy => type
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$stored      = get_option( self::OPTION_KEY, array() );
		self::$cache = is_array( $stored ) ? $stored : array();
		return self::$cache;
	}

	/**
	 * Get the configured display type for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug (e.g. 'pa_color').
	 * @return string
	 */
	public static function get_type( $taxonomy ) {
		$all = self::all();
		return isset( $all[ $taxonomy ] ) ? (string) $all[ $taxonomy ] : self::TYPE_DEFAULT;
	}

	/**
	 * Whether the given type is one of our swatch types (not "default").
	 *
	 * @param string $type Type slug.
	 * @return bool
	 */
	public static function is_swatch_type( $type ) {
		return in_array( $type, array( 'color', 'image', 'label', 'button' ), true );
	}

	/**
	 * Replace the entire map atomically.
	 *
	 * @param array $map Sanitized map of taxonomy => type.
	 * @return bool
	 */
	public static function update_all( array $map ) {
		$result      = update_option( self::OPTION_KEY, $map );
		self::$cache = null;
		return $result;
	}

	/**
	 * Sanitize a single value against allowed types.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function sanitize_type( $value ) {
		$value = is_string( $value ) ? $value : '';
		return array_key_exists( $value, self::types() ) ? $value : self::TYPE_DEFAULT;
	}

	/**
	 * Convenience: get all WC product attribute taxonomies as
	 *   [ taxonomy_slug => label ]
	 *
	 * @return array
	 */
	public static function get_attribute_choices() {
		$out = array();
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $out;
		}
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$slug = wc_attribute_taxonomy_name( $tax->attribute_name );
			if ( $slug ) {
				$out[ $slug ] = $tax->attribute_label ? $tax->attribute_label : $tax->attribute_name;
			}
		}
		return $out;
	}

	/**
	 * Drop the cached map (mostly for tests).
	 */
	public static function flush_cache() {
		self::$cache = null;
	}
}
