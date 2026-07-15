import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Editor view for the simplified summary block.
 *
 * The summary is authored in the Accessibility Checker sidebar panel, not in
 * the block, so the editor shows a static placeholder mirroring the front end
 * markup shape. The heading can be changed on the front end via the
 * edac_filter_simplified_summary_heading PHP filter, which cannot run here;
 * the default heading is an acceptable preview approximation.
 *
 * @return {Object} The block edit component.
 */
const Edit = () => {
	const blockProps = useBlockProps( {
		className: 'edac-simplified-summary',
	} );

	return (
		<div { ...blockProps }>
			<h2>{ __( 'Simplified Summary', 'accessibility-checker' ) }</h2>
			<p className="edac-simplified-summary-block__placeholder">
				{ __(
					'The simplified summary for this post will display here. Write the summary in the Accessibility Checker sidebar panel.',
					'accessibility-checker'
				) }
			</p>
		</div>
	);
};

export default Edit;
