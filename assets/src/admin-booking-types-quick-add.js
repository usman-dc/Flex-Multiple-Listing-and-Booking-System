/**
 * Quick-add booking types on admin booking types screen.
 */
( function () {
	function init() {
		const cbs = document.querySelectorAll( '.ulbm-quick-add-cb' );
		const btn = document.getElementById( 'ulbm-quick-add-btn' );
		const st  = document.getElementById( 'ulbm-quick-add-status' );
		if ( ! btn || ! cbs.length ) {
			return;
		}

		const cfg = typeof ulbmAdmin !== 'undefined' ? ulbmAdmin : {};
		const creating = cfg.quickAddCreating || 'Creating...';
		const failed   = cfg.quickAddFailed || 'Request failed.';

		cbs.forEach( function ( cb ) {
			cb.addEventListener( 'change', function () {
				btn.disabled = ! document.querySelector( '.ulbm-quick-add-cb:checked' );
			} );
		} );

		btn.addEventListener( 'click', function () {
			const sel = [];
			document.querySelectorAll( '.ulbm-quick-add-cb:checked' ).forEach( function ( c ) {
				sel.push( c.value );
			} );
			if ( ! sel.length ) {
				return;
			}
			btn.disabled = true;
			if ( st ) {
				st.textContent = creating;
			}
			const fd = new FormData();
			fd.append( 'action', 'ulbm_setup_finish' );
			fd.append( 'nonce', cfg.nonce || '' );
			sel.forEach( function ( v ) {
				fd.append( 'industries[]', v );
			} );
			const url = cfg.ajaxUrl || ( typeof ajaxurl !== 'undefined' ? ajaxurl : '' );
			fetch( url, { method: 'POST', body: fd, credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( res ) {
					if ( res && res.success ) {
						window.location.reload();
						return;
					}
					if ( st ) {
						st.textContent = ( res && res.data && res.data.message ) || 'Error';
					}
					btn.disabled = false;
				} )
				.catch( function () {
					if ( st ) {
						st.textContent = failed;
					}
					btn.disabled = false;
				} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
