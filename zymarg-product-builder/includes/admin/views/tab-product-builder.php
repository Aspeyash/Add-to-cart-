<?php
/**
 * "Product Builder" tab content (per-product overrides).
 *
 * Vars from caller:
 *   $product             \WC_Product
 *   $product_id          int
 *   $overrides           array  Current stored overrides.
 *   $is_variable         bool
 *   $product_attributes  array  [ taxonomy_slug => attribute_label ]  (variation taxonomies only)
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Attribute_Settings;
use Zymarg\ProductBuilder\Admin\Settings_Store;
use Zymarg\ProductBuilder\Product_Overrides;

/** @var \WC_Product $product */
/** @var int   $product_id */
/** @var array $overrides */
/** @var bool  $is_variable */
/** @var array $product_attributes */

$display_overrides = isset( $overrides['attribute_display'] ) && is_array( $overrides['attribute_display'] )
	? $overrides['attribute_display']
	: array();
$hidden_attrs      = isset( $overrides['hidden_attributes'] ) && is_array( $overrides['hidden_attributes'] )
	? $overrides['hidden_attributes']
	: array();
$atc_overrides     = isset( $overrides['add_to_cart'] ) && is_array( $overrides['add_to_cart'] )
	? $overrides['add_to_cart']
	: array();
$widgets_disabled  = isset( $overrides['widgets_disabled'] ) && is_array( $overrides['widgets_disabled'] )
	? $overrides['widgets_disabled']
	: array();

$type_choices = array_merge(
	array( Product_Overrides::INHERIT => __( 'Inherit (Global)', 'zymarg-product-builder' ) ),
	Attribute_Settings::types()
);
?>
<div id="zpb_product_builder_data" class="panel woocommerce_options_panel hidden">

	<?php wp_nonce_field( 'zpb_save_overrides', 'zpb_overrides_nonce' ); ?>

	<div class="zpb-overrides-intro">
		<p>
			<?php esc_html_e( 'Override plugin-wide defaults for this product only. Settings left as "Inherit" use the global configuration.', 'zymarg-product-builder' ); ?>
		</p>
	</div>

	<?php /* ============================================================
	 *  Attribute Display Overrides (variable products only, taxonomy attrs)
	 * ============================================================ */ ?>
	<div class="options_group">
		<h4 class="zpb-overrides__heading">
			<?php esc_html_e( 'Attribute Display Overrides', 'zymarg-product-builder' ); ?>
		</h4>

		<?php if ( ! $is_variable ) : ?>
			<p class="form-field zpb-overrides__notice">
				<?php esc_html_e( 'Available on variable products only.', 'zymarg-product-builder' ); ?>
			</p>
		<?php elseif ( empty( $product_attributes ) ) : ?>
			<p class="form-field zpb-overrides__notice">
				<?php esc_html_e( 'This product has no taxonomy-based variation attributes yet.', 'zymarg-product-builder' ); ?>
			</p>
		<?php else : ?>
			<?php foreach ( $product_attributes as $taxonomy => $label ) :
				$current = isset( $display_overrides[ $taxonomy ] ) ? $display_overrides[ $taxonomy ] : Product_Overrides::INHERIT;
				$global  = Attribute_Settings::get_type( $taxonomy );
				$global_label = isset( Attribute_Settings::types()[ $global ] ) ? Attribute_Settings::types()[ $global ] : $global;
				?>
				<p class="form-field zpb-overrides__field">
					<label for="zpb_attr_display_<?php echo esc_attr( $taxonomy ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
					<select
						id="zpb_attr_display_<?php echo esc_attr( $taxonomy ); ?>"
						name="zpb_overrides[attribute_display][<?php echo esc_attr( $taxonomy ); ?>]"
					>
						<?php foreach ( $type_choices as $value => $choice_label ) :
							$visible_label = ( Product_Overrides::INHERIT === $value )
								? sprintf(
									/* translators: %s: global type label */
									__( 'Inherit (%s)', 'zymarg-product-builder' ),
									$global_label
								)
								: $choice_label;
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
								<?php echo esc_html( $visible_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php /* ============================================================
	 *  Hide Attributes from Swatches
	 * ============================================================ */ ?>
	<?php if ( $is_variable && ! empty( $product_attributes ) ) : ?>
		<div class="options_group">
			<h4 class="zpb-overrides__heading">
				<?php esc_html_e( 'Hide Attributes from Swatches', 'zymarg-product-builder' ); ?>
			</h4>
			<p class="form-field zpb-overrides__field zpb-overrides__field--checkbox-group">
				<span class="zpb-overrides__legend">
					<?php esc_html_e( 'Hidden attributes are auto-resolved when adding to cart so variations still work.', 'zymarg-product-builder' ); ?>
				</span>
				<?php foreach ( $product_attributes as $taxonomy => $label ) :
					$checked = in_array( $taxonomy, $hidden_attrs, true );
					?>
					<label class="zpb-overrides__checkbox">
						<input type="checkbox"
							name="zpb_overrides[hidden_attributes][]"
							value="<?php echo esc_attr( $taxonomy ); ?>"
							<?php checked( $checked ); ?>
						/>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</p>
		</div>
	<?php endif; ?>

	<?php /* ============================================================
	 *  Add to Cart overrides
	 * ============================================================ */ ?>
	<div class="options_group">
		<h4 class="zpb-overrides__heading">
			<?php esc_html_e( 'Add to Cart', 'zymarg-product-builder' ); ?>
		</h4>

		<?php
		woocommerce_wp_text_input(
			array(
				'id'          => 'zpb_overrides_atc_button_text',
				'name'        => 'zpb_overrides[add_to_cart][button_text]',
				'label'       => __( 'Custom Button Text', 'zymarg-product-builder' ),
				'value'       => isset( $atc_overrides['button_text'] ) ? $atc_overrides['button_text'] : '',
				'placeholder' => Settings_Store::get( 'add_to_cart.button_text', __( 'Add to Cart', 'zymarg-product-builder' ) ),
				'desc_tip'    => true,
				'description' => __( 'Leave empty to use the global default.', 'zymarg-product-builder' ),
			)
		);
		?>

		<p class="form-field zpb-overrides__field">
			<label for="zpb_overrides_atc_buy_now">
				<?php esc_html_e( 'Show "Buy Now" Button', 'zymarg-product-builder' ); ?>
			</label>
			<select id="zpb_overrides_atc_buy_now" name="zpb_overrides[add_to_cart][show_buy_now]">
				<?php
				$current_buy_now = isset( $atc_overrides['show_buy_now'] ) ? $atc_overrides['show_buy_now'] : Product_Overrides::INHERIT;
				$buy_now_choices = array(
					Product_Overrides::INHERIT => __( 'Inherit (Global)', 'zymarg-product-builder' ),
					'yes'                      => __( 'Force ON', 'zymarg-product-builder' ),
					'no'                       => __( 'Force OFF', 'zymarg-product-builder' ),
				);
				foreach ( $buy_now_choices as $val => $lbl ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $val ),
						selected( $current_buy_now, $val, false ),
						esc_html( $lbl )
					);
				}
				?>
			</select>
		</p>

		<p class="form-field zpb-overrides__field">
			<label for="zpb_overrides_atc_oos">
				<?php esc_html_e( 'Out-of-Stock Behavior', 'zymarg-product-builder' ); ?>
			</label>
			<select id="zpb_overrides_atc_oos" name="zpb_overrides[add_to_cart][out_of_stock_behavior]">
				<?php
				$current_oos = isset( $atc_overrides['out_of_stock_behavior'] ) ? $atc_overrides['out_of_stock_behavior'] : Product_Overrides::INHERIT;
				$oos_choices = array(
					Product_Overrides::INHERIT => __( 'Inherit (Global)', 'zymarg-product-builder' ),
					'disable'                  => __( 'Disable Button', 'zymarg-product-builder' ),
					'hide'                     => __( 'Hide Button', 'zymarg-product-builder' ),
					'message'                  => __( 'Show Message Instead', 'zymarg-product-builder' ),
				);
				foreach ( $oos_choices as $val => $lbl ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $val ),
						selected( $current_oos, $val, false ),
						esc_html( $lbl )
					);
				}
				?>
			</select>
		</p>

		<p class="form-field zpb-overrides__field">
			<label for="zpb_overrides_atc_redirect">
				<?php esc_html_e( 'Redirect After Adding', 'zymarg-product-builder' ); ?>
			</label>
			<select id="zpb_overrides_atc_redirect" name="zpb_overrides[add_to_cart][redirect_after]">
				<?php
				$current_redirect = isset( $atc_overrides['redirect_after'] ) ? $atc_overrides['redirect_after'] : Product_Overrides::INHERIT;
				$redirect_choices = array(
					Product_Overrides::INHERIT => __( 'Inherit (Global)', 'zymarg-product-builder' ),
					'none'                     => __( 'Stay on Page', 'zymarg-product-builder' ),
					'cart'                     => __( 'Go to Cart', 'zymarg-product-builder' ),
					'checkout'                 => __( 'Go to Checkout', 'zymarg-product-builder' ),
				);
				foreach ( $redirect_choices as $val => $lbl ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $val ),
						selected( $current_redirect, $val, false ),
						esc_html( $lbl )
					);
				}
				?>
			</select>
		</p>
	</div>

	<?php /* ============================================================
	 *  Widget disable toggles
	 * ============================================================ */ ?>
	<div class="options_group">
		<h4 class="zpb-overrides__heading">
			<?php esc_html_e( 'Disable Widgets for This Product', 'zymarg-product-builder' ); ?>
		</h4>
		<p class="form-field zpb-overrides__field zpb-overrides__field--checkbox-group">
			<span class="zpb-overrides__legend">
				<?php esc_html_e( 'Selected widgets render nothing on the front-end for this product.', 'zymarg-product-builder' ); ?>
			</span>
			<?php
			$widgets = array(
				'add_to_cart' => __( 'Add to Cart', 'zymarg-product-builder' ),
				'swatches'    => __( 'Variation Swatches', 'zymarg-product-builder' ),
				'gallery'     => __( 'Product Gallery', 'zymarg-product-builder' ),
			);
			foreach ( $widgets as $key => $label ) :
				$checked = in_array( $key, $widgets_disabled, true );
				?>
				<label class="zpb-overrides__checkbox">
					<input type="checkbox"
						name="zpb_overrides[widgets_disabled][]"
						value="<?php echo esc_attr( $key ); ?>"
						<?php checked( $checked ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</p>
	</div>

	<?php /* ============================================================
	 *  Reset overrides
	 * ============================================================ */ ?>
	<div class="options_group">
		<p class="form-field zpb-overrides__field zpb-overrides__field--reset">
			<label class="zpb-overrides__checkbox zpb-overrides__reset-label">
				<input type="checkbox"
					id="zpb_reset_overrides"
					name="zpb_reset_overrides"
					value="1"
					data-zpb-reset
				/>
				<strong><?php esc_html_e( 'Reset all overrides for this product on save', 'zymarg-product-builder' ); ?></strong>
			</label>
			<span class="description">
				<?php esc_html_e( 'Check this box and click Update to remove every override above. The product will fall back to global config.', 'zymarg-product-builder' ); ?>
			</span>
		</p>
	</div>
</div>
