/**
 * Public bundle — booking form + grid AJAX filters.
 */
import './public.scss';

document.addEventListener( 'DOMContentLoaded', () => {
	initBookingForm();
	initGridFilters();
	initVendorPortal();
	initGallerySlider();
	initFavorites();
	initListingReviews();
} );

/* ─── BOOKING FORM ─────────────────────────────────────────── */
function initBookingForm() {
	const form = document.getElementById( 'fbs-booking-form-fields' );
	if ( ! form || typeof fbsPublic === 'undefined' ) return;

	const root = form.closest( '.fbs-booking-form' );
	const typeId = root ? parseInt( root.dataset.fbsTypeId || '0', 10 ) : 0;
	const listingId = root ? parseInt( root.dataset.fbsListingId || '0', 10 ) : 0;
	const feedback = form.querySelector( '.fbs-form-feedback' );
	const isMarketplace = root && root.classList.contains( 'fbs-booking-form--marketplace' );
	const contactPanel = form.querySelector( '.fbs-booking-contact-panel' );

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
		'fbs_checkin', 'fbs_checkout', 'guests_count',
	] );

	form.addEventListener( 'submit', async ( e ) => {
		e.preventDefault();

		if ( isMarketplace && contactPanel && contactPanel.classList.contains( 'd-none' ) ) {
			if ( ! form.checkValidity() ) {
				form.classList.add( 'was-validated' );
				return;
			}
			contactPanel.classList.remove( 'd-none' );
			contactPanel.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			return;
		}

		if ( ! form.checkValidity() ) { form.classList.add( 'was-validated' ); return; }

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
		body.append( 'action', 'fbs_create_booking' );
		body.append( 'nonce', fbsPublic.bookingNonce || '' );
		body.append( 'booking_type_id', String( typeId > 0 ? typeId : 1 ) );
		body.append( 'start', String( fd.get( 'start' ) || '' ) );
		body.append( 'end', String( fd.get( 'end' ) || '' ) );
		body.append( 'base_price', String( fd.get( 'base_price' ) || '0' ) );
		body.append( 'currency', root?.dataset.fbsCurrency || 'USD' );
		body.append( 'customer_email', String( fd.get( 'customer_email' ) || '' ) );
		body.append( 'customer_phone', String( fd.get( 'customer_phone' ) || '' ) );
		body.append( 'customer_first_name', String( fd.get( 'customer_first_name' ) || '' ) );
		body.append( 'customer_last_name', String( fd.get( 'customer_last_name' ) || '' ) );
		body.append( 'form_values_json', JSON.stringify( formValues ) );
		const lid = parseInt( fd.get( 'listing_id' ) || listingId || '0', 10 );
		if ( lid > 0 ) body.append( 'listing_id', String( lid ) );

		try {
			const res = await fetch( fbsPublic.ajaxUrl, {
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
					contactPanel?.classList.add( 'd-none' );
					initMarketplaceBooking( form, root );
				}
			} else {
				const msg = json?.data?.errors?.join?.( ' | ' ) || json?.data?.message || 'Booking failed.';
				showFeedback( msg, true );
			}
		} catch {
			showFeedback( 'Network error. Please try again.', true );
		} finally {
			if ( btn ) btn.disabled = false;
		}
	} );
}

function initMarketplaceBooking( form, root ) {
	if ( ! form || ! root ) return;

	const checkinEl  = form.querySelector( '.fbs-mp-checkin' );
	const checkoutEl = form.querySelector( '.fbs-mp-checkout' );
	const startHidden = form.querySelector( '#fbs-start' );
	const endHidden   = form.querySelector( '#fbs-end' );
	const baseHidden  = form.querySelector( '[name="base_price"]' );
	if ( ! checkinEl || ! checkoutEl ) return;

	const nightly   = parseFloat( root.dataset.fbsNightly || '0' );
	const cleaning  = parseFloat( root.dataset.fbsCleaning || '0' );
	let serviceFee  = parseFloat( root.dataset.fbsService || '0' );
	const currency  = root.dataset.fbsCurrency || 'USD';
	const suffix    = root.dataset.fbsPriceSuffix || '/night';
	const checkInT  = root.dataset.fbsCheckInTime || '14:00';
	const checkOutT = root.dataset.fbsCheckOutTime || '11:00';

	const nightsLine  = form.querySelector( '.fbs-price-line--nights .fbs-price-line-label' );
	const nightsVal   = form.querySelector( '.fbs-price-line--nights .fbs-price-line-value' );
	const cleaningVal = form.querySelector( '.fbs-price-line--cleaning .fbs-price-line-value' );
	const serviceVal  = form.querySelector( '.fbs-price-line--service .fbs-price-line-value' );
	const totalVal    = form.querySelector( '.fbs-price-total-value' );

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
		outDate.setDate( outDate.getDate() + 5 );
		checkinEl.min = toIsoDate( today );
		checkinEl.value = toIsoDate( inDate );
		checkoutEl.min = toIsoDate( inDate );
		checkoutEl.value = toIsoDate( outDate );
		updateBreakdown();
	}

	function syncHiddenDates() {
		if ( startHidden && checkinEl.value ) {
			startHidden.value = checkinEl.value + 'T' + checkInT;
		}
		if ( endHidden && checkoutEl.value ) {
			endHidden.value = checkoutEl.value + 'T' + checkOutT;
		}
	}

	function updateBreakdown() {
		syncHiddenDates();
		const inD  = checkinEl.value ? new Date( checkinEl.value + 'T12:00:00' ) : null;
		const outD = checkoutEl.value ? new Date( checkoutEl.value + 'T12:00:00' ) : null;
		let nights = 1;
		if ( inD && outD && outD > inD ) {
			nights = Math.max( 1, Math.round( ( outD - inD ) / 86400000 ) );
		}
		const subtotal = nightly * nights;
		if ( ! serviceFee && subtotal > 0 ) {
			serviceFee = Math.round( subtotal * 0.05 );
		}
		const total = subtotal + cleaning + serviceFee;

		if ( nightsLine ) {
			nightsLine.textContent = fmtMoney( nightly ) + ' x ' + nights + ' nights';
		}
		if ( nightsVal ) nightsVal.textContent = fmtMoney( subtotal );
		if ( cleaningVal ) cleaningVal.textContent = fmtMoney( cleaning );
		if ( serviceVal ) serviceVal.textContent = fmtMoney( serviceFee );
		if ( totalVal ) totalVal.textContent = fmtMoney( total );
		if ( baseHidden ) baseHidden.value = String( total );
	}

	checkinEl.addEventListener( 'change', () => {
		checkoutEl.min = checkinEl.value;
		if ( checkoutEl.value && checkoutEl.value <= checkinEl.value ) {
			const d = new Date( checkinEl.value + 'T12:00:00' );
			d.setDate( d.getDate() + 1 );
			checkoutEl.value = toIsoDate( d );
		}
		updateBreakdown();
	} );
	checkoutEl.addEventListener( 'change', updateBreakdown );
	form.querySelector( '.fbs-mp-guests' )?.addEventListener( 'change', updateBreakdown );

	if ( ! checkinEl.value ) {
		setDefaults();
	} else {
		updateBreakdown();
	}
}

/* ─── GRID AJAX FILTERS ────────────────────────────────────── */
function initGridFilters() {
	document.querySelectorAll( '.fbs-listing-grid' ).forEach( ( grid ) => {
		if ( typeof fbsPublic === 'undefined' ) return;

		const resultsEl  = grid.querySelector( '.fbs-grid-results' );
		const countEl    = grid.querySelector( '.fbs-grid-count' );
		const spinnerEl  = grid.querySelector( '.fbs-grid-spinner' );
		const paginEl    = grid.querySelector( '.fbs-grid-pagination' );
		const prevBtn    = grid.querySelector( '.fbs-grid-prev' );
		const nextBtn    = grid.querySelector( '.fbs-grid-next' );
		const pageInfo   = grid.querySelector( '.fbs-grid-page-info' );
		const filterBtn  = grid.querySelector( '.fbs-filter-submit' );
		const resetBtn   = grid.querySelector( '.fbs-filter-reset' );
		const sortSelect = grid.querySelector( '.fbs-filter-sort-select' );
		const sortHidden = grid.querySelector( '.fbs-filter-sort' );

		if ( ! resultsEl ) return;

		let currentPage = 1;
		let totalPages  = paginEl ? parseInt( paginEl.dataset.pages || '1', 10 ) : 1;
		const perPage   = parseInt( grid.dataset.perPage || '12', 10 );
		const baseType  = grid.dataset.type || '';

		function formatCount( data ) {
			const total = data.count || 0;
			if ( total <= 0 ) {
				return 'No properties found';
			}
			const start = data.showing_start || 1;
			const end   = data.showing_end || Math.min( perPage, total );
			return 'Showing ' + start + '–' + end + ' of ' + total + ' properties';
		}

		function getFilters() {
			const sortVal = sortSelect ? sortSelect.value : ( sortHidden ? sortHidden.value : 'date' );
			if ( sortHidden ) sortHidden.value = sortVal;
			return {
				keyword:   ( grid.querySelector( '.fbs-filter-keyword' ) || {} ).value || '',
				type:      baseType || ( grid.querySelector( '.fbs-filter-type' ) || {} ).value || '',
				min_price: ( grid.querySelector( '.fbs-filter-min-price' ) || {} ).value || '',
				max_price: ( grid.querySelector( '.fbs-filter-max-price' ) || {} ).value || '',
				guests:    ( grid.querySelector( '.fbs-filter-guests' ) || {} ).value || '',
				sort:      sortVal || 'date',
			};
		}

		async function loadPage( page ) {
			if ( spinnerEl ) spinnerEl.classList.remove( 'd-none' );
			if ( filterBtn ) filterBtn.disabled = true;

			const filters = getFilters();
			const body = new URLSearchParams();
			body.append( 'action', 'fbs_grid_filter' );
			body.append( 'nonce', fbsPublic.bookingNonce || '' );
			body.append( 'page', String( page ) );
			body.append( 'per_page', String( perPage ) );
			Object.keys( filters ).forEach( ( k ) => { if ( filters[ k ] ) body.append( k, filters[ k ] ); } );

			try {
				const res = await fetch( fbsPublic.ajaxUrl, {
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
				}
			} catch {
				resultsEl.innerHTML = '<div class="col-12"><p class="text-danger">Filter request failed.</p></div>';
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

		grid.querySelectorAll( '.fbs-grid-filters input' ).forEach( ( inp ) => {
			inp.addEventListener( 'keydown', ( e ) => {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
					currentPage = 1;
					loadPage( 1 );
				}
			} );
		} );

		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', () => {
				currentPage = 1;
				loadPage( 1 );
			} );
		}

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', () => {
				grid.querySelectorAll( '.fbs-grid-filters input' ).forEach( ( i ) => { i.value = ''; } );
				grid.querySelectorAll( '.fbs-grid-filters select' ).forEach( ( s ) => { s.selectedIndex = 0; } );
				if ( sortSelect ) sortSelect.selectedIndex = 0;
				currentPage = 1;
				loadPage( 1 );
			} );
		}

		if ( prevBtn ) prevBtn.addEventListener( 'click', () => { if ( currentPage > 1 ) loadPage( --currentPage ); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', () => { if ( currentPage < totalPages ) loadPage( ++currentPage ); } );
	} );
}

/* ─── LISTING GALLERY (MOSAIC + LEGACY SLIDER) + LIGHTBOX ─── */
function initGallerySlider() {
	document.querySelectorAll( '.fbs-gallery-mosaic-wrap, .fbs-hero-slider' ).forEach( ( wrap ) => {
		const modalEl = wrap.querySelector( '.fbs-gallery-lightbox' );
		const dataEl  = wrap.querySelector( '.fbs-gallery-data' );
		const carouselEl = wrap.querySelector( '.fbs-main-carousel' );

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
			carouselEl.querySelectorAll( '.carousel-item .fbs-gallery-main-img' ).forEach( ( img ) => {
				images.push( {
					full: img.dataset.full || img.src,
					large: img.src,
					alt: img.alt || '',
				} );
			} );
		}

		if ( ! images.length ) return;

		let currentIndex = 0;
		const counterEl = wrap.querySelector( '.fbs-gallery-current' );
		const thumbs = wrap.querySelectorAll( '.fbs-gallery-thumb' );
		const viewAllBtn = wrap.querySelector( '.fbs-gallery-view-all, .fbs-gallery-view-photos' );
		const openBtns = wrap.querySelectorAll( '.fbs-gallery-open' );

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
		const lightboxImg = modalEl.querySelector( '.fbs-lightbox-img' );
		const lightboxCounter = modalEl.querySelector( '.fbs-lightbox-counter' );
		const lightboxThumbs = modalEl.querySelectorAll( '.fbs-lightbox-thumb' );
		const prevBtn = modalEl.querySelector( '.fbs-lightbox-prev' );
		const nextBtn = modalEl.querySelector( '.fbs-lightbox-next' );

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

/* ─── FAVORITES / WISHLIST (localStorage) ──────────────────── */
function initFavorites( scope ) {
	const root = scope || document;
	const storageKey = 'fbs_favorites';
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

	root.querySelectorAll( '.fbs-card-wishlist, .fbs-favorite-btn' ).forEach( ( btn ) => {
		if ( btn.dataset.fbsFavBound ) return;
		btn.dataset.fbsFavBound = '1';
		syncButton( btn );
		btn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			toggleFavorite( btn.dataset.id );
			syncButton( btn );
		} );
	} );
}

/* ─── PARTNER PORTAL ───────────────────────────────────────── */
function initVendorPortal() {
	if ( typeof fbsPublic === 'undefined' ) return;

	function showVendorFeedback( el, msg, isError ) {
		if ( ! el ) return;
		el.classList.remove( 'd-none', 'alert-success', 'alert-danger' );
		el.classList.add( isError ? 'alert-danger' : 'alert-success' );
		el.textContent = msg;
	}

	const regForm = document.getElementById( 'fbs-vendor-register-form' );
	if ( regForm ) {
		regForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const pass = regForm.querySelector( '[name="password"]' )?.value || '';
			const pass2 = regForm.querySelector( '[name="password_confirm"]' )?.value || '';
			const fb = regForm.querySelector( '.fbs-vendor-feedback' );
			if ( pass !== pass2 ) {
				showVendorFeedback( fb, 'Passwords do not match.', true );
				return;
			}
			const fd = new FormData( regForm );
			fd.append( 'action', 'fbs_vendor_register' );
			fd.append( 'nonce', fbsPublic.bookingNonce || '' );
			const btn = regForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( fbsPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
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

	const loginForm = document.getElementById( 'fbs-vendor-login-form' );
	if ( loginForm ) {
		loginForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = loginForm.querySelector( '.fbs-vendor-feedback' );
			const fd = new FormData( loginForm );
			fd.append( 'action', 'fbs_vendor_login' );
			fd.append( 'nonce', fbsPublic.bookingNonce || '' );
			const btn = loginForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( fbsPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
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

	const becomeForm = document.getElementById( 'fbs-vendor-become-partner-form' );
	if ( becomeForm ) {
		becomeForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = becomeForm.querySelector( '.fbs-vendor-feedback' );
			const fd = new FormData( becomeForm );
			fd.append( 'action', 'fbs_vendor_become_partner' );
			fd.append( 'nonce', fbsPublic.bookingNonce || '' );
			const btn = becomeForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( fbsPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
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

	const listingForm = document.getElementById( 'fbs-vendor-listing-form' );
	if ( listingForm ) {
		listingForm.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const fb = listingForm.querySelector( '.fbs-vendor-feedback' );
			const fd = new FormData( listingForm );
			fd.append( 'action', 'fbs_vendor_save_listing' );
			fd.append( 'nonce', fbsPublic.bookingNonce || '' );
			const btn = listingForm.querySelector( '[type="submit"]' );
			if ( btn ) btn.disabled = true;
			try {
				const res = await fetch( fbsPublic.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } );
				const json = await res.json();
				if ( json?.success ) {
					showVendorFeedback( fb, json.data.message || 'Saved!', false );
					setTimeout( () => { window.location.href = window.location.pathname + '?fbs_tab=listings'; }, 1200 );
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

	document.querySelectorAll( '.fbs-vendor-delete-listing' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', async () => {
			if ( ! window.confirm( 'Delete this listing permanently?' ) ) return;
			const id = btn.dataset.id;
			const body = new URLSearchParams();
			body.append( 'action', 'fbs_vendor_delete_listing' );
			body.append( 'nonce', fbsPublic.bookingNonce || '' );
			body.append( 'post_id', id );
			try {
				const res = await fetch( fbsPublic.ajaxUrl, {
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

/* ─── LISTING REVIEWS ──────────────────────────────────────── */
function initListingReviews() {
	document.querySelectorAll( '.fbs-review-form' ).forEach( ( form ) => {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			if ( typeof fbsPublic === 'undefined' ) return;

			const listingId = parseInt( form.dataset.listingId || '0', 10 );
			const feedback  = form.querySelector( '.fbs-review-feedback' );
			const btn       = form.querySelector( '.fbs-review-submit' );

			const body = new URLSearchParams();
			body.append( 'action', 'fbs_submit_review' );
			body.append( 'nonce', fbsPublic.bookingNonce || '' );
			body.append( 'listing_id', String( listingId ) );
			body.append( 'author_name', ( form.querySelector( '.fbs-review-name' ) || {} ).value || '' );
			body.append( 'author_email', ( form.querySelector( '.fbs-review-email' ) || {} ).value || '' );
			body.append( 'rating', ( form.querySelector( '.fbs-review-rating' ) || {} ).value || '5' );
			body.append( 'content', ( form.querySelector( '.fbs-review-content' ) || {} ).value || '' );

			if ( btn ) btn.disabled = true;
			if ( feedback ) {
				feedback.classList.add( 'd-none' );
			}

			try {
				const res = await fetch( fbsPublic.ajaxUrl, {
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
