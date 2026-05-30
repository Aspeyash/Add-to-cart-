<?php
/**
 * Image swatch partial.
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

$has_image = ! empty( $swatch_data['image_url'] );
?>
<button
	type="button"
	class="zpb-swatch zpb-swatch--image"
	data-zpb-swatch
	data-value="<?php echo esc_attr( $value ); ?>"
	data-label="<?php echo esc_attr( $label ); ?>"
	data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
	role="radio"
	aria-checked="false"
	aria-label="<?php echo esc_attr( $label ); ?>"
	tabindex="-1"
>
	<?php if ( $has_image ) : ?>
		<img src="<?php echo esc_url( $swatch_data['image_url'] ); ?>" alt="" loading="lazy" />
	<?php else : ?>
		<span class="zpb-swatch__visual zpb-swatch__visual--empty" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( '' !== $swatch_price ) : ?>
		<span class="zpb-swatch__price" data-zpb-swatch-price><?php echo wp_kses_post( $swatch_price ); ?></span>
	<?php endif; ?>
	<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
</button>
