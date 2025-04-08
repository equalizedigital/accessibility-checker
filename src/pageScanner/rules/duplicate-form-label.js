export default {
	id: 'duplicate_form_label',
	selector: 'input, select, textarea, [role="checkbox"], [role="radio"], [role="combobox"], [role="listbox"], [role="slider"], [role="spinbutton"], [role="textbox"]',
	tags: [],
	metadata: {
		description: 'Ensures that form fields do not have duplicate labels.',
		help: 'Form fields should have only one associated label.',
		helpUrl: 'https://a11ychecker.com/help1954',
	},
	any: [ 'duplicate_form_label_check' ],
	all: [],
	none: [],
};
