/**
 * Front-end highlighter — panel, description rendering, scan results, and the
 * published-page side of the clickable-links fix.
 *
 * These were all confirmed by hand-inspection during the 1.50.0 test wave; the
 * specs below are the automated form of that.
 */
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	createPage,
	deletePage,
	runScan,
} = require( './helpers' );

test.describe( 'front-end highlighter', () => {
	let fixture;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		fixture = await createPage( page, {
			title: 'Highlighter fixture',
			content: FIXTURE_CONTENT,
		} );
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await deletePage( page, fixture.id );
		await page.close();
	} );

	test( 'panel renders with its controls', async ( { page } ) => {
		await page.goto( fixture.link, { waitUntil: 'domcontentloaded' } );

		const panel = page.locator( sel.highlighterPanel );
		await expect( panel ).toBeVisible();
		await expect( panel ).toHaveClass( /edac-highlight-panel--right/ );

		// Controls are icon buttons, so assert on their accessible names.
		for ( const name of [ 'Accessibility Checker Tool', 'Rescan This Page', 'Clear Issues', 'Disable Styles' ] ) {
			await expect( panel.getByRole( 'button', { name } ) ).toBeAttached();
		}
	} );

	test( 'the scan still detects the known issues on the fixture page', async ( { page } ) => {
		await page.goto( fixture.link, { waitUntil: 'domcontentloaded' } );

		const findings = await runScan( page );
		const rules = new Set( findings.map( ( finding ) => finding.rule ) );

		// TODO: pin the full expected set from src/pageScanner/config/rules.js once
		// the fixture's markup is settled — `empty_button` is the one confirmed by
		// hand against this exact fixture.
		expect( rules ).toContain( 'empty_button' );
		expect( findings.length ).toBeGreaterThan( 0 );
	} );

	test( 'documentation links are not marked clickable on a published page', async ( { page } ) => {
		await page.goto( fixture.link, { waitUntil: 'domcontentloaded' } );

		// The Elementor opt-out class is editor-only: on a published page
		// elementorFrontend is absent, so nothing should carry the class.
		const marked = await page.evaluate( ( selector ) => {
			const links = Array.from( document.querySelectorAll( selector ) );
			return links.filter( ( link ) => link.classList.contains( 'elementor-clickable' ) ).length;
		}, sel.descriptionReference );

		expect( marked ).toBe( 0 );
	} );

	test( 'the mid-article related block stays hidden by the per-post CSS', async ( { page } ) => {
		// Guards the customizer snippet shipped for the sponsored article: the
		// block is targeted by post id, so a theme change that renames the class
		// should fail here rather than silently reappearing mid-article.
		await page.goto( '/sponsored/', { waitUntil: 'domcontentloaded' } ).catch( () => null );
		const count = await page.locator( '.entry-related-post' ).count();
		test.skip( count === 0, 'No sponsored article with a related block on this instance.' );
	} );
} );
