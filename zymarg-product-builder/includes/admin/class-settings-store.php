<?php
/**
 * Settings storage and retrieval helper.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings_Store
 *
 * Wraps the single `zpb_settings` option with defaults, dot-notation get(),
 * and section helpers. Used by both the settings page and every widget.
 *
 * Storage shape:
 *   [
 *     'version'     => '0.1.0',
 *     'general'     => [...],
 *     'add_to_cart' => [...],
 *   ]
 */
final class Settings_Store {

	/** Option name. */
	const OPTION_KEY = 'zpb_settings';

	/** Cached merged settings. */
	private static $cache = null;

	/**
	 * Default values for every section.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'version'     => defined( 'ZPB_VERSION' ) ? ZPB_VERSION : '0.1.0',
			'general'     => array(
				'enable_add_to_cart' => 'yes',
				'product_source'     => 'current',
				'use_ajax'           => 'yes',
				'spinner_style'      => 'spinner',
				'success_behavior'   => 'restore',
			),
			'add_to_cart' => array(
				'button_text'           => __( 'Add to Cart', 'zymarg-product-builder' ),
				'show_stock'            => 'yes',
				'show_price'            => 'no',
				'show_quantity'         => 'yes',
				'show_quantity_label'   => 'yes',
				'quantity_label'        => __( 'Quantity:', 'zymarg-product-builder' ),
				'show_buy_now'          => 'no',
				'buy_now_text'          => __( 'Buy Now', 'zymarg-product-builder' ),
				'redirect_after'        => 'none',
				'out_of_stock_behavior' => 'disable',
				'out_of_stock_text'     => __( 'Out of Stock', 'zymarg-product-builder' ),
				'use_product_min_max'   => 'yes',
			),
		);
	}

	/**
	 * Get the full merged settings array (option ∪ defaults).
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored   = get_option( self::OPTION_KEY, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = self::defaults();

		// Deep-merge per section (keys present in defaults always win the shape).
		$merged = $defaults;
		foreach ( $defaults as $section => $section_defaults ) {
			if ( ! is_array( $section_defaults ) ) {
				if ( isset( $stored[ $section ] ) ) {
					$merged[ $section ] = $stored[ $section ];
				}
				continue;
			}
			$merged[ $section ] = array_merge(
				$section_defaults,
				isset( $stored[ $section ] ) && is_array( $stored[ $section ] ) ? $stored[ $section ] : array()
			);
		}

		self::$cache = $merged;
		return self::$cache;
	}

	/**
	 * Get a value by dot-notation path or full section.
	 *
	 * Examples:
	 *   Settings_Store::get( 'add_to_cart.button_text' )
	 *   Settings_Store::get( 'general' )
	 *
	 * @param string $key      Dot-notation key.
	 * @param mixed  $fallback Fallback when missing.
	 * @return mixed
	 */
	public static function get( $key, $fallback = '' ) {
		$all   = self::all();
		$parts = explode( '.', $key );
		$node  = $all;
		foreach ( $parts as $part ) {
			if ( is_array( $node ) && array_key_exists( $part, $node ) ) {
				$node = $node[ $part ];
			} else {
				return $fallback;
			}
		}
		return $node;
	}

	/**
	 * Get an entire section as an array (with defaults applied).
	 *
	 * @param string $section Section key (e.g. 'add_to_cart').
	 * @return array
	 */
	public static function section( $section ) {
		$all = self::all();
		return isset( $all[ $section ] ) && is_array( $all[ $section ] ) ? $all[ $section ] : array();
	}

	/**
	 * Update one full section atomically. Caller must pass already-sanitized values.
	 *
	 * @param string $section      Section key.
	 * @param array  $section_data Sanitized values to store for this section.
	 * @return bool
	 */
	public static function update_section( $section, array $section_data ) {
		$current             = get_option( self::OPTION_KEY, array() );
		$current             = is_array( $current ) ? $current : array();
		$current[ $section ] = $section_data;
		$current['version']  = defined( 'ZPB_VERSION' ) ? ZPB_VERSION : '0.1.0';

		$result      = update_option( self::OPTION_KEY, $current );
		self::$cache = null;
		return $result;
	}

	/**
	 * Reset a single section to its default values.
	 *
	 * @param string $section Section key.
	 * @return bool
	 */
	public static function reset_section( $section ) {
		$defaults = self::defaults();
		if ( ! isset( $defaults[ $section ] ) || ! is_array( $defaults[ $section ] ) ) {
			return false;
		}
		return self::update_section( $section, $defaults[ $section ] );
	}

	/**
	 * Seed defaults if no option exists yet (used at activation).
	 */
	public static function seed_defaults() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			update_option( self::OPTION_KEY, self::defaults() );
		}
	}

	/**
	 * Clear the in-memory cache (mostly for tests).
	 */
	public static function flush_cache() {
		self::$cache = null;
	}
}
