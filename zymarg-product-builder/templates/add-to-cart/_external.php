<?php
/**
 * External / affiliate product Add to Cart layout.
 *
 * Renders a single button that links to the external URL.
 *
 * Vars:
 *   $product   \WC_Product_External
 *   $settings  array
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Settings_Store;

/** @var \WC_Product_External $product */
/** @var array $settings */

$external_url = $product->get_product_url();
if ( empty( $external_url ) ) {
	return;
}

// Priority: WC product's own button text, then widget setting, then global default.
$button_text = $product->get_button_text();
if ( empty( $button_text ) ) {
	$button_text = ! empty( $settings['button_text'] )
		? $settings['button_text']
		: Settings_Store::get( 'add_to_cart.external_button_default_text', __( 'Buy Product', 'zymarg-product-builder' ) );
}
?>
<div class="zpb-atc zpb-atc--external">
	<div class="zpb-atc__buttons">
		<a href="<?php echo esc_url( $external_url ); ?>"
			target="_blank"
			rel="noopener noreferrer nofollow"
			class="zpb-atc__btn zpb-atc__btn--external">
			<span class="zpb-atc__btn-text"><?php echo esc_html( $button_text ); ?></span>
			<span class="zpb-atc__external-icon" aria-hidden="true">&#x2197;</span>
			<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'zymarg-product-builder' ); ?></span>
		</a>
	</div>
</div>
