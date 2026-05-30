<?php
/**
 * Admin bootstrap — menu, plugin row links, activation notice.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin — orchestrates the admin-only side of the plugin.
 */
final class Admin {

	/** Transient key for first-time activation notice. */
	const NOTICE_OPTION = 'zpb_show_welcome_notice';

	/** Singleton. @var Admin|null */
	private static $instance = null;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Hook into WordPress. */
	private function register() {
		// Boot the settings page.
		Settings::instance();

		// Plugins-list row links.
		add_filter( 'plugin_action_links_' . ZPB_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// First-time activation notice.
		add_action( 'admin_notices', array( $this, 'maybe_show_welcome_notice' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_welcome_notice' ) );
	}

	/**
	 * Add Settings + Docs links to the plugins list row.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_url = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG );
		$custom       = array(
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'zymarg-product-builder' )
			),
			'docs'     => sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( 'https://zymarg.com/zymarg-product-builder/docs' ),
				esc_html__( 'Docs', 'zymarg-product-builder' )
			),
		);
		// Put our links first.
		return array_merge( $custom, $links );
	}

	/**
	 * Show a one-time welcome notice pointing to the settings page.
	 */
	public function maybe_show_welcome_notice() {
		if ( ! current_user_can( Settings::CAPABILITY ) ) {
			return;
		}
		if ( ! get_option( self::NOTICE_OPTION ) ) {
			return;
		}

		// Don't show on our own page (we're already there).
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, Settings::PAGE_SLUG ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG );
		$dismiss_url  = wp_nonce_url(
			add_query_arg( 'zpb_dismiss_welcome', '1' ),
			'zpb_dismiss_welcome'
		);
		?>
		<div class="notice notice-info is-dismissible zpb-welcome-notice">
			<p>
				<strong><?php esc_html_e( 'Zymarg Product Builder is ready.', 'zymarg-product-builder' ); ?></strong>
				<?php esc_html_e( 'Configure widget defaults under', 'zymarg-product-builder' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'WooCommerce → Product Builder', 'zymarg-product-builder' ); ?></a>.
				<a href="<?php echo esc_url( $dismiss_url ); ?>" style="float:right; text-decoration:none;">
					<?php esc_html_e( 'Dismiss', 'zymarg-product-builder' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/** Handle the dismiss link. */
	public function maybe_dismiss_welcome_notice() {
		if ( ! isset( $_GET['zpb_dismiss_welcome'] ) ) {
			return;
		}
		if ( ! current_user_can( Settings::CAPABILITY ) ) {
			return;
		}
		check_admin_referer( 'zpb_dismiss_welcome' );
		delete_option( self::NOTICE_OPTION );
		wp_safe_redirect( remove_query_arg( array( 'zpb_dismiss_welcome', '_wpnonce' ) ) );
		exit;
	}
}
