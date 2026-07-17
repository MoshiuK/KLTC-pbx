/**
 * ApexDrive theme JS — nav, reveal animations, inventory AJAX filters,
 * vehicle gallery, financing calculator, lead forms. No dependencies.
 */
( function () {
	'use strict';

	var cfg = window.apexdrive || {};

	/* ---------- Sticky header + mobile nav ---------- */
	var header = document.getElementById( 'site-header' );
	if ( header ) {
		window.addEventListener( 'scroll', function () {
			header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
		}, { passive: true } );
	}

	var navToggle = document.getElementById( 'nav-toggle' );
	var mainNav = document.getElementById( 'main-nav' );
	if ( navToggle && mainNav ) {
		navToggle.addEventListener( 'click', function () {
			var open = mainNav.classList.toggle( 'is-open' );
			navToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	}

	/* ---------- Scroll-reveal ---------- */
	if ( 'IntersectionObserver' in window ) {
		var revealObserver = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					revealObserver.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.12 } );

		var observeReveals = function ( root ) {
			( root || document ).querySelectorAll( '.reveal:not(.is-visible)' ).forEach( function ( el ) {
				revealObserver.observe( el );
			} );
		};
		observeReveals();
	} else {
		document.querySelectorAll( '.reveal' ).forEach( function ( el ) {
			el.classList.add( 'is-visible' );
		} );
	}

	function showNewCards( root ) {
		( root || document ).querySelectorAll( '.reveal:not(.is-visible)' ).forEach( function ( el ) {
			el.classList.add( 'is-visible' );
		} );
	}

	/* ---------- Inventory AJAX filtering ---------- */
	var grid = document.getElementById( 'inventory-grid' );
	var filterForm = document.getElementById( 'inventory-filters' );

	if ( grid && filterForm && cfg.ajaxUrl ) {
		var sortSelect = document.getElementById( 'inventory-sort' );
		var countEl = document.getElementById( 'inventory-count' );
		var loadMoreBtn = document.getElementById( 'load-more' );
		var resetBtn = document.getElementById( 'filter-reset' );
		var currentPage = 1;
		var maxPages = parseInt( grid.dataset.maxPages || '1', 10 );
		var debounceTimer = null;

		function collectFilters() {
			var data = new FormData( filterForm );
			data.append( 'action', 'apexdrive_filter' );
			data.append( 'nonce', cfg.filterNonce );
			if ( sortSelect && sortSelect.value ) {
				data.append( 'sort', sortSelect.value );
			}
			return data;
		}

		function syncUrl( data ) {
			var params = new URLSearchParams();
			data.forEach( function ( value, key ) {
				if ( value && [ 'action', 'nonce', 'paged' ].indexOf( key ) === -1 ) {
					params.set( key, value );
				}
			} );
			var qs = params.toString();
			window.history.replaceState( null, '', window.location.pathname + ( qs ? '?' + qs : '' ) );
		}

		function fetchVehicles( page, append ) {
			var data = collectFilters();
			data.append( 'paged', page );
			grid.classList.add( 'is-loading' );

			fetch( cfg.ajaxUrl, { method: 'POST', body: data } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( res ) {
					if ( ! res || ! res.success ) {
						return;
					}
					var payload = res.data;
					if ( append ) {
						grid.insertAdjacentHTML( 'beforeend', payload.html );
					} else {
						grid.innerHTML = payload.html ||
							'<div class="inventory-empty">No vehicles match those filters. Try widening your search.</div>';
					}
					if ( countEl ) {
						countEl.textContent = payload.found;
					}
					currentPage = payload.paged;
					maxPages = payload.max_pages;
					if ( loadMoreBtn ) {
						loadMoreBtn.hidden = currentPage >= maxPages;
					}
					showNewCards( grid );
					syncUrl( data );
				} )
				.catch( function () { /* keep current results on network error */ } )
				.finally( function () {
					grid.classList.remove( 'is-loading' );
				} );
		}

		filterForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			fetchVehicles( 1, false );
		} );

		filterForm.addEventListener( 'change', function () {
			fetchVehicles( 1, false );
		} );

		var searchInput = filterForm.querySelector( 'input[type="search"]' );
		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				clearTimeout( debounceTimer );
				debounceTimer = setTimeout( function () {
					fetchVehicles( 1, false );
				}, 400 );
			} );
		}

		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				fetchVehicles( 1, false );
			} );
		}

		if ( loadMoreBtn ) {
			loadMoreBtn.addEventListener( 'click', function () {
				fetchVehicles( currentPage + 1, true );
			} );
		}

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function () {
				filterForm.reset();
				if ( sortSelect ) {
					sortSelect.value = '';
				}
				fetchVehicles( 1, false );
			} );
		}
	}

	/* ---------- Vehicle photo gallery ---------- */
	var thumbs = document.getElementById( 'gallery-thumbs' );
	var mainImg = document.getElementById( 'gallery-main-img' );
	if ( thumbs && mainImg ) {
		thumbs.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( 'button[data-full]' );
			if ( ! btn ) {
				return;
			}
			mainImg.src = btn.dataset.full;
			mainImg.removeAttribute( 'srcset' );
			thumbs.querySelectorAll( 'button' ).forEach( function ( b ) {
				b.classList.toggle( 'is-active', b === btn );
			} );
		} );
	}

	/* ---------- Financing calculator ---------- */
	var calcPanel = document.querySelector( '.calc-panel[data-price]' );
	if ( calcPanel ) {
		var price = parseFloat( calcPanel.dataset.price ) || 0;
		var downInput = document.getElementById( 'calc-down' );
		var aprInput = document.getElementById( 'calc-apr' );
		var termInput = document.getElementById( 'calc-term' );
		var paymentEl = document.getElementById( 'calc-payment' );

		function recalc() {
			var down = parseFloat( downInput.value ) || 0;
			var apr = parseFloat( aprInput.value ) || 0;
			var term = parseInt( termInput.value, 10 ) || 60;
			var principal = Math.max( 0, price - down );
			var payment;

			if ( apr <= 0 ) {
				payment = principal / term;
			} else {
				var r = apr / 100 / 12;
				payment = principal * ( r * Math.pow( 1 + r, term ) ) / ( Math.pow( 1 + r, term ) - 1 );
			}

			paymentEl.textContent = '$' + ( isFinite( payment ) ? Math.round( payment ).toLocaleString() : '0' );
		}

		[ downInput, aprInput, termInput ].forEach( function ( input ) {
			if ( input ) {
				input.addEventListener( 'input', recalc );
			}
		} );
		recalc();
	}

	/* ---------- Lead forms (test drive / inquiry) ---------- */
	document.querySelectorAll( '.lead-form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var msg = form.querySelector( '.form-msg' );
			var submitBtn = form.querySelector( 'button[type="submit"]' );
			var data = new FormData( form );
			data.append( 'action', 'apexdrive_lead' );
			data.append( 'nonce', cfg.leadNonce );
			data.append( 'lead_type', form.dataset.leadType || 'inquiry' );
			data.append( 'vehicle_id', form.dataset.vehicle || '0' );

			if ( submitBtn ) {
				submitBtn.disabled = true;
			}

			fetch( cfg.ajaxUrl, { method: 'POST', body: data } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( res ) {
					if ( ! msg ) {
						return;
					}
					msg.className = 'form-msg ' + ( res.success ? 'is-success' : 'is-error' );
					msg.textContent = ( res.data && res.data.message ) ||
						( res.success ? 'Thanks! We will be in touch shortly.' : 'Something went wrong — please call us instead.' );
					if ( res.success ) {
						form.reset();
					}
				} )
				.catch( function () {
					if ( msg ) {
						msg.className = 'form-msg is-error';
						msg.textContent = 'Network error — please try again or call us.';
					}
				} )
				.finally( function () {
					if ( submitBtn ) {
						submitBtn.disabled = false;
					}
				} );
		} );
	} );
} )();
