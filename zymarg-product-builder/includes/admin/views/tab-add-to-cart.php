<?php
/**
 * Add to Cart defaults tab.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Settings;

/** @var array  $fields */
/** @var array  $values */
/** @var string $current_tab */
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="zpb-form" data-zpb-form>
	<?php wp_nonce_field( Settings::ACTION_SAVE . '_' . $current_tab ); ?>
	<input type="hidden" name="action" value="<?php echo esc_attr( Settings::ACTION_SAVE ); ?>" />
	<input type="hidden" name="zpb_tab" value="<?php echo esc_attr( $current_tab ); ?>" />

	<p class="description">
		<?php esc_html_e( 'Defaults for new Add to Cart widget instances. Existing widgets keep their saved settings.', 'zymarg-product-builder' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tbody>
			<?php foreach ( $fields as $key => $field ) :
				$value = isset( $values[ $key ] ) ? $values[ $key ] : ( isset( $field['default'] ) ? $field['default'] : '' );
				include ZPB_PLUGIN_DIR . 'includes/admin/views/_field.php';
			endforeach; ?>
		</tbody>
	</table>

	<?php include ZPB_PLUGIN_DIR . 'includes/admin/views/_form-actions.php'; ?>
<?php // _form-actions.php closes the </form> ?>
