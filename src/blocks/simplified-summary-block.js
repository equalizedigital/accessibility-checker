/**
 * WordPress Block for Simplified Summary
 */

const { registerBlockType } = wp.blocks;
const { createElement: el, Fragment } = wp.element;
const {
	InspectorControls,
	useBlockProps,
} = wp.blockEditor;
const {
	PanelBody,
	ToggleControl,
	RangeControl,
	TextControl,
	SelectControl,
	Placeholder,
	Spinner,
} = wp.components;
// Check if ServerSideRender is available
let ServerSideRender = null;
if ( wp.serverSideRender && wp.serverSideRender.ServerSideRender ) {
	ServerSideRender = wp.serverSideRender.ServerSideRender;
} else if ( wp.components && wp.components.ServerSideRender ) {
	// Fallback for older WordPress versions where it was in wp.components
	ServerSideRender = wp.components.ServerSideRender;
}
const { __ } = wp.i18n;

registerBlockType( 'accessibility-checker/simplified-summary', {
	title: __( 'Simplified Summary', 'accessibility-checker' ),
	description: __( 'Display a simplified summary of the content for accessibility.', 'accessibility-checker' ),
	category: 'widgets',
	icon: 'universal-access-alt',
	keywords: [
		__( 'accessibility', 'accessibility-checker' ),
		__( 'summary', 'accessibility-checker' ),
		__( 'simple', 'accessibility-checker' ),
	],
	attributes: {
		postId: {
			type: 'number',
			default: 0,
		},
		showHeading: {
			type: 'boolean',
			default: true,
		},
		headingLevel: {
			type: 'number',
			default: 2,
		},
		customHeading: {
			type: 'string',
			default: '',
		},
	},
	supports: {
		align: [ 'wide', 'full' ],
		spacing: {
			margin: true,
			padding: true,
		},
	},

	edit( props ) {
		const { attributes, setAttributes } = props;
		const { postId, showHeading, headingLevel, customHeading } = attributes;
		const blockProps = useBlockProps();

		// Set default post ID if not set and safely check for window object
		if ( ! postId && typeof window !== 'undefined' && window.edacSimplifiedSummaryBlock?.postId ) {
			setAttributes( { postId: window.edacSimplifiedSummaryBlock.postId } );
		}

		const headingLevelOptions = [
			{ label: __( 'H1', 'accessibility-checker' ), value: 1 },
			{ label: __( 'H2', 'accessibility-checker' ), value: 2 },
			{ label: __( 'H3', 'accessibility-checker' ), value: 3 },
			{ label: __( 'H4', 'accessibility-checker' ), value: 4 },
			{ label: __( 'H5', 'accessibility-checker' ), value: 5 },
			{ label: __( 'H6', 'accessibility-checker' ), value: 6 },
		];

		// Render the block content with error handling
		const renderBlockContent = () => {
			// Check if ServerSideRender is available
			if ( ! ServerSideRender ) {
				return el(
					Placeholder,
					{
						icon: 'universal-access-alt',
						label: __( 'Simplified Summary', 'accessibility-checker' ),
					},
					el( 'p', {}, __( 'Server-side rendering is not available. The block will work on the frontend.', 'accessibility-checker' ) )
				);
			}

			// Ensure we have clean attributes to prevent React errors
			const cleanAttributes = {
				postId: parseInt( postId ) || 0,
				showHeading: Boolean( showHeading ),
				headingLevel: parseInt( headingLevel ) || 2,
				customHeading: String( customHeading || '' ),
			};

			try {
				return el( ServerSideRender, {
					block: 'accessibility-checker/simplified-summary',
					attributes: cleanAttributes,
					EmptyResponsePlaceholder: () => el(
						Placeholder,
						{
							icon: 'universal-access-alt',
							label: __( 'Simplified Summary', 'accessibility-checker' ),
						},
						el( 'p', {}, __( 'No summary available for this content.', 'accessibility-checker' ) )
					),
					ErrorResponsePlaceholder: ( { response } ) => el(
						Placeholder,
						{
							icon: 'warning',
							label: __( 'Simplified Summary Error', 'accessibility-checker' ),
						},
						el( 'p', {}, __( 'There was an error loading the summary. Please check your settings.', 'accessibility-checker' ) ),
						response && el( 'small', {}, response.message || '' )
					),
					LoadingResponsePlaceholder: () => el(
						Placeholder,
						{
							icon: 'universal-access-alt',
							label: __( 'Simplified Summary', 'accessibility-checker' ),
						},
						el( Spinner ),
						el( 'p', {}, __( 'Loading summary...', 'accessibility-checker' ) )
					),
				} );
			} catch ( error ) {
				return el(
					Placeholder,
					{
						icon: 'warning',
						label: __( 'Simplified Summary Error', 'accessibility-checker' ),
					},
					el( 'p', {}, __( 'Unable to load the summary preview.', 'accessibility-checker' ) )
				);
			}
		};

		return el(
			Fragment,
			{},
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{
						title: __( 'Simplified Summary Settings', 'accessibility-checker' ),
						initialOpen: true,
					},
					el( ToggleControl, {
						label: __( 'Show Heading', 'accessibility-checker' ),
						checked: showHeading,
						onChange: ( value ) => setAttributes( { showHeading: value } ),
					} ),
					showHeading && el( SelectControl, {
						label: __( 'Heading Level', 'accessibility-checker' ),
						value: headingLevel,
						options: headingLevelOptions,
						onChange: ( value ) => setAttributes( { headingLevel: parseInt( value ) } ),
					} ),
					showHeading && el( TextControl, {
						label: __( 'Custom Heading Text', 'accessibility-checker' ),
						value: customHeading,
						onChange: ( value ) => setAttributes( { customHeading: value } ),
						placeholder: __( 'Simplified Summary', 'accessibility-checker' ),
						help: __( 'Leave empty to use the default heading text.', 'accessibility-checker' ),
					} ),
					el( RangeControl, {
						label: __( 'Post ID', 'accessibility-checker' ),
						value: postId,
						onChange: ( value ) => setAttributes( { postId: value } ),
						min: 0,
						max: 10000,
						help: __( 'Set to 0 to use the current post ID.', 'accessibility-checker' ),
					} )
				)
			),
			el(
				'div',
				blockProps,
				renderBlockContent()
			)
		);
	},

	save() {
		// Server-side rendering, so return null
		return null;
	},
} );
