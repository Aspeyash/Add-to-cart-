<?php
/**
 * Settings page shell — header, status messages, tab nav, form, footer.
 *
 * Vars from caller:
 *   $schema       array  Full schema map.
 *   $tabs         array  List of tab keys.
 *   $current_tab  string Active tab key.
 *   $values       array  Stored values for the active section.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

use Zymarg\ProductBuilder\Admin\Settings;

/** @var array  $schema */
/** @var array  $tabs */
/** @var string $current_tab */
/** @var array  $values */

$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap zpb-settings">
	<h1 class="zpb-settings__title">
		<?php esc_html_e( 'Zymarg Product Builder', 'zymarg-product-builder' ); ?>
		<span class="zpb-settings__version">v<?php echo esc_html( ZPB_VERSION ); ?></span>
	</h1>

	<?php if ( 'saved' === $status ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'zymarg-product-builder' ); ?></p></div>
	<?php elseif ( 'reset' === $status ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Tab reset to default values.', 'zymarg-product-builder' ); ?></p></div>
	<?php elseif ( 'error' === $status ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Could not save settings.', 'zymarg-product-builder' ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper zpb-settings__tabs">
		<?php foreach ( $schema as $key => $tab ) :
			$url    = add_query_arg(
				array(
					'page' => Settings::PAGE_SLUG,
					'tab'  => $key,
				),
				admin_url( 'admin.php' )
			);
			$active = ( $key === $current_tab ) ? ' nav-tab-active' : '';
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>">
				<?php echo esc_html( $tab['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="zpb-settings__body">
		<?php
		$tab_view = ZPB_PLUGIN_DIR . 'includes/admin/views/tab-' . str_replace( '_', '-', $current_tab ) . '.php';
		if ( file_exists( $tab_view ) ) {
			$fields = $schema[ $current_tab ]['fields'];
			include $tab_view;
		} else {
			echo '<p>' . esc_html__( 'Unknown tab.', 'zymarg-product-builder' ) . '</p>';
		}
		?>
	</div>
</div>
