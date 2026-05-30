<?php
/**
 * Layer 3: Swatch preview column on attribute term list tables.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Term_List_Table — adds a "Swatch" column to each WC attribute term list.
 */
final class Term_List_Table {

	/** Singleton. @var Term_List_Table|null */
	private static $instance = null;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Hook into each WC attribute taxonomy's list table. */
	private function register() {
		add_action( 'admin_init', array( $this, 'attach_hooks' ), 20 );
	}

	/** Add column + content + width filters per taxonomy. */
	public function attach_hooks() {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$slug = wc_attribute_taxonomy_name( $tax->attribute_name );
			if ( ! $slug ) {
				continue;
			}
			add_filter( 'manage_edit-' . $slug . '_columns',          array( $this, 'add_column' ) );
			add_filter( 'manage_' . $slug . '_custom_column',         array( $this, 'render_column' ), 10, 3 );
			add_filter( 'manage_edit-' . $slug . '_sortable_columns', array( $this, 'sortable_columns' ) );
		}
	}

	/**
	 * Insert "Swatch" as the first column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$new = array();
		// Keep checkbox first if present.
		if ( isset( $columns['cb'] ) ) {
			$new['cb'] = $columns['cb'];
			unset( $columns['cb'] );
		}
		$new['zpb_swatch'] = '<span class="zpb-col-swatch-header">' . esc_html__( 'Swatch', 'zymarg-product-builder' ) . '</span>';
		return array_merge( $new, $columns );
	}

	/**
	 * Render swatch preview cell.
	 *
	 * @param string $content     Existing content (always empty for custom columns).
	 * @param string $column_name Column key.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function render_column( $content, $column_name, $term_id ) {
		if ( 'zpb_swatch' !== $column_name ) {
			return $content;
		}

		$swatch = Term_Meta::get_swatch( (int) $term_id );

		switch ( $swatch['type'] ) {
			case 'color':
				if ( '' === $swatch['color'] ) {
					$content = '<span class="zpb-swatch-preview zpb-swatch-preview--empty" aria-hidden="true"></span>';
				} elseif ( '' !== $swatch['color_2'] ) {
					$content = sprintf(
						'<span class="zpb-swatch-preview zpb-swatch-preview--split" style="background:linear-gradient(135deg,%1$s 0%%,%1$s 50%%,%2$s 50%%,%2$s 100%%);" title="%3$s"></span>',
						esc_attr( $swatch['color'] ),
						esc_attr( $swatch['color_2'] ),
						esc_attr( $swatch['color'] . ' / ' . $swatch['color_2'] )
					);
				} else {
					$content = sprintf(
						'<span class="zpb-swatch-preview" style="background-color:%1$s;" title="%1$s"></span>',
						esc_attr( $swatch['color'] )
					);
				}
				break;

			case 'image':
				if ( $swatch['image_url'] ) {
					$content = sprintf(
						'<span class="zpb-swatch-preview zpb-swatch-preview--image"><img src="%s" alt="" /></span>',
						esc_url( $swatch['image_url'] )
					);
				} else {
					$content = '<span class="zpb-swatch-preview zpb-swatch-preview--empty" aria-hidden="true"></span>';
				}
				break;

			case 'label':
			case 'button':
				$content = sprintf(
					'<span class="zpb-swatch-preview zpb-swatch-preview--text">%s</span>',
					esc_html( $swatch['label'] )
				);
				break;

			default:
				$content = '<span class="zpb-swatch-preview zpb-swatch-preview--none" aria-hidden="true">&mdash;</span>';
		}

		return $content;
	}

	/**
	 * Mark our column non-sortable (it doesn't map to a single DB field).
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		return $columns;
	}
}
