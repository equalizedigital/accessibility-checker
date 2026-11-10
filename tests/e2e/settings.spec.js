/**
 * Settings page — values persist across a save, and the ones that gate editor behaviour
 * actually do.
 *
 * Every test restores what it changed in `finally`: the Playground instance is reused
 * across specs (and across runs locally), so leaked options would skew later tests.
 * Restoring a value is not always enough to undo the change, though — see the post-type
 * test, which is only run against an instance this run booted itself.
 */
const fs = require( 'fs' );
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	SETTINGS_PAGE,
	DISPOSABLE_MARKER,
	createPage,
	deletePage,
	openBlockEditor,
	openBlockEditorRaw,
	saveSettings,
} = require( './helpers' );

/**
 * Did this run boot the Playground instance itself, rather than reuse a running one?
 *
 * global-setup.js writes the marker for the instance it starts and removes it when it
 * finds one already up. Tests that are destructive past their own fixture (unchecking a
 * post type purges that post type's saved scan data for the whole site — see
 * `edac_sanitize_post_types()` in includes/options-page.php) are only safe on the
 * throwaway instance a cold run or CI boots, not on a long-lived local one.
 */
const DISPOSABLE_INSTANCE = fs.existsSync( DISPOSABLE_MARKER );

/**
 * Open the fixture in the block editor, waiting for the meta box only when the test
 * expects it to be there.
 *
 * `openBlockEditor()` waits on an element inside the meta box, so on the "it should be
 * gone" side it would spend its whole timeout waiting for something that is meant never
 * to arrive.
 *
 * @param {Object}  page     Playwright page.
 * @param {number}  postId   Post id.
 * @param {boolean} expected Whether the meta box is expected to be rendered.
 */
async function openFixture( page, postId, expected ) {
	if ( expected ) {
		await openBlockEditor( page, postId );
		return;
	}
	await openBlockEditorRaw( page, postId );
}

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

			// A reused instance need not be at the option's default, so record what it
			// is and restore that, rather than assuming it started on.
			await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
			const original = await page.locator( box ).isChecked();

			try {
				await saveSettings( page, ( p ) => p.locator( box ).uncheck() );
				await openBlockEditorRaw( page, fixture.id );
				// The sidebar still loads, so wait on the editor being fully up, not the meta box.
				await expect( page.locator( '.editor-header' ).getByRole( 'button', { name: 'Accessibility Checker', exact: true } ) ).toBeVisible();
				await expect( page.locator( sel.metaBox ) ).toHaveCount( 0 );
			} finally {
				await saveSettings( page, ( p ) => p.locator( box ).setChecked( original ) );
			}

			// Restored, so the meta box matches the value the option started at.
			await openFixture( page, fixture.id, original );
			await expect( page.locator( sel.metaBox ) ).toHaveCount( original ? 1 : 0 );
		} );

		test( 'removing a post type from "Post Types To Be Checked" removes the meta box from it', async ( { page } ) => {
			test.skip(
				! DISPOSABLE_INSTANCE,
				'Unchecking a post type purges that post type\'s saved scan data for the whole site, so this only runs against the throwaway instance this run booted.'
			);

			const box = '#edac_post_types_page';

			// As above: record what the instance was left with and restore that.
			await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
			const original = await page.locator( box ).isChecked();

			try {
				await saveSettings( page, ( p ) => p.locator( box ).uncheck() );
				await openBlockEditorRaw( page, fixture.id );
				await expect( page.locator( sel.metaBox ) ).toHaveCount( 0 );
			} finally {
				await saveSettings( page, ( p ) => p.locator( box ).setChecked( original ) );
			}

			await openFixture( page, fixture.id, original );
			await expect( page.locator( sel.metaBox ) ).toHaveCount( original ? 1 : 0 );
		} );
	} );
} );
