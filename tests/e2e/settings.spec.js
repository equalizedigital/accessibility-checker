/**
 * Settings page — values persist across a save, and the ones that gate editor behaviour
 * actually do.
 *
 * Every test restores what it changed in `finally`: the Playground instance is reused
 * across specs (and across runs locally), so leaked options would skew later tests.
 */
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	SETTINGS_PAGE,
	createPage,
	deletePage,
	openBlockEditor,
	openBlockEditorRaw,
	saveSettings,
} = require( './helpers' );

test.describe( 'settings', () => {
	test( 'a checkbox value persists across a save', async ( { page } ) => {
		const box = '#edac_delete_data';
		await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
		const original = await page.locator( box ).isChecked();

		try {
			await saveSettings( page, ( p ) => p.locator( box ).setChecked( ! original ) );
			await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
			await expect( page.locator( box ) ).toBeChecked( { checked: ! original } );
		} finally {
			await saveSettings( page, ( p ) => p.locator( box ).setChecked( original ) );
		}
	} );

	test( 'the highlighter position radio persists across a save', async ( { page } ) => {
		const radios = 'input[name="edac_frontend_highlighter_position"]';
		await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );

		const original = await page.locator( `${ radios }:checked` ).inputValue();
		const other = await page.locator( radios ).evaluateAll(
			( els, current ) => els.map( ( el ) => el.value ).find( ( value ) => value !== current ),
			original
		);

		try {
			await saveSettings( page, ( p ) => p.locator( `${ radios }[value="${ other }"]` ).check() );
			await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
			await expect( page.locator( `${ radios }:checked` ) ).toHaveValue( other );
		} finally {
			await saveSettings( page, ( p ) => p.locator( `${ radios }[value="${ original }"]` ).check() );
		}
	} );

	test.describe( 'editor gating', () => {
		let fixture;

		test.beforeAll( async ( { browser } ) => {
			const page = await browser.newPage();
			fixture = await createPage( page, { title: 'Settings fixture', content: FIXTURE_CONTENT } );
			await page.close();
		} );

		test.afterAll( async ( { browser } ) => {
			const page = await browser.newPage();
			await deletePage( page, fixture.id );
			await page.close();
		} );

		test( 'turning off "Block Editor Metabox" removes the meta box from the block editor', async ( { page } ) => {
			const box = '#edac_show_metabox_in_block_editor';
			await saveSettings( page, ( p ) => p.locator( box ).uncheck() );

			try {
				await openBlockEditorRaw( page, fixture.id );
				// The sidebar still loads, so wait on the editor being fully up, not the meta box.
				await expect( page.locator( '.editor-header' ).getByRole( 'button', { name: 'Accessibility Checker', exact: true } ) ).toBeVisible();
				await expect( page.locator( sel.metaBox ) ).toHaveCount( 0 );
			} finally {
				await saveSettings( page, ( p ) => p.locator( box ).check() );
			}

			await openBlockEditor( page, fixture.id );
			await expect( page.locator( sel.metaBox ) ).toHaveCount( 1 );
		} );

		test( 'removing a post type from "Post Types To Be Checked" removes the meta box from it', async ( { page } ) => {
			const box = '#edac_post_types_page';
			await saveSettings( page, ( p ) => p.locator( box ).uncheck() );

			try {
				await openBlockEditorRaw( page, fixture.id );
				await expect( page.locator( sel.metaBox ) ).toHaveCount( 0 );
			} finally {
				await saveSettings( page, ( p ) => p.locator( box ).check() );
			}

			await openBlockEditor( page, fixture.id );
			await expect( page.locator( sel.metaBox ) ).toHaveCount( 1 );
		} );
	} );
} );
