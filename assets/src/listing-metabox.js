/**
 * Listing metabox — gallery manager + repeaters for features, services, FAQ.
 */
( function ( $ ) {
	'use strict';

	$( function () {

		/* ───── Gallery ───── */
		const $galleryIds = $( '#ulbm-gallery-ids' );
		const $preview    = $( '#ulbm-gallery-preview' );

		$( '#ulbm-gallery-add' ).on( 'click', function ( e ) {
			e.preventDefault();
			const frame = wp.media( {
				title: 'Select gallery images',
				multiple: true,
				library: { type: 'image' },
			} );
			frame.on( 'select', function () {
				const selection = frame.state().get( 'selection' );
				selection.each( function ( att ) {
					const id    = att.id;
					const thumb = att.attributes.sizes && att.attributes.sizes.thumbnail
						? att.attributes.sizes.thumbnail.url
						: att.attributes.url;
					$preview.append(
						'<div class="ulbm-gallery-thumb position-relative" data-id="' + id + '">' +
						'<img src="' + thumb + '" style="width:80px;height:80px;object-fit:cover;border-radius:4px;">' +
						'<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 ulbm-gallery-remove" style="background-color:rgba(0,0,0,.5);padding:4px;font-size:8px;" aria-label="Remove"></button>' +
						'</div>'
					);
				} );
				syncGallery();
			} );
			frame.open();
		} );

		$preview.on( 'click', '.ulbm-gallery-remove', function () {
			$( this ).closest( '.ulbm-gallery-thumb' ).remove();
			syncGallery();
		} );

		function syncGallery() {
			const ids = [];
			$preview.find( '.ulbm-gallery-thumb' ).each( function () {
				ids.push( $( this ).data( 'id' ) );
			} );
			$galleryIds.val( ids.join( ',' ) );
		}

		/* ───── Features repeater ───── */
		const $featuresJson = $( '#ulbm-features-json' );
		const $featuresList = $( '#ulbm-features-list' );
		let features = [];
		try { features = JSON.parse( $featuresJson.val() ) || []; } catch ( e ) { features = []; }

		function renderFeatures() {
			$featuresList.empty();
			features.forEach( function ( f, i ) {
				$featuresList.append(
					'<div class="input-group input-group-sm mb-2" data-idx="' + i + '">' +
					'<input type="text" class="form-control" placeholder="Icon (bi-wifi)" value="' + esc( f.icon ) + '" data-field="icon">' +
					'<input type="text" class="form-control" placeholder="Label" value="' + esc( f.label ) + '" data-field="label">' +
					'<input type="text" class="form-control" placeholder="Value" value="' + esc( f.value ) + '" data-field="value">' +
					'<button type="button" class="btn btn-outline-danger ulbm-repeater-remove">&times;</button>' +
					'</div>'
				);
			} );
			$featuresJson.val( JSON.stringify( features ) );
		}
		renderFeatures();

		$( '#ulbm-feature-add' ).on( 'click', function () {
			features.push( { icon: '', label: '', value: '' } );
			renderFeatures();
		} );
		$featuresList.on( 'input', 'input', function () {
			const $row = $( this ).closest( '[data-idx]' );
			const idx  = $row.data( 'idx' );
			features[ idx ][ $( this ).data( 'field' ) ] = $( this ).val();
			$featuresJson.val( JSON.stringify( features ) );
		} );
		$featuresList.on( 'click', '.ulbm-repeater-remove', function () {
			features.splice( $( this ).closest( '[data-idx]' ).data( 'idx' ), 1 );
			renderFeatures();
		} );

		/* ───── Extra services repeater ───── */
		const $servicesJson = $( '#ulbm-services-json' );
		const $servicesList = $( '#ulbm-services-list' );
		let services = [];
		try { services = JSON.parse( $servicesJson.val() ) || []; } catch ( e ) { services = []; }

		function renderServices() {
			$servicesList.empty();
			services.forEach( function ( s, i ) {
				$servicesList.append(
					'<div class="input-group input-group-sm mb-2" data-idx="' + i + '">' +
					'<input type="text" class="form-control" placeholder="Name" value="' + esc( s.name ) + '" data-field="name">' +
					'<input type="number" class="form-control" placeholder="Price" value="' + ( s.price || '' ) + '" data-field="price" step="0.01">' +
					'<select class="form-select" data-field="per"><option value="booking"' + ( s.per === 'booking' ? ' selected' : '' ) + '>Per booking</option><option value="night"' + ( s.per === 'night' ? ' selected' : '' ) + '>Per night</option><option value="guest"' + ( s.per === 'guest' ? ' selected' : '' ) + '>Per guest</option></select>' +
					'<div class="input-group-text"><input class="form-check-input" type="checkbox" data-field="required"' + ( s.required ? ' checked' : '' ) + ' title="Required"></div>' +
					'<button type="button" class="btn btn-outline-danger ulbm-repeater-remove">&times;</button>' +
					'</div>'
				);
			} );
			$servicesJson.val( JSON.stringify( services ) );
		}
		renderServices();

		$( '#ulbm-service-add' ).on( 'click', function () {
			services.push( { name: '', price: 0, per: 'booking', required: false } );
			renderServices();
		} );
		$servicesList.on( 'input change', 'input,select', function () {
			const $row = $( this ).closest( '[data-idx]' );
			const idx  = $row.data( 'idx' );
			const field = $( this ).data( 'field' );
			if ( field === 'required' ) {
				services[ idx ][ field ] = $( this ).is( ':checked' );
			} else if ( field === 'price' ) {
				services[ idx ][ field ] = parseFloat( $( this ).val() ) || 0;
			} else {
				services[ idx ][ field ] = $( this ).val();
			}
			$servicesJson.val( JSON.stringify( services ) );
		} );
		$servicesList.on( 'click', '.ulbm-repeater-remove', function () {
			services.splice( $( this ).closest( '[data-idx]' ).data( 'idx' ), 1 );
			renderServices();
		} );

		/* ───── FAQ repeater ───── */
		const $faqJson = $( '#ulbm-faq-json' );
		const $faqList = $( '#ulbm-faq-list' );
		let faqs = [];
		try { faqs = JSON.parse( $faqJson.val() ) || []; } catch ( e ) { faqs = []; }

		function renderFaqs() {
			$faqList.empty();
			faqs.forEach( function ( f, i ) {
				$faqList.append(
					'<div class="border rounded p-2 mb-2" data-idx="' + i + '">' +
					'<div class="d-flex gap-2 mb-1"><input type="text" class="form-control form-control-sm" placeholder="Question" value="' + esc( f.question ) + '" data-field="question"><button type="button" class="btn btn-sm btn-outline-danger ulbm-repeater-remove">&times;</button></div>' +
					'<textarea class="form-control form-control-sm" placeholder="Answer" data-field="answer" rows="2">' + esc( f.answer ) + '</textarea>' +
					'</div>'
				);
			} );
			$faqJson.val( JSON.stringify( faqs ) );
		}
		renderFaqs();

		$( '#ulbm-faq-add' ).on( 'click', function () {
			faqs.push( { question: '', answer: '' } );
			renderFaqs();
		} );
		$faqList.on( 'input', 'input,textarea', function () {
			const $row = $( this ).closest( '[data-idx]' );
			const idx  = $row.data( 'idx' );
			faqs[ idx ][ $( this ).data( 'field' ) ] = $( this ).val();
			$faqJson.val( JSON.stringify( faqs ) );
		} );
		$faqList.on( 'click', '.ulbm-repeater-remove', function () {
			faqs.splice( $( this ).closest( '[data-idx]' ).data( 'idx' ), 1 );
			renderFaqs();
		} );

		/* ───── Util ───── */
		function esc( str ) {
			if ( ! str ) return '';
			return String( str ).replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
		}
	} );
} )( jQuery );
