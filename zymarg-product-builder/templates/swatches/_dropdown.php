<?php
/**
 * Dropdown fallback partial — used when display type = default.
 *
 * Vars: $attribute_name, $attribute_label, $options, $product, $is_taxonomy, $taxonomy.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/** @var string $attribute_name */
/** @var string $attribute_label */
/** @var array  $options */
/** @var \WC_Product $product */
/** @var bool   $is_taxonomy */
/** @var string $taxonomy */
?>
<select
	class="zpb-swatches__dropdown"
	data-zpb-dropdown="<?php echo esc_attr( $attribute_name ); ?>"
	aria-label="<?php echo esc_attr( $attribute_label ); ?>"
>
	<option value=""><?php
		printf(
			/* translators: %s: attribute label */
			esc_html__( 'Choose %s', 'zymarg-product-builder' ),
			esc_html( $attribute_label )
		);
	?></option>
	<?php foreach ( $options as $option_value ) :
		if ( $is_taxonomy ) {
			$term = get_term_by( 'slug', $option_value, $taxonomy );
			$option_label = $term && ! is_wp_error( $term ) ? $term->name : $option_value;
		} else {
			$option_label = $option_value;
		}
	?>
		<option value="<?php echo esc_attr( $option_value ); ?>"><?php echo esc_html( $option_label ); ?></option>
	<?php endforeach; ?>
</select>
