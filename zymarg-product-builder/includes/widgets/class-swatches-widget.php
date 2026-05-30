<?php
/**
 * Variation Swatches Elementor widget.
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
use Zymarg\ProductBuilder\Admin\Attribute_Settings;
use Zymarg\ProductBuilder\Admin\Term_Meta;
use Zymarg\ProductBuilder\Assets;
use Zymarg\ProductBuilder\Frontend\Product_Data;
use Zymarg\ProductBuilder\Plugin;
use Zymarg\ProductBuilder\Product_Context;
use Zymarg\ProductBuilder\Product_Overrides;

defined( 'ABSPATH' ) || exit;

/**
 * Variation Swatches widget — renders one block per attribute, syncs with
 * Add to Cart via the page-global ZPB.product(id) state bus.
 *
 * Designed to live in its own Elementor section, so its state has to travel
 * across DOM boundaries (handled by product-state.js).
 */
class Swatches_Widget extends Widget_Base {

	public function get_name() {
		return 'zpb-swatches';
	}

	public function get_title() {
		return __( 'Variation Swatches', 'zymarg-product-builder' );
	}

	public function get_icon() {
		return 'eicon-product-categories';
	}

	public function get_categories() {
		return array( Plugin::WIDGET_CATEGORY );
	}

	public function get_keywords() {
		return array( 'swatches', 'variation', 'color', 'size', 'woocommerce', 'zymarg' );
	}

	public function get_script_depends() {
		return array( Assets::HANDLE_STATE, Assets::HANDLE_SWATCHES_JS );
	}

	public function get_style_depends() {
		return array( Assets::HANDLE_SWATCHES_CSS );
	}

	/* ====================================================================
	 * CONTROLS
	 * ==================================================================== */

	protected function register_controls() {
		$this->controls_content();
		$this->controls_style_layout();
		$this->controls_style_label();
		$this->controls_style_color();
		$this->controls_style_image();
		$this->controls_style_text();
		$this->controls_style_states();
		$this->controls_style_tooltip();
	}

	/* ----- CONTENT ----- */

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

		/* Section: Display */
		$this->start_controls_section(
			'section_display',
			array( 'label' => __( 'Display', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'show_attribute_label',
			array(
				'label'        => __( 'Show Attribute Label', 'zymarg-product-builder' ),
				'description'  => __( 'Hide to remove the "Color:", "Size:" labels.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_selected_value',
			array(
				'label'        => __( 'Show Selected Value', 'zymarg-product-builder' ),
				'description'  => __( 'Append the selected term name next to the attribute label.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_attribute_label' => 'yes' ),
			)
		);

		$this->add_control(
			'show_colon',
			array(
				'label'        => __( 'Append Colon to Label', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_attribute_label' => 'yes' ),
			)
		);

		$this->add_control(
			'show_per_swatch_price',
			array(
				'label'        => __( 'Show Price per Swatch', 'zymarg-product-builder' ),
				'description'  => __( 'Display each variation price beneath its swatch (Amazon-style).', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_reset',
			array(
				'label'        => __( 'Show Reset Selection Link', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'reset_text',
			array(
				'label'     => __( 'Reset Link Text', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Reset selection', 'zymarg-product-builder' ),
				'condition' => array( 'show_reset' => 'yes' ),
			)
		);

		$this->add_control(
			'auto_select_first',
			array(
				'label'        => __( 'Auto-select First Variation', 'zymarg-product-builder' ),
				'description'  => __( 'On page load, pre-select the first available (in stock) variation.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'enable_url_sync',
			array(
				'label'        => __( 'Sync Selection to URL', 'zymarg-product-builder' ),
				'description'  => __( 'Reflect chosen swatches in URL params (e.g. ?attribute_pa_color=red). Restores selection on refresh + back/forward.', 'zymarg-product-builder' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		/* Section: Per-attribute display override */
		$this->start_controls_section(
			'section_overrides',
			array( 'label' => __( 'Per-Attribute Override', 'zymarg-product-builder' ) )
		);

		$this->add_control(
			'overrides_help',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<em>' . esc_html__( 'Override the global display type set under Product Builder → Swatches, only for this widget instance. Choose "Inherit" to use the global setting.', 'zymarg-product-builder' ) . '</em>',
				'content_classes' => 'elementor-descriptor',
			)
		);

		$choices = array_merge(
			array( 'inherit' => __( 'Inherit (Global)', 'zymarg-product-builder' ) ),
			Attribute_Settings::types()
		);

		$attributes = Attribute_Settings::get_attribute_choices();
		if ( empty( $attributes ) ) {
			$this->add_control(
				'overrides_none',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => '<em>' . esc_html__( 'No product attributes have been registered yet.', 'zymarg-product-builder' ) . '</em>',
					'content_classes' => 'elementor-descriptor',
				)
			);
		} else {
			foreach ( $attributes as $taxonomy => $label ) {
				$this->add_control(
					'override_' . $taxonomy,
					array(
						'label'   => sprintf(
							/* translators: %s: attribute label */
							esc_html__( 'Display for "%s"', 'zymarg-product-builder' ),
							$label
						),
						'type'    => Controls_Manager::SELECT,
						'default' => 'inherit',
						'options' => $choices,
					)
				);
			}
		}

		$this->end_controls_section();
	}

	/* ----- STYLE: Layout ----- */

	private function controls_style_layout() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Layout', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap_attrs',
			array(
				'label'      => __( 'Spacing Between Attributes', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 18 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatches' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap_swatches',
			array(
				'label'      => __( 'Spacing Between Swatches', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatches__list' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Labels ----- */

	private function controls_style_label() {
		$this->start_controls_section(
			'section_style_label',
			array(
				'label'     => __( 'Attribute Label', 'zymarg-product-builder' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_attribute_label' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typo',
				'selector' => '{{WRAPPER}} .zpb-swatches__attr-name',
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Label Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-swatches__attr-name' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'value_color',
			array(
				'label'     => __( 'Selected Value Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-swatches__attr-value' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Color swatches ----- */

	private function controls_style_color() {
		$this->start_controls_section(
			'section_style_color',
			array(
				'label' => __( 'Color Swatches', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'color_size',
			array(
				'label'      => __( 'Size', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 44 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--color' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'color_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--color' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'color_border_color',
			array(
				'label'     => __( 'Border Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d0d0d0',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--color' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'color_active_border',
			array(
				'label'     => __( 'Active Border Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array(
					'{{WRAPPER}} .zpb-swatch--color.is-active' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}} inset;',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Image swatches ----- */

	private function controls_style_image() {
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => __( 'Image Swatches', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'image_size',
			array(
				'label'      => __( 'Size', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 30, 'max' => 120 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 56 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'     => __( 'Image Fit', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => __( 'Cover', 'zymarg-product-builder' ),
					'contain' => __( 'Contain', 'zymarg-product-builder' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .zpb-swatch--image img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Text (label/button) swatches ----- */

	private function controls_style_text() {
		$this->start_controls_section(
			'section_style_text',
			array(
				'label' => __( 'Label / Button Swatches', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typo',
				'selector' => '{{WRAPPER}} .zpb-swatch--label, {{WRAPPER}} .zpb-swatch--button',
			)
		);

		$this->add_responsive_control(
			'text_padding',
			array(
				'label'      => __( 'Padding', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => 0, 'right' => 14, 'bottom' => 0, 'left' => 14, 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--label,
					 {{WRAPPER}} .zpb-swatch--button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_height',
			array(
				'label'      => __( 'Height', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 28, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--label,
					 {{WRAPPER}} .zpb-swatch--button' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_radius',
			array(
				'label'      => __( 'Border Radius', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch--label,
					 {{WRAPPER}} .zpb-swatch--button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'text_states' );

		$this->start_controls_tab( 'text_normal', array( 'label' => __( 'Normal', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'text_color_normal',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2c3338',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label, {{WRAPPER}} .zpb-swatch--button' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_bg_normal',
			array(
				'label'     => __( 'Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label, {{WRAPPER}} .zpb-swatch--button' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_border_normal',
			array(
				'label'     => __( 'Border Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d0d0d0',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label, {{WRAPPER}} .zpb-swatch--button' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'text_hover', array( 'label' => __( 'Hover', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'text_color_hover',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label:hover, {{WRAPPER}} .zpb-swatch--button:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_bg_hover',
			array(
				'label'     => __( 'Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label:hover, {{WRAPPER}} .zpb-swatch--button:hover' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_border_hover',
			array(
				'label'     => __( 'Border Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#999',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label:hover, {{WRAPPER}} .zpb-swatch--button:hover' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'text_active', array( 'label' => __( 'Active', 'zymarg-product-builder' ) ) );

		$this->add_control(
			'text_color_active',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label.is-active, {{WRAPPER}} .zpb-swatch--button.is-active' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_bg_active',
			array(
				'label'     => __( 'Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f5faff',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label.is-active, {{WRAPPER}} .zpb-swatch--button.is-active' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_border_active',
			array(
				'label'     => __( 'Border Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2271b1',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch--label.is-active, {{WRAPPER}} .zpb-swatch--button.is-active' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ----- STYLE: Disabled / OOS state ----- */

	private function controls_style_states() {
		$this->start_controls_section(
			'section_style_states',
			array(
				'label' => __( 'Disabled / Out of Stock', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'disabled_opacity',
			array(
				'label'      => __( 'Disabled Opacity', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 10, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 45 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch.is-disabled' => 'opacity: calc({{SIZE}}/100);',
				),
			)
		);

		$this->add_control(
			'oos_opacity',
			array(
				'label'      => __( 'Out-of-Stock Opacity', 'zymarg-product-builder' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 10, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 70 ),
				'selectors'  => array(
					'{{WRAPPER}} .zpb-swatch.is-out-of-stock' => 'opacity: calc({{SIZE}}/100);',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----- STYLE: Tooltip ----- */

	private function controls_style_tooltip() {
		$this->start_controls_section(
			'section_style_tooltip',
			array(
				'label' => __( 'Tooltip', 'zymarg-product-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'tooltip_bg',
			array(
				'label'     => __( 'Background', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2c3338',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch[data-tooltip]:hover::before, {{WRAPPER}} .zpb-swatch[data-tooltip]:focus-visible::before' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'tooltip_color',
			array(
				'label'     => __( 'Text Color', 'zymarg-product-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array( '{{WRAPPER}} .zpb-swatch[data-tooltip]:hover::before, {{WRAPPER}} .zpb-swatch[data-tooltip]:focus-visible::before' => 'color: {{VALUE}};' ),
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
			&& Product_Overrides::is_widget_disabled( $product->get_id(), 'swatches' ) ) {
			if ( $this->is_editor_mode() ) {
				$this->render_placeholder( __( 'Variation Swatches widget is disabled for this product (Product Builder tab).', 'zymarg-product-builder' ) );
			}
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			$this->render_placeholder( __( 'Variation Swatches are only available for variable products.', 'zymarg-product-builder' ) );
			return;
		}

		Product_Data::instance()->queue( $product->get_id() );

		$variation_attributes = $product->get_variation_attributes();
		if ( empty( $variation_attributes ) ) {
			$this->render_placeholder( __( 'This variable product has no published variations yet.', 'zymarg-product-builder' ) );
			return;
		}

		$context = array(
			'product'              => $product,
			'settings'             => $settings,
			'variation_attributes' => $variation_attributes,
			'widget'               => $this,
		);

		$template = ZPB_TEMPLATES_DIR . 'swatches/swatches.php';
		if ( file_exists( $template ) ) {
			extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template;
		}
	}

	/** Whether we are rendering inside the Elementor editor. */
	private function is_editor_mode() {
		return class_exists( '\Elementor\Plugin' )
			&& \Elementor\Plugin::$instance
			&& \Elementor\Plugin::$instance->editor
			&& \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Render an editor / fallback placeholder.
	 *
	 * @param string $message Already-translated text.
	 */
	private function render_placeholder( $message ) {
		?>
		<div class="zpb-swatches zpb-swatches--placeholder">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/* ====================================================================
	 * Helpers
	 * ==================================================================== */

	/**
	 * Resolve display type for a taxonomy: per-product override → per-widget
	 * override → global → default.
	 *
	 * @param string $taxonomy   Taxonomy slug (e.g. 'pa_color').
	 * @param array  $settings   Widget settings.
	 * @param int    $product_id Product ID (0 if unknown).
	 * @return string
	 */
	public static function resolve_display_type( $taxonomy, $settings, $product_id = 0 ) {
		// 1) Per-widget override (Elementor control).
		$override_key = 'override_' . $taxonomy;
		$override     = isset( $settings[ $override_key ] ) ? (string) $settings[ $override_key ] : 'inherit';
		if ( 'inherit' !== $override && '' !== $override && array_key_exists( $override, Attribute_Settings::types() ) ) {
			return $override;
		}
		// 2) Per-product override → 3) global → 4) default — handled inside Product_Overrides.
		if ( $product_id && class_exists( '\Zymarg\ProductBuilder\Product_Overrides' ) ) {
			return Product_Overrides::get_attribute_display( $product_id, $taxonomy );
		}
		return Attribute_Settings::get_type( $taxonomy );
	}

	/**
	 * Get product options for the manual picker (variable products only).
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
				'type'    => array( 'variable' ),
			)
		);
		foreach ( $products as $p ) {
			$options[ $p->get_id() ] = sprintf( '#%d — %s', $p->get_id(), $p->get_name() );
		}
		return $options;
	}
}
