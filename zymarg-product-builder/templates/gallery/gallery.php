<?php
/**
 * Product Gallery widget template.
 *
 * Override in your theme by copying to:
 *   yourtheme/zymarg-product-builder/gallery/gallery.php
 *
 * Available vars:
 *   $product   \WC_Product
 *   $settings  array      Elementor settings
 *   $images    array      List of [ id, thumb, main, full, alt ]
 *   $badges    array      Map of badge_key => label (sale, oos, featured)
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Widgets\Gallery_Widget;

/** @var \WC_Product $product */
/** @var array $settings */
/** @var array $images */
/** @var array $badges */

$layout         = ! empty( $settings['layout'] ) ? $settings['layout'] : 'vertical-left';
$show_arrows    = ! empty( $settings['show_nav_arrows'] ) && 'yes' === $settings['show_nav_arrows'];
$enable_zoom    = ! empty( $settings['enable_zoom'] ) && 'yes' === $settings['enable_zoom'];
$enable_lb      = ! empty( $settings['enable_lightbox'] ) && 'yes' === $settings['enable_lightbox'];
$swap_variation = ! empty( $settings['enable_variation_swap'] ) && 'yes' === $settings['enable_variation_swap'];
$zoom_level     = ! empty( $settings['zoom_level']['size'] ) ? (float) $settings['zoom_level']['size'] : 2;
$badges_pos     = ! empty( $settings['badges_position'] ) ? $settings['badges_position'] : 'top-left';
$aspect_class   = Gallery_Widget::aspect_class( ! empty( $settings['main_aspect'] ) ? $settings['main_aspect'] : 'square' );

$first = $images[0];
?>
<div class="zpb-gallery zpb-gallery--<?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $aspect_class ); ?>"
	data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
	data-default-image="<?php echo esc_url( $first['main'] ); ?>"
	data-zoom="<?php echo $enable_zoom ? '1' : '0'; ?>"
	data-zoom-level="<?php echo esc_attr( $zoom_level ); ?>"
	data-lightbox="<?php echo $enable_lb ? '1' : '0'; ?>"
	data-variation-swap="<?php echo $swap_variation ? '1' : '0'; ?>">

	<?php if ( count( $images ) > 1 ) : ?>
		<div class="zpb-gallery__thumbs" role="list">
			<?php foreach ( $images as $i => $image ) :
				$active = 0 === $i ? 'is-active' : '';
				?>
				<button
					type="button"
					class="zpb-gallery__thumb <?php echo esc_attr( $active ); ?>"
					data-zpb-thumb
					data-image="<?php echo esc_url( $image['main'] ); ?>"
					data-full="<?php echo esc_url( $image['full'] ); ?>"
					aria-label="<?php
					printf(
						/* translators: 1: image index, 2: total images */
						esc_attr__( 'View image %1$d of %2$d', 'zymarg-product-builder' ),
						(int) ( $i + 1 ),
						(int) count( $images )
					);
					?>"
					aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				>
					<img src="<?php echo esc_url( $image['thumb'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy" />
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="zpb-gallery__main">
		<?php if ( ! empty( $badges ) ) : ?>
			<div class="zpb-gallery__badges zpb-gallery__badges--<?php echo esc_attr( $badges_pos ); ?>">
				<?php foreach ( $badges as $key => $label ) : ?>
					<span class="zpb-gallery__badge zpb-gallery__badge--<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $label ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $show_arrows && count( $images ) > 1 ) : ?>
			<button type="button" class="zpb-gallery__nav zpb-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Previous image', 'zymarg-product-builder' ); ?>">&lsaquo;</button>
		<?php endif; ?>

		<div class="zpb-gallery__main-frame">
			<img
				class="zpb-gallery__main-img"
				src="<?php echo esc_url( $first['main'] ); ?>"
				data-full="<?php echo esc_url( $first['full'] ); ?>"
				alt="<?php echo esc_attr( $first['alt'] ); ?>"
			/>
		</div>

		<?php if ( $show_arrows && count( $images ) > 1 ) : ?>
			<button type="button" class="zpb-gallery__nav zpb-gallery__nav--next" aria-label="<?php esc_attr_e( 'Next image', 'zymarg-product-builder' ); ?>">&rsaquo;</button>
		<?php endif; ?>
	</div>
</div>
