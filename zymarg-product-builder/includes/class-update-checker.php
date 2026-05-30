<?php
/**
 * GitHub-based plugin update checker.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Update_Checker — polls GitHub Releases API and integrates the result into
 * WordPress's standard plugin update mechanism (Dashboard → Updates,
 * Plugins screen "Update now" link, etc.).
 *
 * Strict version-aware: an update is offered ONLY when the latest release
 * tag is strictly newer than the installed plugin version per
 * version_compare(). Pre-releases and drafts are excluded automatically by
 * GitHub's /releases/latest endpoint.
 *
 * Public release format expected:
 *   - Tag named  v0.6.0  or  0.6.0  (we strip a leading "v")
 *   - Optionally an attached ZIP asset matching the plugin slug
 *     (e.g. zymarg-product-builder.zip). When absent we fall back to the
 *     auto-generated zipball, and rename_source() rescues the layout.
 */
final class Update_Checker {

	/** Transient key for the cached release lookup. */
	const CACHE_KEY = 'zpb_github_release_v1';

	/** Default cache lifetime (12 hours). */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/** Short cache lifetime when API errors / rate-limits (1 hour). */
	const CACHE_TTL_ERROR = HOUR_IN_SECONDS;

	/** Force-recheck query var on the plugins screen. */
	const FORCE_CHECK_QUERY = 'zpb_check_updates';

	/** Force-recheck nonce action. */
	const FORCE_CHECK_NONCE = 'zpb_check_updates';

	/** Absolute path to main plugin file. @var string */
	private $plugin_file;

	/** Plugin basename (relative to wp-content/plugins). @var string */
	private $plugin_basename;

	/** Plugin slug (folder name). @var string */
	private $plugin_slug;

	/** GitHub repo owner. @var string */
	private $owner;

	/** GitHub repo name. @var string */
	private $repo;

	/** Singleton. @var Update_Checker|null */
	private static $instance = null;

	/**
	 * Boot the singleton.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @param string $owner       GitHub owner (e.g. "Aspeyash").
	 * @param string $repo        GitHub repo (e.g. "Add-to-cart-").
	 * @return Update_Checker
	 */
	public static function instance( $plugin_file = '', $owner = '', $repo = '' ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $plugin_file, $owner, $repo );
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Private constructor. */
	private function __construct( $plugin_file, $owner, $repo ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->plugin_slug     = dirname( $this->plugin_basename );
		$this->owner           = (string) $owner;
		$this->repo            = (string) $repo;
	}

	/** Wire up filters. */
	private function register() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'provide_plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'rename_source' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'after_upgrade' ), 10, 2 );

		// Plugins screen extras: "Check for Updates" row link + handler.
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ), 30 );
		add_action( 'admin_init', array( $this, 'maybe_force_check' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_check_notice' ) );
	}

	/* =====================================================================
	 * Core update flow
	 * ===================================================================== */

	/**
	 * Filter: pre_set_site_transient_update_plugins
	 *
	 * Inject our plugin into the standard "updates available" transient when
	 * a strictly newer release exists on GitHub.
	 *
	 * @param mixed $transient Transient (object) being saved.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( empty( $release['version'] ) ) {
			return $transient;
		}

		$current = $this->get_installed_version();

		// STRICT version comparison: only offer when remote > installed.
		if ( ! version_compare( $release['version'], $current, '>' ) ) {
			// Make sure we don't leave a stale entry around if remote moves backwards.
			if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
				unset( $transient->response[ $this->plugin_basename ] );
			}
			// Mark "no update available" so the row is clean.
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$transient->no_update[ $this->plugin_basename ] = $this->build_update_object( $release, $current );
			return $transient;
		}

		$update = $this->build_update_object( $release, $current );

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$transient->response[ $this->plugin_basename ] = $update;

		return $transient;
	}

	/**
	 * Filter: plugins_api
	 *
	 * Provide data for the "View details" modal on the Plugins / Updates screens.
	 *
	 * @param mixed  $result Default result (false) or an existing payload.
	 * @param string $action API action.
	 * @param object $args   Args passed by WP.
	 * @return mixed
	 */
	public function provide_plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( empty( $release['version'] ) ) {
			return $result;
		}

		$body = $release['body'] ? $this->markdown_to_basic_html( $release['body'] ) : '';

		return (object) array(
			'name'              => 'Zymarg Product Builder',
			'slug'              => $this->plugin_slug,
			'version'           => $release['version'],
			'author'            => '<a href="https://zymarg.com">Zymarg</a>',
			'homepage'          => $release['html_url'],
			'short_description' => __( 'Connected Elementor widgets for WooCommerce: Gallery, Variation Swatches, Add to Cart.', 'zymarg-product-builder' ),
			'sections'          => array(
				'description' => __( 'Connected Elementor widgets for WooCommerce that stay in sync across separate page sections via a shared client-side state bus.', 'zymarg-product-builder' ),
				'changelog'   => $body ? $body : __( 'See GitHub release notes.', 'zymarg-product-builder' ),
			),
			'download_link'     => $this->preferred_download_url( $release ),
			'requires'          => defined( 'ZPB_MIN_WP' ) ? ZPB_MIN_WP : '6.0',
			'requires_php'      => defined( 'ZPB_MIN_PHP' ) ? ZPB_MIN_PHP : '7.4',
			'tested'            => '6.5',
			'last_updated'      => $release['published_at'],
		);
	}

	/**
	 * Filter: upgrader_source_selection
	 *
	 * After WordPress extracts the downloaded ZIP, the resulting directory
	 * needs to match our plugin slug. GitHub-attached release ZIPs (built by
	 * the workflow) already match. Auto-generated zipballs do NOT — they
	 * produce e.g. "Aspeyash-Add-to-cart--abc123/" containing the repo,
	 * inside which lives "zymarg-product-builder/". We locate the plugin
	 * file and rename the containing directory back to our slug.
	 *
	 * @param string       $source        Path to extracted source.
	 * @param string       $remote_source Path to the parent staging dir.
	 * @param object       $upgrader      WP_Upgrader instance.
	 * @param array        $hook_extra    Extra context.
	 * @return string|\WP_Error
	 */
	public function rename_source( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		// Bail unless this is OUR plugin being upgraded.
		if ( ! is_array( $hook_extra ) ) {
			return $source;
		}
		$plugin_being_upgraded = isset( $hook_extra['plugin'] ) ? $hook_extra['plugin'] : '';
		if ( $plugin_being_upgraded !== $this->plugin_basename ) {
			return $source;
		}
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$plugin_filename = basename( $this->plugin_file );
		$found = $this->find_plugin_dir( $source, $plugin_filename, $wp_filesystem );
		if ( ! $found ) {
			return $source; // Couldn't locate plugin file; let WP try its own logic.
		}

		$expected = trailingslashit( $remote_source ) . $this->plugin_slug;

		// Already correctly named? Done.
		if ( untrailingslashit( $found ) === untrailingslashit( $expected ) ) {
			return trailingslashit( $expected );
		}

		// Move into the expected location.
		if ( $wp_filesystem->exists( $expected ) ) {
			$wp_filesystem->delete( $expected, true );
		}
		if ( $wp_filesystem->move( $found, $expected, true ) ) {
			return trailingslashit( $expected );
		}

		return $source;
	}

	/**
	 * Locate the directory containing the main plugin file inside an
	 * extracted source. Searches up to 2 levels deep — handles both:
	 *   - asset ZIPs:   <source>/zymarg-product-builder.php
	 *   - asset ZIPs:   <source>/zymarg-product-builder/zymarg-product-builder.php
	 *   - zipball:      <source>/Aspeyash-Add-to-cart--abc123/zymarg-product-builder/zymarg-product-builder.php
	 *
	 * @param string $source       Extracted source path.
	 * @param string $plugin_file  Plugin filename (e.g. zymarg-product-builder.php).
	 * @param object $fs           $wp_filesystem.
	 * @return string|false        Directory path or false.
	 */
	private function find_plugin_dir( $source, $plugin_file, $fs ) {
		$source = trailingslashit( $source );

		if ( $fs->exists( $source . $plugin_file ) ) {
			return untrailingslashit( $source );
		}

		$level1 = $fs->dirlist( $source );
		if ( ! is_array( $level1 ) ) {
			return false;
		}

		foreach ( $level1 as $name => $info ) {
			if ( 'd' !== $info['type'] ) {
				continue;
			}
			$level1_path = $source . $name;
			if ( $fs->exists( trailingslashit( $level1_path ) . $plugin_file ) ) {
				return $level1_path;
			}
			// One more level (zipball wraps repo, plugin lives in subdir).
			$level2 = $fs->dirlist( trailingslashit( $level1_path ) );
			if ( ! is_array( $level2 ) ) {
				continue;
			}
			foreach ( $level2 as $sub_name => $sub_info ) {
				if ( 'd' !== $sub_info['type'] ) {
					continue;
				}
				$level2_path = trailingslashit( $level1_path ) . $sub_name;
				if ( $fs->exists( trailingslashit( $level2_path ) . $plugin_file ) ) {
					return $level2_path;
				}
			}
		}

		return false;
	}

	/**
	 * Action: upgrader_process_complete
	 *
	 * Clear our cache after any plugin/upgrade activity so subsequent checks
	 * pick up the freshly installed version immediately.
	 *
	 * @param object $upgrader_object WP_Upgrader instance.
	 * @param array  $options         Upgrade options.
	 */
	public function after_upgrade( $upgrader_object, $options ) {
		if ( ! is_array( $options ) ) {
			return;
		}
		$relevant = (
			( 'update'  === ( $options['action'] ?? '' ) || 'install' === ( $options['action'] ?? '' ) )
			&& 'plugin' === ( $options['type'] ?? '' )
		);
		if ( $relevant ) {
			$this->clear_cache();
		}
	}

	/* =====================================================================
	 * Force recheck UX
	 * ===================================================================== */

	/**
	 * Filter: plugin_action_links_<basename>
	 *
	 * Add a "Check for Updates" link on the plugins row.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return $links;
		}
		$url = wp_nonce_url(
			add_query_arg( self::FORCE_CHECK_QUERY, '1', admin_url( 'plugins.php' ) ),
			self::FORCE_CHECK_NONCE
		);
		$links['check_updates'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Check for Updates', 'zymarg-product-builder' )
		);
		return $links;
	}

	/**
	 * Action: admin_init
	 *
	 * Handle the force-check link click — clears our cache and WP's plugin
	 * update transient so the next page load runs a fresh check.
	 */
	public function maybe_force_check() {
		if ( empty( $_GET[ self::FORCE_CHECK_QUERY ] ) ) {
			return;
		}
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		check_admin_referer( self::FORCE_CHECK_NONCE );

		$this->clear_cache();
		delete_site_transient( 'update_plugins' );

		// Also force WP to re-run the update check on next request.
		wp_clean_plugins_cache( true );

		wp_safe_redirect(
			add_query_arg(
				array( 'zpb_checked' => '1' ),
				admin_url( 'plugins.php' )
			)
		);
		exit;
	}

	/**
	 * Action: admin_notices
	 *
	 * Confirm a force-check completed.
	 */
	public function maybe_show_check_notice() {
		if ( empty( $_GET['zpb_checked'] ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		$release = $this->get_latest_release();
		$current = $this->get_installed_version();

		if ( empty( $release['version'] ) ) {
			$msg = esc_html__( 'Zymarg Product Builder: could not reach GitHub. Try again in a moment.', 'zymarg-product-builder' );
			$cls = 'notice-warning';
		} elseif ( version_compare( $release['version'], $current, '>' ) ) {
			$msg = sprintf(
				/* translators: 1: latest version, 2: installed version */
				esc_html__( 'Zymarg Product Builder: update available — version %1$s is out (you have %2$s). Refresh the Plugins screen to update.', 'zymarg-product-builder' ),
				esc_html( $release['version'] ),
				esc_html( $current )
			);
			$cls = 'notice-success';
		} else {
			$msg = sprintf(
				/* translators: %s: installed version */
				esc_html__( 'Zymarg Product Builder is up to date (version %s).', 'zymarg-product-builder' ),
				esc_html( $current )
			);
			$cls = 'notice-success';
		}

		printf(
			'<div class="notice %s is-dismissible"><p>%s</p></div>',
			esc_attr( $cls ),
			$msg // already escaped
		);
	}

	/* =====================================================================
	 * Building the standard update payload
	 * ===================================================================== */

	/**
	 * Build the update object that WordPress's update_plugins transient expects.
	 *
	 * @param array  $release Release info.
	 * @param string $current Installed version.
	 * @return object
	 */
	private function build_update_object( $release, $current ) {
		$package = $this->preferred_download_url( $release );

		return (object) array(
			'id'           => 'zymarg-product-builder/' . $this->plugin_basename,
			'slug'         => $this->plugin_slug,
			'plugin'       => $this->plugin_basename,
			'new_version'  => $release['version'],
			'url'          => $release['html_url'],
			'package'      => $package,
			'tested'       => '6.5',
			'requires'     => defined( 'ZPB_MIN_WP' ) ? ZPB_MIN_WP : '6.0',
			'requires_php' => defined( 'ZPB_MIN_PHP' ) ? ZPB_MIN_PHP : '7.4',
			'icons'        => array(),
			'banners'      => array(),
			'compatibility' => new \stdClass(),
		);
	}

	/**
	 * Choose the best available download URL.
	 * Prefers an attached ZIP asset whose name matches the plugin slug.
	 * Falls back to any ZIP asset, then to the auto-generated zipball.
	 *
	 * @param array $release Release info.
	 * @return string
	 */
	private function preferred_download_url( $release ) {
		if ( ! empty( $release['asset_url'] ) ) {
			return $release['asset_url'];
		}
		return ! empty( $release['zip_url'] ) ? $release['zip_url'] : '';
	}

	/* =====================================================================
	 * GitHub API
	 * ===================================================================== */

	/**
	 * Get the latest release info, with caching.
	 *
	 * @return array Empty array on failure / no releases.
	 */
	private function get_latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		if ( '' === $this->owner || '' === $this->repo ) {
			return array();
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			rawurlencode( $this->owner ),
			rawurlencode( $this->repo )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'ZymargProductBuilder/' . ( defined( 'ZPB_VERSION' ) ? ZPB_VERSION : '0.0.0' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			set_site_transient( self::CACHE_KEY, array(), self::CACHE_TTL_ERROR );
			return array();
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			set_site_transient( self::CACHE_KEY, array(), self::CACHE_TTL_ERROR );
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_site_transient( self::CACHE_KEY, array(), self::CACHE_TTL_ERROR );
			return array();
		}

		$release = array(
			'tag'          => (string) $data['tag_name'],
			'version'      => $this->parse_version( $data['tag_name'] ),
			'name'         => isset( $data['name'] ) ? (string) $data['name'] : (string) $data['tag_name'],
			'body'         => isset( $data['body'] ) ? (string) $data['body'] : '',
			'published_at' => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
			'html_url'     => isset( $data['html_url'] ) ? (string) $data['html_url'] : '',
			'zip_url'      => isset( $data['zipball_url'] ) ? (string) $data['zipball_url'] : '',
			'asset_url'    => '',
		);

		// Pick the best matching ZIP asset.
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			$best       = '';
			$slug_match = '';
			foreach ( $data['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
				$dl   = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
				if ( ! $dl || ! preg_match( '/\.zip$/i', $name ) ) {
					continue;
				}
				if ( '' === $best ) {
					$best = $dl;
				}
				if ( false !== stripos( $name, $this->plugin_slug ) && '' === $slug_match ) {
					$slug_match = $dl;
				}
			}
			$release['asset_url'] = $slug_match ? $slug_match : $best;
		}

		set_site_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
		return $release;
	}

	/**
	 * Strip a leading "v" / "V" / "release-" prefix from a tag and return a
	 * version string suitable for version_compare(). Returns '' if the tag
	 * isn't a recognizable version.
	 *
	 * @param string $tag Tag name.
	 * @return string
	 */
	private function parse_version( $tag ) {
		$tag = trim( (string) $tag );
		$tag = preg_replace( '/^(release[-_])?v?/i', '', $tag );
		// Must look like x.y.z (with optional pre-release suffix).
		if ( ! preg_match( '/^\d+\.\d+(\.\d+)?([\-+].*)?$/', $tag ) ) {
			return '';
		}
		return $tag;
	}

	/** Manually expire the cached release. */
	public function clear_cache() {
		delete_site_transient( self::CACHE_KEY );
	}

	/**
	 * Read the currently-installed version from the plugin file header.
	 *
	 * @return string
	 */
	private function get_installed_version() {
		if ( defined( 'ZPB_VERSION' ) ) {
			return ZPB_VERSION;
		}
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$data = get_plugin_data( $this->plugin_file, false, false );
		return isset( $data['Version'] ) ? (string) $data['Version'] : '0.0.0';
	}

	/**
	 * Light Markdown-to-HTML conversion for the View Details modal.
	 * Handles headings, bullet lists, **bold**, and paragraphs. We don't
	 * pull in a full Markdown library — release notes only need the basics.
	 *
	 * @param string $md Markdown text.
	 * @return string
	 */
	private function markdown_to_basic_html( $md ) {
		$md = (string) $md;
		// Normalize newlines.
		$md = str_replace( "\r\n", "\n", $md );

		$lines  = explode( "\n", $md );
		$html   = '';
		$in_ul  = false;

		foreach ( $lines as $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
				continue;
			}

			if ( preg_match( '/^(#{1,4})\s+(.*)$/', $line, $m ) ) {
				if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
				$level = max( 2, strlen( $m[1] ) + 1 ); // h2..h5
				$html .= '<h' . $level . '>' . esc_html( $m[2] ) . '</h' . $level . '>';
				continue;
			}

			if ( preg_match( '/^[\-\*]\s+(.*)$/', $line, $m ) ) {
				if ( ! $in_ul ) { $html .= '<ul>'; $in_ul = true; }
				$html .= '<li>' . $this->md_inline( $m[1] ) . '</li>';
				continue;
			}

			if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
			$html .= '<p>' . $this->md_inline( $line ) . '</p>';
		}
		if ( $in_ul ) { $html .= '</ul>'; }

		return $html;
	}

	/**
	 * Inline markdown — bold + escaped text. No HTML allowed through.
	 *
	 * @param string $text Inline text.
	 * @return string
	 */
	private function md_inline( $text ) {
		$text = esc_html( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
		return $text;
	}
}
