<?php
/**
 * Variation Swatches widget template.
 *
 * Override in your theme:
 *   yourtheme/zymarg-product-builder/swatches/swatches.php
 *
 * Available vars:
 *   $product               \WC_Product_Variable
 *   $settings              array
 *   $variation_attributes  array  Product->get_variation_attributes()
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Attribute_Settings;
use Zymarg\ProductBuilder\Admin\Term_Meta;
use Zymarg\ProductBuilder\Product_Overrides;
use Zymarg\ProductBuilder\Widgets\Swatches_Widget;

/** @var \WC_Product $product */
/** @var array $settings */
/** @var array $variation_attributes */

$show_label    = ! empty( $settings['show_attribute_label'] ) && 'yes' === $settings['show_attribute_label'];
$show_value    = ! empty( $settings['show_selected_value'] ) && 'yes' === $settings['show_selected_value'];
$show_colon    = ! empty( $settings['show_colon'] ) && 'yes' === $settings['show_colon'];
$show_price    = ! empty( $settings['show_per_swatch_price'] ) && 'yes' === $settings['show_per_swatch_price'];
$show_reset    = ! empty( $settings['show_reset'] ) && 'yes' === $settings['show_reset'];
$reset_text    = ! empty( $settings['reset_text'] ) ? $settings['reset_text'] : __( 'Reset selection', 'zymarg-product-builder' );
$auto_first    = ! empty( $settings['auto_select_first'] ) && 'yes' === $settings['auto_select_first'];

// Per-product hidden attributes — auto-resolve sensible defaults so cart
// submission still picks a real variation.
$hidden_resolved = class_exists( '\Zymarg\ProductBuilder\Product_Overrides' )
	? Product_Overrides::resolve_hidden_attributes( $product )
	: array();

// Build a price-per-swatch lookup if needed: attribute_key => term_slug => priceHtml
$price_map = array();
if ( $show_price ) {
	$available = $product->get_available_variations();
	foreach ( $available as $variation ) {
		if ( empty( $variation['attributes'] ) ) {
			continue;
		}
		// Use first attribute that distinguishes this variation as the lookup key.
		// Practically we attach the price to the LAST non-empty attribute in the variation.
		foreach ( $variation['attributes'] as $attr_key => $attr_value ) {
			if ( '' === (string) $attr_value ) {
				continue;
			}
			if ( ! isset( $price_map[ $attr_key ] ) ) {
				$price_map[ $attr_key ] = array();
			}
			if ( ! isset( $price_map[ $attr_key ][ $attr_value ] ) ) {
				$price_map[ $attr_key ][ $attr_value ] = isset( $variation['price_html'] ) ? $variation['price_html'] : '';
			}
		}
	}
}
?>
<div class="zpb-swatches"
	data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
	data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
	data-auto-select-first="<?php echo $auto_first ? '1' : '0'; ?>">

	<?php
	foreach ( $variation_attributes as $attribute_name => $options ) :
		// $attribute_name is like 'attribute_pa_color' (lowercase, prefixed) or
		// 'attribute_size' (custom non-taxonomy attribute).
		$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_', '', $attribute_name ) );
		$is_taxonomy = $taxonomy && taxonomy_exists( $taxonomy );

		// Per-product hidden — skip rendering this attribute entirely.
		if ( $is_taxonomy
			&& class_exists( '\Zymarg\ProductBuilder\Product_Overrides' )
			&& Product_Overrides::is_attribute_hidden( $product->get_id(), $taxonomy ) ) {
			continue;
		}

		$type = $is_taxonomy
			? Swatches_Widget::resolve_display_type( $taxonomy, $settings, $product->get_id() )
			: Attribute_Settings::TYPE_DEFAULT;

		// Human label (e.g. "Color").
		$attribute_label = wc_attribute_label( $is_taxonomy ? $taxonomy : str_replace( 'attribute_', '', $attribute_name ), $product );
		?>

		<div class="zpb-swatches__attr"
			data-zpb-attr="<?php echo esc_attr( $attribute_name ); ?>"
			data-taxonomy="<?php echo esc_attr( $is_taxonomy ? $taxonomy : '' ); ?>"
			role="<?php echo Attribute_Settings::TYPE_DEFAULT === $type ? '' : 'radiogroup'; ?>"
			aria-label="<?php echo esc_attr( $attribute_label ); ?>">

			<?php if ( $show_label ) : ?>
				<div class="zpb-swatches__label">
					<span class="zpb-swatches__attr-name">
						<?php
						echo esc_html( $attribute_label );
						if ( $show_colon ) {
							echo ':';
						}
						?>
					</span>
					<?php if ( $show_value ) : ?>
						<span class="zpb-swatches__attr-value" data-zpb-selected-value></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( Attribute_Settings::TYPE_DEFAULT === $type ) : ?>
				<?php
				$context = array(
					'attribute_name'  => $attribute_name,
					'attribute_label' => $attribute_label,
					'options'         => $options,
					'product'         => $product,
					'is_taxonomy'     => $is_taxonomy,
					'taxonomy'        => $taxonomy,
				);
				extract( $context, EXTR_SKIP ); // phpcs:ignore
				include ZPB_TEMPLATES_DIR . 'swatches/_dropdown.php';
				?>
			<?php else : ?>
				<div class="zpb-swatches__list zpb-swatches__list--<?php echo esc_attr( $type ); ?>">
					<?php
					foreach ( $options as $option_value ) :
						$term = $is_taxonomy ? get_term_by( 'slug', $option_value, $taxonomy ) : null;

						$swatch_data = $term ? Term_Meta::get_swatch( $term->term_id, $taxonomy ) : array(
							'color'     => '',
							'color_2'   => '',
							'image_id'  => 0,
							'image_url' => '',
							'label'     => $option_value,
							'tooltip'   => '',
						);
						$label = $term ? Term_Meta::get_label( $term->term_id, $term ) : $option_value;
						$tooltip = $term ? Term_Meta::get_tooltip( $term->term_id ) : '';
						if ( '' === $tooltip ) {
							$tooltip = $label;
						}

						$swatch_price = '';
						if ( $show_price && isset( $price_map[ $attribute_name ][ $option_value ] ) ) {
							$swatch_price = $price_map[ $attribute_name ][ $option_value ];
						}

						$partial_context = array(
							'value'        => $option_value,
							'label'        => $label,
							'tooltip'      => $tooltip,
							'swatch_data'  => $swatch_data,
							'swatch_price' => $swatch_price,
						);

						extract( $partial_context, EXTR_SKIP ); // phpcs:ignore

						$partial = ZPB_TEMPLATES_DIR . 'swatches/_' . $type . '.php';
						if ( file_exists( $partial ) ) {
							include $partial;
						}
					endforeach;
					?>
				</div>
			<?php endif; ?>
		</div>

	<?php endforeach; ?>

	<?php if ( $show_reset ) : ?>
		<button type="button" class="zpb-swatches__reset" data-zpb-reset hidden>
			<?php echo esc_html( $reset_text ); ?>
		</button>
	<?php endif; ?>

	<?php /* Hidden attributes — auto-resolved server-side so JS variation
	         matching has the right values without showing a swatch. */ ?>
	<?php foreach ( $hidden_resolved as $attr_key => $attr_value ) : ?>
		<input type="hidden"
			data-zpb-hidden-attr="<?php echo esc_attr( $attr_key ); ?>"
			value="<?php echo esc_attr( $attr_value ); ?>" />
	<?php endforeach; ?>
</div>
