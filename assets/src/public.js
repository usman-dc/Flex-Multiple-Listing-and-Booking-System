/**
 * Public bundle  booking form + grid AJAX filters.
 */
import './public.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	initBookingForm();
	initGridFilters();
	initVendorPortal();
	initGallerySlider();
	initFavorites();
	initListingReviews();
	initGoogleMapOptIn();
} );

function initGoogleMapOptIn() {
	document.querySelectorAll( '.ulbm-show-google-map' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			const wrap = btn.closest( '.ulbm-google-map-optin' );
			const frame = wrap?.querySelector( '.ulbm-google-map-frame' );
			if ( ! wrap || ! frame || frame.querySelector( 'iframe' ) ) {
				return;
			}
			const lat = wrap.dataset.lat || '';
			const lng = wrap.dataset.lng || '';
			if ( ! lat || ! lng ) {
				return;
			}
			const iframe = document.createElement( 'iframe' );
			iframe.width = '100%';
			iframe.height = '280';
			iframe.setAttribute( 'frameborder', '0' );
			iframe.style.border = '0';
			iframe.loading = 'lazy';
			iframe.allowFullscreen = true;
			iframe.src = `https://maps.google.com/maps?q=${ encodeURIComponent( lat + ',' + lng ) }&z=14&output=embed`;
			frame.appendChild( iframe );
			frame.classList.remove( 'd-none' );
			frame.setAttribute( 'aria-hidden', 'false' );
			btn.classList.add( 'd-none' );
			const note = wrap.querySelector( 'p.small' );
			if ( note ) {
				note.classList.add( 'd-none' );
			}
		} );
	} );
}

/* --- BOOKING FORM ------------------------------------------- */
function initBookingForm() {
	const form = document.getElementById( 'ulbm-booking-form-fields' );
	if ( ! form || typeof ulbmPublic === 'undefined' ) return;

	const root = form.closest( '.ulbm-booking-form' );
	const typeId = root ? parseInt( root.dataset.ulbmTypeId || '0', 10 ) : 0;
	const listingId = root ? parseInt( root.dataset.ulbmListingId || '0', 10 ) : 0;
	const feedback = form.querySelector( '.ulbm-form-feedback' );
	const isMarketplace = root && root.classList.contains( 'ulbm-booking-form--marketplace' );

	if ( isMarketplace ) {
		initMarketplaceBooking( form, root );
	}

	function showFeedback( msg, isError ) {
		if ( ! feedback ) return;
		feedback.classList.remove( 'd-none', 'alert-success', 'alert-danger' );
		feedback.classList.add( isError ? 'alert-danger' : 'alert-success' );
		feedback.textContent = msg;
	}

	const reserved = new Set( [
		'start', 'end', 'base_price', 'currency',
		'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone',
		'ulbm_checkin', 'ulbm_checkout', 'guests_count',
	] );

	form.addEventListener( 'submit', async ( e ) => {
		e.preventDefault();

		if ( isMarketplace ) {
			syncMarketplaceHiddenDates( form, root );
		}

		if ( ! form.checkValidity() ) {
			form.classList.add( 'was-validated' );
			showFeedback( 'Please complete all required fields below.', true );
			return;
		}

		const btn = form.querySelector( '[type="submit"]' );
		if ( btn ) btn.disabled = true;

		const fd = new FormData( form );
		const formValues = {};
		for ( const [ k, v ] of fd.entries() ) {
			if ( reserved.has( k ) || v === '' || v === null ) continue;
			formValues[ k ] = String( v );
		}
		if ( isMarketplace && fd.get( 'guests_count' ) ) {
			formValues.guests_count = String( fd.get( 'guests_count' ) );
		}

		const body = new URLSearchParams();
		body.append( 'action', 'ulbm_create_booking' );
		body.append( 'nonce', ulbmPublic.bookingNonce || '' );
		body.append( 'booking_type_id', String( typeId > 0 ? typeId : 1 ) );
		body.append( 'start', String( fd.get( 'start' ) || '' ) );
		body.append( 'end', String( fd.get( 'end' ) || '' ) );
		body.append( 'base_price', String( fd.get( 'base_price' ) || '0' ) );
		body.append( 'currency', root?.dataset.ulbmCurrency || 'USD' );
		body.append( 'customer_email', String( fd.get( 'customer_email' ) || '' ) );
		body.append( 'customer_phone', String( fd.get( 'customer_phone' ) || '' ) );
		body.append( 'customer_first_name', String( fd.get( 'customer_first_name' ) || '' ) );
		body.append( 'customer_last_name', String( fd.get( 'customer_last_name' ) || '' ) );
		body.append( 'form_values_json', JSON.stringify( formValues ) );
		const lid = parseInt( fd.get( 'listing_id' ) || listingId || '0', 10 );
		if ( lid > 0 ) body.append( 'listing_id', String( lid ) );

		try {
			const res = await fetch( ulbmPublic.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				credentials: 'same-origin',
				body,
			} );
			const json = await res.json();
			if ( json && json.success && json.data && json.data.uid ) {
				showFeedback( 'Booking confirmed! Reference: ' + json.data.uid, false );
				form.reset();
				form.classList.remove( 'was-validated' );
				if ( isMarketplace ) {
					initMarketplaceBooking( form, root );
				}
			} else {
				const errData = json?.data || {};
				const msg = Array.isArray( errData.errors )
					? errData.errors.join( ' | ' )
					: ( errData.message || 'Booking failed.' );
				showFeedback( msg, true );
			}
		} catch {
			showFeedback( 'Network error. Please try again.', true );
		} finally {
			if ( btn ) btn.disabled = false;
		}
	} );
}

function syncMarketplaceHiddenDates( form, root ) {
	if ( ! form || ! root ) return;
	const checkinEl   = form.querySelector( '.ulbm-mp-checkin' );
	const checkoutEl  = form.querySelector( '.ulbm-mp-checkout' );
	const startHidden = form.querySelector( '#ulbm-start' );
	const endHidden   = form.querySelector( '#ulbm-end' );
	const checkInT    = root.dataset.ulbmCheckInTime || '14:00';
	const checkOutT   = root.dataset.ulbmCheckOutTime || '11:00';
	const showEnd     = root.dataset.ulbmShowEndDate !== '0';
	if ( startHidden && checkinEl?.value ) {
		startHidden.value = checkinEl.value + 'T' + checkInT;
	}
	if ( endHidden ) {
		const endDate = ( showEnd && checkoutEl?.value ) ? checkoutEl.value : ( checkinEl?.value || '' );
		if ( endDate ) {
			endHidden.value = endDate + 'T' + checkOutT;
		}
	}
}

function initMarketplaceBooking( form, root ) {
	if ( ! form || ! root ) return;

	const checkinEl  = form.querySelector( '.ulbm-mp-checkin' );
	const checkoutEl = form.querySelector( '.ulbm-mp-checkout' );
	const startHidden = form.querySelector( '#ulbm-start' );
	const endHidden   = form.querySelector( '#ulbm-end' );
	const baseHidden  = form.querySelector( '[name="base_price"]' );
	if ( ! checkinEl ) return;

	const nightly   = parseFloat( root.dataset.ulbmNightly || '0' );
	const cleaning  = parseFloat( root.dataset.ulbmCleaning || '0' );
	let serviceFee  = parseFloat( root.dataset.ulbmService || '0' );
	const currency  = root.dataset.ulbmCurrency || 'USD';
	const suffix    = root.dataset.ulbmPriceSuffix || '/night';
	const unitLabel = root.dataset.ulbmUnitLabel || 'nights';
	const showEnd   = root.dataset.ulbmShowEndDate !== '0';
	const checkInT  = root.dataset.ulbmCheckInTime || '14:00';
	const checkOutT = root.dataset.ulbmCheckOutTime || '11:00';

	const nightsLine  = form.querySelector( '.ulbm-price-line--nights .ulbm-price-line-label' );
	const nightsVal   = form.querySelector( '.ulbm-price-line--nights .ulbm-price-line-value' );
	const cleaningVal = form.querySelector( '.ulbm-price-line--cleaning .ulbm-price-line-value' );
	const serviceVal  = form.querySelector( '.ulbm-price-line--service .ulbm-price-line-value' );
	const totalVal    = form.querySelector( '.ulbm-price-total-value' );

	function fmtMoney( amount ) {
		return currency + ' ' + Math.round( amount ).toLocaleString();
	}

	function fmtDateLabel( iso ) {
		if ( ! iso ) return '';
		const d = new Date( iso + 'T12:00:00' );
		return d.toLocaleDateString( undefined, { month: 'long', day: 'numeric', year: 'numeric' } );
	}

	function toIsoDate( date ) {
		const y = date.getFullYear();
		const m = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const d = String( date.getDate() ).padStart( 2, '0' );
		return y + '-' + m + '-' + d;
	}

	function setDefaults() {
		const today = new Date();
		const inDate = new Date( today );
		inDate.setDate( inDate.getDate() + 5 );
		const outDate = new Date( inDate );
		if ( showEnd ) {
			outDate.setDate( outDate.getDate() + 5 );
		}
		checkinEl.min = toIsoDate( today );
		checkinEl.value = toIsoDate( inDate );
		if ( checkoutEl ) {
			checkoutEl.min = toIsoDate( inDate );
			checkoutEl.value = toIsoDate( showEnd ? outDate : inDate );
		}
		updateBreakdown();
	}

	function syncHiddenDates() {
		if ( startHidden && checkinEl.value ) {
			startHidden.value = checkinEl.value + 'T' + checkInT;
		}
		if ( endHidden ) {
			const endDate = ( showEnd && checkoutEl?.value ) ? checkoutEl.value : checkinEl.value;
			if ( endDate ) {
				endHidden.value = endDate + 'T' + checkOutT;
			}
		}
	}

	function updateBreakdown() {
		if ( ! showEnd && checkoutEl && checkinEl.value ) {
			checkoutEl.value = checkinEl.value;
		}
		syncHiddenDates();
		const inD  = checkinEl.value ? new Date( checkinEl.value + 'T12:00:00' ) : null;
		const outD = ( showEnd && checkoutEl?.value ) ? new Date( checkoutEl.value + 'T12:00:00' ) : inD;
		let units = 1;
		if ( showEnd && inD && outD && outD > inD ) {
			units = Math.max( 1, Math.round( ( outD - inD ) / 86400000 ) );
		}
		const subtotal = nightly * units;
		if ( ! serviceFee && subtotal > 0 ) {
			serviceFee = Math.round( subtotal * 0.05 );
		}
		const total = subtotal + cleaning + serviceFee;

		if ( nightsLine ) {
			nightsLine.textContent = fmtMoney( nightly ) + ' x ' + units + ' ' + unitLabel;
		}
		if ( nightsVal ) nightsVal.textContent = fmtMoney( subtotal );
		if ( cleaningVal ) cleaningVal.textContent = fmtMoney( cleaning );
		if ( serviceVal ) serviceVal.textContent = fmtMoney( serviceFee );
		if ( totalVal ) totalVal.textContent = fmtMoney( total );
		if ( baseHidden ) baseHidden.value = String( total );
	}

	checkinEl.addEventListener( 'change', () => {
		if ( checkoutEl ) {
			checkoutEl.min = checkinEl.value;
			if ( showEnd && checkoutEl.value && checkoutEl.value <= checkinEl.value ) {
				const d = new Date( checkinEl.value + 'T12:00:00' );
				d.setDate( d.getDate() + 1 );
				checkoutEl.value = toIsoDate( d );
			}
		}
		updateBreakdown();
	} );
	checkoutEl?.addEventListener( 'change', updateBreakdown );
	form.querySelector( '.ulbm-mp-guests' )?.addEventListener( 'change', updateBreakdown );

	if ( ! checkinEl.value ) {
		setDefaults();
	} else {
		updateBreakdown();
	}
}

/* --- GRID AJAX FILTERS -------------------------------------- */
function initGridFilters() {
	document.querySelectorAll( '.ulbm-listing-grid' ).forEach( ( grid ) => {
		const hasPublic = typeof ulbmPublic !== 'undefined';

		const resultsEl  = grid.querySelector( '.ulbm-grid-results' );
		const countEl    = grid.querySelector( '.ulbm-grid-count' );
		const spinnerEl  = grid.querySelector( '.ulbm-grid-spinner' );
		const paginEl    = grid.querySelector( '.ulbm-grid-pagination' );
		const prevBtn    = grid.querySelector( '.ulbm-grid-prev' );
		const nextBtn    = grid.querySelector( '.ulbm-grid-next' );
		const pageInfo   = grid.querySelector( '.ulbm-grid-page-info' );
		const filterBtn  = grid.querySelector( '.ulbm-filter-submit' );
		const resetBtn   = grid.querySelector( '.ulbm-filter-reset' );
		const sortHidden = grid.querySelector( '.ulbm-filter-sort' );
		const sortBtns   = grid.querySelectorAll( '.ulbm-sort-btn' );

		if ( ! resultsEl ) return;

		function getActiveSort() {
			const active = grid.querySelector( '.ulbm-sort-btn.active' );
			return active ? ( active.dataset.sort || 'date' ) : 'date';
		}

		function setActiveSort( sortKey ) {
			sortBtns.forEach( ( btn ) => {
				const on = btn.dataset.sort === sortKey;
				btn.classList.toggle( 'active', on );
				btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			if ( sortHidden ) {
				sortHidden.value = sortKey;
			}
		}

		const viewToggleBtns = grid.querySelectorAll( '.ulbm-view-toggle-btn' );
		const storageKey     = 'ulbm_grid_view_' + ( grid.id || 'default' );

		function setGridView( view ) {
			const mode = view === 'list' ? 'list' : 'grid';
			resultsEl.classList.remove( 'ulbm-view-grid', 'ulbm-view-list' );
			resultsEl.classList.add( mode === 'list' ? 'ulbm-view-list' : 'ulbm-view-grid' );
			viewToggleBtns.forEach( ( btn ) => {
				const active = btn.dataset.view === mode;
				btn.classList.toggle( 'active', active );
				btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
			try {
				localStorage.setItem( storageKey, mode );
			} catch {
				// Ignore storage errors (private browsing).
			}
		}

		if ( viewToggleBtns.length ) {
			let savedView = 'grid';
			try {
				savedView = localStorage.getItem( storageKey ) || 'grid';
			} catch {
				savedView = 'grid';
			}
			setGridView( savedView );
			viewToggleBtns.forEach( ( btn ) => {
				btn.addEventListener( 'click', () => setGridView( btn.dataset.view ) );
			} );
		}

		let currentPage = 1;
		let totalPages  = paginEl ? parseInt( paginEl.dataset.pages || '1', 10 ) : 1;
		const perPage   = parseInt( grid.dataset.perPage || '12', 10 );
		const columns   = parseInt( grid.dataset.columns || '3', 10 );
		const baseType  = grid.dataset.type || '';
		const i18n      = ( typeof ulbmPublic !== 'undefined' && ulbmPublic.i18n ) ? ulbmPublic.i18n : {};

		function formatCount( data ) {
			const total = data.count || 0;
			if ( total <= 0 ) {
				return i18n.noProperties || 'No properties found';
			}
			const start = data.showing_start || 1;
			const end   = data.showing_end || Math.min( perPage, total );
			const tpl   = i18n.showingCount || 'Showing %1$d\u2013%2$d of %3$d properties';
			return tpl
				.replace( '%1$d', String( start ) )
				.replace( '%2$d', String( end ) )
				.replace( '%3$d', String( total ) );
		}


		function getFilters() {
			const sortVal = getActiveSort();
			if ( sortHidden ) {
				sortHidden.value = sortVal;
			}
			return {
				keyword:   ( grid.querySelector( '.ulbm-filter-keyword' ) || {} ).value || '',
				type:      baseType || ( grid.querySelector( '.ulbm-filter-type' ) || {} ).value || '',
				min_price: ( grid.querySelector( '.ulbm-filter-min-price' ) || {} ).value || '',
				max_price: ( grid.querySelector( '.ulbm-filter-max-price' ) || {} ).value || '',
				guests:    ( grid.querySelector( '.ulbm-filter-guests' ) || {} ).value || '',
				sort:      sortVal || 'date',
			};
		}

		async function loadPage( page ) {
			if ( ! hasPublic ) {
				return;
			}

			if ( spinnerEl ) spinnerEl.classList.remove( 'd-none' );
			if ( filterBtn ) filterBtn.disabled = true;

			const filters = getFilters();
			const body = new URLSearchParams();
			body.append( 'action', 'ulbm_grid_filter' );
			body.append( 'nonce', ulbmPublic.bookingNonce || '' );
			body.append( 'page', String( page ) );
			body.append( 'per_page', String( perPage ) );
			body.append( 'columns', String( columns ) );
			Object.keys( filters ).forEach( ( k ) => { if ( filters[ k ] ) body.append( k, filters[ k ] ); } );

			try {
				const res = await fetch( ulbmPublic.ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					credentials: 'same-origin',
					body,
				} );
				const json = await res.json();
				if ( json && json.success && json.data ) {
					resultsEl.innerHTML = json.data.html;
					totalPages  = json.data.pages || 1;
					currentPage = json.data.current || 1;
					if ( countEl ) countEl.textContent = formatCount( json.data );
					updatePagination();
					initFavorites( grid );
				} else {
					const msg = i18n.sessionExpired || 'Session expired. Please refresh the page and try again.';
					resultsEl.innerHTML = '<div class="col-12"><p class="text-muted text-center py-4">' + msg + '</p></div>';
				}
			} catch {
				const msg = i18n.filterFailed || 'Filter request failed.';
				resultsEl.innerHTML = '<div class="col-12"><p class="text-danger text-center py-4">' + msg + '</p></div>';
			} finally {
				if ( spinnerEl ) spinnerEl.classList.add( 'd-none' );
				if ( filterBtn ) filterBtn.disabled = false;
			}
		}

		function updatePagination() {
			if ( ! paginEl ) return;
			if ( totalPages <= 1 ) {
				paginEl.classList.add( 'd-none' );
				return;
			}
			paginEl.classList.remove( 'd-none' );
			if ( prevBtn ) prevBtn.disabled = currentPage <= 1;
			if ( nextBtn ) nextBtn.disabled = currentPage >= totalPages;
			if ( pageInfo ) pageInfo.textContent = String( currentPage );
		}

		if ( filterBtn ) {
			filterBtn.addEventListener( 'click', () => { currentPage = 1; loadPage( 1 ); } );
		}

		grid.querySelectorAll( '.ulbm-grid-filters input' ).forEach( ( inp ) => {
			inp.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					currentPage = 1;
					loadPage( 1 );
				}
			} );
		} );

		sortBtns.forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				setActiveSort( btn.dataset.sort || 'date' );
				currentPage = 1;
				loadPage( 1 );
			} );
		} );

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', () => {
				grid.querySelectorAll( '.ulbm-grid-filters input:not([type="hidden"])' ).forEach( ( i ) => { i.value = ''; } );
				grid.querySelectorAll( '.ulbm-grid-filters select' ).forEach( ( s ) => { s.selectedIndex = 0; } );
				setActiveSort( 'date' );
				currentPage = 1;
				loadPage( 1 );
			} );
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', () => {
				if ( currentPage > 1 ) {
					loadPage( currentPage - 1 );
				}
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', () => {
				if ( currentPage < totalPages ) {
					loadPage( currentPage + 1 );
				}
			} );
		}
	} );
}

/* --- LISTING GALLERY (MOSAIC + LEGACY SLIDER) + LIGHTBOX --- */
function initGallerySlider() {
	document.querySelectorAll( '.ulbm-gallery-mosaic-wrap, .ulbm-hero-slider' ).forEach( ( wrap ) => {
		const modalEl = wrap.querySelector( '.ulbm-gallery-lightbox' );
		const dataEl  = wrap.querySelector( '.ulbm-gallery-data' );
		const carouselEl = wrap.querySelector( '.ulbm-main-carousel' );

		const images = [];
		if ( dataEl ) {
			dataEl.querySelectorAll( 'img' ).forEach( ( img ) => {
				images.push( {
					full: img.dataset.full || img.src,
					large: img.src,
					alt: img.alt || '',
				} );
			} );
		} else if ( carouselEl ) {
			carouselEl.querySelectorAll( '.carousel-item .ulbm-gallery-main-img' ).forEach( ( img ) => {
				images.push( {
					full: img.dataset.full || img.src,
					large: img.src,
					alt: img.alt || '',
				} );
			} );
		}

		if ( ! images.length ) return;

		let currentIndex = 0;
		const counterEl = wrap.querySelector( '.ulbm-gallery-current' );
		const thumbs = wrap.querySelectorAll( '.ulbm-gallery-thumb' );
		const viewAllBtn = wrap.querySelector( '.ulbm-gallery-view-all, .ulbm-gallery-view-photos' );
		const openBtns = wrap.querySelectorAll( '.ulbm-gallery-open' );

		function setActiveThumb( index ) {
			thumbs.forEach( ( t, i ) => t.classList.toggle( 'active', i === index ) );
			if ( counterEl ) counterEl.textContent = String( index + 1 );
			currentIndex = index;
		}

		if ( carouselEl ) {
			carouselEl.addEventListener( 'slid.bs.carousel', ( e ) => {
				setActiveThumb( e.to );
			} );

			thumbs.forEach( ( thumb ) => {
				thumb.addEventListener( 'click', () => {
					const idx = parseInt( thumb.dataset.index || '0', 10 );
					setActiveThumb( idx );
				} );
			} );
		}

		if ( ! modalEl || typeof bootstrap === 'undefined' ) {
			return;
		}

		const modal = bootstrap.Modal.getOrCreateInstance( modalEl );
		const lightboxImg = modalEl.querySelector( '.ulbm-lightbox-img' );
		const lightboxCounter = modalEl.querySelector( '.ulbm-lightbox-counter' );
		const lightboxThumbs = modalEl.querySelectorAll( '.ulbm-lightbox-thumb' );
		const prevBtn = modalEl.querySelector( '.ulbm-lightbox-prev' );
		const nextBtn = modalEl.querySelector( '.ulbm-lightbox-next' );

		function showLightbox( index ) {
			const img = images[ index ];
			if ( ! img || ! lightboxImg ) return;
			currentIndex = index;
			lightboxImg.src = img.full;
			lightboxImg.alt = img.alt;
			if ( lightboxCounter ) {
				lightboxCounter.textContent = ( index + 1 ) + ' / ' + images.length;
			}
			lightboxThumbs.forEach( ( t, i ) => t.classList.toggle( 'active', i === index ) );
			const activeThumb = lightboxThumbs[ index ];
			if ( activeThumb ) activeThumb.scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'center' } );
		}

		function openLightbox( index ) {
			showLightbox( index );
			modal.show();
		}

		openBtns.forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				openLightbox( parseInt( btn.dataset.index || '0', 10 ) );
			} );
		} );

		if ( viewAllBtn ) {
			viewAllBtn.addEventListener( 'click', () => openLightbox( currentIndex ) );
		}

		lightboxThumbs.forEach( ( thumb ) => {
			thumb.addEventListener( 'click', () => {
				showLightbox( parseInt( thumb.dataset.index || '0', 10 ) );
			} );
		} );

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', () => {
				showLightbox( ( currentIndex - 1 + images.length ) % images.length );
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', () => {
				showLightbox( ( currentIndex + 1 ) % images.length );
			} );
		}

		modalEl.addEventListener( 'keydown', ( e ) => {
			if ( ! modalEl.classList.contains( 'show' ) ) return;
			if ( e.key === 'ArrowLeft' ) {
				e.preventDefault();
				showLightbox( ( currentIndex - 1 + images.length ) % images.length );
			} else if ( e.key === 'ArrowRight' ) {
				e.preventDefault();
				showLightbox( ( currentIndex + 1 ) % images.length );
			}
		} );
	} );
}

/* --- FAVORITES / WISHLIST (localStorage) -------------------- */
function initFavorites( scope ) {
	const root = scope || document;
	const storageKey = 'ulbm_favorites';
	let favorites = [];

	try {
		favorites = JSON.parse( localStorage.getItem( storageKey ) || '[]' );
		if ( ! Array.isArray( favorites ) ) favorites = [];
	} catch {
		favorites = [];
	}

	function isFavorite( id ) {
		return favorites.includes( String( id ) );
	}

	function save() {
		localStorage.setItem( storageKey, JSON.stringify( favorites ) );
	}

	function toggleFavorite( id ) {
		const key = String( id );
		if ( isFavorite( key ) ) {
			favorites = favorites.filter( ( f ) => f !== key );
		} else {
			favorites.push( key );
		}
		save();
	}

	function syncButton( btn ) {
		const id = btn.dataset.id;
		if ( ! id ) return;
		const active = isFavorite( id );
		btn.classList.toggle( 'active', active );
		btn.classList.toggle( 'is-active', active );
		const icon = btn.querySelector( 'i' );
		if ( icon ) {
			icon.classList.toggle( 'bi-heart', ! active );
			icon.classList.toggle( 'bi-heart-fill', active );
		}
	}

	root.querySelectorAll( '.ulbm-card-wishlist, .ulbm-favorite-btn' ).forEach( ( btn ) => {
		if ( btn.dataset.ulbmFavBound ) return;
		btn.dataset.ulbmFavBound = '1';
		syncButton( btn );
		btn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			toggleFavorite( btn.dataset.id );
			syncButton( btn );
		} );
	} );
}

/* --- PARTNER PORTAL ----------------------------------------- */
function initVendorPortal() {
	if ( typeof ulbmPublic === 'undefined' ) return;

	function showVendorFeedback( el, msg, isError ) {
		if ( ! el ) return;
		el.classList.remove( 'd-none', 'alert-success', 'alert-danger' );
		el.classList.add( isError ? 'alert-danger' : 'alert-success' );
		el.textContent = msg;
	}

	const regForm = document.getElementById( 'ulbm-vendor-register-form' );
	if ( regForm ) {
		regForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const pass = regForm.querySelector( '[name="password"]' )?.value || '';
			const pass2 = regForm.querySelector( '[name="password_confirm"]' )?.value || '';
			const fb = regForm.querySelector( '.ulbm-vendor-feedback' );
			if ( pass !== pass2 ) {
				showVendorFeedback( fb, 'Passwords do not match.', true );
				return;
			}
			const fd = new FormData( regForm );
			fd.append( 'action', 'ulbm_vendor_register' );
			fd.append( 'nonce', ulbmPublic.bookingNonce || '' );
			const btn = regForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( ulbmPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
				const json = await res.json();
				if ( json?.success ) {
					showVendorFeedback( fb, json.data.message || 'Registered!', false );
					if ( json.data.redirect ) window.location.href = json.data.redirect;
				} else {
					showVendorFeedback( fb, json?.data?.message || 'Registration failed.', true );
				}
			} catch {
				showVendorFeedback( fb, 'Network error.', true );
			} finally {
				if ( btn ) btn.disabled = false;
			}
		} );
	}

	const loginForm = document.getElementById( 'ulbm-vendor-login-form' );
	if ( loginForm ) {
		loginForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = loginForm.querySelector( '.ulbm-vendor-feedback' );
			const fd = new FormData( loginForm );
			fd.append( 'action', 'ulbm_vendor_login' );
			fd.append( 'nonce', ulbmPublic.bookingNonce || '' );
			const btn = loginForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( ulbmPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
				const json = await res.json();
				if ( json?.success ) {
					window.location.href = json.data.redirect || '/';
				} else {
					showVendorFeedback( fb, json?.data?.message || 'Login failed.', true );
				}
			} catch {
				showVendorFeedback( fb, 'Network error.', true );
			} finally {
				if ( btn ) btn.disabled = false;
			}
		} );
	}

	const becomeForm = document.getElementById( 'ulbm-vendor-become-partner-form' );
	if ( becomeForm ) {
		becomeForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = becomeForm.querySelector( '.ulbm-vendor-feedback' );
			const fd = new FormData( becomeForm );
			fd.append( 'action', 'ulbm_vendor_become_partner' );
			fd.append( 'nonce', ulbmPublic.bookingNonce || '' );
			const btn = becomeForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( ulbmPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
				const json = await res.json();
				if ( json?.success ) {
					showVendorFeedback( fb, json.data.message || 'Partner access enabled!', false );
					if ( json.data.redirect ) window.location.href = json.data.redirect;
				} else {
					showVendorFeedback( fb, json?.data?.message || 'Request failed.', true );
				}
			} catch {
				showVendorFeedback( fb, 'Network error.', true );
			} finally {
				if ( btn ) btn.disabled = false;
			}
		} );
	}

	const listingForm = document.getElementById( 'ulbm-vendor-listing-form' );
	if ( listingForm ) {
		listingForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = listingForm.querySelector( '.ulbm-vendor-feedback' );
			const fd = new FormData( listingForm );
			fd.append( 'action', 'ulbm_vendor_save_listing' );
			fd.append( 'nonce', ulbmPublic.bookingNonce || '' );
			const btn = listingForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( ulbmPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
				const json = await res.json();
				if ( json?.success ) {
					showVendorFeedback( fb, json.data.message || 'Saved!', false );
					setTimeout( () => { window.location.href = window.location.pathname + '?ulbm_tab=listings'; }, 1200 );
				} else {
					showVendorFeedback( fb, json?.data?.message || 'Save failed.', true );
				}
			} catch {
				showVendorFeedback( fb, 'Network error.', true );
			} finally {
				if ( btn ) btn.disabled = false;
			}
		} );
	}

	document.querySelectorAll( '.ulbm-vendor-delete-listing' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', async () => {
			if ( ! window.confirm( 'Delete this listing permanently?' ) ) return;
			const id = btn.dataset.id;
			const body = new URLSearchParams();
			body.append( 'action', 'ulbm_vendor_delete_listing' );
			body.append( 'nonce', ulbmPublic.bookingNonce || '' );
			body.append( 'post_id', id );
			try {
				const res = await fetch( ulbmPublic.ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body,
					credentials: 'same-origin',
				} );
				const json = await res.json();
				if ( json?.success ) {
					btn.closest( 'tr' )?.remove();
				} else {
					window.alert( json?.data?.message || 'Delete failed.' );
				}
			} catch {
				window.alert( 'Network error.' );
			}
		} );
	} );
}

/* --- LISTING REVIEWS ---------------------------------------- */
function initListingReviews() {
	document.querySelectorAll( '.ulbm-review-form' ).forEach( ( form ) => {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			if ( typeof ulbmPublic === 'undefined' ) return;

			const listingId = parseInt( form.dataset.listingId || '0', 10 );
			const feedback  = form.querySelector( '.ulbm-review-feedback' );
			const btn       = form.querySelector( '.ulbm-review-submit' );

			const body = new URLSearchParams();
			body.append( 'action', 'ulbm_submit_review' );
			body.append( 'nonce', ulbmPublic.bookingNonce || '' );
			body.append( 'listing_id', String( listingId ) );
			body.append( 'author_name', ( form.querySelector( '.ulbm-review-name' ) || {} ).value || '' );
			body.append( 'author_email', ( form.querySelector( '.ulbm-review-email' ) || {} ).value || '' );
			body.append( 'rating', ( form.querySelector( '.ulbm-review-rating' ) || {} ).value || '5' );
			body.append( 'content', ( form.querySelector( '.ulbm-review-content' ) || {} ).value || '' );

			if ( btn ) btn.disabled = true;
			if ( feedback ) {
				feedback.classList.add( 'd-none' );
			}

			try {
				const res = await fetch( ulbmPublic.ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					credentials: 'same-origin',
					body,
				} );
				const json = await res.json();
				if ( feedback ) {
					feedback.classList.remove( 'd-none', 'alert-success', 'alert-danger' );
					feedback.classList.add( json?.success ? 'alert-success' : 'alert-danger' );
					feedback.textContent = json?.data?.message || ( json?.success ? 'Submitted.' : 'Request failed.' );
				}
				if ( json?.success ) {
					form.reset();
					setTimeout( () => window.location.reload(), 1500 );
				}
			} catch {
				if ( feedback ) {
					feedback.classList.remove( 'd-none', 'alert-success' );
					feedback.classList.add( 'alert-danger' );
					feedback.textContent = 'Network error.';
				}
			} finally {
				if ( btn ) btn.disabled = false;
			}
		} );
	} );
}
