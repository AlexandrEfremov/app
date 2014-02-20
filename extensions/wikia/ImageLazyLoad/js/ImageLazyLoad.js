/* Lazy loading for images inside articles (skips wikiamobile)
 * @author Piotr Bablok <pbablok@wikia-inc.com>
 */

$( function() {
	'use strict';

	// it's a global, it should be a global
	var ImgLzy = {
		cache: [],
		timestats: 0,

		init: function() {
			var self = this,
				proxy = $.proxy( self.checkAndLoad, self ),
				throttled = $.throttle( 250, proxy );

			self.createCache();
			self.checkAndLoad();

			$( window ).on( 'scroll', throttled );
			$( '.scroller' ).on( 'scroll', throttled );
			$( document ).on( 'tablesorter_sortComplete', proxy );
		},

		relativeTop: function( e ) {
			return e.offset().top - e.parents( '.scroller' ).offset().top;
		},

		absTop: function( e ) {
			return e.offset().top;
		},

		createCache: function() {
			var self = this;
			self.cache = [];
			$( 'img.lzy' ).each( function( idx ) {
				var $el = $( this ),
					relativeTo = $( '.scroller' ).find( this ),
					topCalc, top;

				if ( relativeTo.length != 0 ) {
					relativeTo = relativeTo.parents( '.scroller' );
					topCalc = self.relativeTop;
				} else {
					relativeTo = $( window );
					topCalc = self.absTop;
				}

				top = topCalc( $el );
				self.cache[idx] = {
					el: this,
					jq: $el,
					topCalc: topCalc,
					top: top,
					bottom: $el.height() + top,
					parent: relativeTo
				};
			} );
		},

		verifyCache: function() {
			if ( this.cache.length === 0 ) {
				return;
			}
			// make sure that position of elements in the cache didn't change
			var lastidx = this.cache.length - 1,
				randidx = Math.floor( Math.random() * lastidx ),
				checkidx = [ lastidx, randidx ],
				changed = false,
				i,
				idx,
				pos,
				diff;
			for ( i in checkidx ) {
				idx = checkidx[ i ];
				if ( idx in this.cache ) {
					pos = this.cache[idx].topCalc( this.cache[idx].jq );
					diff = Math.abs( pos - this.cache[idx].top );

					if ( diff > 5 ) {
						changed = true;
						break;
					}
				}
			}
			if ( changed ) {
				this.createCache();
			}
		},

		load: function( image ) {
			// this code can only be run from AJAX requests (ie. ImgLzy is registered AFTER DOM ready event
			// so those are new images in DOM
			var $img = $( image ),
				dataSrc = $img.data( 'src' );
			image.onload = '';
			if ( dataSrc ) {
				image.src = dataSrc;
			}
			$img.removeClass( 'lzy' ).removeClass( 'lzyPlcHld' );

		},

		parentVisible: function( item ) {
			if ( item.parent[0] == window ) {
				return true;
			}

			var fold = $( window ).scrollTop() + $( window ).height(),
				parentTop = item.parent.offset().top;

			return fold > parentTop;
		},

		checkAndLoad: function() {
			//var timestart = ( new Date() ).getTime();

			this.verifyCache();

			var onload = function() {
					this.setAttribute( 'class', this.getAttribute( 'class' ) + ' lzyLoaded' );
				},
				scrollTop,
				scrollSpeed,
				lastScrollTop,
				scrollBottom,
				idx,
				visible,
				cacheItem;

			for ( idx in this.cache ) {
				cacheItem = this.cache[idx];
				scrollTop = cacheItem.parent.scrollTop();
				lastScrollTop = cacheItem.parent.data( 'lastScrollTop' ) || 0;
				scrollSpeed = Math.min( Math.abs( scrollTop - lastScrollTop ), 1000 ) * 3 + 200;
				scrollBottom = scrollTop + cacheItem.parent.height() + scrollSpeed;
				scrollTop = scrollTop - scrollSpeed;

				cacheItem.parent.data( 'lastScrollTop', lastScrollTop );
				visible = (scrollTop < cacheItem.top && scrollBottom > cacheItem.top) ||
					(scrollTop < cacheItem.bottom && scrollBottom > cacheItem.bottom)

				if ( visible && this.parentVisible( cacheItem ) ) {
					cacheItem.jq.addClass( 'lzyTrns' );
					cacheItem.el.onload = onload;
					cacheItem.el.src = cacheItem.jq.data( 'src' );
					cacheItem.jq.removeClass( 'lzy' );
					delete this.cache[ idx ];
				}
			}
			//this.timestats = ( new Date() ).getTime() - timestart;
			//console.log( this.timestats );
		}
	};

	ImgLzy.init();

	// fix iOS bug - not firing scroll event when after refresh page is opened in the middle of its content
	require( [ 'wikia.browserDetect', 'wikia.window' ], function( browserDetect, w ) {
		if ( browserDetect.isIPad() ) {
			w.addEventListener( 'pageshow', function() {
				// Safari iOS doesn't trigger scroll event after page refresh.
				// This is a hack to manually lazy-load images after browser scroll the page after refreshing.
				// Should be fixed if we found better solution
				w.setTimeout( $.proxy( ImgLzy.checkAndLoad, ImgLzy ), 0 );
			} );
		}

	} );

	window.ImgLzy = ImgLzy;
} );
