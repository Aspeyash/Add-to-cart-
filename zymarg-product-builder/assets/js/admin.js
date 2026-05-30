/**
 * Zymarg Product Builder — admin settings page JS.
 *
 * - Live "On/Off" label next to toggles
 * - Reset confirmation
 * - Unsaved changes guard
 */
( function () {
	'use strict';

	var Admin = window.ZPBAdmin || { i18n: {} };

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function bindToggleLabels() {
		document.querySelectorAll( '.zpb-toggle' ).forEach( function ( wrap ) {
			var cb   = wrap.querySelector( 'input[type="checkbox"]' );
			var text = wrap.querySelector( '.zpb-toggle__text' );
			if ( ! cb || ! text ) return;

			var on  = text.getAttribute( 'data-on' )  || text.textContent.trim();
			var off = text.getAttribute( 'data-off' ) || ( cb.checked ? 'Off' : text.textContent.trim() );

			// Cache the labels once (use the localized strings already rendered).
			if ( ! text.getAttribute( 'data-on' ) ) {
				if ( cb.checked ) {
					text.setAttribute( 'data-on', text.textContent.trim() );
					text.setAttribute( 'data-off', 'Off' );
				} else {
					text.setAttribute( 'data-off', text.textContent.trim() );
					text.setAttribute( 'data-on', 'On' );
				}
			}

			cb.addEventListener( 'change', function () {
				text.textContent = cb.checked
					? text.getAttribute( 'data-on' )
					: text.getAttribute( 'data-off' );
			} );
		} );
	}

	function bindResetConfirm() {
		document.querySelectorAll( '[data-zpb-reset-form]' ).forEach( function ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				var msg = ( Admin.i18n && Admin.i18n.resetConfirm ) || 'Reset this tab to default values?';
				if ( ! window.confirm( msg ) ) {
					e.preventDefault();
				}
			} );
		} );
	}

	function bindUnsavedGuard() {
		var dirty = false;

		document.querySelectorAll( '[data-zpb-form]' ).forEach( function ( form ) {
			form.addEventListener( 'change', function () { dirty = true; } );
			form.addEventListener( 'input',  function () { dirty = true; } );
			form.addEventListener( 'submit', function () { dirty = false; } );
		} );

		// Reset form submit also clears.
		document.querySelectorAll( '[data-zpb-reset-form]' ).forEach( function ( form ) {
			form.addEventListener( 'submit', function () { dirty = false; } );
		} );

		window.addEventListener( 'beforeunload', function ( e ) {
			if ( ! dirty ) return;
			var msg = ( Admin.i18n && Admin.i18n.unsavedWarning ) || 'You have unsaved changes.';
			e.preventDefault();
			e.returnValue = msg;
			return msg;
		} );
	}

	ready( function () {
		bindToggleLabels();
		bindResetConfirm();
		bindUnsavedGuard();
	} );
} )();
