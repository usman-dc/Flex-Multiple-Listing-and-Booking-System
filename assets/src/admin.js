/**
 * Admin bundle � dashboard pings, setup wizard, future SPA mounts.
 */
import './admin.scss';

( function ( $ ) {
	$( function () {
		$( '#ulbm-wizard-finish' ).on( 'click', function () {
			if ( typeof ulbmAdmin === 'undefined' ) {
				return;
			}
			const $btn = $( this );
			const $status = $( '#ulbm-wizard-status' );
			const industries = [];
			$( '.ulbm-industry-cb:checked' ).each( function () {
				industries.push( $( this ).val() );
			} );

			$btn.prop( 'disabled', true );
			$status.text( '' );

			$.post( ulbmAdmin.ajaxUrl, {
				action: 'ulbm_setup_finish',
				nonce: ulbmAdmin.nonce,
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
		$( '#ulbm-wizard-complete' ).on( 'click', function () {
			if ( typeof ulbmAdmin === 'undefined' ) {
				return;
			}
			$.post( ulbmAdmin.ajaxUrl, {
				action: 'ulbm_setup_finish',
				nonce: ulbmAdmin.nonce,
				industries: [],
			} ).done( function ( res ) {
				if ( res && res.success && res.data && res.data.redirect ) {
					window.location.href = res.data.redirect;
				}
			} );
		} );

		const $bookFeedback = $( '#ulbm-bookings-feedback' );
		function showBookMsg( text, isError ) {
			if ( ! $bookFeedback.length ) {
				return;
			}
			$bookFeedback
				.removeClass( 'd-none alert-success alert-danger' )
				.addClass( isError ? 'alert-danger' : 'alert-success' )
				.text( text );
		}
		function ulbmBookingBadgeTone( status ) {
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
		function ulbmPaymentBadgeTone( ps ) {
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

		$( document ).on( 'click', '.ulbm-booking-action', function ( e ) {
			e.preventDefault();
			if ( typeof ulbmAdmin === 'undefined' ) {
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
				action: 'ulbm_booking_update',
				nonce: ulbmAdmin.nonce,
				booking_id: id,
				send_notification: $( '#ulbm-bookings-notify' ).is( ':checked' ) ? '1' : '0',
			};
			if ( field === 'status' ) {
				payload.status = value;
			} else if ( field === 'payment_status' ) {
				payload.payment_status = value;
			} else {
				return;
			}
			$btn.prop( 'disabled', true );
			$.post( ulbmAdmin.ajaxUrl, payload )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.booking ) {
						const b = res.data.booking;
						const st = ulbmBookingBadgeTone( b.status );
						const pt = ulbmPaymentBadgeTone( b.payment_status );
						$row
							.find( '.ulbm-cell-status' )
							.attr( 'class', 'badge rounded-pill text-bg-' + st + ' ulbm-cell-status' )
							.text( b.status );
						$row
							.find( '.ulbm-cell-payment' )
							.attr( 'class', 'badge rounded-pill text-bg-' + pt + ' ulbm-cell-payment' )
							.text( b.payment_status );
						$row.addClass( 'ulbm-row-updated' );
						setTimeout( function () {
							$row.removeClass( 'ulbm-row-updated' );
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

		/* --- Demo content import / delete --- */
		const $demoImport   = $( '#ulbm-demo-import' );
		const $demoDelete   = $( '#ulbm-demo-delete-all' );
		const $demoSpinner  = $( '#ulbm-demo-spinner' );
		const $demoStatus   = $( '#ulbm-demo-status' );
		const $demoProgress = $( '#ulbm-demo-progress' );
		const $demoProgressWrap = $( '#ulbm-demo-progress-wrap' );
		const $demoSelectAll = $( '#ulbm-demo-select-all' );

		if ( $demoSelectAll.length ) {
			$demoSelectAll.on( 'change', function () {
				$( '.ulbm-demo-type-cb' ).prop( 'checked', $( this ).is( ':checked' ) );
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
				if ( typeof ulbmAdmin === 'undefined' ) return;

				const ids = [];
				$( '.ulbm-demo-type-cb:checked' ).each( function () {
					ids.push( parseInt( $( this ).val(), 10 ) );
				} );
				if ( ! ids.length ) {
					demoShowStatus( 'Select at least one booking type.', 'danger' );
					return;
				}

				const count = parseInt( $( '#ulbm-demo-count' ).val(), 10 ) || 20;
				$demoImport.prop( 'disabled', true );
				$demoDelete.prop( 'disabled', true );
				$demoSpinner.removeClass( 'd-none' );
				$demoProgressWrap.removeClass( 'd-none' );
				demoShowStatus( 'Importing demo content�', 'info' );

				let done = 0;
				let totalCreated = 0;
				const total = ids.length;

				for ( const typeId of ids ) {
					try {
						const res = await $.post( ulbmAdmin.ajaxUrl, {
							action: 'ulbm_import_demo_content',
							nonce: ulbmAdmin.nonce,
							booking_type_id: typeId,
							count: count,
						} );
						if ( res && res.success && res.data ) {
							totalCreated += res.data.created || 0;
							const tid = typeId;
							const $badge = $( '.ulbm-demo-count-' + tid );
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
				if ( typeof ulbmAdmin === 'undefined' ) return;
				if ( ! window.confirm( 'Remove ALL demo listings for every booking type? This cannot be undone.' ) ) {
					return;
				}
				$demoDelete.prop( 'disabled', true );
				$demoImport.prop( 'disabled', true );
				$demoSpinner.removeClass( 'd-none' );
				$.post( ulbmAdmin.ajaxUrl, {
					action: 'ulbm_delete_demo_content',
					nonce: ulbmAdmin.nonce,
					booking_type_id: 0,
				} )
					.done( function ( res ) {
						if ( res && res.success ) {
							demoShowStatus( res.data.message || 'Demo content removed.', 'success' );
							$( '[class*="ulbm-demo-count-"]' ).text( '0' );
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

		const $provisionBtn = $( '#ulbm-provision-vendor-pages' );
		const $provisionSpinner = $( '#ulbm-provision-spinner' );
		const $provisionStatus = $( '#ulbm-provision-status' );

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
				const $tr = $( '#ulbm-vendor-pages-table tr[data-page-key="' + key + '"]' );
				if ( ! $tr.length ) return;
				const $urlCell = $tr.find( '.ulbm-vendor-page-url' );
				const $actions = $tr.find( '.ulbm-vendor-page-actions' );
				if ( row.url ) {
					$urlCell.html( '<a href="' + row.url + '" target="_blank" rel="noopener">' + row.url + '</a>' );
				}
				if ( row.edit_url ) {
					$actions.html( '<a href="' + row.edit_url + '" class="btn btn-sm btn-outline-secondary">Edit</a>' );
				}
				const $select = $( '[name="ulbm_' + key + '"]' );
				if ( $select.length && row.page_id ) {
					$select.val( String( row.page_id ) );
				}
			} );
		}

		if ( $provisionBtn.length ) {
			$provisionBtn.on( 'click', function () {
				if ( typeof ulbmAdmin === 'undefined' ) return;
				$provisionBtn.prop( 'disabled', true );
				$provisionSpinner.removeClass( 'd-none' );
				provisionShowStatus( 'Creating partner pages�', 'info' );
				$.post( ulbmAdmin.ajaxUrl, {
					action: 'ulbm_provision_vendor_pages',
					nonce: ulbmAdmin.nonce,
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

		function ulbmNormalizeHex( val ) {
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

		function ulbmSyncColorPair( $source ) {
			const targetId = $source.data( 'ulbm-target' );
			const settingsKey = $source.data( 'ulbm-settings-key' );
			let hex = '';

			if ( $source.hasClass( 'ulbm-color-picker' ) ) {
				hex = ulbmNormalizeHex( $source.val() );
				if ( targetId && hex ) {
					$( '#' + targetId ).val( hex );
				}
			} else {
				hex = ulbmNormalizeHex( $source.val() );
				if ( hex ) {
					$source.val( hex );
				}
				if ( settingsKey ) {
					$( '.ulbm-color-picker[data-ulbm-settings-key="' + settingsKey + '"]' ).val( hex || $source.val() );
				}
			}
		}

		function ulbmUpdateColorPreview() {
			const $preview = $( '#ulbm-color-preview' );
			if ( ! $preview.length ) {
				return;
			}
			const colors = {};
			const cssVars = {};
			$( '.ulbm-color-hex-input' ).each( function () {
				const key = $( this ).data( 'ulbm-color-key' );
				const val = ulbmNormalizeHex( $( this ).val() );
				if ( key && val ) {
					colors[ key ] = val;
					cssVars[ '--ulbm-preview-' + key ] = val;
				}
			} );
			$preview.css( cssVars );
		}

		$( document ).on( 'input change', '.ulbm-color-picker', function () {
			ulbmSyncColorPair( $( this ) );
			ulbmUpdateColorPreview();
		} );

		$( document ).on( 'input change', '.ulbm-color-hex-input', function () {
			ulbmSyncColorPair( $( this ) );
			ulbmUpdateColorPreview();
		} );

		ulbmUpdateColorPreview();

		function ulbmCollectColorPayload() {
			const defs =
				typeof ulbmAdmin !== 'undefined' && ulbmAdmin.colorDefaults
					? ulbmAdmin.colorDefaults
					: {};
			const colors = {};

			$( '.ulbm-color-hex-input' ).each( function () {
				const settingsKey = $( this ).data( 'ulbm-settings-key' );
				if ( ! settingsKey ) {
					return;
				}
				let val = ulbmNormalizeHex( $( this ).val() );
				if ( ! val && defs[ settingsKey ] ) {
					val = ulbmNormalizeHex( defs[ settingsKey ] );
				}
				if ( val ) {
					$( this ).val( val );
					colors[ settingsKey ] = val;
				}
			} );

			return colors;
		}

		function ulbmSyncColorsJsonField() {
			const colors = ulbmCollectColorPayload();
			$( '#ulbm_colors_json' ).val( JSON.stringify( colors ) );
		}

		$( '#ulbm-reset-colors' ).on( 'click', function ( e ) {
			e.preventDefault();
			const defs =
				typeof ulbmAdmin !== 'undefined' && ulbmAdmin.colorDefaults
					? ulbmAdmin.colorDefaults
					: {};
			$( '.ulbm-color-hex-input' ).each( function () {
				const settingsKey = $( this ).data( 'ulbm-settings-key' );
				if ( settingsKey && defs[ settingsKey ] ) {
					$( this ).val( defs[ settingsKey ] ).trigger( 'input' );
				}
			} );
			ulbmSyncColorsJsonField();
		} );

		$( '#ulbm-fix-page-bg' ).on( 'click', function ( e ) {
			e.preventDefault();
			const $pageBg = $( '#ulbm_color_page_bg' );
			if ( $pageBg.length ) {
				$pageBg.val( '#f5f6f8' ).trigger( 'input' );
			}
			ulbmSyncColorsJsonField();
		} );

		ulbmSyncColorsJsonField();

		$( '#ulbm-settings-form' ).on( 'submit', function () {
			ulbmSyncColorsJsonField();
		} );

		$( '#ulbm-settings-tabs [data-ulbm-tab]' ).on( 'shown.bs.tab', function ( e ) {
			const tab = $( e.target ).data( 'ulbm-tab' );
			if ( tab ) {
				$( '#ulbm_settings_tab' ).val( tab );
			}
		} );

		const $reviewsFeedback = $( '#ulbm-reviews-feedback' );
		function ulbmReviewsFeedback( msg, ok ) {
			if ( ! $reviewsFeedback.length ) return;
			$reviewsFeedback
				.removeClass( 'd-none alert-success alert-danger' )
				.addClass( ok ? 'alert-success' : 'alert-danger' )
				.text( msg );
		}

		function ulbmReviewModerate( reviewId, action, $row ) {
			if ( typeof ulbmAdmin === 'undefined' ) return;
			$.post( ulbmAdmin.ajaxUrl, {
				action: 'ulbm_review_moderate',
				nonce: ulbmAdmin.nonce,
				review_id: reviewId,
				review_action: action,
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						if ( res.data && res.data.deleted ) {
							$row.remove();
							ulbmReviewsFeedback( res.data.message || 'Deleted.', true );
							return;
						}
						const status = res.data && res.data.status ? res.data.status : '';
						const $badge = $row.find( '.ulbm-review-status-badge' );
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
						$row.find( '.ulbm-review-approve, .ulbm-review-reject, .ulbm-review-delete' ).prop( 'disabled', false );
						ulbmReviewsFeedback( res.data.message || 'Updated.', true );
						setTimeout( () => window.location.reload(), 600 );
					} else {
						ulbmReviewsFeedback(
							res && res.data && res.data.message ? res.data.message : 'Update failed.',
							false
						);
					}
				} )
				.fail( function () {
					ulbmReviewsFeedback( 'Request failed.', false );
				} );
		}

		$( document ).on( 'click', '.ulbm-review-approve, .ulbm-review-reject, .ulbm-review-delete', function ( e ) {
			e.preventDefault();
			const $btn = $( this );
			const id = parseInt( $btn.data( 'id' ), 10 );
			const $row = $btn.closest( '[data-review-id]' );
			let action = 'approve';
			if ( $btn.hasClass( 'ulbm-review-reject' ) ) {
				action = 'reject';
			} else if ( $btn.hasClass( 'ulbm-review-delete' ) ) {
				if ( ! window.confirm( 'Delete this review permanently?' ) ) {
					return;
				}
				action = 'delete';
			}
			$btn.prop( 'disabled', true );
			ulbmReviewModerate( id, action, $row );
		} );

		const $partnersFeedback = $( '#ulbm-partners-feedback' );
		function ulbmPartnersFeedback( msg, ok ) {
			if ( ! $partnersFeedback.length ) return;
			$partnersFeedback
				.removeClass( 'd-none alert-success alert-danger' )
				.addClass( ok ? 'alert-success' : 'alert-danger' )
				.text( msg );
		}

		function ulbmPartnerModerate( vendorId, action, $row, extra ) {
			if ( typeof ulbmAdmin === 'undefined' ) return;
			const payload = {
				action: 'ulbm_partner_moderate',
				nonce: ulbmAdmin.nonce,
				vendor_id: vendorId,
				partner_action: action,
			};
			if ( extra ) {
				Object.assign( payload, extra );
			}
			$.post( ulbmAdmin.ajaxUrl, payload )
				.done( function ( res ) {
					if ( res && res.success ) {
						if ( res.data && res.data.deleted ) {
							$row.remove();
							ulbmPartnersFeedback( res.data.message || 'Removed.', true );
							return;
						}
						if ( action === 'update_business' ) {
							ulbmPartnersFeedback( res.data.message || 'Saved.', true );
							return;
						}
						ulbmPartnersFeedback( res.data.message || 'Updated.', true );
						setTimeout( () => window.location.reload(), 600 );
					} else {
						ulbmPartnersFeedback(
							res && res.data && res.data.message ? res.data.message : 'Update failed.',
							false
						);
					}
				} )
				.fail( function () {
					ulbmPartnersFeedback( 'Request failed.', false );
				} );
		}

		$( document ).on( 'click', '.ulbm-partner-approve, .ulbm-partner-suspend, .ulbm-partner-delete', function ( e ) {
			e.preventDefault();
			const $btn = $( this );
			const id = parseInt( $btn.data( 'id' ), 10 );
			const $row = $btn.closest( '[data-partner-id]' );
			let action = 'approve';
			if ( $btn.hasClass( 'ulbm-partner-suspend' ) ) {
				action = 'suspend';
			} else if ( $btn.hasClass( 'ulbm-partner-delete' ) ) {
				if ( ! window.confirm( 'Remove this partner account? The WordPress user will be kept but partner access is revoked.' ) ) {
					return;
				}
				action = 'delete';
			}
			$btn.prop( 'disabled', true );
			ulbmPartnerModerate( id, action, $row );
		} );

		$( document ).on( 'change', '.ulbm-partner-business-input', function () {
			const $input = $( this );
			const id = parseInt( $input.data( 'vendor-id' ), 10 );
			const $row = $input.closest( '[data-partner-id]' );
			if ( ! id ) return;
			ulbmPartnerModerate( id, 'update_business', $row, { business_name: $input.val() } );
		} );

	} );
} )( jQuery );
