import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Editor view for the simplified summary block.
 *
 * The summary is authored in the Accessibility Checker sidebar panel, not in
 * the block, so the editor shows a static placeholder mirroring the front end
 * markup shape. The heading is localized server-side with the
 * edac_filter_simplified_summary_heading filter applied, so a custom heading
 * (a pro setting) previews exactly as it renders on the front end.
 *
 * @return {Object} The block edit component.
 */
const Edit = () => {
	const blockProps = useBlockProps( {
		className: 'edac-simplified-summary',
	} );

	const heading =
		window.edacSimplifiedSummaryBlock?.heading ||
		__( 'Simplified Summary', 'accessibility-checker' );

	return (
		<div { ...blockProps }>
			<h2>{ heading }</h2>
			<p className="edac-simplified-summary-block__placeholder">
				{ __(
					'The simplified summary for this post will display here. Write the summary in the Readability section of the Accessibility Checker sidebar or meta box.',
					'accessibility-checker'
				) }
			</p>
		</div>
	);
};

export default Edit;
