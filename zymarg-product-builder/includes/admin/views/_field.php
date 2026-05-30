<?php
/**
 * Single-field renderer (partial). Included from tab views.
 *
 * Vars:
 *   $key   string Field key.
 *   $field array  Field schema.
 *   $value mixed  Current value.
 *
 * @package Zymarg_Product_Builder
 */

defined( 'ABSPATH' ) || exit;

/** @var string $key */
/** @var array  $field */
/** @var mixed  $value */

$input_name = 'zpb[' . $key . ']';
$input_id   = 'zpb-' . $key;
?>
<tr class="zpb-field zpb-field--<?php echo esc_attr( $field['type'] ); ?>">
	<th scope="row">
		<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
	</th>
	<td>
		<?php
		switch ( $field['type'] ) {
			case 'toggle':
				$checked = ( 'yes' === $value );
				?>
				<label class="zpb-toggle">
					<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="no" />
					<input
						type="checkbox"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="<?php echo esc_attr( $input_name ); ?>"
						value="yes"
						<?php checked( $checked ); ?>
					/>
					<span class="zpb-toggle__slider" aria-hidden="true"></span>
					<span class="zpb-toggle__text">
						<?php echo $checked ? esc_html__( 'On', 'zymarg-product-builder' ) : esc_html__( 'Off', 'zymarg-product-builder' ); ?>
					</span>
				</label>
				<?php
				break;

			case 'select':
				?>
				<select id="<?php echo esc_attr( $input_id ); ?>" name="<?php echo esc_attr( $input_name ); ?>" class="regular-text">
					<?php foreach ( (array) $field['choices'] as $choice_value => $choice_label ) : ?>
						<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( (string) $value, (string) $choice_value ); ?>>
							<?php echo esc_html( $choice_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php
				break;

			case 'text':
				?>
				<input
					type="text"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="<?php echo esc_attr( $input_name ); ?>"
					value="<?php echo esc_attr( (string) $value ); ?>"
					class="regular-text"
				/>
				<?php
				break;

			case 'number':
				?>
				<input
					type="number"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="<?php echo esc_attr( $input_name ); ?>"
					value="<?php echo esc_attr( (string) $value ); ?>"
					<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
					<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
					<?php if ( isset( $field['step'] ) ) : ?>step="<?php echo esc_attr( $field['step'] ); ?>"<?php endif; ?>
					class="small-text"
				/>
				<?php
				break;
		}

		if ( ! empty( $field['description'] ) ) :
			?>
			<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php
		endif;
		?>
	</td>
</tr>
<?php
