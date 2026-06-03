/**
 * Admin bundle — dashboard pings, setup wizard, future SPA mounts.
 */
import './admin.scss';

( function ( $ ) {
	$( function () {
		$( '#fbs-wizard-finish' ).on( 'click', function () {
			if ( typeof fbsAdmin === 'undefined' ) {
				return;
			}
			const $btn = $( this );
			const $status = $( '#fbs-wizard-status' );
			const industries = [];
			$( '.fbs-industry-cb:checked' ).each( function () {
				industries.push( $( this ).val() );
			} );

			$btn.prop( 'disabled', true );
			$status.text( '' );

			$.post( fbsAdmin.ajaxUrl, {
				action: 'fbs_setup_finish',
				nonce: fbsAdmin.nonce,
				industries: industries,
			} )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.redirect ) {
						window.location.href = res.data.redirect;
						return;
					}
					$status.text(
						res && res.data && res.data.message
							? res.data.message
							: 'Setup failed.'
					);
				} )
				.fail( function () {
					$status.text( 'Request failed. Retry or check your connection.' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false );
				} );
		} );

		// Legacy single-step completion (older cached bundles).
		$( '#fbs-wizard-complete' ).on( 'click', function () {
			if ( typeof fbsAdmin === 'undefined' ) {
				return;
			}
			$.post( fbsAdmin.ajaxUrl, {
				action: 'fbs_setup_finish',
				nonce: fbsAdmin.nonce,
				industries: [],
			} ).done( function ( res ) {
				if ( res && res.success && res.data && res.data.redirect ) {
					window.location.href = res.data.redirect;
				}
			} );
		} );

		const $bookFeedback = $( '#fbs-bookings-feedback' );
		function showBookMsg( text, isError ) {
			if ( ! $bookFeedback.length ) {
				return;
			}
			$bookFeedback
				.removeClass( 'd-none alert-success alert-danger' )
				.addClass( isError ? 'alert-danger' : 'alert-success' )
				.text( text );
		}
		function fbsBookingBadgeTone( status ) {
			const s = String( status || '' ).toLowerCase();
			if ( [ 'confirmed', 'completed', 'approved' ].indexOf( s ) !== -1 ) {
				return 'success';
			}
			if ( [ 'cancelled', 'canceled', 'refunded', 'rejected' ].indexOf( s ) !== -1 ) {
				return 'danger';
			}
			if ( [ 'pending', 'hold', 'draft', 'on_hold' ].indexOf( s ) !== -1 ) {
				return 'warning';
			}
			return 'secondary';
		}
		function fbsPaymentBadgeTone( ps ) {
			const s = String( ps || '' ).toLowerCase();
			if ( s.indexOf( 'paid' ) !== -1 || s === 'captured' ) {
				return 'success';
			}
			if ( s.indexOf( 'fail' ) !== -1 || s.indexOf( 'declin' ) !== -1 ) {
				return 'danger';
			}
			if ( s === 'unpaid' || s === 'pending' ) {
				return 'warning';
			}
			return 'secondary';
		}

		$( document ).on( 'click', '.fbs-booking-action', function ( e ) {
			e.preventDefault();
			if ( typeof fbsAdmin === 'undefined' ) {
				return;
			}
			const $btn = $( this );
			const $row = $btn.closest( '[data-booking-id]' );
			const id = $row.data( 'booking-id' );
			const field = $btn.data( 'field' );
			const value = $btn.data( 'value' );
			if ( ! id || ! field || typeof value === 'undefined' ) {
				return;
			}
			const payload = {
				action: 'fbs_booking_update',
				nonce: fbsAdmin.nonce,
				booking_id: id,
				send_notification: $( '#fbs-bookings-notify' ).is( ':checked' ) ? '1' : '0',
			};
			if ( field === 'status' ) {
				payload.status = value;
			} else if ( field === 'payment_status' ) {
				payload.payment_status = value;
			} else {
				return;
			}
			$btn.prop( 'disabled', true );
			$.post( fbsAdmin.ajaxUrl, payload )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.booking ) {
						const b = res.data.booking;
						const st = fbsBookingBadgeTone( b.status );
						const pt = fbsPaymentBadgeTone( b.payment_status );
						$row
							.find( '.fbs-cell-status' )
							.attr( 'class', 'badge rounded-pill text-bg-' + st + ' fbs-cell-status' )
							.text( b.status );
						$row
							.find( '.fbs-cell-payment' )
							.attr( 'class', 'badge rounded-pill text-bg-' + pt + ' fbs-cell-payment' )
							.text( b.payment_status );
						$row.addClass( 'fbs-row-updated' );
						setTimeout( function () {
							$row.removeClass( 'fbs-row-updated' );
						}, 1400 );
						let msg = 'Booking updated.';
						if ( res.data.emailed ) {
							msg += ' Customer notified by email.';
						}
						showBookMsg( msg, false );
						return;
					}
					const err =
						res && res.data && res.data.message
							? res.data.message
							: 'Update failed.';
					showBookMsg( err, true );
				} )
				.fail( function ( xhr ) {
					let err = 'Request failed.';
					if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
						err = xhr.responseJSON.data.message;
					}
					showBookMsg( err, true );
				} )
				.always( function () {
					$btn.prop( 'disabled', false );
				} );
		} );

		/* ─── Demo content import / delete ─── */
		const $demoImport   = $( '#fbs-demo-import' );
		const $demoDelete   = $( '#fbs-demo-delete-all' );
		const $demoSpinner  = $( '#fbs-demo-spinner' );
		const $demoStatus   = $( '#fbs-demo-status' );
		const $demoProgress = $( '#fbs-demo-progress' );
		const $demoProgressWrap = $( '#fbs-demo-progress-wrap' );
		const $demoSelectAll = $( '#fbs-demo-select-all' );

		if ( $demoSelectAll.length ) {
			$demoSelectAll.on( 'change', function () {
				$( '.fbs-demo-type-cb' ).prop( 'checked', $( this ).is( ':checked' ) );
			} );
		}

		function demoShowStatus( msg, type ) {
			if ( ! $demoStatus.length ) return;
			$demoStatus.removeClass( 'd-none alert-info alert-success alert-danger' ).addClass( 'alert-' + ( type || 'info' ) ).html( msg );
		}

		function demoSetProgress( pct, label ) {
			if ( ! $demoProgress.length ) return;
			$demoProgress.css( 'width', pct + '%' ).text( label || ( pct + '%' ) );
		}

		if ( $demoImport.length ) {
			$demoImport.on( 'click', async function () {
				if ( typeof fbsAdmin === 'undefined' ) return;

				const ids = [];
				$( '.fbs-demo-type-cb:checked' ).each( function () {
					ids.push( parseInt( $( this ).val(), 10 ) );
				} );
				if ( ! ids.length ) {
					demoShowStatus( 'Select at least one booking type.', 'danger' );
					return;
				}

				const count = parseInt( $( '#fbs-demo-count' ).val(), 10 ) || 20;
				$demoImport.prop( 'disabled', true );
				$demoDelete.prop( 'disabled', true );
				$demoSpinner.removeClass( 'd-none' );
				$demoProgressWrap.removeClass( 'd-none' );
				demoShowStatus( 'Importing demo content…', 'info' );

				let done = 0;
				let totalCreated = 0;
				const total = ids.length;

				for ( const typeId of ids ) {
					try {
						const res = await $.post( fbsAdmin.ajaxUrl, {
							action: 'fbs_import_demo_content',
							nonce: fbsAdmin.nonce,
							booking_type_id: typeId,
							count: count,
						} );
						if ( res && res.success && res.data ) {
							totalCreated += res.data.created || 0;
							const tid = typeId;
							const $badge = $( '.fbs-demo-count-' + tid );
							if ( $badge.length ) {
								const cur = parseInt( $badge.text(), 10 ) || 0;
								$badge.text( cur + ( res.data.created || 0 ) );
							}
						} else {
							const err = res?.data?.message || 'Import failed for type #' + typeId;
							demoShowStatus( err, 'danger' );
						}
					} catch {
						demoShowStatus( 'Network error during import.', 'danger' );
						break;
					}
					done++;
					demoSetProgress( Math.round( ( done / total ) * 100 ), done + ' / ' + total );
				}

				demoShowStatus(
					'<strong>Done!</strong> Created <strong>' + totalCreated + '</strong> demo listing(s) across ' + done + ' booking type(s).',
					'success'
				);
				$demoImport.prop( 'disabled', false );
				$demoDelete.prop( 'disabled', false );
				$demoSpinner.addClass( 'd-none' );
			} );
		}

		if ( $demoDelete.length ) {
			$demoDelete.on( 'click', function () {
				if ( typeof fbsAdmin === 'undefined' ) return;
				if ( ! window.confirm( 'Remove ALL demo listings for every booking type? This cannot be undone.' ) ) {
					return;
				}
				$demoDelete.prop( 'disabled', true );
				$demoImport.prop( 'disabled', true );
				$demoSpinner.removeClass( 'd-none' );
				$.post( fbsAdmin.ajaxUrl, {
					action: 'fbs_delete_demo_content',
					nonce: fbsAdmin.nonce,
					booking_type_id: 0,
				} )
					.done( function ( res ) {
						if ( res && res.success ) {
							demoShowStatus( res.data.message || 'Demo content removed.', 'success' );
							$( '[class*="fbs-demo-count-"]' ).text( '0' );
						} else {
							demoShowStatus( res?.data?.message || 'Delete failed.', 'danger' );
						}
					} )
					.fail( function () {
						demoShowStatus( 'Request failed.', 'danger' );
					} )
					.always( function () {
						$demoDelete.prop( 'disabled', false );
						$demoImport.prop( 'disabled', false );
						$demoSpinner.addClass( 'd-none' );
					} );
			} );
		}

		const $provisionBtn = $( '#fbs-provision-vendor-pages' );
		const $provisionSpinner = $( '#fbs-provision-spinner' );
		const $provisionStatus = $( '#fbs-provision-status' );

		function provisionShowStatus( msg, type ) {
			if ( ! $provisionStatus.length ) return;
			$provisionStatus
				.removeClass( 'd-none alert-info alert-success alert-danger' )
				.addClass( 'alert-' + ( type || 'info' ) )
				.html( msg );
		}

		function provisionUpdateTable( rows ) {
			if ( ! rows ) return;
			Object.keys( rows ).forEach( ( key ) => {
				const row = rows[ key ];
				const $tr = $( '#fbs-vendor-pages-table tr[data-page-key="' + key + '"]' );
				if ( ! $tr.length ) return;
				const $urlCell = $tr.find( '.fbs-vendor-page-url' );
				const $actions = $tr.find( '.fbs-vendor-page-actions' );
				if ( row.url ) {
					$urlCell.html( '<a href="' + row.url + '" target="_blank" rel="noopener">' + row.url + '</a>' );
				}
				if ( row.edit_url ) {
					$actions.html( '<a href="' + row.edit_url + '" class="btn btn-sm btn-outline-secondary">Edit</a>' );
				}
				const $select = $( '[name="fbs_' + key + '"]' );
				if ( $select.length && row.page_id ) {
					$select.val( String( row.page_id ) );
				}
			} );
		}

		if ( $provisionBtn.length ) {
			$provisionBtn.on( 'click', function () {
				if ( typeof fbsAdmin === 'undefined' ) return;
				$provisionBtn.prop( 'disabled', true );
				$provisionSpinner.removeClass( 'd-none' );
				provisionShowStatus( 'Creating partner pages…', 'info' );
				$.post( fbsAdmin.ajaxUrl, {
					action: 'fbs_provision_vendor_pages',
					nonce: fbsAdmin.nonce,
				} )
					.done( function ( res ) {
						if ( res && res.success && res.data ) {
							const msgs = ( res.data.messages || [] ).join( '<br>' );
							provisionShowStatus(
								( res.data.message || 'Partner pages ready.' ) + ( msgs ? '<br>' + msgs : '' ),
								'success'
							);
							provisionUpdateTable( res.data.rows );
						} else {
							provisionShowStatus( res?.data?.message || 'Could not create pages.', 'danger' );
						}
					} )
					.fail( function () {
						provisionShowStatus( 'Request failed.', 'danger' );
					} )
					.always( function () {
						$provisionBtn.prop( 'disabled', false );
						$provisionSpinner.addClass( 'd-none' );
					} );
			} );
		}

		function fbsNormalizeHex( val ) {
			if ( ! val ) {
				return '';
			}
			let hex = String( val ).trim();
			if ( ! hex.startsWith( '#' ) ) {
				hex = '#' + hex;
			}
			if ( /^#[0-9A-Fa-f]{3}$/.test( hex ) ) {
				hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
			}
			return /^#[0-9A-Fa-f]{6}$/.test( hex ) ? hex.toLowerCase() : '';
		}

		function fbsSyncColorPair( $source ) {
			const targetId = $source.data( 'fbs-target' );
			const settingsKey = $source.data( 'fbs-settings-key' );
			let hex = '';

			if ( $source.hasClass( 'fbs-color-picker' ) ) {
				hex = fbsNormalizeHex( $source.val() );
				if ( targetId && hex ) {
					$( '#' + targetId ).val( hex );
				}
			} else {
				hex = fbsNormalizeHex( $source.val() );
				if ( hex ) {
					$source.val( hex );
				}
				if ( settingsKey ) {
					$( '.fbs-color-picker[data-fbs-settings-key="' + settingsKey + '"]' ).val( hex || $source.val() );
				}
			}
		}

		function fbsUpdateColorPreview() {
			const $preview = $( '#fbs-color-preview' );
			if ( ! $preview.length ) {
				return;
			}
			const colors = {};
			const cssVars = {};
			$( '.fbs-color-hex-input' ).each( function () {
				const key = $( this ).data( 'fbs-color-key' );
				const val = fbsNormalizeHex( $( this ).val() );
				if ( key && val ) {
					colors[ key ] = val;
					cssVars[ '--fbs-preview-' + key ] = val;
				}
			} );
			$preview.css( cssVars );
		}

		$( document ).on( 'input change', '.fbs-color-picker', function () {
			fbsSyncColorPair( $( this ) );
			fbsUpdateColorPreview();
		} );

		$( document ).on( 'input change', '.fbs-color-hex-input', function () {
			fbsSyncColorPair( $( this ) );
			fbsUpdateColorPreview();
		} );

		fbsUpdateColorPreview();

		function fbsCollectColorPayload() {
			const defs =
				typeof fbsAdmin !== 'undefined' && fbsAdmin.colorDefaults
					? fbsAdmin.colorDefaults
					: {};
			const colors = {};

			$( '.fbs-color-hex-input' ).each( function () {
				const settingsKey = $( this ).data( 'fbs-settings-key' );
				if ( ! settingsKey ) {
					return;
				}
				let val = fbsNormalizeHex( $( this ).val() );
				if ( ! val && defs[ settingsKey ] ) {
					val = fbsNormalizeHex( defs[ settingsKey ] );
				}
				if ( val ) {
					$( this ).val( val );
					colors[ settingsKey ] = val;
				}
			} );

			return colors;
		}

		function fbsSyncColorsJsonField() {
			const colors = fbsCollectColorPayload();
			$( '#fbs_colors_json' ).val( JSON.stringify( colors ) );
		}

		$( '#fbs-reset-colors' ).on( 'click', function ( e ) {
			e.preventDefault();
			const defs =
				typeof fbsAdmin !== 'undefined' && fbsAdmin.colorDefaults
					? fbsAdmin.colorDefaults
					: {};
			$( '.fbs-color-hex-input' ).each( function () {
				const settingsKey = $( this ).data( 'fbs-settings-key' );
				if ( settingsKey && defs[ settingsKey ] ) {
					$( this ).val( defs[ settingsKey ] ).trigger( 'input' );
				}
			} );
			fbsSyncColorsJsonField();
		} );

		$( '#fbs-fix-page-bg' ).on( 'click', function ( e ) {
			e.preventDefault();
			const $pageBg = $( '#fbs_color_page_bg' );
			if ( $pageBg.length ) {
				$pageBg.val( '#f5f6f8' ).trigger( 'input' );
			}
			fbsSyncColorsJsonField();
		} );

		fbsSyncColorsJsonField();

		$( '#fbs-settings-form' ).on( 'submit', function () {
			fbsSyncColorsJsonField();
		} );

		$( '#fbs-settings-tabs [data-fbs-tab]' ).on( 'shown.bs.tab', function ( e ) {
			const tab = $( e.target ).data( 'fbs-tab' );
			if ( tab ) {
				$( '#fbs_settings_tab' ).val( tab );
			}
		} );

		const $reviewsFeedback = $( '#fbs-reviews-feedback' );
		function fbsReviewsFeedback( msg, ok ) {
			if ( ! $reviewsFeedback.length ) return;
			$reviewsFeedback
				.removeClass( 'd-none alert-success alert-danger' )
				.addClass( ok ? 'alert-success' : 'alert-danger' )
				.text( msg );
		}

		function fbsReviewModerate( reviewId, action, $row ) {
			if ( typeof fbsAdmin === 'undefined' ) return;
			$.post( fbsAdmin.ajaxUrl, {
				action: 'fbs_review_moderate',
				nonce: fbsAdmin.nonce,
				review_id: reviewId,
				review_action: action,
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						if ( res.data && res.data.deleted ) {
							$row.remove();
							fbsReviewsFeedback( res.data.message || 'Deleted.', true );
							return;
						}
						const status = res.data && res.data.status ? res.data.status : '';
						const $badge = $row.find( '.fbs-review-status-badge' );
						if ( $badge.length && status ) {
							$badge
								.removeClass( 'text-bg-success text-bg-warning text-bg-secondary' )
								.addClass(
									status === 'approved'
										? 'text-bg-success'
										: status === 'pending'
											? 'text-bg-warning'
											: 'text-bg-secondary'
								)
								.text( status.charAt( 0 ).toUpperCase() + status.slice( 1 ) );
						}
						$row.find( '.fbs-review-approve, .fbs-review-reject, .fbs-review-delete' ).prop( 'disabled', false );
						fbsReviewsFeedback( res.data.message || 'Updated.', true );
						setTimeout( () => window.location.reload(), 600 );
					} else {
						fbsReviewsFeedback(
							res && res.data && res.data.message ? res.data.message : 'Update failed.',
							false
						);
					}
				} )
				.fail( function () {
					fbsReviewsFeedback( 'Request failed.', false );
				} );
		}

		$( document ).on( 'click', '.fbs-review-approve, .fbs-review-reject, .fbs-review-delete', function ( e ) {
			e.preventDefault();
			const $btn = $( this );
			const id = parseInt( $btn.data( 'id' ), 10 );
			const $row = $btn.closest( '[data-review-id]' );
			let action = 'approve';
			if ( $btn.hasClass( 'fbs-review-reject' ) ) {
				action = 'reject';
			} else if ( $btn.hasClass( 'fbs-review-delete' ) ) {
				if ( ! window.confirm( 'Delete this review permanently?' ) ) {
					return;
				}
				action = 'delete';
			}
			$btn.prop( 'disabled', true );
			fbsReviewModerate( id, action, $row );
		} );
	} );
} )( jQuery );
