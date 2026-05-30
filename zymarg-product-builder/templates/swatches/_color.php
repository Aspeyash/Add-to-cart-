<?php
/**
 * Color swatch partial.
 *
 * Vars: $value, $label, $tooltip, $swatch_data, $swatch_price.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/** @var string $value */
/** @var string $label */
/** @var string $tooltip */
/** @var array  $swatch_data */
/** @var string $swatch_price */

$has_color = ! empty( $swatch_data['color'] );
$has_split = $has_color && ! empty( $swatch_data['color_2'] );
$style     = '';
if ( $has_color ) {
	$style .= '--zpb-color:' . esc_attr( $swatch_data['color'] ) . ';';
	if ( $has_split ) {
		$style .= '--zpb-color-2:' . esc_attr( $swatch_data['color_2'] ) . ';';
	}
}
$visual_class = 'zpb-swatch__visual';
if ( $has_split ) {
	$visual_class .= ' zpb-swatch__visual--split';
} elseif ( ! $has_color ) {
	$visual_class .= ' zpb-swatch__visual--empty';
}
?>
<button
	type="button"
	class="zpb-swatch zpb-swatch--color"
	data-zpb-swatch
	data-value="<?php echo esc_attr( $value ); ?>"
	data-label="<?php echo esc_attr( $label ); ?>"
	data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
	role="radio"
	aria-checked="false"
	aria-label="<?php echo esc_attr( $label ); ?>"
	tabindex="-1"
	style="<?php echo esc_attr( $style ); ?>"
>
	<span class="<?php echo esc_attr( $visual_class ); ?>" aria-hidden="true"></span>
	<?php if ( '' !== $swatch_price ) : ?>
		<span class="zpb-swatch__price" data-zpb-swatch-price><?php echo wp_kses_post( $swatch_price ); ?></span>
	<?php endif; ?>
	<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
</button>
