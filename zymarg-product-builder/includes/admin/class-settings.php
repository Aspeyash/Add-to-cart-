<?php
/**
 * Settings page registration, rendering, and save handler.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings — defines fields, renders the tabbed page, handles save + reset.
 *
 * The field schema below is the single source of truth used for both
 * rendering inputs and sanitizing posted values.
 */
final class Settings {

	/** Page slug. */
	const PAGE_SLUG = 'zymarg-product-builder';

	/** Save action name. */
	const ACTION_SAVE = 'zpb_save_settings';

	/** Reset action name. */
	const ACTION_RESET = 'zpb_reset_settings';

	/** Capability required. */
	const CAPABILITY = 'manage_woocommerce';

	/** Singleton. @var Settings|null */
	private static $instance = null;

	/** Field schema, lazily built so __() runs after textdomain load. */
	private $schema = null;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Hook everything up. */
	private function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handle_save' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( $this, 'handle_reset' ) );
	}

	/* ------------------------------------------------------------------
	 * Menu / rendering
	 * ------------------------------------------------------------------ */

	/** Register the WooCommerce → Product Builder submenu. */
	public function register_menu() {
		$hook = add_submenu_page(
			'woocommerce',
			__( 'Zymarg Product Builder', 'zymarg-product-builder' ),
			__( 'Product Builder', 'zymarg-product-builder' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'on_page_load' ) );
		}
	}

	/** Per-page hook (enqueue admin assets only on our screen). */
	public function on_page_load() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** Enqueue admin CSS/JS. */
	public function enqueue_assets() {
		wp_enqueue_style(
			'zpb-admin',
			ZPB_ASSETS_URL . 'css/admin.css',
			array(),
			ZPB_VERSION
		);

		wp_enqueue_script(
			'zpb-admin',
			ZPB_ASSETS_URL . 'js/admin.js',
			array(),
			ZPB_VERSION,
			true
		);

		wp_localize_script(
			'zpb-admin',
			'ZPBAdmin',
			array(
				'i18n' => array(
					'unsavedWarning' => __( 'You have unsaved changes. Leave this page?', 'zymarg-product-builder' ),
					'resetConfirm'   => __( 'Reset this tab to default values?', 'zymarg-product-builder' ),
				),
			)
		);
	}

	/** Render the settings page (delegates to view files per tab). */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'zymarg-product-builder' ) );
		}

		$schema      = $this->get_schema();
		$tabs        = array_keys( $schema );
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $current_tab, $tabs, true ) ) {
			$current_tab = 'general';
		}

		$values = Settings_Store::section( $current_tab );

		// Pass to view.
		include ZPB_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
	}

	/* ------------------------------------------------------------------
	 * Save / Reset
	 * ------------------------------------------------------------------ */

	/** Handle Save POST. */
	public function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'zymarg-product-builder' ) );
		}

		$tab = isset( $_POST['zpb_tab'] ) ? sanitize_key( wp_unslash( $_POST['zpb_tab'] ) ) : '';
		check_admin_referer( self::ACTION_SAVE . '_' . $tab );

		$schema = $this->get_schema();
		if ( ! isset( $schema[ $tab ] ) ) {
			$this->redirect_back( $tab, 'error' );
		}

		$raw       = isset( $_POST['zpb'] ) && is_array( $_POST['zpb'] ) ? wp_unslash( $_POST['zpb'] ) : array(); // phpcs:ignore
		$sanitized = $this->sanitize_section( $tab, $raw, $schema[ $tab ]['fields'] );

		Settings_Store::update_section( $tab, $sanitized );

		$this->redirect_back( $tab, 'saved' );
	}

	/** Handle Reset POST (per-tab). */
	public function handle_reset() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'zymarg-product-builder' ) );
		}

		$tab = isset( $_POST['zpb_tab'] ) ? sanitize_key( wp_unslash( $_POST['zpb_tab'] ) ) : '';
		check_admin_referer( self::ACTION_RESET . '_' . $tab );

		$schema = $this->get_schema();
		if ( ! isset( $schema[ $tab ] ) ) {
			$this->redirect_back( $tab, 'error' );
		}

		Settings_Store::reset_section( $tab );

		$this->redirect_back( $tab, 'reset' );
	}

	/** Sanitize one section's POST input against its field schema. */
	private function sanitize_section( $section, $raw, $fields ) {
		$clean = array();
		foreach ( $fields as $key => $field ) {
			$value = isset( $raw[ $key ] ) ? $raw[ $key ] : null;
			$clean[ $key ] = $this->sanitize_value( $field, $value );
		}
		/**
		 * Filter the sanitized section before saving.
		 *
		 * @param array  $clean   Sanitized values.
		 * @param array  $raw     Raw POST input (already wp_unslash'd).
		 * @param string $section Section key.
		 */
		return apply_filters( 'zpb_sanitize_settings_section', $clean, $raw, $section );
	}

	/** Sanitize a single value based on its field type. */
	private function sanitize_value( $field, $value ) {
		switch ( $field['type'] ) {
			case 'toggle':
				return ( 'yes' === $value || '1' === $value || true === $value ) ? 'yes' : 'no';

			case 'select':
				$choices = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
				return in_array( (string) $value, $choices, true ) ? (string) $value : ( isset( $field['default'] ) ? $field['default'] : '' );

			case 'text':
				return sanitize_text_field( (string) $value );

			case 'number':
				$n   = is_numeric( $value ) ? (float) $value : 0;
				$min = isset( $field['min'] ) ? (float) $field['min'] : null;
				$max = isset( $field['max'] ) ? (float) $field['max'] : null;
				if ( null !== $min && $n < $min ) {
					$n = $min;
				}
				if ( null !== $max && $n > $max ) {
					$n = $max;
				}
				return $n;

			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/** Redirect back to the page with a status flag. */
	private function redirect_back( $tab, $status ) {
		$url = add_query_arg(
			array(
				'page'   => self::PAGE_SLUG,
				'tab'    => $tab ? $tab : 'general',
				'status' => $status,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Schema
	 * ------------------------------------------------------------------ */

	/**
	 * Get the field schema, lazily built so translations are loaded.
	 *
	 * @return array
	 */
	public function get_schema() {
		if ( null === $this->schema ) {
			$this->schema = $this->build_schema();
		}
		return $this->schema;
	}

	/**
	 * Field schema. The single source of truth for fields, defaults, and validation.
	 */
	private function build_schema() {
		$defaults = Settings_Store::defaults();

		return array(
			'general' => array(
				'label'  => __( 'General', 'zymarg-product-builder' ),
				'fields' => array(
					'enable_add_to_cart' => array(
						'type'        => 'toggle',
						'label'       => __( 'Enable Add to Cart Widget', 'zymarg-product-builder' ),
						'description' => __( 'Master switch for the Add to Cart Elementor widget.', 'zymarg-product-builder' ),
						'default'     => $defaults['general']['enable_add_to_cart'],
					),
					'product_source'     => array(
						'type'        => 'select',
						'label'       => __( 'Default Product Source', 'zymarg-product-builder' ),
						'description' => __( 'Used as the default for new widget instances.', 'zymarg-product-builder' ),
						'choices'     => array(
							'current' => __( 'Current Product (single product page / loop)', 'zymarg-product-builder' ),
							'manual'  => __( 'Pick Manually', 'zymarg-product-builder' ),
						),
						'default'     => $defaults['general']['product_source'],
					),
					'use_ajax'           => array(
						'type'        => 'toggle',
						'label'       => __( 'AJAX Add to Cart', 'zymarg-product-builder' ),
						'description' => __( 'Add products without reloading the page. Recommended.', 'zymarg-product-builder' ),
						'default'     => $defaults['general']['use_ajax'],
					),
					'spinner_style'      => array(
						'type'        => 'select',
						'label'       => __( 'Loading Indicator', 'zymarg-product-builder' ),
						'choices'     => array(
							'spinner' => __( 'Spinner', 'zymarg-product-builder' ),
							'dots'    => __( 'Dots', 'zymarg-product-builder' ),
							'bar'     => __( 'Progress Bar', 'zymarg-product-builder' ),
						),
						'default'     => $defaults['general']['spinner_style'],
					),
					'success_behavior'   => array(
						'type'        => 'select',
						'label'       => __( 'After Successful Add', 'zymarg-product-builder' ),
						'choices'     => array(
							'restore'   => __( 'Restore Button Text', 'zymarg-product-builder' ),
							'stay'      => __( 'Keep "Added" Label', 'zymarg-product-builder' ),
							'checkmark' => __( 'Show Checkmark', 'zymarg-product-builder' ),
						),
						'default'     => $defaults['general']['success_behavior'],
					),
				),
			),

			'add_to_cart' => array(
				'label'  => __( 'Add to Cart', 'zymarg-product-builder' ),
				'fields' => array(
					'button_text'           => array(
						'type'        => 'text',
						'label'       => __( 'Default Button Text', 'zymarg-product-builder' ),
						'default'     => $defaults['add_to_cart']['button_text'],
					),
					'show_stock'            => array(
						'type'    => 'toggle',
						'label'   => __( 'Show Stock Status', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['show_stock'],
					),
					'show_price'            => array(
						'type'    => 'toggle',
						'label'   => __( 'Show Price', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['show_price'],
					),
					'show_quantity'         => array(
						'type'    => 'toggle',
						'label'   => __( 'Show Quantity Stepper', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['show_quantity'],
					),
					'show_quantity_label'   => array(
						'type'        => 'toggle',
						'label'       => __( 'Show Quantity Label', 'zymarg-product-builder' ),
						'description' => __( 'The text label above the +/- stepper.', 'zymarg-product-builder' ),
						'default'     => $defaults['add_to_cart']['show_quantity_label'],
					),
					'quantity_label'        => array(
						'type'    => 'text',
						'label'   => __( 'Quantity Label Text', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['quantity_label'],
					),
					'show_buy_now'          => array(
						'type'    => 'toggle',
						'label'   => __( 'Show "Buy Now" Button', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['show_buy_now'],
					),
					'buy_now_text'          => array(
						'type'    => 'text',
						'label'   => __( 'Buy Now Button Text', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['buy_now_text'],
					),
					'redirect_after'        => array(
						'type'    => 'select',
						'label'   => __( 'Redirect After Adding', 'zymarg-product-builder' ),
						'choices' => array(
							'none'     => __( 'Stay on Page', 'zymarg-product-builder' ),
							'cart'     => __( 'Go to Cart', 'zymarg-product-builder' ),
							'checkout' => __( 'Go to Checkout', 'zymarg-product-builder' ),
						),
						'default' => $defaults['add_to_cart']['redirect_after'],
					),
					'out_of_stock_behavior' => array(
						'type'    => 'select',
						'label'   => __( 'Out-of-Stock Button Behavior', 'zymarg-product-builder' ),
						'choices' => array(
							'disable' => __( 'Disable Button', 'zymarg-product-builder' ),
							'hide'    => __( 'Hide Button', 'zymarg-product-builder' ),
							'message' => __( 'Show Message Instead', 'zymarg-product-builder' ),
						),
						'default' => $defaults['add_to_cart']['out_of_stock_behavior'],
					),
					'out_of_stock_text'     => array(
						'type'    => 'text',
						'label'   => __( 'Out-of-Stock Text', 'zymarg-product-builder' ),
						'default' => $defaults['add_to_cart']['out_of_stock_text'],
					),
					'use_product_min_max'   => array(
						'type'        => 'toggle',
						'label'       => __( 'Respect Product Min/Max Quantity', 'zymarg-product-builder' ),
						'description' => __( 'Pull min/max quantity from the WooCommerce product settings.', 'zymarg-product-builder' ),
						'default'     => $defaults['add_to_cart']['use_product_min_max'],
					),
				),
			),
		);
	}
}
