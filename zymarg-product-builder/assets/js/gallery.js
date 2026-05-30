/**
 * Zymarg Product Builder — Product Gallery widget.
 *
 * Behavior:
 *  - Click thumbnail → swap main image
 *  - Prev/Next arrows cycle through gallery images
 *  - Hover main image → CSS-transform zoom following cursor
 *  - Click main image → custom lightbox (no external lib)
 *  - Listens for variation:selected → swaps main image to variation image
 *  - Listens for variation:cleared → reverts to featured image
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

	/* ====================================================================
	 * Lightbox — single shared instance
	 * ==================================================================== */

	var Lightbox = {
		overlay: null,
		images:  [],
		index:   0,
		previousFocus: null,
		onKeydown: null,

		open: function ( images, startIndex ) {
			if ( ! images || ! images.length ) return;
			this.images = images.slice();
			this.index  = Math.max( 0, Math.min( startIndex || 0, images.length - 1 ) );

			this.previousFocus = document.activeElement;

			this.overlay = document.createElement( 'div' );
			this.overlay.className = 'zpb-lightbox';
			this.overlay.setAttribute( 'role', 'dialog' );
			this.overlay.setAttribute( 'aria-modal', 'true' );
			this.overlay.innerHTML =
				'<button class="zpb-lightbox__close" aria-label="Close" type="button">&times;</button>' +
				'<button class="zpb-lightbox__nav zpb-lightbox__nav--prev" aria-label="Previous image" type="button">&lsaquo;</button>' +
				'<div class="zpb-lightbox__frame">' +
					'<img class="zpb-lightbox__img" src="" alt="" />' +
				'</div>' +
				'<button class="zpb-lightbox__nav zpb-lightbox__nav--next" aria-label="Next image" type="button">&rsaquo;</button>' +
				'<div class="zpb-lightbox__counter" aria-live="polite"></div>';

			document.body.appendChild( this.overlay );
			document.body.classList.add( 'zpb-lightbox-open' );

			var self = this;

			this.overlay.querySelector( '.zpb-lightbox__close' ).addEventListener( 'click', function () { self.close(); } );
			this.overlay.querySelector( '.zpb-lightbox__nav--prev' ).addEventListener( 'click', function () { self.prev(); } );
			this.overlay.querySelector( '.zpb-lightbox__nav--next' ).addEventListener( 'click', function () { self.next(); } );

			this.overlay.addEventListener( 'click', function ( e ) {
				if ( e.target === self.overlay ) self.close();
			} );

			this.onKeydown = function ( e ) {
				if ( e.key === 'Escape' )       { e.preventDefault(); self.close(); }
				else if ( e.key === 'ArrowLeft' ){ e.preventDefault(); self.prev(); }
				else if ( e.key === 'ArrowRight' ){ e.preventDefault(); self.next(); }
			};
			document.addEventListener( 'keydown', this.onKeydown );

			this.update();

			// Trap focus on close button.
			this.overlay.querySelector( '.zpb-lightbox__close' ).focus();
		},

		update: function () {
			if ( ! this.overlay ) return;
			var img = this.overlay.querySelector( '.zpb-lightbox__img' );
			img.src = this.images[ this.index ];
			var counter = this.overlay.querySelector( '.zpb-lightbox__counter' );
			counter.textContent = ( this.index + 1 ) + ' / ' + this.images.length;
			// Hide nav buttons if only one image.
			var navs = this.overlay.querySelectorAll( '.zpb-lightbox__nav' );
			navs.forEach( function ( n ) {
				n.style.display = ( Lightbox.images.length > 1 ) ? '' : 'none';
			} );
		},

		next: function () {
			if ( ! this.overlay ) return;
			this.index = ( this.index + 1 ) % this.images.length;
			this.update();
		},

		prev: function () {
			if ( ! this.overlay ) return;
			this.index = ( this.index - 1 + this.images.length ) % this.images.length;
			this.update();
		},

		close: function () {
			if ( ! this.overlay ) return;
			document.body.classList.remove( 'zpb-lightbox-open' );
			this.overlay.remove();
			this.overlay = null;
			document.removeEventListener( 'keydown', this.onKeydown );
			this.onKeydown = null;
			if ( this.previousFocus && typeof this.previousFocus.focus === 'function' ) {
				this.previousFocus.focus();
			}
		},
	};

	/* ====================================================================
	 * Gallery instance
	 * ==================================================================== */

	function GalleryInstance( root ) {
		this.root           = root;
		this.productId      = parseInt( root.getAttribute( 'data-product-id' ), 10 );
		this.channel        = ZPB.product( this.productId );
		this.zoomEnabled    = root.getAttribute( 'data-zoom' ) === '1';
		this.zoomLevel      = parseFloat( root.getAttribute( 'data-zoom-level' ) ) || 2;
		this.lightboxEnabled = root.getAttribute( 'data-lightbox' ) === '1';
		this.swapOnVariation = root.getAttribute( 'data-variation-swap' ) === '1';

		this.mainImg        = root.querySelector( '.zpb-gallery__main-img' );
		this.thumbs         = Array.prototype.slice.call( root.querySelectorAll( '[data-zpb-thumb]' ) );
		this.prevBtn        = root.querySelector( '.zpb-gallery__nav--prev' );
		this.nextBtn        = root.querySelector( '.zpb-gallery__nav--next' );

		// Active gallery index (0..thumbs.length-1)
		this.activeIndex    = 0;
		// True when a variation image is currently overriding the gallery.
		this.variationOverride = false;

		// Default image is whatever the main slot started with.
		this.defaultImage   = this.mainImg ? this.mainImg.getAttribute( 'src' ) : '';
		this.defaultFull    = this.mainImg ? ( this.mainImg.getAttribute( 'data-full' ) || this.defaultImage ) : '';
	}

	GalleryInstance.prototype.init = function () {
		var self = this;

		this.bindThumbs();
		this.bindNavArrows();
		if ( this.zoomEnabled )    this.bindZoom();
		if ( this.lightboxEnabled ) this.bindLightbox();

		// Cross-section sync.
		this.channel.on( 'variation:selected', function ( data ) {
			if ( ! self.swapOnVariation || ! data || ! data.image ) return;
			self.variationOverride = true;
			self.applyExternalImage( data.image );
		} );

		this.channel.on( 'variation:cleared', function () {
			if ( ! self.swapOnVariation ) return;
			self.variationOverride = false;
			self.setActiveIndex( self.activeIndex );
		} );
	};

	GalleryInstance.prototype.bindThumbs = function () {
		var self = this;
		this.thumbs.forEach( function ( thumb, i ) {
			thumb.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				self.variationOverride = false;
				self.setActiveIndex( i );
			} );
			thumb.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar' ) {
					e.preventDefault();
					self.variationOverride = false;
					self.setActiveIndex( i );
				}
			} );
		} );
	};

	GalleryInstance.prototype.bindNavArrows = function () {
		var self = this;
		if ( this.prevBtn ) {
			this.prevBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				self.variationOverride = false;
				var n = self.thumbs.length;
				if ( ! n ) return;
				self.setActiveIndex( ( self.activeIndex - 1 + n ) % n );
			} );
		}
		if ( this.nextBtn ) {
			this.nextBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				self.variationOverride = false;
				var n = self.thumbs.length;
				if ( ! n ) return;
				self.setActiveIndex( ( self.activeIndex + 1 ) % n );
			} );
		}
	};

	GalleryInstance.prototype.setActiveIndex = function ( index ) {
		if ( ! this.thumbs.length ) return;
		this.activeIndex = index;

		this.thumbs.forEach( function ( t, i ) {
			var on = i === index;
			t.classList.toggle( 'is-active', on );
			t.setAttribute( 'aria-current', on ? 'true' : 'false' );
		} );

		var thumb = this.thumbs[ index ];
		if ( thumb && this.mainImg ) {
			var src  = thumb.getAttribute( 'data-image' );
			var full = thumb.getAttribute( 'data-full' ) || src;
			this.swapMainImage( src, full );
		}
	};

	/** Swap main image to an arbitrary URL (variation image). */
	GalleryInstance.prototype.applyExternalImage = function ( url ) {
		if ( ! this.mainImg ) return;
		this.swapMainImage( url, url );
		this.thumbs.forEach( function ( t ) {
			t.classList.remove( 'is-active' );
			t.setAttribute( 'aria-current', 'false' );
		} );
	};

	/**
	 * Cross-fade the main image to a new src.
	 * Falls back to instant swap if `prefers-reduced-motion: reduce`.
	 */
	GalleryInstance.prototype.swapMainImage = function ( src, full ) {
		if ( ! this.mainImg ) return;
		if ( this.mainImg.getAttribute( 'src' ) === src ) {
			this.mainImg.setAttribute( 'data-full', full );
			return;
		}
		var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( reduced ) {
			this.mainImg.setAttribute( 'src', src );
			this.mainImg.setAttribute( 'data-full', full );
			return;
		}
		var img = this.mainImg;
		img.classList.add( 'is-fading' );
		// Preload, then swap on load.
		var pre = new Image();
		pre.onload = function () {
			img.setAttribute( 'src', src );
			img.setAttribute( 'data-full', full );
			img.classList.remove( 'is-fading' );
		};
		pre.onerror = function () {
			// Fall back to direct swap on load failure.
			img.setAttribute( 'src', src );
			img.classList.remove( 'is-fading' );
		};
		pre.src = src;
	};

	/** CSS-driven zoom on hover (desktop) or tap-to-toggle (touch devices). */
	GalleryInstance.prototype.bindZoom = function () {
		var self = this;
		var frame = this.root.querySelector( '.zpb-gallery__main-frame' );
		if ( ! frame || ! this.mainImg ) return;

		// Detect coarse pointer / no-hover (touch devices).
		var isTouch = window.matchMedia &&
			( window.matchMedia( '(hover: none)' ).matches || window.matchMedia( '(pointer: coarse)' ).matches );

		frame.classList.add( 'is-zoomable' );

		if ( isTouch ) {
			// Tap to toggle zoom; second tap or click anywhere else collapses.
			frame.addEventListener( 'click', function ( e ) {
				if ( self.lightboxEnabled ) {
					// Lightbox handler will run too — only toggle zoom if not lightboxable.
					return;
				}
				e.preventDefault();
				if ( frame.classList.toggle( 'is-zoomed' ) ) {
					self.mainImg.style.transform = 'scale(' + self.zoomLevel + ')';
				} else {
					self.mainImg.style.transform = '';
					self.mainImg.style.transformOrigin = '';
				}
			} );
		} else {
			frame.addEventListener( 'mousemove', function ( e ) {
				var rect = frame.getBoundingClientRect();
				var x = ( ( e.clientX - rect.left ) / rect.width ) * 100;
				var y = ( ( e.clientY - rect.top ) / rect.height ) * 100;
				self.mainImg.style.transformOrigin = x + '% ' + y + '%';
				self.mainImg.style.transform = 'scale(' + self.zoomLevel + ')';
			} );

			frame.addEventListener( 'mouseleave', function () {
				self.mainImg.style.transform = '';
				self.mainImg.style.transformOrigin = '';
			} );
		}
	};

	/** Open lightbox on main-image click. */
	GalleryInstance.prototype.bindLightbox = function () {
		var self = this;
		var frame = this.root.querySelector( '.zpb-gallery__main-frame' );
		if ( ! frame ) return;

		frame.classList.add( 'is-lightboxable' );

		frame.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var images;
			var startIndex;
			if ( self.variationOverride && self.mainImg ) {
				// Show only the current variation image.
				images = [ self.mainImg.getAttribute( 'data-full' ) || self.mainImg.getAttribute( 'src' ) ];
				startIndex = 0;
			} else {
				images = self.thumbs.map( function ( t ) {
					return t.getAttribute( 'data-full' ) || t.getAttribute( 'data-image' );
				} );
				startIndex = self.activeIndex;
			}
			Lightbox.open( images, startIndex );
		} );
	};

	/* ====================================================================
	 * Init
	 * ==================================================================== */

	function initAll() {
		document.querySelectorAll( '.zpb-gallery[data-product-id]' ).forEach( function ( root ) {
			if ( root.__zpbGalleryInit ) return;
			root.__zpbGalleryInit = true;
			var inst = new GalleryInstance( root );
			inst.init();
		} );
	}

	ready( initAll );

	if ( window.elementorFrontend ) {
		window.elementorFrontend.hooks &&
			window.elementorFrontend.hooks.addAction &&
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/zpb-gallery.default', function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ].querySelector( '.zpb-gallery' ) : null;
				if ( el && ! el.__zpbGalleryInit ) {
					el.__zpbGalleryInit = true;
					var inst = new GalleryInstance( el );
					inst.init();
				}
			} );
	}
} )( window, document );
