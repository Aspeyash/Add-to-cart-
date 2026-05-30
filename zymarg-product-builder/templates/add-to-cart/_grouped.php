<?php
/**
 * Grouped product Add to Cart layout.
 *
 * Renders a small table of child products with individual quantity inputs.
 * The form posts to WC's standard cart endpoint (?add-to-cart=grouped_id
 * with quantity[child_id]=N).
 *
 * Vars:
 *   $product   \WC_Product_Grouped
 *   $settings  array
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Settings_Store;

/** @var \WC_Product_Grouped $product */
/** @var array $settings */

$children = $product->get_children();
if ( empty( $children ) ) {
	return;
}

$button_text = ! empty( $settings['button_text'] )
	? $settings['button_text']
	: Settings_Store::get( 'add_to_cart.button_text', __( 'Add to Cart', 'zymarg-product-builder' ) );
$show_quantity = ! empty( $settings['show_quantity'] ) && 'yes' === $settings['show_quantity'];
?>
<form class="zpb-atc zpb-atc--grouped"
	method="post"
	enctype="multipart/form-data"
	action="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">

	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />

	<table class="zpb-atc-grouped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Product', 'zymarg-product-builder' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Price', 'zymarg-product-builder' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Stock', 'zymarg-product-builder' ); ?></th>
				<?php if ( $show_quantity ) : ?>
					<th scope="col"><?php esc_html_e( 'Qty', 'zymarg-product-builder' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $children as $child_id ) :
				$child = wc_get_product( $child_id );
				if ( ! $child || ! $child->is_purchasable() ) {
					continue;
				}
				$is_in_stock = $child->is_in_stock();
				$max_qty     = $child->get_max_purchase_quantity();
				$max_attr    = is_numeric( $max_qty ) && $max_qty > 0 ? (int) $max_qty : '';
				?>
				<tr class="zpb-atc-grouped__row<?php echo $is_in_stock ? '' : ' is-out-of-stock'; ?>">
					<td class="zpb-atc-grouped__name">
						<a href="<?php echo esc_url( get_permalink( $child->get_id() ) ); ?>">
							<?php echo esc_html( $child->get_name() ); ?>
						</a>
					</td>
					<td class="zpb-atc-grouped__price"><?php echo wp_kses_post( $child->get_price_html() ); ?></td>
					<td class="zpb-atc-grouped__stock">
						<?php echo $is_in_stock
							? '<span class="is-in-stock">' . esc_html__( 'In Stock', 'zymarg-product-builder' ) . '</span>'
							: '<span class="is-out-of-stock">' . esc_html__( 'Out of Stock', 'zymarg-product-builder' ) . '</span>'; ?>
					</td>
					<?php if ( $show_quantity ) : ?>
						<td class="zpb-atc-grouped__qty">
							<?php if ( $is_in_stock ) : ?>
								<input type="number"
									name="quantity[<?php echo esc_attr( $child->get_id() ); ?>]"
									class="zpb-atc__qty-input"
									value="0"
									min="0"
									step="1"
									<?php if ( '' !== $max_attr ) : ?>max="<?php echo esc_attr( $max_attr ); ?>"<?php endif; ?>
									aria-label="<?php
										printf(
											/* translators: %s: child product name */
											esc_attr__( 'Quantity of %s', 'zymarg-product-builder' ),
											esc_attr( $child->get_name() )
										);
									?>"
								/>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="zpb-atc__buttons">
		<button type="submit" class="zpb-atc__btn">
			<span class="zpb-atc__btn-text"><?php echo esc_html( $button_text ); ?></span>
		</button>
	</div>
</form>
