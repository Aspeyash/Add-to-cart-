<?php
/**
 * Layer 2 writer: render term form fields and save term meta.
 *
 * @package Zymarg_Product_Builder
 */

namespace Zymarg\ProductBuilder\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Term_Fields — hooks into each WC attribute taxonomy's add/edit term forms.
 *
 * Responsibilities:
 *  - Render the swatch fieldset (color / image / label / tooltip)
 *  - Save term meta on create + update
 *  - Enqueue color picker + media library on those screens
 */
final class Term_Fields {

	/** Singleton. @var Term_Fields|null */
	private static $instance = null;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
		return self::$instance;
	}

	/** Hook into every WC attribute taxonomy. */
	private function register() {
		add_action( 'admin_init', array( $this, 'attach_taxonomy_hooks' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Attach add/edit/save hooks for each WC attribute taxonomy.
	 *
	 * Called on admin_init so wc_get_attribute_taxonomies() is reliably available.
	 */
	public function attach_taxonomy_hooks() {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$slug = wc_attribute_taxonomy_name( $tax->attribute_name );
			if ( ! $slug ) {
				continue;
			}
			add_action( $slug . '_add_form_fields',  array( $this, 'render_add_fields' ), 10, 1 );
			add_action( $slug . '_edit_form_fields', array( $this, 'render_edit_fields' ), 10, 2 );
			add_action( 'created_' . $slug,          array( $this, 'save_term_meta' ),    10, 2 );
			add_action( 'edited_' . $slug,           array( $this, 'save_term_meta' ),    10, 2 );
		}
	}

	/**
	 * Enqueue color picker + media library only on attribute term screens.
	 */
	public function enqueue_assets( $hook ) {
		// Term screens only.
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $taxonomy || 0 !== strpos( $taxonomy, 'pa_' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_script(
			'zpb-term-fields',
			ZPB_ASSETS_URL . 'js/term-fields.js',
			array( 'jquery', 'wp-color-picker' ),
			ZPB_VERSION,
			true
		);

		wp_localize_script(
			'zpb-term-fields',
			'ZPBTermFields',
			array(
				'i18n' => array(
					'chooseImage' => __( 'Choose Image', 'zymarg-product-builder' ),
					'useImage'    => __( 'Use this image', 'zymarg-product-builder' ),
					'remove'      => __( 'Remove', 'zymarg-product-builder' ),
				),
			)
		);

		wp_enqueue_style(
			'zpb-term-fields',
			ZPB_ASSETS_URL . 'css/term-fields.css',
			array(),
			ZPB_VERSION
		);
	}

	/**
	 * Render fields on the "Add new term" form.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function render_add_fields( $taxonomy ) {
		$type = Attribute_Settings::get_type( $taxonomy );
		?>
		<div class="form-field zpb-term-fields zpb-term-fields--add">
			<?php $this->render_inner_fields( 0, $type, true ); ?>
		</div>
		<?php
	}

	/**
	 * Render fields on the "Edit term" form (table layout).
	 *
	 * @param \WP_Term $term     Term object.
	 * @param string   $taxonomy Taxonomy slug.
	 */
	public function render_edit_fields( $term, $taxonomy ) {
		$type = Attribute_Settings::get_type( $taxonomy );
		?>
		<tr class="form-field zpb-term-fields-row">
			<th colspan="2" style="padding:0;">
				<div class="zpb-term-fields zpb-term-fields--edit">
					<?php $this->render_inner_fields( $term->term_id, $type, false ); ?>
				</div>
			</th>
		</tr>
		<?php
	}

	/**
	 * Render the inner fieldset (used by both add and edit views).
	 *
	 * @param int    $term_id Term ID (0 on add).
	 * @param string $type    Display type.
	 * @param bool   $is_add  Add (true) vs edit (false).
	 */
	private function render_inner_fields( $term_id, $type, $is_add ) {
		$swatch = $term_id ? Term_Meta::get_swatch( $term_id ) : array(
			'color'     => '',
			'color_2'   => '',
			'image_id'  => 0,
			'image_url' => '',
			'label'     => '',
			'tooltip'   => '',
		);

		$type_label = Attribute_Settings::types();
		$type_label = isset( $type_label[ $type ] ) ? $type_label[ $type ] : $type;
		?>
		<h3 class="zpb-term-fields__title">
			<?php esc_html_e( 'Swatch Settings', 'zymarg-product-builder' ); ?>
			<span class="zpb-term-fields__type">
				<?php
				printf(
					/* translators: %s: configured display type */
					esc_html__( 'this attribute renders as: %s', 'zymarg-product-builder' ),
					'<strong>' . esc_html( $type_label ) . '</strong>'
				);
				?>
			</span>
		</h3>

		<?php wp_nonce_field( 'zpb_save_term_meta', 'zpb_term_nonce' ); ?>

		<?php if ( Attribute_Settings::TYPE_DEFAULT === $type ) : ?>
			<p class="description zpb-term-fields__notice">
				<?php
				$settings_url = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG . '&tab=swatches' );
				printf(
					/* translators: %s: link to settings page */
					wp_kses(
						/* translators: %s: link to settings page */
						__( 'No swatch type configured for this attribute. Set one in <a href="%s">Product Builder &rarr; Swatches</a> to enable visual swatches.', 'zymarg-product-builder' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( $settings_url )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( 'color' === $type ) : ?>
			<div class="zpb-term-field">
				<label for="zpb_color"><?php esc_html_e( 'Primary Color', 'zymarg-product-builder' ); ?></label>
				<input
					type="text"
					id="zpb_color"
					name="zpb_color"
					value="<?php echo esc_attr( $swatch['color'] ); ?>"
					class="zpb-color-picker"
					data-default-color="#cccccc"
				/>
				<p class="description"><?php esc_html_e( 'Main color shown on the swatch.', 'zymarg-product-builder' ); ?></p>
			</div>

			<div class="zpb-term-field">
				<label for="zpb_color_2"><?php esc_html_e( 'Secondary Color', 'zymarg-product-builder' ); ?></label>
				<input
					type="text"
					id="zpb_color_2"
					name="zpb_color_2"
					value="<?php echo esc_attr( $swatch['color_2'] ); ?>"
					class="zpb-color-picker"
				/>
				<p class="description"><?php esc_html_e( 'Optional second color for split / two-tone swatches.', 'zymarg-product-builder' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( 'image' === $type ) : ?>
			<div class="zpb-term-field zpb-image-field" data-zpb-image-field>
				<label><?php esc_html_e( 'Swatch Image', 'zymarg-product-builder' ); ?></label>
				<div class="zpb-image-field__preview">
					<?php if ( $swatch['image_url'] ) : ?>
						<img src="<?php echo esc_url( $swatch['image_url'] ); ?>" alt="" />
					<?php endif; ?>
				</div>
				<input
					type="hidden"
					name="zpb_image_id"
					value="<?php echo esc_attr( (int) $swatch['image_id'] ); ?>"
					data-zpb-image-id
				/>
				<p>
					<button type="button" class="button" data-zpb-image-upload>
						<?php echo $swatch['image_id']
							? esc_html__( 'Replace Image', 'zymarg-product-builder' )
							: esc_html__( 'Choose Image', 'zymarg-product-builder' ); ?>
					</button>
					<button
						type="button"
						class="button button-link-delete"
						data-zpb-image-remove
						<?php disabled( ! $swatch['image_id'] ); ?>
					>
						<?php esc_html_e( 'Remove', 'zymarg-product-builder' ); ?>
					</button>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( Attribute_Settings::is_swatch_type( $type ) ) : ?>
			<div class="zpb-term-field">
				<label for="zpb_label"><?php esc_html_e( 'Custom Label', 'zymarg-product-builder' ); ?></label>
				<input
					type="text"
					id="zpb_label"
					name="zpb_label"
					value="<?php echo esc_attr( $swatch['label'] ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'Override the term name on the swatch (leave empty to use the term name).', 'zymarg-product-builder' ); ?></p>
			</div>

			<div class="zpb-term-field">
				<label for="zpb_tooltip"><?php esc_html_e( 'Tooltip Text', 'zymarg-product-builder' ); ?></label>
				<input
					type="text"
					id="zpb_tooltip"
					name="zpb_tooltip"
					value="<?php echo esc_attr( $swatch['tooltip'] ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'Shown on hover. Defaults to the label if empty.', 'zymarg-product-builder' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save term meta on create or update.
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID (unused).
	 */
	public function save_term_meta( $term_id, $tt_id = 0 ) {
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( empty( $_POST['zpb_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['zpb_term_nonce'] ) ), 'zpb_save_term_meta' ) ) { // phpcs:ignore
			return;
		}

		// Color
		if ( isset( $_POST['zpb_color'] ) ) {
			$value = Term_Meta::normalize_color( wp_unslash( $_POST['zpb_color'] ) );
			$this->update_or_delete( $term_id, Term_Meta::META_COLOR, $value );
		}
		if ( isset( $_POST['zpb_color_2'] ) ) {
			$value = Term_Meta::normalize_color( wp_unslash( $_POST['zpb_color_2'] ) );
			$this->update_or_delete( $term_id, Term_Meta::META_COLOR_2, $value );
		}

		// Image
		if ( isset( $_POST['zpb_image_id'] ) ) {
			$image_id = absint( wp_unslash( $_POST['zpb_image_id'] ) );
			$this->update_or_delete( $term_id, Term_Meta::META_IMAGE_ID, $image_id );
		}

		// Label & tooltip
		if ( isset( $_POST['zpb_label'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['zpb_label'] ) );
			$this->update_or_delete( $term_id, Term_Meta::META_LABEL, $value );
		}
		if ( isset( $_POST['zpb_tooltip'] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST['zpb_tooltip'] ) );
			$this->update_or_delete( $term_id, Term_Meta::META_TOOLTIP, $value );
		}
	}

	/**
	 * Update term meta or delete it if the value is empty/zero.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Sanitized value.
	 */
	private function update_or_delete( $term_id, $key, $value ) {
		if ( '' === $value || 0 === $value || '0' === $value ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $value );
		}
	}
}
