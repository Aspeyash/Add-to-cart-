<?php
/**
 * Add to Cart Elementor widget.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Zymarg\ProductBuilder\Admin\Settings_Store;
use Zymarg\ProductBuilder\Assets;
use Zymarg\ProductBuilder\Frontend\Product_Data;
use Zymarg\ProductBuilder\Plugin;
use Zymarg\ProductBuilder\Product_Context;

defined( 'ABSPATH' ) || exit;

/**
 * Quantity stepper + Add to Cart button (and optional Buy Now) widget.
 *
 * Designed to live in its own Elementor section, separate from the
 * Variation Swatches widget. Communicates via the global ZPB state bus.
 */
class Add_To_Cart_Widget extends Widget_Base {

	public function get_name() {
		return 'zpb-add-to-cart';
	}

	public function get_title() {
		return __( 'Add to Cart', 'zymarg-product-builder' );
	}

	public function get_icon() {
		return 'eicon-cart-solid';
	}

	public function get_categories() {
		return array( Plugin::WIDGET_CATEGORY );
	}

	public function get_keywords() {
		return array( 'cart', 'buy', 'woocommerce', 'product', 'quantity', 'zymarg' );
	}

	public function get_script_depends() {
		return array( Assets::HANDLE_STATE, Assets::HANDLE_ADD_TO_CART_JS );
	}

	public function get_style_depends() {
		return array( Assets::HANDLE_ADD_TO_CART_CSS );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_quantity_style_controls();
		$this->register_button_style_controls();
		$this->register_buy_now_style_controls();
		$this->register_message_style_controls();
	}

	/* ====================================================================
	 * CONTENT TAB
	 * ==================================================================== */

	private function register_content_controls() {

		/* --- Product source --- */
		$this->start_controls_section(
			'section_product',
			array( 'label' => __( 'Product', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'product_source',
			array(
				'label'   => __( 'Product Source', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => Settings_Store::get( 'general.product_source', 'current' ),
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

		/* --- Layout / display --- */
		$this->start_controls_section(
			'section_layout',
			array( 'label' => __( 'Layout', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'show_stock',
			array(
				'label'        => __( 'Show Stock Status', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'add_to_cart.show_stock', 'yes' ),
				'label_on'     => __( 'Show', 'zymarg-product-builder' ),
				'label_off'    => __( 'Hide', 'zymarg-product-builder' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => __( 'Show Price', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'add_to_cart.show_price', '' ) === 'yes' ? 'yes' : '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_quantity',
			array(
				'label'        => __( 'Show Quantity Stepper', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'add_to_cart.show_quantity', 'yes' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_quantity_label',
			array(
				'label'        => __( 'Show Quantity Label', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'add_to_cart.show_quantity_label', 'yes' ),
				'return_value' => 'yes',
				'condition'    => array( 'show_quantity' => 'yes' ),
			)
		);

		$this->add_control(
			'quantity_label',
			array(
				'label'     => __( 'Quantity Label Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => Settings_Store::get( 'add_to_cart.quantity_label', __( 'Quantity:', 'zymarg-product-builder' ) ),
				'condition' => array(
					'show_quantity'       => 'yes',
					'show_quantity_label' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'alignment',
			array(
				'label'     => __( 'Alignment', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Left', 'zymarg-product-builder' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => __( 'Center', 'zymarg-product-builder' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Right', 'zymarg-product-builder' ),
						'icon'  => 'eicon-text-align-right',
					),
					'stretch'    => array(
						'title' => __( 'Stretch', 'zymarg-product-builder' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .zpb-atc'             => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .zpb-atc__buttons'    => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Element Spacing', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		/* --- Button --- */
		$this->start_controls_section(
			'section_button',
			array( 'label' => __( 'Button', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Button Text', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::TEXT,
				'default' => Settings_Store::get( 'add_to_cart.button_text', __( 'Add to Cart', 'zymarg-product-builder' ) ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label' => __( 'Icon', 'zymarg-product-builder' ),
				'type'  => Controls_Manager::ICONS,
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'     => __( 'Icon Position', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => array(
					'before' => __( 'Before Text', 'zymarg-product-builder' ),
					'after'  => __( 'After Text', 'zymarg-product-builder' ),
				),
				'condition' => array( 'button_icon[value]!' => '' ),
			)
		);

		$this->add_responsive_control(
			'button_width',
			array(
				'label'      => __( 'Button Width', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 80, 'max' => 800 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array( 'unit' => '%', 'size' => 100 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__btn' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'use_ajax',
			array(
				'label'        => __( 'AJAX Add to Cart', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'general.use_ajax', 'yes' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'redirect_after',
			array(
				'label'   => __( 'After Adding', 'zymarg-product-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => Settings_Store::get( 'add_to_cart.redirect_after', 'none' ),
				'options' => array(
					'none'     => __( 'Stay on Page', 'zymarg-product-builder' ),
					'cart'     => __( 'Go to Cart', 'zymarg-product-builder' ),
					'checkout' => __( 'Go to Checkout', 'zymarg-product-builder' ),
					'custom'   => __( 'Custom URL', 'zymarg-product-builder' ),
				),
			)
		);

		$this->add_control(
			'redirect_url',
			array(
				'label'     => __( 'Custom Redirect URL', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::URL,
				'default'   => array( 'url' => '' ),
				'condition' => array( 'redirect_after' => 'custom' ),
			)
		);

		$this->end_controls_section();

		/* --- Buy Now --- */
		$this->start_controls_section(
			'section_buy_now',
			array( 'label' => __( 'Buy Now Button', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'show_buy_now',
			array(
				'label'        => __( 'Show Buy Now Button', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => Settings_Store::get( 'add_to_cart.show_buy_now', '' ) === 'yes' ? 'yes' : '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'buy_now_text',
			array(
				'label'     => __( 'Button Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => Settings_Store::get( 'add_to_cart.buy_now_text', __( 'Buy Now', 'zymarg-product-builder' ) ),
				'condition' => array( 'show_buy_now' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ====================================================================
	 * STYLE TABS
	 * ==================================================================== */

	private function register_quantity_style_controls() {
		$this->start_controls_section(
			'section_qty_style',
			array(
				'label'     => __( 'Quantity Stepper', 'zymarg-product-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_quantity' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'qty_typography',
				'selector' => '{{WRAPPER}} .zpb-atc__qty-input, {{WRAPPER}} .zpb-atc__qty-btn, {{WRAPPER}} .zpb-atc__qty-label',
			)
		);

		$this->add_control(
			'qty_label_color',
			array(
				'label'     => __( 'Label Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zpb-atc__qty-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'qty_text_color',
			array(
				'label'     => __( 'Input Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zpb-atc__qty-input' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'qty_bg_color',
			array(
				'label'     => __( 'Background Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .zpb-atc__qty-input, {{WRAPPER}} .zpb-atc__qty-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'qty_border',
				'selector' => '{{WRAPPER}} .zpb-atc__qty',
			)
		);

		$this->add_control(
			'qty_border_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'qty_height',
			array(
				'label'      => __( 'Stepper Height', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 28, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 44 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__qty'       => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .zpb-atc__qty-btn'   => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .zpb-atc__qty-input' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_button_style_controls() {
		$this->start_controls_section(
			'section_btn_style',
			array(
				'label' => __( 'Add to Cart Button', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => '{{WRAPPER}} .zpb-atc__btn',
			)
		);

		$this->start_controls_tabs( 'btn_states' );

		/* Normal */
		$this->start_controls_tab( 'btn_normal', array( 'label' => __( 'Normal', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'btn_color',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__btn' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'btn_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zpb-atc__btn',
			)
		);

		$this->end_controls_tab();

		/* Hover */
		$this->start_controls_tab( 'btn_hover', array( 'label' => __( 'Hover', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'btn_color_hover',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-atc__btn:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'btn_bg_hover',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zpb-atc__btn:hover',
			)
		);

		$this->end_controls_tab();

		/* Loading */
		$this->start_controls_tab( 'btn_loading', array( 'label' => __( 'Loading', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'btn_loading_color',
			array(
				'label'     => __( 'Spinner Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__btn .zpb-spinner' => 'border-top-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		/* Success */
		$this->start_controls_tab( 'btn_success', array( 'label' => __( 'Success', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'btn_success_bg',
			array(
				'label'     => __( 'Background Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1f9d55',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__btn.is-success' => 'background-color: {{VALUE}}; background-image: none;' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'btn_border',
				'selector' => '{{WRAPPER}} .zpb-atc__btn',
			)
		);

		$this->add_control(
			'btn_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_shadow',
				'selector' => '{{WRAPPER}} .zpb-atc__btn',
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => __( 'Padding', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => 12,
					'right'    => 24,
					'bottom'   => 12,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_icon_spacing',
			array(
				'label'      => __( 'Icon Spacing', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-atc__btn .zpb-icon' => 'margin-inline-end: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .zpb-atc__btn .zpb-icon--after' => 'margin-inline-end: 0; margin-inline-start: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array( 'button_icon[value]!' => '' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_buy_now_style_controls() {
		$this->start_controls_section(
			'section_buy_now_style',
			array(
				'label'     => __( 'Buy Now Button', 'zymarg-product-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_buy_now' => 'yes' ),
			)
		);

		$this->add_control(
			'buy_now_color',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__buy-now' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'buy_now_bg',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .zpb-atc__buy-now',
			)
		);

		$this->add_control(
			'buy_now_color_hover',
			array(
				'label'     => __( 'Hover Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-atc__buy-now:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function register_message_style_controls() {
		$this->start_controls_section(
			'section_msg_style',
			array(
				'label' => __( 'Stock & Messages', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'stock_in_color',
			array(
				'label'     => __( 'In Stock Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1f9d55',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__stock.is-in-stock' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'stock_out_color',
			array(
				'label'     => __( 'Out of Stock Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#c92a2a',
				'selectors' => array( '{{WRAPPER}} .zpb-atc__stock.is-out-of-stock' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'label'    => __( 'Price Typography', 'zymarg-product-builder' ),
				'selector' => '{{WRAPPER}} .zpb-atc__price',
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => __( 'Price Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-atc__price' => 'color: {{VALUE}};' ),
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

		// In Elementor editor with no product available — show a placeholder.
		if ( ! $product ) {
			$this->render_placeholder();
			return;
		}

		if ( ! Product_Context::is_supported( $product ) ) {
			$this->render_unsupported( $product );
			return;
		}

		// Queue product for footer JSON.
		Product_Data::instance()->queue( $product->get_id() );

		// Pass everything the template needs.
		$context = array(
			'widget'   => $this,
			'settings' => $settings,
			'product'  => $product,
		);

		$template = ZPB_TEMPLATES_DIR . 'add-to-cart/add-to-cart.php';
		if ( file_exists( $template ) ) {
			extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template;
		}
	}

	/**
	 * Render an editor placeholder so the widget shows something useful when
	 * dropped onto a page that has no product context.
	 */
	private function render_placeholder() {
		$button_text = ! empty( $this->get_settings_for_display( 'button_text' ) )
			? $this->get_settings_for_display( 'button_text' )
			: __( 'Add to Cart', 'zymarg-product-builder' );
		?>
		<div class="zpb-atc zpb-atc--placeholder">
			<p class="zpb-atc__notice">
				<?php esc_html_e( 'Select a product or place this widget on a single product page.', 'zymarg-product-builder' ); ?>
			</p>
			<button type="button" class="zpb-atc__btn" disabled>
				<?php echo esc_html( $button_text ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render a notice when the product type is not supported in v1.
	 *
	 * @param \WC_Product $product Product.
	 */
	private function render_unsupported( $product ) {
		?>
		<div class="zpb-atc zpb-atc--unsupported">
			<p class="zpb-atc__notice">
				<?php
				printf(
					/* translators: %s: product type slug */
					esc_html__( 'Product type "%s" is not supported yet (simple and variable only in v1).', 'zymarg-product-builder' ),
					esc_html( $product->get_type() )
				);
				?>
			</p>
		</div>
		<?php
	}

	/* ====================================================================
	 * Helpers
	 * ==================================================================== */

	/**
	 * Get product options for the manual picker.
	 * Limited list — for large catalogs we'd swap to ajax-search later.
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
