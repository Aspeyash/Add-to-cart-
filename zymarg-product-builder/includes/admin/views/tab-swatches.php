<?php
/**
 * Swatches tab — per-attribute display type configuration.
 *
 * Vars:
 *   $current_tab string
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Attribute_Settings;
use Zymarg\ProductBuilder\Admin\Settings;

/** @var string $current_tab */

$attributes  = Attribute_Settings::get_attribute_choices();
$types       = Attribute_Settings::types();
$current_map = Attribute_Settings::all();

// Detect potentially conflicting plugin (Emran Ahmed's swatches).
$conflict = $this->detect_swatch_conflict();
?>

<?php if ( $conflict ) : ?>
	<div class="notice notice-warning inline">
		<p>
			<?php
			printf(
				/* translators: %s: name of conflicting plugin */
				esc_html__( 'Detected another swatches plugin: %s. Disable it before configuring Zymarg swatches to avoid display conflicts.', 'zymarg-product-builder' ),
				'<strong>' . esc_html( $conflict ) . '</strong>'
			);
			?>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="zpb-form" data-zpb-form>
	<?php wp_nonce_field( Settings::ACTION_SAVE . '_' . $current_tab ); ?>
	<input type="hidden" name="action" value="<?php echo esc_attr( Settings::ACTION_SAVE ); ?>" />
	<input type="hidden" name="zpb_tab" value="<?php echo esc_attr( $current_tab ); ?>" />

	<p class="description">
		<?php esc_html_e( 'Choose how each attribute should render. After picking a type, edit individual terms (e.g. each Color value) to set its hex color, image, or custom label.', 'zymarg-product-builder' ); ?>
	</p>

	<?php if ( empty( $attributes ) ) : ?>
		<div class="notice notice-info inline">
			<p>
				<?php
				$attr_url = admin_url( 'edit.php?post_type=product&page=product_attributes' );
				printf(
					/* translators: %s: link to WC attributes admin */
					wp_kses(
						/* translators: %s: link to WooCommerce attributes admin */
						__( 'No product attributes found yet. Create some at <a href="%s">Products &rarr; Attributes</a> first, then return here.', 'zymarg-product-builder' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( $attr_url )
				);
				?>
			</p>
		</div>
	<?php else : ?>
		<table class="widefat striped zpb-swatches-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Attribute', 'zymarg-product-builder' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Display Type', 'zymarg-product-builder' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Configure Terms', 'zymarg-product-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $attributes as $taxonomy => $label ) :
					$current = isset( $current_map[ $taxonomy ] ) ? $current_map[ $taxonomy ] : Attribute_Settings::TYPE_DEFAULT;
					$terms_url = admin_url( 'edit-tags.php?taxonomy=' . rawurlencode( $taxonomy ) . '&post_type=product' );
				?>
					<tr>
						<td>
							<strong><?php echo esc_html( $label ); ?></strong>
							<br />
							<code><?php echo esc_html( $taxonomy ); ?></code>
						</td>
						<td>
							<select name="zpb_types[<?php echo esc_attr( $taxonomy ); ?>]" class="regular-text">
								<?php foreach ( $types as $type_value => $type_label ) : ?>
									<option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $current, $type_value ); ?>>
										<?php echo esc_html( $type_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<a class="button button-secondary" href="<?php echo esc_url( $terms_url ); ?>">
								<?php
								printf(
									/* translators: %s: attribute label */
									esc_html__( 'Edit %s terms', 'zymarg-product-builder' ),
									esc_html( $label )
								);
								?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php include ZPB_PLUGIN_DIR . 'includes/admin/views/_form-actions.php'; ?>
<?php // _form-actions.php closes the </form> ?>
