/**
 * Block editor — scan results in the meta box and sidebar, and the save-triggered rescan.
 *
 * The editor scans the post through a preview request (`?edac_pageScanner=1`) as soon as
 * it loads, then refreshes the meta box and sidebar from the saved results.
 */
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	FIXTURE_RULE_TITLES,
	createPage,
	deletePage,
	openBlockEditor,
	expandMetaBoxes,
} = require( './helpers' );

test.describe( 'block editor', () => {
	test.use( { viewport: { width: 1440, height: 1100 } } );

	let fixture;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		fixture = await createPage( page, {
			title: 'Block editor fixture',
			content: FIXTURE_CONTENT,
		} );
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await deletePage( page, fixture.id );
		await page.close();
	} );

	test( 'the meta box Details tab lists the fixture\'s known issues', async ( { page } ) => {
		await openBlockEditor( page, fixture.id );
		await expandMetaBoxes( page );

		await expect( page.locator( sel.summaryTab ) ).toBeVisible();

		// The panel re-renders (and falls back to the Summary tab) when the scan data
		// arrives, so a click made before that is lost. Retry click + assertion together.
		const ruleTitle = ( title ) => page.locator( sel.detailsRuleTitle, { hasText: title } ).first();
		await expect( async () => {
			await page.locator( sel.detailsTab ).click();
			for ( const title of FIXTURE_RULE_TITLES ) {
				await expect( ruleTitle( title ) ).toBeVisible( { timeout: 2_000 } );
			}
		} ).toPass( { timeout: 120_000 } );
		await expect( ruleTitle( 'Empty Button' ) ).toContainText( '(1)' );
	} );

	test( 'the sidebar opens from the header and reports problems', async ( { page } ) => {
		await openBlockEditor( page, fixture.id );

		await page
			.locator( '.editor-header' )
			.getByRole( 'button', { name: 'Accessibility Checker', exact: true } )
			.click();

		const sidebar = page.locator( sel.sidebarContent );
		await expect( sidebar ).toBeVisible();
		await expect( sidebar.locator( sel.statusCard ).first() ).toBeVisible();

		// The first card is "Problems"; its value stays at 0 until the scan has saved.
		const problems = sidebar.locator( '.edac-status-card--clickable' ).first().locator( '.edac-status-card__value' );
		await expect( problems ).not.toHaveText( '0' );
	} );

	test( 'saving the post triggers a rescan and refreshes the sidebar data', async ( { page } ) => {
		await openBlockEditor( page, fixture.id );

		// Let the on-load scan finish so the save-triggered one is the only one left to see.
		await page.waitForResponse( /sidebar-data\/\d+/ );

		const rescan = page.waitForRequest( /edac_pageScanner=1/ );
		const refetch = page.waitForResponse( /sidebar-data\/\d+/ );

		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Block editor fixture (edited)' } );
		} );
		await page.locator( '.editor-post-publish-button' ).click();

		await rescan;
		await refetch;
	} );
} );
