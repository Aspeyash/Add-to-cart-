<?php
/**
 * Label swatch partial (text-only).
 *
 * Vars: $value, $label, $tooltip, $swatch_data, $swatch_price.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/** @var string $value */
/** @var string $label */
/** @var string $tooltip */
/** @var string $swatch_price */
?>
<button
	type="button"
	class="zpb-swatch zpb-swatch--label"
	data-zpb-swatch
	data-value="<?php echo esc_attr( $value ); ?>"
	data-label="<?php echo esc_attr( $label ); ?>"
	data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
	role="radio"
	aria-checked="false"
	aria-label="<?php echo esc_attr( $label ); ?>"
	tabindex="-1"
>
	<span class="zpb-swatch__text"><?php echo esc_html( $label ); ?></span>
	<?php if ( '' !== $swatch_price ) : ?>
		<span class="zpb-swatch__price" data-zpb-swatch-price><?php echo wp_kses_post( $swatch_price ); ?></span>
	<?php endif; ?>
</button>
