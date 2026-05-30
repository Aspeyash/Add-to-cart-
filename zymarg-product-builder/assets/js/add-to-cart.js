/**
 * Zymarg Product Builder — Add to Cart widget.
 *
 * - Quantity stepper (+/-) with min/max enforcement
 * - AJAX add-to-cart with loading/success/error states
 * - Listens for variation:selected to update price/stock/variation_id
 * - Triggers WC fragments refresh + standard added_to_cart event so themes
 *   with custom mini-carts pick it up.
 */
( function ( window, document ) {
	'use strict';

	var Config = window.ZPBConfig || {};
	var ZPB = window.ZPB;

	if ( ! ZPB ) {
		return;
	}

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	/**
	 * Initialize a single .zpb-atc widget instance.
	 * @param {HTMLElement} root Widget root element.
	 */
	function initInstance( root ) {
		if ( root.__zpbInit ) return;
		root.__zpbInit = true;

		var productId   = parseInt( root.getAttribute( 'data-product-id' ), 10 );
		var productType = root.getAttribute( 'data-product-type' ) || 'simple';
		var useAjax     = root.getAttribute( 'data-use-ajax' ) === '1';
		var redirect    = root.getAttribute( 'data-redirect' ) || 'none';
		var redirectUrl = root.getAttribute( 'data-redirect-url' ) || '';

		if ( ! productId ) return;

		var channel = ZPB.product( productId );

		var qtyInput   = root.querySelector( '[data-zpb-qty-input]' );
		var qtyMinus   = root.querySelector( '[data-action="decrement"]' );
		var qtyPlus    = root.querySelector( '[data-action="increment"]' );
		var addBtn     = root.querySelector( '[data-zpb-add-to-cart]' );
		var buyNowBtn  = root.querySelector( '[data-zpb-buy-now]' );
		var msgEl      = root.querySelector( '[data-zpb-message]' );
		var stockEl    = root.querySelector( '[data-zpb-stock]' );
		var priceEl    = root.querySelector( '[data-zpb-price]' );
		var btnText    = root.querySelector( '.zpb-atc__btn-text' );

		/* ----- Quantity stepper ----- */
		function readQty() {
			if ( ! qtyInput ) return 1;
			var n = parseInt( qtyInput.value, 10 );
			return isNaN( n ) || n < 1 ? 1 : n;
		}

		function clampQty( n ) {
			var min = parseInt( qtyInput.getAttribute( 'min' ), 10 );
			var max = parseInt( qtyInput.getAttribute( 'max' ), 10 );
			if ( ! isNaN( min ) && n < min ) n = min;
			if ( ! isNaN( max ) && max > 0 && n > max ) n = max;
			return n;
		}

		function setQty( n ) {
			if ( ! qtyInput ) return;
			n = clampQty( n );
			qtyInput.value = String( n );
			channel.setState( { qty: n } );
			channel.emit( 'qty:changed', { qty: n } );
		}

		if ( qtyMinus ) {
			qtyMinus.addEventListener( 'click', function () {
				setQty( readQty() - 1 );
			} );
		}
		if ( qtyPlus ) {
			qtyPlus.addEventListener( 'click', function () {
				setQty( readQty() + 1 );
			} );
		}
		if ( qtyInput ) {
			qtyInput.addEventListener( 'change', function () {
				setQty( readQty() );
			} );
		}

		/* ----- Listen for variation changes from the Swatches widget ----- */
		channel.on( 'variation:selected', function ( data ) {
			if ( ! data ) return;

			// Update price.
			if ( priceEl && data.priceHtml ) {
				priceEl.innerHTML = data.priceHtml;
			}

			// Update stock display.
			if ( stockEl ) {
				stockEl.classList.toggle( 'is-in-stock', !! data.isInStock );
				stockEl.classList.toggle( 'is-out-of-stock', ! data.isInStock );
				stockEl.textContent = data.isInStock
					? ( Config.i18n && Config.i18n.inStock ) || 'In Stock'
					: ( Config.i18n && Config.i18n.outOfStock ) || 'Out of stock';
			}

			// Toggle button.
			if ( addBtn ) {
				addBtn.disabled = ! data.isInStock;
			}
			if ( buyNowBtn ) {
				buyNowBtn.disabled = ! data.isInStock;
			}

			// Apply min/max from variation.
			if ( qtyInput ) {
				if ( typeof data.minQty === 'number' && data.minQty > 0 ) {
					qtyInput.setAttribute( 'min', String( data.minQty ) );
				}
				if ( typeof data.maxQty === 'number' && data.maxQty > 0 ) {
					qtyInput.setAttribute( 'max', String( data.maxQty ) );
				} else {
					qtyInput.removeAttribute( 'max' );
				}
				setQty( readQty() );
			}
		} );

		channel.on( 'variation:cleared', function () {
			if ( addBtn ) addBtn.disabled = productType === 'variable';
		} );

		// On variable products with no variation chosen yet, disable the button.
		if ( productType === 'variable' ) {
			var st = channel.getState();
			if ( ! st.variationId ) {
				if ( addBtn ) addBtn.disabled = true;
				if ( buyNowBtn ) buyNowBtn.disabled = true;
			}
		}

		/* ----- Add to Cart ----- */
		function setBtnState( state, message ) {
			if ( ! addBtn ) return;
			addBtn.classList.remove( 'is-loading', 'is-success', 'is-error' );
			if ( state ) addBtn.classList.add( 'is-' + state );

			if ( btnText ) {
				if ( state === 'loading' ) {
					btnText.textContent = ( Config.i18n && Config.i18n.adding ) || 'Adding...';
				} else if ( state === 'success' ) {
					btnText.textContent = ( Config.i18n && Config.i18n.added ) || 'Added!';
				} else {
					btnText.textContent = btnText.getAttribute( 'data-original' ) || btnText.textContent;
				}
			}

			if ( msgEl ) {
				msgEl.textContent = message || '';
				msgEl.className = 'zpb-atc__message' + ( state ? ' is-' + state : '' );
			}
		}

		// Cache original text so we can restore it.
		if ( btnText ) {
			btnText.setAttribute( 'data-original', btnText.textContent );
		}

		function performAdd( goToCheckout ) {
			var state = channel.getState();
			var qty   = readQty();

			if ( productType === 'variable' && ! state.variationId ) {
				setBtnState( 'error', ( Config.i18n && Config.i18n.selectOptions ) || 'Select options' );
				return;
			}

			if ( ! useAjax ) {
				// Classic submit: build a temporary form and submit.
				submitClassic( productId, state.variationId, qty, state.selectedAttributes, goToCheckout );
				return;
			}

			setBtnState( 'loading' );

			var fd = new FormData();
			fd.append( 'action', 'zpb_add_to_cart' );
			fd.append( 'nonce', Config.nonce || '' );
			fd.append( 'product_id', String( productId ) );
			fd.append( 'quantity', String( qty ) );
			if ( state.variationId ) {
				fd.append( 'variation_id', String( state.variationId ) );
			}
			if ( state.selectedAttributes ) {
				for ( var k in state.selectedAttributes ) {
					if ( Object.prototype.hasOwnProperty.call( state.selectedAttributes, k ) ) {
						fd.append( 'variation[' + k + ']', state.selectedAttributes[ k ] );
					}
				}
			}

			fetch( Config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: fd,
			} )
				.then( function ( res ) {
					return res.json().catch( function () { return { success: false }; } );
				} )
				.then( function ( res ) {
					if ( ! res || ! res.success ) {
						var err = res && res.data && res.data.message
							? res.data.message
							: ( Config.i18n && Config.i18n.error ) || 'Could not add to cart.';
						setBtnState( 'error', err );
						channel.emit( 'cart:error', { message: err } );
						setTimeout( function () { setBtnState( null ); }, 2500 );
						return;
					}

					setBtnState( 'success', res.data.message || '' );

					channel.emit( 'cart:added', {
						productId:   productId,
						variationId: state.variationId,
						qty:         qty,
						fragments:   res.data.fragments || {},
					} );

					// Apply fragments for theme mini-cart.
					applyFragments( res.data.fragments );

					// Trigger WC's standard event so other plugins/themes react.
					triggerWooEvent( 'added_to_cart', [
						res.data.fragments,
						null,
						null,
					] );

					// Redirects.
					var target = '';
					if ( goToCheckout ) {
						target = res.data.checkout_url || '';
					} else if ( redirect === 'cart' ) {
						target = res.data.cart_url || '';
					} else if ( redirect === 'checkout' ) {
						target = res.data.checkout_url || '';
					} else if ( redirect === 'custom' && redirectUrl ) {
						target = redirectUrl;
					} else if ( res.data.redirect_url ) {
						target = res.data.redirect_url;
					}

					if ( target ) {
						window.location.href = target;
						return;
					}

					setTimeout( function () { setBtnState( null ); }, 1800 );
				} )
				.catch( function () {
					var err = ( Config.i18n && Config.i18n.error ) || 'Could not add to cart.';
					setBtnState( 'error', err );
					setTimeout( function () { setBtnState( null ); }, 2500 );
				} );
		}

		if ( addBtn ) {
			addBtn.addEventListener( 'click', function () {
				performAdd( false );
			} );
		}
		if ( buyNowBtn ) {
			buyNowBtn.addEventListener( 'click', function () {
				performAdd( true );
			} );
		}
	}

	/**
	 * Replace fragment HTML in the page (mini-cart, count, etc.).
	 * @param {Object} fragments map of selector -> HTML
	 */
	function applyFragments( fragments ) {
		if ( ! fragments || typeof fragments !== 'object' ) return;
		Object.keys( fragments ).forEach( function ( selector ) {
			try {
				document.querySelectorAll( selector ).forEach( function ( el ) {
					var temp = document.createElement( 'div' );
					temp.innerHTML = fragments[ selector ];
					var fresh = temp.firstElementChild;
					if ( fresh && el.parentNode ) {
						el.parentNode.replaceChild( fresh, el );
					}
				} );
			} catch ( e ) { /* ignore selector errors */ }
		} );
	}

	/**
	 * Trigger a jQuery event for WC ecosystem compatibility (most themes
	 * still listen on jQuery body events).
	 */
	function triggerWooEvent( name, args ) {
		if ( window.jQuery ) {
			try {
				window.jQuery( document.body ).trigger( name, args || [] );
			} catch ( e ) { /* noop */ }
		}
	}

	/**
	 * Classic (non-AJAX) submit — build a form, submit it. Cart page handles redirect.
	 */
	function submitClassic( productId, variationId, qty, attrs, goToCheckout ) {
		var form = document.createElement( 'form' );
		form.method = 'post';
		form.action = window.location.href;
		form.style.display = 'none';

		var fields = {
			'add-to-cart':   String( productId ),
			'quantity':      String( qty ),
		};
		if ( variationId ) {
			fields.variation_id = String( variationId );
			fields.product_id   = String( productId );
		}

		Object.keys( fields ).forEach( function ( key ) {
			var input = document.createElement( 'input' );
			input.type  = 'hidden';
			input.name  = key;
			input.value = fields[ key ];
			form.appendChild( input );
		} );

		if ( attrs ) {
			Object.keys( attrs ).forEach( function ( key ) {
				var input = document.createElement( 'input' );
				input.type  = 'hidden';
				input.name  = key;
				input.value = attrs[ key ];
				form.appendChild( input );
			} );
		}

		// Buy Now: cart page will redirect to checkout via filter hook
		// (left for theme-level integration; classic mode is best-effort).
		if ( goToCheckout ) {
			var marker = document.createElement( 'input' );
			marker.type  = 'hidden';
			marker.name  = 'zpb_buy_now';
			marker.value = '1';
			form.appendChild( marker );
		}

		document.body.appendChild( form );
		form.submit();
	}

	function initAll() {
		document.querySelectorAll( '.zpb-atc[data-product-id]' ).forEach( initInstance );
	}

	ready( initAll );

	// Re-init after Elementor frontend loads (editor preview).
	if ( window.elementorFrontend ) {
		window.elementorFrontend.hooks &&
			window.elementorFrontend.hooks.addAction &&
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/zpb-add-to-cart.default', function ( $scope ) {
				var el = $scope && $scope[ 0 ] ? $scope[ 0 ].querySelector( '.zpb-atc' ) : null;
				if ( el ) initInstance( el );
			} );
	}
} )( window, document );
