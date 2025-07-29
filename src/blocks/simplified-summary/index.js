const { registerBlockType } = wp.blocks;
const { useSelect } = wp.data;
const { __ } = wp.i18n;
const { createElement: el } = wp.element;

registerBlockType('accessibility-checker/simplified-summary', {
	edit: function({ context }) {
		const { postId } = context;
		
		const blockData = useSelect(function(select) {
			const postMeta = select('core/editor').getEditedPostAttribute('meta') || {};
			
			// Get the simplified summary from post meta
			const summary = postMeta._edac_simplified_summary || '';
			
			// Check if manual placement is enabled by checking site options
			// We'll assume manual placement if position is 'none'
			const siteOptions = select('core').getEntityRecord('root', 'site');
			const position = (siteOptions && siteOptions.edac_simplified_summary_position) || 'after';
			const isManual = position === 'none';
			
			return {
				simplifiedSummary: summary,
				isManualPlacement: isManual
			};
		}, [postId]);

		// Don't render anything if manual placement is not enabled
		if (!blockData.isManualPlacement) {
			return el('div', { className: 'edac-block-notice' },
				el('p', {}, __('Simplified Summary block is only available when "Insert manually" is selected in the plugin settings.', 'accessibility-checker'))
			);
		}

		// Don't render anything if no simplified summary exists
		if (!blockData.simplifiedSummary) {
			return el('div', { className: 'edac-block-notice' },
				el('p', {}, __('No simplified summary found for this post.', 'accessibility-checker'))
			);
		}

		// Render the simplified summary with the same markup as PHP version
		return el('div', { className: 'edac-simplified-summary' },
			el('h2', {}, __('Simplified Summary', 'accessibility-checker')),
			el('p', {}, blockData.simplifiedSummary)
		);
	},
	save: function() {
		// Return null since this is a dynamic block rendered server-side
		return null;
	}
});