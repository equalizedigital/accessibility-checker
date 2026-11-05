describe( 'minimumTextSizeFix', () => {
	let minimumTextSizeFix;

	beforeEach( async () => {
		document.body.innerHTML = '';
		window.edac_frontend_fixes = {
			minimum_text_size: {
				enabled: true,
				min_size: 10,
			},
		};

		jest.resetModules();
		( { default: minimumTextSizeFix } = await import( '../../../src/frontendFixes/Fixes/minimumTextSizeFix' ) );
	} );

	test( 'increases text size for readable text below minimum', () => {
		document.body.innerHTML = '<p id="small">small text</p>';
		const el = document.getElementById( 'small' );
		el.style.fontSize = '8px';

		minimumTextSizeFix();

		expect( el.style.fontSize ).toBe( '10px' );
	} );

	test( 'does not modify excluded elements', () => {
		document.body.innerHTML = '<code id="code">tiny code</code>';
		const el = document.getElementById( 'code' );
		el.style.fontSize = '8px';

		minimumTextSizeFix();

		expect( el.style.fontSize ).toBe( '8px' );
	} );

	test( 'respects configured minimum when higher than default', () => {
		window.edac_frontend_fixes.minimum_text_size.min_size = 14;
		document.body.innerHTML = '<span id="small">small text</span>';
		const el = document.getElementById( 'small' );
		el.style.fontSize = '11px';

		minimumTextSizeFix();

		expect( el.style.fontSize ).toBe( '14px' );
	} );
} );
