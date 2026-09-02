describe( 'RemoveEmptyParagraphTagsFix', () => {
	let RemoveEmptyParagraphTagsFix;

	beforeEach( async () => {
		document.body.innerHTML = '';
		window.edac_frontend_fixes = {
			remove_empty_paragraph_tags: {
				enabled: true,
			},
		};

		jest.resetModules();
		const module = await import( '../../../src/frontendFixes/Fixes/removeEmptyParagraphTagsFix.js' );
		RemoveEmptyParagraphTagsFix = module.default;
	} );

	test( 'removes empty paragraph tags', () => {
		document.body.innerHTML = '<p> </p><p>Real content</p><p></p>';

		RemoveEmptyParagraphTagsFix();

		const paragraphs = document.querySelectorAll( 'p' );
		expect( paragraphs ).toHaveLength( 1 );
		expect( paragraphs[ 0 ].textContent ).toBe( 'Real content' );
	} );

	test( 'does not remove paragraphs with non-text child nodes', () => {
		document.body.innerHTML = '<p><span></span></p>';

		RemoveEmptyParagraphTagsFix();

		expect( document.querySelectorAll( 'p' ) ).toHaveLength( 1 );
	} );

	test( 'does not remove aria-hidden paragraphs', () => {
		document.body.innerHTML = '<p aria-hidden="true"></p>';

		RemoveEmptyParagraphTagsFix();

		expect( document.querySelectorAll( 'p' ) ).toHaveLength( 1 );
	} );
} );
