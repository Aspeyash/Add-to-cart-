/**
 * Zymarg Product Builder — per-product overrides meta box.
 *
 * - When the user ticks the "Reset all overrides" checkbox, dim the rest of
 *   the panel so it's clear the other fields will be discarded on save.
 * - Confirm dialog before allowing the form submission with the reset
 *   checkbox active (avoids accidental data loss).
 */
( function () {
	'use strict';

	var I18N = ( window.ZPBOverrides && window.ZPBOverrides.i18n ) || {};

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var panel = document.getElementById( 'zpb_product_builder_data' );
		if ( ! panel ) return;
		var resetCheckbox = panel.querySelector( '[data-zpb-reset]' );
		if ( ! resetCheckbox ) return;

		function syncDimState() {
			panel.classList.toggle( 'is-reset-pending', resetCheckbox.checked );
		}

		resetCheckbox.addEventListener( 'change', function () {
			if ( resetCheckbox.checked ) {
				var msg = I18N.resetConfirm || 'Reset all overrides for this product?';
				if ( ! window.confirm( msg ) ) {
					resetCheckbox.checked = false;
				}
			}
			syncDimState();
		} );

		// Initial state.
		syncDimState();
	} );
} )();
