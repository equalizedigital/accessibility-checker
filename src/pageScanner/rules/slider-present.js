/**
 * Check to detect the presence of slider/carousel components that may require accessibility features.
 * Identifies various slider types including those with specific classes, data attributes, or roles.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} False if the node is a slider/carousel element (triggering violation), true otherwise (no violation).
 */

export default {
	id: 'slider_present',
	selector: '[class], [data-jssor-slider], [data-layerslider-uid]',
	excludeHidden: false,
	tags: [ 'cat.structure' ],
	metadata: {
		description: 'Identifies presence of slider/carousel components that may require accessibility improvements',
		help: 'Sliders and carousels must be keyboard accessible and provide appropriate navigation controls',
		impact: 'moderate',
	},
	all: [],
	any: [ 'slider_detected' ],
	none: [],
};
