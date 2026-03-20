/**
 * Tests for removeTextJustificationFix
 */

describe( 'removeTextJustificationFix', () => {
	let RemoveTextJustificationFix;
	const originalFixes = window.edac_frontend_fixes;

	beforeEach( async () => {
		document.body.innerHTML = '';
		window.edac_frontend_fixes = {
			remove_text_justification: {
				enabled: true,
				target: 'p',
			},
		};

		jest.resetModules();
		const module = await import( '../../../src/frontendFixes/Fixes/removeTextJustificationFix.js' );
		RemoveTextJustificationFix = module.default;
	} );

	afterEach( () => {
		window.edac_frontend_fixes = originalFixes;
	} );

	test( 'changes justified long text to left aligned', () => {
		const text = 'A'.repeat( 210 );
		document.body.innerHTML = `<p style="text-align: justify">${ text }</p>`;

		RemoveTextJustificationFix();

		const paragraph = document.querySelector( 'p' );
		expect( paragraph.style.textAlign ).toBe( 'left' );
	} );

	test( 'does not change short justified text', () => {
		document.body.innerHTML = '<p style="text-align: justify">Short text</p>';

		RemoveTextJustificationFix();

		const paragraph = document.querySelector( 'p' );
		expect( paragraph.style.textAlign ).toBe( 'justify' );
	} );

	test( 'does not change long non-justified text', () => {
		const text = 'A'.repeat( 210 );
		document.body.innerHTML = `<p style="text-align: left">${ text }</p>`;

		RemoveTextJustificationFix();

		const paragraph = document.querySelector( 'p' );
		expect( paragraph.style.textAlign ).toBe( 'left' );
	} );
} );
