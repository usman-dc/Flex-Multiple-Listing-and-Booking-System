/**
 * Gutenberg Blocks — Flex Listings and Booking Manager.
 *
 * Registers: ulbm-booking/form, ulbm-booking/grid, ulbm-booking/search
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl, RangeControl, ToggleControl } = wp.components;
const { createElement: el, Fragment } = wp.element;
const SSR = ( wp.serverSideRender && wp.serverSideRender.default )
	? wp.serverSideRender.default
	: ( wp.serverSideRender || ( wp.components && wp.components.ServerSideRender ) || null );

const bookingTypes = ( window.ulbmBlockData && window.ulbmBlockData.types ) || [];
const typeOptions = [ { label: '— All Types —', value: '' } ].concat(
	bookingTypes.map( ( t ) => ( { label: t.name + ' (#' + t.id + ')', value: t.slug } ) )
);
const typeIdOptions = [ { label: '— Select —', value: '0' } ].concat(
	bookingTypes.map( ( t ) => ( { label: t.name + ' (#' + t.id + ')', value: String( t.id ) } ) )
);

/* ─── BOOKING FORM BLOCK ───────────────────────────────────── */
registerBlockType( 'ulbm-booking/form', {
	title: 'Flex Listings Booking Form',
	description: 'Display a booking form for a specific booking type.',
	icon: 'calendar-alt',
	category: 'widgets',
	keywords: [ 'booking', 'form', 'reservation' ],
	attributes: {
		id: { type: 'number', default: 0 },
		type: { type: 'string', default: '' },
	},
	edit: function ( props ) {
		const { attributes, setAttributes } = props;
		const blockProps = useBlockProps();

		return el( Fragment, {},
			el( InspectorControls, {},
				el( PanelBody, { title: 'Booking Form Settings', initialOpen: true },
					el( SelectControl, {
						label: 'Booking Type',
						value: String( attributes.id || '0' ),
						options: typeIdOptions,
						onChange: ( val ) => setAttributes( { id: parseInt( val, 10 ) } ),
					} ),
					el( TextControl, {
						label: 'Or type slug (optional)',
						value: attributes.type || '',
						onChange: ( val ) => setAttributes( { type: val } ),
					} )
				)
			),
			el( 'div', blockProps,
				SSR
					? el( SSR, { block: 'ulbm-booking/form', attributes: attributes } )
					: el( 'div', { className: 'ulbm-block-placeholder' },
						el( 'p', {}, '📋 Flex Listings Booking Form' ),
						el( 'p', { className: 'components-placeholder__instructions' },
							attributes.id > 0 ? 'Type #' + attributes.id : 'Select a booking type in the sidebar.'
						)
					)
			)
		);
	},
	save: () => null,
} );

/* ─── LISTING GRID BLOCK ──────────────────────────────────── */
registerBlockType( 'ulbm-booking/grid', {
	title: 'Usman Listing Grid',
	description: 'Display a filterable listing grid with AJAX search for any booking type.',
	icon: 'grid-view',
	category: 'widgets',
	keywords: [ 'listings', 'grid', 'booking', 'filter', 'search' ],
	attributes: {
		type: { type: 'string', default: '' },
		columns: { type: 'number', default: 3 },
		limit: { type: 'number', default: 12 },
		gap: { type: 'number', default: 0 },
		paddingX: { type: 'number', default: 0 },
		paddingY: { type: 'number', default: 0 },
		marginTop: { type: 'number', default: 0 },
		marginBottom: { type: 'number', default: 0 },
		cardPadding: { type: 'number', default: 0 },
	},
	edit: function ( props ) {
		const { attributes, setAttributes } = props;
		const blockProps = useBlockProps();

		return el( Fragment, {},
			el( InspectorControls, {},
				el( PanelBody, { title: 'Grid Settings', initialOpen: true },
					el( SelectControl, {
						label: 'Booking Type',
						value: attributes.type || '',
						options: typeOptions,
						onChange: ( val ) => setAttributes( { type: val } ),
						help: 'Leave "All Types" to show listings from every booking type.',
					} ),
					el( RangeControl, {
						label: 'Columns',
						value: attributes.columns,
						onChange: ( val ) => setAttributes( { columns: val } ),
						min: 1,
						max: 4,
					} ),
					el( RangeControl, {
						label: 'Listings per page',
						value: attributes.limit,
						onChange: ( val ) => setAttributes( { limit: val } ),
						min: 1,
						max: 50,
					} )
				),
				el( PanelBody, { title: 'Spacing (px)', initialOpen: false },
					el( RangeControl, {
						label: 'Column gap (0 = global default)',
						value: attributes.gap,
						onChange: ( val ) => setAttributes( { gap: val } ),
						min: 0,
						max: 80,
					} ),
					el( RangeControl, {
						label: 'Padding X',
						value: attributes.paddingX,
						onChange: ( val ) => setAttributes( { paddingX: val } ),
						min: 0,
						max: 80,
					} ),
					el( RangeControl, {
						label: 'Padding Y',
						value: attributes.paddingY,
						onChange: ( val ) => setAttributes( { paddingY: val } ),
						min: 0,
						max: 80,
					} ),
					el( RangeControl, {
						label: 'Margin top',
						value: attributes.marginTop,
						onChange: ( val ) => setAttributes( { marginTop: val } ),
						min: 0,
						max: 120,
					} ),
					el( RangeControl, {
						label: 'Margin bottom',
						value: attributes.marginBottom,
						onChange: ( val ) => setAttributes( { marginBottom: val } ),
						min: 0,
						max: 120,
					} ),
					el( RangeControl, {
						label: 'Card padding',
						value: attributes.cardPadding,
						onChange: ( val ) => setAttributes( { cardPadding: val } ),
						min: 0,
						max: 48,
					} )
				)
			),
			el( 'div', blockProps,
				SSR
					? el( SSR, { block: 'ulbm-booking/grid', attributes: attributes } )
					: el( 'div', { className: 'ulbm-block-placeholder' },
						el( 'p', {}, '🏠 Flex Listing Grid' ),
						el( 'p', { className: 'components-placeholder__instructions' },
							( attributes.type ? 'Type: ' + attributes.type : 'All types' ) +
							' · ' + attributes.columns + ' cols · ' + attributes.limit + ' per page'
						)
					)
			)
		);
	},
	save: () => null,
} );

/* ─── SEARCH BLOCK ─────────────────────────────────────────── */
registerBlockType( 'ulbm-booking/search', {
	title: 'Usman Listing Search',
	description: 'AJAX-powered availability search UI.',
	icon: 'search',
	category: 'widgets',
	keywords: [ 'search', 'booking', 'availability' ],
	attributes: {
		layout: { type: 'string', default: 'horizontal' },
	},
	edit: function ( props ) {
		const { attributes, setAttributes } = props;
		const blockProps = useBlockProps();

		return el( Fragment, {},
			el( InspectorControls, {},
				el( PanelBody, { title: 'Search Settings', initialOpen: true },
					el( SelectControl, {
						label: 'Layout',
						value: attributes.layout,
						options: [
							{ label: 'Horizontal', value: 'horizontal' },
							{ label: 'Vertical', value: 'vertical' },
						],
						onChange: ( val ) => setAttributes( { layout: val } ),
					} )
				)
			),
			el( 'div', blockProps,
				SSR
					? el( SSR, { block: 'ulbm-booking/search', attributes: attributes } )
					: el( 'div', { className: 'ulbm-block-placeholder' },
						el( 'p', {}, '🔍 Usman Listing Search' ),
						el( 'p', { className: 'components-placeholder__instructions' }, 'Layout: ' + attributes.layout )
					)
			)
		);
	},
	save: () => null,
} );
