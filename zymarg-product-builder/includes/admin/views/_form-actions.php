<?php
/**
 * Save + Reset action row used by every tab form.
 *
 * Closes the outer save form and renders a separate reset form.
 * Tab views must NOT emit their own </form>; this partial does it.
 *
 * Vars: $current_tab.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Settings;

/** @var string $current_tab */
?>
	<p class="submit zpb-form__actions">
		<?php submit_button( __( 'Save Changes', 'zymarg-product-builder' ), 'primary', 'submit', false ); ?>
	</p>
</form>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="zpb-form zpb-form--reset" data-zpb-reset-form>
	<?php wp_nonce_field( Settings::ACTION_RESET . '_' . $current_tab ); ?>
	<input type="hidden" name="action" value="<?php echo esc_attr( Settings::ACTION_RESET ); ?>" />
	<input type="hidden" name="zpb_tab" value="<?php echo esc_attr( $current_tab ); ?>" />
	<p>
		<button type="submit" class="button button-secondary zpb-reset-button">
			<?php esc_html_e( 'Reset This Tab to Defaults', 'zymarg-product-builder' ); ?>
		</button>
	</p>
</form>
