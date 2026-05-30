/**
 * Zymarg Product Builder — Variation Swatches widget.
 *
 * Behavior:
 *  - Click a swatch to select / deselect within its attribute group
 *  - All attributes selected → match against page-injected variation JSON
 *    and emit 'variation:selected' on ZPB.product(id) channel
 *  - Any attribute unselected → emit 'variation:cleared'
 *  - Smart-grey unavailable combinations (cross-attribute matrix)
 *  - Keyboard navigation: arrows within a radiogroup, Enter/Space selects
 *
 * Depends on: product-state.js (window.ZPB)
 * No jQuery required.
 */
( function ( window, document ) {
	'use strict';

	if ( ! window.ZPB ) {
		return;
	}
	var ZPB = window.ZPB;

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	/**
	 * One controller per .zpb-swatches root element.
	 * @param {HTMLElement} root
	 */
	function SwatchesController( root ) {
		this.root      = root;
		this.productId = parseInt( root.getAttribute( 'data-product-id' ), 10 );
		this.channel   = ZPB.product( this.productId );
		this.selected  = {}; // attribute_pa_color -> slug
		this.autoFirst = root.getAttribute( 'data-auto-select-first' ) === '1';
		this.urlSync   = root.getAttribute( 'data-url-sync' ) === '1';

		this.attrGroups   = Array.prototype.slice.call( root.querySelectorAll( '[data-zpb-attr]' ) );
		this.resetBtn     = root.querySelector( '[data-zpb-reset]' );
		this.dropdownEls  = Array.prototype.slice.call( root.querySelectorAll( '[data-zpb-dropdown]' ) );
		this.srStatus     = root.querySelector( '[data-zpb-sr-status]' );
	}

	SwatchesController.prototype.init = function () {
		var self = this;

		// Seed hidden-attribute selections from server-resolved values so
		// variation matching has the right values without showing a swatch.
		var hiddenInputs = this.root.querySelectorAll( '[data-zpb-hidden-attr]' );
		Array.prototype.forEach.call( hiddenInputs, function ( input ) {
			var attr  = input.getAttribute( 'data-zpb-hidden-attr' );
			var value = input.value || '';
			if ( attr && value ) {
				self.selected[ attr ] = value;
			}
		} );

		// Click handlers on each swatch.
		this.attrGroups.forEach( function ( group ) {
			var swatches = group.querySelectorAll( '[data-zpb-swatch]' );
			Array.prototype.forEach.call( swatches, function ( el ) {
				el.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					self.onSwatchClick( el );
				} );
				el.addEventListener( 'keydown', function ( e ) {
					self.onSwatchKey( e, el );
				} );
			} );
		} );

		// Native dropdown handlers (fallback type = default).
		this.dropdownEls.forEach( function ( select ) {
			select.addEventListener( 'change', function () {
				var attr  = select.getAttribute( 'data-zpb-dropdown' );
				var value = select.value || '';
				if ( value ) {
					self.selected[ attr ] = value;
				} else {
					delete self.selected[ attr ];
				}
				self.afterChange();
			} );
		} );

		// Reset link.
		if ( this.resetBtn ) {
			this.resetBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				self.reset();
			} );
		}

		// When the cart submission succeeds, optionally clear (configurable later).
		this.channel.on( 'cart:added', function () { /* keep selection */ } );

		// URL param sync: restore selection from URL on load.
		if ( this.urlSync ) {
			this.restoreFromURL();
			window.addEventListener( 'popstate', function () {
				self.restoreFromURL();
				self.afterChange();
			} );
		}

		// Auto-select the first available variation if requested.
		if ( this.autoFirst && Object.keys( this.selected ).length === this.countHiddenAttrs() ) {
			this.tryAutoSelectFirst();
		}

		// Initial pass to grey out impossible options + publish state.
		this.updateActiveStates();
		this.updateSelectedValueLabels();
		this.recomputeAvailability();
		this.publishVariation();
		this.toggleResetVisibility();
		this.syncDropdowns();
	};

	/** Count hidden auto-resolved attributes (so init logic can ignore them). */
	SwatchesController.prototype.countHiddenAttrs = function () {
		return this.root.querySelectorAll( '[data-zpb-hidden-attr]' ).length;
	};

	/** Restore selection from URL query string. */
	SwatchesController.prototype.restoreFromURL = function () {
		var params = new URLSearchParams( window.location.search );
		// Only update VISIBLE attribute selections from URL — don't clobber hidden auto-resolved values.
		this.attrGroups.forEach( function ( group ) {
			var attr  = group.getAttribute( 'data-zpb-attr' );
			var value = params.get( attr );
			if ( null !== value && '' !== value ) {
				this.selected[ attr ] = value;
			} else {
				delete this.selected[ attr ];
			}
		}, this );
	};

	/** Push current visible selection into URL via replaceState (no scroll, no reload). */
	SwatchesController.prototype.pushToURL = function () {
		if ( ! this.urlSync || typeof history === 'undefined' || ! history.replaceState ) return;
		try {
			var url    = new URL( window.location.href );
			var params = url.searchParams;
			var sel    = this.selected;
			this.attrGroups.forEach( function ( group ) {
				var attr = group.getAttribute( 'data-zpb-attr' );
				if ( sel[ attr ] ) {
					params.set( attr, sel[ attr ] );
				} else {
					params.delete( attr );
				}
			} );
			history.replaceState( history.state, '', url.toString() );
		} catch ( e ) { /* SecurityError on file:// etc. — ignore */ }
	};

	/**
	 * Handle a swatch click — toggle selection within its attribute group.
	 */
	SwatchesController.prototype.onSwatchClick = function ( el ) {
		if ( el.classList.contains( 'is-disabled' ) || el.getAttribute( 'aria-disabled' ) === 'true' ) {
			return;
		}
		var group = el.closest( '[data-zpb-attr]' );
		if ( ! group ) return;

		var attr  = group.getAttribute( 'data-zpb-attr' );
		var value = el.getAttribute( 'data-value' );

		// Toggle off if same value already selected.
		if ( this.selected[ attr ] === value ) {
			delete this.selected[ attr ];
		} else {
			this.selected[ attr ] = value;
		}

		this.afterChange();
	};

	/**
	 * Arrow key navigation within a radiogroup; Enter/Space activates.
	 */
	SwatchesController.prototype.onSwatchKey = function ( e, el ) {
		var key = e.key;
		if ( key === 'Enter' || key === ' ' || key === 'Spacebar' ) {
			e.preventDefault();
			this.onSwatchClick( el );
			return;
		}

		var group = el.closest( '[data-zpb-attr]' );
		if ( ! group ) return;
		var swatches = Array.prototype.slice.call( group.querySelectorAll( '[data-zpb-swatch]' ) );
		var idx = swatches.indexOf( el );
		if ( idx < 0 ) return;

		var next = -1;
		if ( key === 'ArrowRight' || key === 'ArrowDown' ) {
			next = ( idx + 1 ) % swatches.length;
		} else if ( key === 'ArrowLeft' || key === 'ArrowUp' ) {
			next = ( idx - 1 + swatches.length ) % swatches.length;
		} else if ( key === 'Home' ) {
			next = 0;
		} else if ( key === 'End' ) {
			next = swatches.length - 1;
		}
		if ( next >= 0 ) {
			e.preventDefault();
			swatches[ next ].focus();
		}
	};

	/**
	 * Run availability + active states + selected-value labels + emit.
	 */
	SwatchesController.prototype.afterChange = function () {
		this.updateActiveStates();
		this.updateSelectedValueLabels();
		this.recomputeAvailability();
		this.publishVariation();
		this.toggleResetVisibility();
		this.syncDropdowns();
		this.pushToURL();
	};

	/** Push a screen-reader announcement (rate-limited via a small debounce). */
	SwatchesController.prototype.announce = function ( msg ) {
		if ( ! this.srStatus || ! msg ) return;
		// Toggle text so repeat announcements aren't suppressed by the AT.
		this.srStatus.textContent = '';
		var node = this.srStatus;
		setTimeout( function () { node.textContent = msg; }, 30 );
	};

	/**
	 * Mark the active swatch in each group, clear others.
	 */
	SwatchesController.prototype.updateActiveStates = function () {
		var sel = this.selected;
		this.attrGroups.forEach( function ( group ) {
			var attr = group.getAttribute( 'data-zpb-attr' );
			var swatches = group.querySelectorAll( '[data-zpb-swatch]' );
			Array.prototype.forEach.call( swatches, function ( el ) {
				var isActive = sel[ attr ] === el.getAttribute( 'data-value' );
				el.classList.toggle( 'is-active', isActive );
				el.setAttribute( 'aria-checked', isActive ? 'true' : 'false' );
				el.setAttribute( 'tabindex', isActive ? '0' : '-1' );
			} );
			// If nothing selected in this group, make the first focusable.
			if ( ! sel[ attr ] && swatches.length ) {
				swatches[ 0 ].setAttribute( 'tabindex', '0' );
			}
		} );
	};

	/**
	 * Update the "Color: Red" selected-value text next to each label.
	 */
	SwatchesController.prototype.updateSelectedValueLabels = function () {
		var sel = this.selected;
		this.attrGroups.forEach( function ( group ) {
			var attr = group.getAttribute( 'data-zpb-attr' );
			var target = group.querySelector( '[data-zpb-selected-value]' );
			if ( ! target ) return;
			if ( sel[ attr ] ) {
				var swatch = group.querySelector( '[data-value="' + cssEscape( sel[ attr ] ) + '"]' );
				target.textContent = swatch ? ( swatch.getAttribute( 'data-label' ) || sel[ attr ] ) : sel[ attr ];
			} else {
				target.textContent = '';
			}
		} );
	};

	/**
	 * Smart-grey: for every value of every attribute, if the resulting
	 * combination has no matching variation, mark it disabled.
	 *
	 * Algorithm runs O(values × attributes × variations) — fine for typical sizes.
	 */
	SwatchesController.prototype.recomputeAvailability = function () {
		var data = this.channel.getData();
		if ( ! data || ! data.variations ) return;

		var sel = this.selected;
		var self = this;

		this.attrGroups.forEach( function ( group ) {
			var attr     = group.getAttribute( 'data-zpb-attr' );
			var swatches = group.querySelectorAll( '[data-zpb-swatch]' );
			var others   = Object.assign( {}, sel );
			delete others[ attr ]; // don't constrain by current attr's selection

			Array.prototype.forEach.call( swatches, function ( el ) {
				var value = el.getAttribute( 'data-value' );
				var candidate = Object.assign( {}, others );
				candidate[ attr ] = value;

				var match = self.findVariation( candidate );

				var hasMatch  = !! match;
				var isInStock = match && match.isInStock;

				el.classList.toggle( 'is-disabled', ! hasMatch );
				el.classList.toggle( 'is-out-of-stock', hasMatch && ! isInStock );
				el.setAttribute( 'aria-disabled', hasMatch ? 'false' : 'true' );

				// Update price-per-swatch if rendered.
				var priceEl = el.querySelector( '[data-zpb-swatch-price]' );
				if ( priceEl && match && match.priceHtml ) {
					priceEl.innerHTML = match.priceHtml;
				}
			} );
		} );
	};

	/**
	 * Find the first variation matching all given attributes.
	 * Mirrors WC: empty string in variation = "any" = matches any value.
	 */
	SwatchesController.prototype.findVariation = function ( attrs ) {
		var data = this.channel.getData();
		if ( ! data || ! data.variations ) return null;
		var variations = data.variations;
		for ( var i = 0; i < variations.length; i++ ) {
			var v = variations[ i ];
			var ok = true;
			for ( var key in attrs ) {
				if ( ! Object.prototype.hasOwnProperty.call( attrs, key ) ) continue;
				var have = v.attributes ? v.attributes[ key ] : '';
				if ( have && have !== attrs[ key ] ) { ok = false; break; }
			}
			if ( ok ) return v;
		}
		return null;
	};

	/**
	 * Either emit 'variation:selected' (full match) or 'variation:cleared' (incomplete).
	 *
	 * Note: only VISIBLE attribute groups count toward "fully selected".
	 * Hidden attributes (set via data-zpb-hidden-attr) are pre-resolved
	 * server-side and stay in `this.selected` permanently so variation
	 * matching works without their swatches being on the page.
	 */
	SwatchesController.prototype.publishVariation = function () {
		var sel  = this.selected;
		var self = this;
		var visibleCount   = this.attrGroups.length;
		var visibleFilled  = 0;
		this.attrGroups.forEach( function ( group ) {
			var attr = group.getAttribute( 'data-zpb-attr' );
			if ( sel[ attr ] ) visibleFilled++;
		} );

		// Always update state with the current attributes for AddToCart's submit.
		this.channel.setState( { selectedAttributes: sel } );

		if ( visibleFilled < visibleCount ) {
			this.channel.setState( { variationId: 0 } );
			this.channel.emit( 'variation:cleared', {} );
			return;
		}

		var match = this.findVariation( sel );
		if ( ! match ) {
			this.channel.setState( { variationId: 0 } );
			this.channel.emit( 'variation:cleared', {} );
			return;
		}

		this.channel.setState( {
			variationId:        match.id,
			selectedAttributes: sel,
			isInStock:          !! match.isInStock,
			priceHtml:          match.priceHtml || '',
		} );

		this.channel.emit( 'variation:selected', {
			variationId: match.id,
			attributes:  sel,
			priceHtml:   match.priceHtml || '',
			isInStock:   !! match.isInStock,
			minQty:      typeof match.minQty === 'number' ? match.minQty : 1,
			maxQty:      typeof match.maxQty === 'number' ? match.maxQty : -1,
			image:       match.image || '',
			sku:         match.sku || '',
		} );

		// SR announcement: "[label values…]. [In stock|Out of stock]."
		var labelParts = [];
		this.attrGroups.forEach( function ( group ) {
			var attr   = group.getAttribute( 'data-zpb-attr' );
			var swatch = group.querySelector( '[data-value="' + cssEscape( sel[ attr ] || '' ) + '"]' );
			if ( swatch ) labelParts.push( swatch.getAttribute( 'data-label' ) || sel[ attr ] );
		} );
		var stockMsg = match.isInStock ? 'in stock' : 'out of stock';
		this.announce( labelParts.join( ', ' ) + '. ' + stockMsg + '.' );
	};

	/** Show/hide the reset link based on whether any VISIBLE attribute is selected. */
	SwatchesController.prototype.toggleResetVisibility = function () {
		if ( ! this.resetBtn ) return;
		var sel = this.selected;
		var hasAny = false;
		for ( var i = 0; i < this.attrGroups.length; i++ ) {
			var attr = this.attrGroups[ i ].getAttribute( 'data-zpb-attr' );
			if ( sel[ attr ] ) { hasAny = true; break; }
		}
		if ( hasAny ) {
			this.resetBtn.removeAttribute( 'hidden' );
		} else {
			this.resetBtn.setAttribute( 'hidden', '' );
		}
	};

	/** Reset visible selections (hidden auto-resolved attrs are preserved). */
	SwatchesController.prototype.reset = function () {
		var self = this;
		this.attrGroups.forEach( function ( group ) {
			var attr = group.getAttribute( 'data-zpb-attr' );
			delete self.selected[ attr ];
		} );
		this.afterChange();
	};

	/** Mirror selected attributes into native dropdown elements (when present). */
	SwatchesController.prototype.syncDropdowns = function () {
		var sel = this.selected;
		this.dropdownEls.forEach( function ( select ) {
			var attr = select.getAttribute( 'data-zpb-dropdown' );
			select.value = sel[ attr ] || '';
		} );
	};

	/**
	 * Pick the first available variation's attributes and apply them.
	 */
	SwatchesController.prototype.tryAutoSelectFirst = function () {
		var data = this.channel.getData();
		if ( ! data || ! data.variations || ! data.variations.length ) return;

		// Find first in-stock & purchasable variation.
		var pick = null;
		for ( var i = 0; i < data.variations.length; i++ ) {
			var v = data.variations[ i ];
			if ( v.isInStock && v.isPurchasable ) { pick = v; break; }
		}
		if ( ! pick ) pick = data.variations[ 0 ];
		if ( ! pick || ! pick.attributes ) return;

		// Only set values that exist as swatches in our DOM (skip "any" attrs).
		for ( var key in pick.attributes ) {
			if ( ! Object.prototype.hasOwnProperty.call( pick.attributes, key ) ) continue;
			var val = pick.attributes[ key ];
			if ( ! val ) continue; // 'any' attribute
			this.selected[ key ] = val;
		}
	};

	/**
	 * Tiny CSS escape (avoid full polyfill; we only need it for slugs).
	 */
	function cssEscape( s ) {
		return String( s ).replace( /([^\w-])/g, '\\$1' );
	}

	/* Initialization */

	function initAll() {
		document.querySelectorAll( '.zpb-swatches[data-product-id]' ).forEach( function ( root ) {
			if ( root.__zpbSwatchesInit ) return;
			root.__zpbSwatchesInit = true;
			var ctrl = new SwatchesController( root );
			ctrl.init();
		} );
	}

	ready( initAll );

	// Re-init in Elementor editor.
	if ( window.elementorFrontend ) {
		window.elementorFrontend.hooks &&
			window.elementorFrontend.hooks.addAction &&
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/zpb-swatches.default', function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ].querySelector( '.zpb-swatches' ) : null;
				if ( el && ! el.__zpbSwatchesInit ) {
					el.__zpbSwatchesInit = true;
					var ctrl = new SwatchesController( el );
					ctrl.init();
				}
			} );
	}
} )( window, document );
