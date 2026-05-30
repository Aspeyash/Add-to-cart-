<?php
/**
 * Product Gallery Elementor widget.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;
use Zymarg\ProductBuilder\Assets;
use Zymarg\ProductBuilder\Frontend\Product_Data;
use Zymarg\ProductBuilder\Plugin;
use Zymarg\ProductBuilder\Product_Context;
use Zymarg\ProductBuilder\Product_Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Product Gallery widget — main image + thumbnails with zoom + lightbox,
 * synced with Variation Swatches via the page-global ZPB state bus.
 */
class Gallery_Widget extends Widget_Base {

	public function get_name() {
		return 'zpb-gallery';
	}

	public function get_title() {
		return __( 'Product Gallery', 'zymarg-product-builder' );
	}

	public function get_icon() {
		return 'eicon-product-images';
	}

	public function get_categories() {
		return array( Plugin::WIDGET_CATEGORY );
	}

	public function get_keywords() {
		return array( 'gallery', 'image', 'product', 'thumbnails', 'lightbox', 'woocommerce', 'zymarg' );
	}

	public function get_script_depends() {
		return array( Assets::HANDLE_STATE, Assets::HANDLE_GALLERY_JS );
	}

	public function get_style_depends() {
		return array( Assets::HANDLE_GALLERY_CSS );
	}

	/* ====================================================================
	 * CONTROLS
	 * ==================================================================== */

	protected function register_controls() {
		$this->controls_content();
		$this->controls_style_main();
		$this->controls_style_thumbs();
		$this->controls_style_arrows();
		$this->controls_style_badges();
		$this->controls_style_lightbox();
	}

	private function controls_content() {

		/* Section: Product */
		$this->start_controls_section(
			'section_product',
			array( 'label' => __( 'Product', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'product_source',
			array(
				'label'   => __( 'Product Source', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => __( 'Current Product', 'zymarg-product-builder' ),
					'manual'  => __( 'Pick a Product', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Select Product', 'zymarg-product-builder' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_product_options(),
				'label_block' => true,
				'condition'   => array( 'product_source' => 'manual' ),
			)
		);

		$this->end_controls_section();

		/* Section: Layout */
		$this->start_controls_section(
			'section_layout',
			array( 'label' => __( 'Layout', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'vertical-left',
				'options' => array(
					'vertical-left'  => __( 'Vertical Thumbs (left of main)', 'zymarg-product-builder' ),
					'vertical-right' => __( 'Vertical Thumbs (right of main)', 'zymarg-product-builder' ),
					'horizontal'     => __( 'Horizontal Thumbs (below main)', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_responsive_control(
			'thumb_size',
			array(
				'label'      => __( 'Thumbnail Size', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 40, 'max' => 140 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 64 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-gallery__thumb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .zpb-gallery--vertical-left  .zpb-gallery__thumbs,
					 {{WRAPPER}} .zpb-gallery--vertical-right .zpb-gallery__thumbs' => 'width: calc({{SIZE}}{{UNIT}} + 16px);',
				),
			)
		);

		$this->add_control(
			'main_aspect',
			array(
				'label'   => __( 'Main Image Aspect Ratio', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'square',
				'options' => array(
					'auto'   => __( 'Auto (intrinsic)', 'zymarg-product-builder' ),
					'square' => __( '1 : 1', 'zymarg-product-builder' ),
					'4-3'    => __( '4 : 3', 'zymarg-product-builder' ),
					'3-4'    => __( '3 : 4 (portrait)', 'zymarg-product-builder' ),
					'16-9'   => __( '16 : 9', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'main_image_size',
			array(
				'label'       => __( 'Main Image Resolution', 'zymarg-product-builder' ),
				'description' => __( 'Higher resolutions look sharper on retina screens but use more bandwidth. "Full Resolution" uses the original uploaded file.', 'zymarg-product-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'woocommerce_single',
				'options'     => array(
					'woocommerce_single'    => __( 'WooCommerce Single (default)', 'zymarg-product-builder' ),
					'woocommerce_thumbnail' => __( 'WooCommerce Thumbnail', 'zymarg-product-builder' ),
					'medium'                => __( 'Medium', 'zymarg-product-builder' ),
					'medium_large'          => __( 'Medium Large', 'zymarg-product-builder' ),
					'large'                 => __( 'Large', 'zymarg-product-builder' ),
					'full'                  => __( 'Full Resolution (original)', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'thumb_image_size',
			array(
				'label'   => __( 'Thumbnail Resolution', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'woocommerce_thumbnail',
				'options' => array(
					'woocommerce_gallery_thumbnail' => __( 'WC Gallery Thumbnail', 'zymarg-product-builder' ),
					'woocommerce_thumbnail'        => __( 'WooCommerce Thumbnail (default)', 'zymarg-product-builder' ),
					'thumbnail'                    => __( 'WP Thumbnail', 'zymarg-product-builder' ),
					'medium'                       => __( 'Medium', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'lightbox_image_size',
			array(
				'label'       => __( 'Lightbox / Zoom Image Resolution', 'zymarg-product-builder' ),
				'description' => __( 'Image used when the lightbox opens or zoom is active. "Full Resolution" gives the sharpest zoom.', 'zymarg-product-builder' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'full',
				'options'     => array(
					'large' => __( 'Large', 'zymarg-product-builder' ),
					'full'  => __( 'Full Resolution (original)', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'show_nav_arrows',
			array(
				'label'        => __( 'Show Navigation Arrows', 'zymarg-product-builder' ),
				'description'  => __( 'Prev / Next arrows on the main image, visible on hover.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		/* Section: Behavior */
		$this->start_controls_section(
			'section_behavior',
			array( 'label' => __( 'Behavior', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'enable_zoom',
			array(
				'label'        => __( 'Hover Zoom', 'zymarg-product-builder' ),
				'description'  => __( 'Magnify the main image on hover.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'zoom_level',
			array(
				'label'      => __( 'Zoom Level', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'x' ),
				'range'      => array( 'x' => array( 'min' => 1.2, 'max' => 4, 'step' => 0.1 ) ),
				'default'    => array( 'unit' => 'x', 'size' => 2 ),
				'condition'  => array( 'enable_zoom' => 'yes' ),
			)
		);

		$this->add_control(
			'enable_lightbox',
			array(
				'label'        => __( 'Click to Open Lightbox', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'enable_variation_swap',
			array(
				'label'        => __( 'Swap Main Image on Variation', 'zymarg-product-builder' ),
				'description'  => __( 'When a variation is selected (Swatches widget), swap the main image to the variation image.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'featured_first',
			array(
				'label'        => __( 'Featured Image First', 'zymarg-product-builder' ),
				'description'  => __( 'Always show the featured image as the first thumbnail.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		/* Section: Badges */
		$this->start_controls_section(
			'section_badges',
			array( 'label' => __( 'Badges', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'badges_position',
			array(
				'label'   => __( 'Position', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top-left',
				'options' => array(
					'top-left'     => __( 'Top Left', 'zymarg-product-builder' ),
					'top-right'    => __( 'Top Right', 'zymarg-product-builder' ),
					'bottom-left'  => __( 'Bottom Left', 'zymarg-product-builder' ),
					'bottom-right' => __( 'Bottom Right', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'show_sale_badge',
			array(
				'label'        => __( 'Show Sale Badge', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'sale_text',
			array(
				'label'     => __( 'Sale Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Sale', 'zymarg-product-builder' ),
				'condition' => array( 'show_sale_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'show_oos_badge',
			array(
				'label'        => __( 'Show Out of Stock Badge', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'oos_text',
			array(
				'label'     => __( 'Out of Stock Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Out of Stock', 'zymarg-product-builder' ),
				'condition' => array( 'show_oos_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'show_featured_badge',
			array(
				'label'        => __( 'Show Featured Badge', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'featured_text',
			array(
				'label'     => __( 'Featured Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Featured', 'zymarg-product-builder' ),
				'condition' => array( 'show_featured_badge' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Main image ----- */

	private function controls_style_main() {
		$this->start_controls_section(
			'section_style_main',
			array(
				'label' => __( 'Main Image', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'main_bg',
			array(
				'label'     => __( 'Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__main-frame' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'main_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-gallery__main-frame' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'main_border',
				'selector' => '{{WRAPPER}} .zpb-gallery__main-frame',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'main_shadow',
				'selector' => '{{WRAPPER}} .zpb-gallery__main-frame',
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Thumbnails ----- */

	private function controls_style_thumbs() {
		$this->start_controls_section(
			'section_style_thumbs',
			array(
				'label' => __( 'Thumbnails', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'thumb_gap',
			array(
				'label'      => __( 'Spacing Between Thumbnails', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array( '{{WRAPPER}} .zpb-gallery__thumbs' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'thumb_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-gallery__thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_border_color',
			array(
				'label'     => __( 'Border Color (Normal)', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d0d0d0',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__thumb' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'thumb_active_color',
			array(
				'label'     => __( 'Border Color (Active)', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__thumb.is-active' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'thumb_hover_color',
			array(
				'label'     => __( 'Border Color (Hover)', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#999999',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__thumb:hover:not(.is-active)' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Navigation arrows ----- */

	private function controls_style_arrows() {
		$this->start_controls_section(
			'section_style_arrows',
			array(
				'label'     => __( 'Navigation Arrows', 'zymarg-product-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_nav_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => __( 'Arrow Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__nav' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => __( 'Arrow Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.55)',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__nav' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => __( 'Arrow Size', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 60 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 36 ),
				'selectors'  => array( '{{WRAPPER}} .zpb-gallery__nav' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Badges ----- */

	private function controls_style_badges() {
		$this->start_controls_section(
			'section_style_badges',
			array(
				'label' => __( 'Badge Styles', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'badge_sale_bg',
			array(
				'label'     => __( 'Sale Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d94a3d',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__badge--sale' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_sale_color',
			array(
				'label'     => __( 'Sale Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__badge--sale' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_oos_bg',
			array(
				'label'     => __( 'Out-of-Stock Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c7781',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__badge--oos' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_featured_bg',
			array(
				'label'     => __( 'Featured Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f59f00',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__badge--featured' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_featured_color',
			array(
				'label'     => __( 'Featured Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2c3338',
				'selectors' => array( '{{WRAPPER}} .zpb-gallery__badge--featured' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-gallery__badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Lightbox ----- */

	private function controls_style_lightbox() {
		$this->start_controls_section(
			'section_style_lightbox',
			array(
				'label'     => __( 'Lightbox', 'zymarg-product-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_lightbox' => 'yes' ),
			)
		);

		$this->add_control(
			'lightbox_backdrop',
			array(
				'label'     => __( 'Backdrop Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.88)',
				'selectors' => array( '.zpb-lightbox' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'lightbox_close_color',
			array(
				'label'     => __( 'Close Button Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '.zpb-lightbox__close' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/* ====================================================================
	 * RENDER
	 * ==================================================================== */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$product  = Product_Context::resolve( $settings );

		if ( ! $product ) {
			$this->render_placeholder( __( 'Select a product or place this widget on a single product page.', 'zymarg-product-builder' ) );
			return;
		}

		// Per-product disable check.
		if ( class_exists( '\Zymarg\ProductBuilder\Product_Overrides' )
			&& Product_Overrides::is_widget_disabled( $product->get_id(), 'gallery' ) ) {
			if ( $this->is_editor_mode() ) {
				$this->render_placeholder( __( 'Product Gallery widget is disabled for this product (Product Builder tab).', 'zymarg-product-builder' ) );
			}
			return;
		}

		Product_Data::instance()->queue( $product->get_id() );

		// Build the image list (featured + gallery) using the resolutions
		// chosen in the widget controls (default: WC sensible sizes).
		$image_sizes = array(
			'thumb'    => ! empty( $settings['thumb_image_size'] )    ? $settings['thumb_image_size']    : 'woocommerce_thumbnail',
			'main'     => ! empty( $settings['main_image_size'] )     ? $settings['main_image_size']     : 'woocommerce_single',
			'lightbox' => ! empty( $settings['lightbox_image_size'] ) ? $settings['lightbox_image_size'] : 'full',
		);
		$images = $this->collect_images(
			$product,
			! empty( $settings['featured_first'] ) && 'yes' === $settings['featured_first'],
			$image_sizes
		);

		if ( empty( $images ) ) {
			$this->render_placeholder( __( 'This product has no images yet.', 'zymarg-product-builder' ) );
			return;
		}

		$context = array(
			'product'  => $product,
			'settings' => $settings,
			'images'   => $images,
			'badges'   => $this->collect_badges( $product, $settings ),
			'widget'   => $this,
		);

		$template = ZPB_TEMPLATES_DIR . 'gallery/gallery.php';
		if ( file_exists( $template ) ) {
			extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template;
		}
	}

	/**
	 * Render an editor / fallback placeholder.
	 *
	 * @param string $message Already-translated text.
	 */
	private function render_placeholder( $message ) {
		?>
		<div class="zpb-gallery zpb-gallery--placeholder">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/** Whether we are rendering inside the Elementor editor. */
	private function is_editor_mode() {
		return class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Gather images for the gallery: featured + gallery image IDs from
	 * `_product_image_gallery` post meta.
	 *
	 * @param \WC_Product $product        Product.
	 * @param bool        $featured_first Place featured image first.
	 * @param array       $sizes          Map with 'thumb', 'main', 'lightbox'
	 *                                    image-size slugs. Falls back to
	 *                                    sensible WC defaults if missing.
	 * @return array Each item: [ id, thumb, main, full, alt ]
	 */
	private function collect_images( $product, $featured_first, $sizes = array() ) {
		$thumb_size    = isset( $sizes['thumb'] )    ? $sizes['thumb']    : 'woocommerce_thumbnail';
		$main_size     = isset( $sizes['main'] )     ? $sizes['main']     : 'woocommerce_single';
		$lightbox_size = isset( $sizes['lightbox'] ) ? $sizes['lightbox'] : 'full';

		$ids = array();

		$featured_id = (int) $product->get_image_id();
		$gallery_ids = array_map( 'absint', (array) $product->get_gallery_image_ids() );

		if ( $featured_first && $featured_id ) {
			$ids[] = $featured_id;
		}
		foreach ( $gallery_ids as $gid ) {
			if ( $gid && ! in_array( $gid, $ids, true ) ) {
				$ids[] = $gid;
			}
		}
		if ( ! $featured_first && $featured_id && ! in_array( $featured_id, $ids, true ) ) {
			$ids[] = $featured_id;
		}

		$images = array();
		foreach ( $ids as $id ) {
			$thumb = wp_get_attachment_image_url( $id, $thumb_size );
			$main  = wp_get_attachment_image_url( $id, $main_size );
			$full  = wp_get_attachment_image_url( $id, $lightbox_size );
			// Fallbacks if a chosen size doesn't exist (e.g. site never regenerated thumbnails).
			if ( ! $thumb ) { $thumb = wp_get_attachment_image_url( $id, 'thumbnail' ); }
			if ( ! $main )  { $main  = wp_get_attachment_image_url( $id, 'full' ); }
			if ( ! $full )  { $full  = $main; }
			if ( ! $thumb || ! $main ) {
				continue;
			}
			$images[] = array(
				'id'    => $id,
				'thumb' => $thumb,
				'main'  => $main,
				'full'  => $full,
				'alt'   => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * Collect active badges based on product state + widget settings.
	 *
	 * @param \WC_Product $product  Product.
	 * @param array       $settings Widget settings.
	 * @return array  list of [ key => label ]
	 */
	private function collect_badges( $product, $settings ) {
		$badges = array();

		if ( ! empty( $settings['show_sale_badge'] ) && 'yes' === $settings['show_sale_badge'] && $product->is_on_sale() ) {
			$badges['sale'] = ! empty( $settings['sale_text'] ) ? $settings['sale_text'] : __( 'Sale', 'zymarg-product-builder' );
		}
		if ( ! empty( $settings['show_oos_badge'] ) && 'yes' === $settings['show_oos_badge'] && ! $product->is_in_stock() ) {
			$badges['oos'] = ! empty( $settings['oos_text'] ) ? $settings['oos_text'] : __( 'Out of Stock', 'zymarg-product-builder' );
		}
		if ( ! empty( $settings['show_featured_badge'] ) && 'yes' === $settings['show_featured_badge'] && $product->is_featured() ) {
			$badges['featured'] = ! empty( $settings['featured_text'] ) ? $settings['featured_text'] : __( 'Featured', 'zymarg-product-builder' );
		}
		return $badges;
	}

	/**
	 * CSS aspect-ratio class helper.
	 *
	 * @param string $value Setting value.
	 * @return string CSS class suffix.
	 */
	public static function aspect_class( $value ) {
		$map = array(
			'auto'   => '',
			'square' => 'is-aspect-1-1',
			'4-3'    => 'is-aspect-4-3',
			'3-4'    => 'is-aspect-3-4',
			'16-9'   => 'is-aspect-16-9',
		);
		return isset( $map[ $value ] ) ? $map[ $value ] : '';
	}

	/**
	 * Get product options for the manual picker.
	 *
	 * @return array
	 */
	private function get_product_options() {
		$options = array();
		if ( ! function_exists( 'wc_get_products' ) ) {
			return $options;
		}
		$products = wc_get_products(
			array(
				'limit'   => 50,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'DESC',
				'type'    => array( 'simple', 'variable' ),
			)
		);
		foreach ( $products as $p ) {
			$options[ $p->get_id() ] = sprintf( '#%d — %s', $p->get_id(), $p->get_name() );
		}
		return $options;
	}
}
