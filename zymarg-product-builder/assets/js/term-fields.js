/**
 * Zymarg Product Builder — term form JS.
 *
 * - Initializes wp-color-picker on .zpb-color-picker
 * - Wires the WP Media Library to the image-field upload/remove buttons
 *
 * Depends on jQuery (required by wp-color-picker) and wp.media.
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.ZPBTermFields && window.ZPBTermFields.i18n ) || {};

	function initColorPickers( context ) {
		var $context = context ? $( context ) : $( document );
		$context.find( '.zpb-color-picker' ).each( function () {
			var $input = $( this );
			if ( $input.data( 'zpbColorInit' ) ) return;
			$input.data( 'zpbColorInit', true );
			$input.wpColorPicker( {
				change: function () { /* default behavior is fine */ },
				clear:  function () { /* default behavior is fine */ }
			} );
		} );
	}

	function bindImageField( $field ) {
		if ( $field.data( 'zpbImageInit' ) ) return;
		$field.data( 'zpbImageInit', true );

		var $idInput  = $field.find( '[data-zpb-image-id]' );
		var $preview  = $field.find( '.zpb-image-field__preview' );
		var $upload   = $field.find( '[data-zpb-image-upload]' );
		var $remove   = $field.find( '[data-zpb-image-remove]' );
		var frame;

		$upload.on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title:    i18n.chooseImage || 'Choose Image',
				button:   { text: i18n.useImage || 'Use this image' },
				library:  { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				$idInput.val( attachment.id );

				var url = '';
				if ( attachment.sizes && attachment.sizes.thumbnail ) {
					url = attachment.sizes.thumbnail.url;
				} else if ( attachment.sizes && attachment.sizes.medium ) {
					url = attachment.sizes.medium.url;
				} else {
					url = attachment.url;
				}

				$preview.html( $( '<img />' ).attr( { src: url, alt: '' } ) );
				$remove.prop( 'disabled', false );
				$upload.text( i18n.replace || 'Replace Image' );
			} );

			frame.open();
		} );

		$remove.on( 'click', function ( e ) {
			e.preventDefault();
			$idInput.val( 0 );
			$preview.empty();
			$remove.prop( 'disabled', true );
			$upload.text( i18n.chooseImage || 'Choose Image' );
		} );
	}

	function initImageFields( context ) {
		var $context = context ? $( context ) : $( document );
		$context.find( '[data-zpb-image-field]' ).each( function () {
			bindImageField( $( this ) );
		} );
	}

	$( function () {
		initColorPickers();
		initImageFields();
	} );

	// Re-init after the WP "Add new term" form submits via AJAX.
	$( document ).on( 'ajaxComplete', function ( event, xhr, settings ) {
		var data = settings && settings.data ? String( settings.data ) : '';
		if ( data.indexOf( 'action=add-tag' ) !== -1 ) {
			initColorPickers( '.zpb-term-fields--add' );
			initImageFields( '.zpb-term-fields--add' );
		}
	} );

} )( window.jQuery );
