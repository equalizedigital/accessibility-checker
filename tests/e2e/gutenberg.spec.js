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
	trackScanSaveEvents,
	waitForScanSaveEvents,
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
		// Watch from before the editor loads: it scans the post as soon as it opens,
		// so a listener registered after openBlockEditor() can miss that scan's own
		// sidebar refresh — and then treat a leftover response as the save's.
		const sidebarFetches = new Set();
		page.on( 'request', ( request ) => {
			if ( /sidebar-data\/\d+/.test( request.url() ) ) {
				sidebarFetches.add( request );
			}
		} );
		await trackScanSaveEvents( page );

		await openBlockEditor( page, fixture.id );

		// Let the on-load scan finish and save, so the rescan waited for below is
		// unambiguously the one the save triggers.
		await waitForScanSaveEvents( page );

		const fetchesBeforeSave = new Set( sidebarFetches );

		await page.evaluate( () => {
			window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Block editor fixture (edited)' } );
		} );
		const rescan = page.waitForRequest( /edac_pageScanner=1/, { timeout: 180_000 } );
		await page.locator( '.editor-post-publish-button' ).click();

		await rescan;

		// Registered once the rescan is under way, and ignoring anything already
		// requested: the save-triggered refresh only starts after that rescan saves,
		// so a request started earlier cannot be it.
		const refresh = await page.waitForResponse( ( res ) => (
			/sidebar-data\/\d+/.test( res.url() ) && ! fetchesBeforeSave.has( res.request() )
		), { timeout: 180_000 } );

		// A refresh that fails keeps the sidebar's previous data and only logs a
		// warning (src/sidebar/store/accessibility-checker-store.js), so assert the
		// payload arrived rather than that a response did.
		expect( refresh.ok() ).toBeTruthy();
		expect( ( await refresh.json() ).success ).toBe( true );
	} );
} );
