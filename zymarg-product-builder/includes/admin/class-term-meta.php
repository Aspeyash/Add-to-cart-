<?php
/**
 * Layer 2 reader: per-term swatch values.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Term_Meta — read swatch values for a WC attribute term.
 *
 * The companion writer is Term_Fields (which renders form inputs and
 * saves on `created_*` / `edited_*` hooks).
 */
final class Term_Meta {

	/** Term meta keys. */
	const META_COLOR    = 'zpb_color';
	const META_COLOR_2  = 'zpb_color_2';
	const META_IMAGE_ID = 'zpb_image_id';
	const META_LABEL    = 'zpb_label';
	const META_TOOLTIP  = 'zpb_tooltip';

	/**
	 * Get a normalized swatch payload for one term.
	 *
	 * Always returns the same shape so callers don't have to branch.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug. Optional — looked up if omitted.
	 * @return array {
	 *     @type string $type      One of color|image|label|button|default
	 *     @type string $color     '' or '#RRGGBB'
	 *     @type string $color_2   '' or '#RRGGBB'
	 *     @type int    $image_id  0 or attachment ID
	 *     @type string $image_url '' or attachment URL (thumbnail size)
	 *     @type string $label     Term name fallback
	 *     @type string $slug      Term slug
	 *     @type string $tooltip   ''
	 * }
	 */
	public static function get_swatch( $term_id, $taxonomy = '' ) {
		$term_id = (int) $term_id;
		$term    = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return self::empty_payload();
		}

		if ( ! $taxonomy ) {
			$taxonomy = $term->taxonomy;
		}

		$type     = Attribute_Settings::get_type( $taxonomy );
		$image_id = (int) get_term_meta( $term_id, self::META_IMAGE_ID, true );

		return array(
			'type'      => $type,
			'color'     => self::normalize_color( get_term_meta( $term_id, self::META_COLOR, true ) ),
			'color_2'   => self::normalize_color( get_term_meta( $term_id, self::META_COLOR_2, true ) ),
			'image_id'  => $image_id,
			'image_url' => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '',
			'label'     => self::get_label( $term_id, $term ),
			'slug'      => $term->slug,
			'tooltip'   => (string) get_term_meta( $term_id, self::META_TOOLTIP, true ),
		);
	}

	/**
	 * Custom label override or fallback to term name.
	 *
	 * @param int          $term_id Term ID.
	 * @param \WP_Term|null $term   Term (optional, looked up if omitted).
	 * @return string
	 */
	public static function get_label( $term_id, $term = null ) {
		$override = (string) get_term_meta( $term_id, self::META_LABEL, true );
		if ( '' !== $override ) {
			return $override;
		}
		if ( null === $term ) {
			$term = get_term( $term_id );
		}
		return ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
	}

	/**
	 * Get the primary swatch color for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function get_color( $term_id ) {
		return self::normalize_color( get_term_meta( $term_id, self::META_COLOR, true ) );
	}

	/**
	 * Get a swatch image URL.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $size    Image size.
	 * @return string
	 */
	public static function get_image_url( $term_id, $size = 'thumbnail' ) {
		$id = (int) get_term_meta( $term_id, self::META_IMAGE_ID, true );
		return $id ? (string) wp_get_attachment_image_url( $id, $size ) : '';
	}

	/**
	 * Get tooltip text.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function get_tooltip( $term_id ) {
		return (string) get_term_meta( $term_id, self::META_TOOLTIP, true );
	}

	/**
	 * Validate / normalize a hex color value.
	 *
	 * @param mixed $value Raw value.
	 * @return string '' or normalized '#rrggbb'.
	 */
	public static function normalize_color( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		// Allow with or without leading #.
		if ( '#' !== $value[0] ) {
			$value = '#' . $value;
		}
		// 3-digit shorthand (#fff) or 6-digit (#ffffff).
		if ( ! preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) {
			return '';
		}
		return strtolower( $value );
	}

	/** Empty payload shape. */
	private static function empty_payload() {
		return array(
			'type'      => Attribute_Settings::TYPE_DEFAULT,
			'color'     => '',
			'color_2'   => '',
			'image_id'  => 0,
			'image_url' => '',
			'label'     => '',
			'slug'      => '',
			'tooltip'   => '',
		);
	}
}
