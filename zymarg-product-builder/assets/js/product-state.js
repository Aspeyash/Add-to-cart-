/**
 * Zymarg Product Builder — Client-side state bus.
 *
 * One global namespace: window.ZPB
 * Per-product channels keyed by product ID. Widgets in different Elementor
 * sections coordinate via this bus. No jQuery dependency.
 *
 * Public API:
 *   ZPB.product(id).on(event, cb)        // subscribe
 *   ZPB.product(id).off(event, cb)       // unsubscribe
 *   ZPB.product(id).emit(event, data)    // publish
 *   ZPB.product(id).getState()           // read latest state
 *   ZPB.product(id).setState(partial)    // merge into state + emit 'state:changed'
 *   ZPB.product(id).getData()            // server-injected product JSON
 *
 * Standard events:
 *   'variation:selected'  { variationId, attributes, priceHtml, isInStock, ... }
 *   'variation:cleared'   {}
 *   'qty:changed'         { qty }
 *   'cart:added'          { productId, variationId, qty, fragments }
 *   'cart:error'          { message }
 *   'state:changed'       { ...state }
 */
( function ( window, document ) {
	'use strict';

	if ( window.ZPB && window.ZPB.__loaded ) {
		return;
	}

	/**
	 * Read the server-injected product data blob.
	 * @returns {Object} map of productId -> product payload
	 */
	function readProductsData() {
		var node = document.getElementById( 'zpb-products-data' );
		if ( ! node ) {
			return {};
		}
		try {
			return JSON.parse( node.textContent || node.innerText || '{}' );
		} catch ( e ) {
			return {};
		}
	}

	var ProductsData = readProductsData();

	/** @type {Object.<string, ProductChannel>} */
	var channels = {};

	/**
	 * ProductChannel — one per product ID.
	 * @param {number|string} id Product ID.
	 * @constructor
	 */
	function ProductChannel( id ) {
		this.id = String( id );
		this.listeners = {};
		this.state = {
			productId: parseInt( this.id, 10 ),
			variationId: 0,
			selectedAttributes: {},
			qty: 1,
			isInStock: true,
			priceHtml: '',
		};
		// Seed state from server data if present.
		var data = ProductsData[ this.id ];
		if ( data ) {
			this.state.isInStock = !! data.isInStock;
			this.state.priceHtml = data.priceHtml || '';
			this.state.qty = data.minQty || 1;
		}
	}

	ProductChannel.prototype.on = function ( event, cb ) {
		if ( typeof cb !== 'function' ) return this;
		( this.listeners[ event ] = this.listeners[ event ] || [] ).push( cb );
		return this;
	};

	ProductChannel.prototype.off = function ( event, cb ) {
		var list = this.listeners[ event ];
		if ( ! list ) return this;
		this.listeners[ event ] = list.filter( function ( fn ) {
			return fn !== cb;
		} );
		return this;
	};

	ProductChannel.prototype.emit = function ( event, data ) {
		var list = this.listeners[ event ];
		if ( ! list || ! list.length ) return this;
		// Iterate over a copy to allow handlers to unsubscribe safely.
		list.slice().forEach( function ( fn ) {
			try {
				fn( data );
			} catch ( e ) {
				if ( window.console && console.error ) {
					console.error( '[ZPB] listener error for', event, e );
				}
			}
		} );
		return this;
	};

	ProductChannel.prototype.getState = function () {
		return Object.assign( {}, this.state );
	};

	ProductChannel.prototype.setState = function ( partial ) {
		if ( ! partial || typeof partial !== 'object' ) return this;
		for ( var key in partial ) {
			if ( Object.prototype.hasOwnProperty.call( partial, key ) ) {
				this.state[ key ] = partial[ key ];
			}
		}
		this.emit( 'state:changed', this.getState() );
		return this;
	};

	ProductChannel.prototype.getData = function () {
		return ProductsData[ this.id ] || null;
	};

	/**
	 * Find a variation matching the given attributes map.
	 * Returns the variation object or null.
	 * @param {Object} attrs map of attribute_name -> value (matches WC's keys)
	 * @returns {Object|null}
	 */
	ProductChannel.prototype.matchVariation = function ( attrs ) {
		var data = this.getData();
		if ( ! data || ! data.variations ) return null;

		var variations = data.variations;
		for ( var i = 0; i < variations.length; i++ ) {
			var v = variations[ i ];
			var match = true;
			for ( var key in attrs ) {
				if ( ! Object.prototype.hasOwnProperty.call( attrs, key ) ) continue;
				var wanted = attrs[ key ];
				var have = v.attributes[ key ];
				// "any" attribute = empty string in WC = matches anything.
				if ( have && have !== wanted ) {
					match = false;
					break;
				}
			}
			if ( match ) return v;
		}
		return null;
	};

	/**
	 * Public API.
	 */
	var ZPB = {
		__loaded: true,
		version: '0.1.0',

		/**
		 * Get (or lazily create) the channel for a product.
		 * @param {number|string} id Product ID.
		 * @returns {ProductChannel}
		 */
		product: function ( id ) {
			var key = String( id );
			if ( ! channels[ key ] ) {
				channels[ key ] = new ProductChannel( key );
			}
			return channels[ key ];
		},

		/**
		 * Get the raw server-injected data map.
		 */
		data: function () {
			return ProductsData;
		},

		/**
		 * Convenience: list of product IDs known on this page.
		 */
		productIds: function () {
			return Object.keys( ProductsData );
		},
	};

	window.ZPB = ZPB;
} )( window, document );
