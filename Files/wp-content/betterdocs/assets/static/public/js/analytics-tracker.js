/**
 * BetterDocs lightweight analytics tracker.
 *
 * On a single doc: records a view after DOMContentLoaded, and tracks max scroll
 * depth, sending a "scroll" beacon on page hide (reading-completion). Both go
 * via fetch + keepalive with the X-WP-Nonce header (see the 401 fix in DECISIONS).
 * No jQuery, no dependencies.
 */
( function () {
	'use strict';

	var cfg = window.betterDocsTracker;
	if ( ! cfg || ! cfg.rest_url || ! cfg.post_id ) {
		return;
	}

	var postId = parseInt( cfg.post_id, 10 );
	if ( ! postId ) {
		return;
	}

	function post( body ) {
		try {
			fetch( cfg.rest_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce || ''
				},
				body: JSON.stringify( body ),
				keepalive: true,
				credentials: 'same-origin'
			} );
		} catch ( e ) {}
	}

	function sendView() {
		var unique = 0;
		try {
			var key = 'bd_v_' + postId;
			if ( ! window.localStorage.getItem( key ) ) {
				unique = 1;
				window.localStorage.setItem( key, '1' );
			}
		} catch ( e ) {
			unique = 0;
		}

		var referrer = '';
		try {
			referrer = document.referrer || '';
		} catch ( e ) {}

		post( { post_id: postId, u: unique, referrer: referrer } );

		forwardGA4( 'betterdocs_doc_view', {} );
	}

	// Forward an event to GA4 when the integration is on. cfg.ga4.method decides how:
	//   datalayer -> raw window.dataLayer push (for sites already using GTM)
	//   gtag      -> gtag('event', …) via the gtag.js BetterDocs injected
	//   mp        -> nothing here; the server sends it via the Measurement Protocol
	function forwardGA4( event, extra ) {
		var ga4 = cfg.ga4;
		if ( ! ga4 || ! ga4.enabled ) {
			return;
		}
		// Shared params — the event name is passed separately to gtag, embedded for dataLayer.
		var params = { doc_id: postId };
		for ( var k in extra ) {
			if ( Object.prototype.hasOwnProperty.call( extra, k ) ) {
				params[ k ] = extra[ k ];
			}
		}
		try {
			if ( ga4.method === 'gtag' ) {
				if ( typeof window.gtag === 'function' ) {
					window.gtag( 'event', event, params );
				}
			} else if ( ga4.method === 'datalayer' ) {
				window.dataLayer = window.dataLayer || [];
				var dl = { event: event, doc_id: postId };
				for ( var j in extra ) {
					if ( Object.prototype.hasOwnProperty.call( extra, j ) ) {
						dl[ j ] = extra[ j ];
					}
				}
				window.dataLayer.push( dl );
			}
			// method 'mp' is handled server-side; nothing to emit client-side.
		} catch ( e ) {}
	}

	// --- reading completion (max scroll depth) ---
	var maxDepth = 0;
	var scrollSent = false;

	function computeDepth() {
		try {
			var doc = document.documentElement;
			var body = document.body;
			var scrollTop = window.pageYOffset || doc.scrollTop || body.scrollTop || 0;
			var winH = window.innerHeight || doc.clientHeight;
			var docH = Math.max(
				body.scrollHeight, doc.scrollHeight,
				body.offsetHeight, doc.offsetHeight,
				doc.clientHeight
			);
			if ( docH <= winH ) {
				return 100; // fits on screen = fully read
			}
			var depth = Math.round( ( ( scrollTop + winH ) / docH ) * 100 );
			return Math.max( 0, Math.min( 100, depth ) );
		} catch ( e ) {
			return 0;
		}
	}

	function onScroll() {
		var d = computeDepth();
		if ( d > maxDepth ) {
			maxDepth = d;
		}
	}

	function sendScroll() {
		if ( scrollSent ) {
			return;
		}
		scrollSent = true;
		var depth = Math.max( maxDepth, computeDepth() );
		post( { post_id: postId, event: 'scroll', depth: depth } );
		if ( depth >= 90 ) {
			forwardGA4( 'betterdocs_doc_complete', { depth: depth } );
		}
	}

	function init() {
		sendView();
		onScroll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'pagehide', sendScroll );
		document.addEventListener( 'visibilitychange', function () {
			if ( document.visibilityState === 'hidden' ) {
				sendScroll();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
