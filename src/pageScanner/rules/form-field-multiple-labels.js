/**
 * Extended rule for form field multiple labels.
 * Detects both multiple label elements AND conflicting labeling methods (HTML + ARIA).
 */

export default {
	id: 'form_field_multiple_labels',
	selector: 'input:not([type="hidden"]):not([type="image"]):not([type="button"]):not([type="submit"]):not([type="reset"]), select, textarea',
	tags: [
		'cat.forms',
		'wcag2a',
		'wcag332',
	],
	metadata: {
		description: 'Ensure form field does not have multiple label elements or conflicting labeling methods',
		help: 'Form field must not have multiple label elements or conflicting labeling methods',
		impact: 'moderate',
	},
	all: [],
	any: [],
	none: [ 'form-field-conflicting-labels' ],
};
