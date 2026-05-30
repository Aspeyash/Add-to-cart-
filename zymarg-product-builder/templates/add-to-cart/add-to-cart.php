<?php
/**
 * Add to Cart widget template.
 *
 * Override this in your theme by copying to:
 *   yourtheme/zymarg-product-builder/add-to-cart/add-to-cart.php
 *
 * Available vars:
 *   $widget   \Zymarg\ProductBuilder\Widgets\Add_To_Cart_Widget
 *   $settings array Elementor settings_for_display
 *   $product  \WC_Product
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/** @var \WC_Product $product */
/** @var array $settings */

$product_id   = $product->get_id();
$is_variable  = $product->is_type( 'variable' );
$is_in_stock  = $product->is_in_stock();
$min_qty      = 1;
$max_qty      = $product->get_max_purchase_quantity();
$max_qty_attr = is_numeric( $max_qty ) && $max_qty > 0 ? (int) $max_qty : '';

$show_stock         = ! empty( $settings['show_stock'] ) && 'yes' === $settings['show_stock'];
$show_price         = ! empty( $settings['show_price'] ) && 'yes' === $settings['show_price'];
$show_quantity      = ! empty( $settings['show_quantity'] ) && 'yes' === $settings['show_quantity'];
$show_qty_label     = ! empty( $settings['show_quantity_label'] ) && 'yes' === $settings['show_quantity_label'];
$show_buy_now       = ! empty( $settings['show_buy_now'] ) && 'yes' === $settings['show_buy_now'];
$use_ajax           = ! empty( $settings['use_ajax'] ) && 'yes' === $settings['use_ajax'];
$button_text        = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Add to Cart', 'zymarg-product-builder' );
$buy_now_text       = ! empty( $settings['buy_now_text'] ) ? $settings['buy_now_text'] : __( 'Buy Now', 'zymarg-product-builder' );
$qty_label_text     = ! empty( $settings['quantity_label'] ) ? $settings['quantity_label'] : __( 'Quantity:', 'zymarg-product-builder' );
$icon_position      = ! empty( $settings['icon_position'] ) ? $settings['icon_position'] : 'before';
$redirect_after     = ! empty( $settings['redirect_after'] ) ? $settings['redirect_after'] : 'none';
$redirect_url_value = '';
if ( 'custom' === $redirect_after && ! empty( $settings['redirect_url']['url'] ) ) {
	$redirect_url_value = esc_url( $settings['redirect_url']['url'] );
}

// Stock label.
$stock_text  = $is_in_stock ? __( 'In Stock', 'zymarg-product-builder' ) : __( 'Out of Stock', 'zymarg-product-builder' );
$stock_class = $is_in_stock ? 'is-in-stock' : 'is-out-of-stock';
?>
<div class="zpb-atc"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
	data-use-ajax="<?php echo $use_ajax ? '1' : '0'; ?>"
	data-redirect="<?php echo esc_attr( $redirect_after ); ?>"
	data-redirect-url="<?php echo esc_attr( $redirect_url_value ); ?>">

	<?php if ( $show_stock ) : ?>
		<div class="zpb-atc__stock <?php echo esc_attr( $stock_class ); ?>" data-zpb-stock>
			<?php echo esc_html( $stock_text ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_price ) : ?>
		<div class="zpb-atc__price" data-zpb-price>
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_quantity ) : ?>
		<div class="zpb-atc__qty-wrap">
			<?php if ( $show_qty_label ) : ?>
				<label class="zpb-atc__qty-label" for="zpb-qty-<?php echo esc_attr( $product_id ); ?>">
					<?php echo esc_html( $qty_label_text ); ?>
				</label>
			<?php endif; ?>
			<div class="zpb-atc__qty" data-zpb-qty>
				<button type="button" class="zpb-atc__qty-btn zpb-atc__qty-btn--minus" data-action="decrement" aria-label="<?php esc_attr_e( 'Decrease quantity', 'zymarg-product-builder' ); ?>">&minus;</button>
				<input
					id="zpb-qty-<?php echo esc_attr( $product_id ); ?>"
					class="zpb-atc__qty-input"
					type="number"
					inputmode="numeric"
					value="<?php echo esc_attr( $min_qty ); ?>"
					min="<?php echo esc_attr( $min_qty ); ?>"
					<?php if ( '' !== $max_qty_attr ) : ?>max="<?php echo esc_attr( $max_qty_attr ); ?>"<?php endif; ?>
					step="1"
					data-zpb-qty-input
				/>
				<button type="button" class="zpb-atc__qty-btn zpb-atc__qty-btn--plus" data-action="increment" aria-label="<?php esc_attr_e( 'Increase quantity', 'zymarg-product-builder' ); ?>">+</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="zpb-atc__buttons">
		<button
			type="button"
			class="zpb-atc__btn"
			data-zpb-add-to-cart
			<?php disabled( ! $is_in_stock ); ?>
		>
			<?php if ( ! empty( $settings['button_icon']['value'] ) && 'before' === $icon_position ) : ?>
				<span class="zpb-icon zpb-icon--before"><?php \Elementor\Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
			<?php endif; ?>
			<span class="zpb-atc__btn-text"><?php echo esc_html( $is_in_stock ? $button_text : __( 'Out of Stock', 'zymarg-product-builder' ) ); ?></span>
			<?php if ( ! empty( $settings['button_icon']['value'] ) && 'after' === $icon_position ) : ?>
				<span class="zpb-icon zpb-icon--after"><?php \Elementor\Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
			<?php endif; ?>
			<span class="zpb-spinner" aria-hidden="true"></span>
		</button>

		<?php if ( $show_buy_now ) : ?>
			<button
				type="button"
				class="zpb-atc__buy-now"
				data-zpb-buy-now
				<?php disabled( ! $is_in_stock ); ?>
			>
				<?php echo esc_html( $buy_now_text ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div class="zpb-atc__message" role="status" aria-live="polite" data-zpb-message></div>
</div>
