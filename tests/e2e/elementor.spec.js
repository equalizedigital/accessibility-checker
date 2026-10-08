/* global process */
/**
 * Elementor integration — the save/rescan wiring.
 *
 * The first spec is the automated form of what was verified by hand: a real save
 * in a real editor repopulates the highlighter panel.
 *
 * The `fixme` specs are drafts for behaviour that could not be exercised in the
 * original environment. They are marked rather than deleted because they are the
 * cases the jest suite structurally cannot reach (it mocks `elementor.saver`).
 */
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	createPage,
	deletePage,
	openElementorEditor,
	saveInElementor,
} = require( './helpers' );

const EDITOR_TIMEOUT = Number( process.env.E2E_EDITOR_TIMEOUT || 240_000 );

test.describe( 'Elementor integration', () => {
	let fixture;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		const nonce = await ( async () => {
			await page.goto( '/wp-admin/index.php', { waitUntil: 'domcontentloaded' } );
			return page.evaluate( () => window.wpApiSettings?.nonce );
		} )();
		fixture = await createPage( page, {
			title: 'Elementor fixture',
			content: FIXTURE_CONTENT,
		} );
		expect( nonce ).toBeTruthy();
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await page.goto( '/wp-admin/index.php', { waitUntil: 'domcontentloaded' } );
		await deletePage( page, fixture.id );
		await page.close();
	} );

	// This was meant to be the flagship spec — automating the hand-verified "a real
	// save rescans the page" behaviour — and the interaction sequence below (panel
	// open, menu open, Clear Issues past its confirm(), all via dispatchEvent since
	// Playwright's simulated click never reaches these buttons inside Elementor's
	// preview iframe) is verified working up to the save call itself.
	//
	// The save call is where this breaks, and not on this plugin's side: Elementor
	// 4.3.1 (the version WordPress.org currently serves as "latest", which is what
	// global-setup.js's blueprint installs) throws inside its own internal
	// `beforeSave` hook — `Cannot read properties of undefined (reading 'toJSON')`
	// in ElementorCommonApp.beforeSave — for *any* save attempt on a page that was
	// created outside the Elementor editor (i.e. every fixture this suite creates
	// over the REST API). Confirmed via three independent paths, all hitting the
	// identical crash: the deprecated `elementor.saver.saveEditor()`, the modern
	// `$e.run('document/save/publish')` it forwards to, and a genuinely dirtied
	// document (`$e.run('document/elements/settings', ...)` then save — ruling out
	// "nothing to save" as the cause). Several of Elementor's own REST routes
	// (`design-system-sync/stylesheet`, `global-classes`, `components`) fail to
	// respond under WordPress Playground, and `beforeSave` appears to depend on
	// that state — this looks like a real Elementor 4.3.1 / Playground
	// incompatibility, not a bug in this plugin or a mistake in the test.
	//
	// To un-fixme this: either pin global-setup.js's blueprint to a pre-atomic-
	// widgets Elementor version (last one before 4.0), or once Playground/Elementor
	// resolve whatever's failing on those REST routes, drop this comment and the
	// `.fixme` below.
	test.fixme( 'saving in the Elementor editor rescans the page', async ( { page } ) => {
		const preview = await openElementorEditor( page, fixture.id, EDITOR_TIMEOUT );

		const panel = preview.locator( sel.highlighterPanel );
		await expect( panel ).toBeAttached( { timeout: EDITOR_TIMEOUT } );

		// Reset to a known state so the assertion is about the save, not leftovers
		// from an earlier scan. Clear Issues is role="menuitem" (correct ARIA for a
		// role="menu" widget), not role="button", and it sits behind the panel
		// toggle and "More options" menu — both of which have to be open first, same
		// as the front-end highlighter panel. Unlike the front end, the editor
		// preview may open the controls dialog automatically once a scan completes,
		// so the toggle is only clicked if it isn't already open (clicking an
		// already-open toggle would close it).
		//
		// Playwright's simulated mouse click never reaches these buttons inside
		// Elementor's scaled preview iframe (something in the editor's own canvas
		// chrome intercepts the pointer event at that position even though the
		// button reports as visible/actionable) — a real user's click does land, and
		// so does dispatchEvent('click'), which fires the same DOM click event
		// without Playwright's hit-testing, so that's used for every click here.
		const controls = preview.locator( '#edac-highlight-panel-controls' );
		if ( ! ( await controls.isVisible() ) ) {
			await preview.locator( '#edac-highlight-panel-toggle' ).dispatchEvent( 'click' );
		}
		await preview.locator( '#edac-highlight-menu-button' ).dispatchEvent( 'click' );
		// Clear Issues opens a native confirm() first — Playwright auto-dismisses any
		// dialog with no handler, which would silently no-op the clear.
		page.once( 'dialog', ( dialog ) => dialog.accept() );
		await panel.getByRole( 'menuitem', { name: 'Clear Issues' } ).dispatchEvent( 'click' );
		// #edac-highlight-panel-description-title/-content are static wrapper divs
		// always present in the panel's markup — clearIssues() empties their
		// innerHTML rather than removing them, so the reset/repopulate check has to
		// be on emptiness, not element count (which never reaches 0).
		const descriptionContent = panel.locator( '.edac-highlight-panel-description-content' );
		await expect( descriptionContent ).toBeEmpty();

		await saveInElementor( page, 'publish' );

		// The save event arrives after the request completes; on slow hosts this is
		// tens of seconds, which is why the timeout is generous.
		await expect( descriptionContent ).not.toBeEmpty( { timeout: 180_000 } );
	} );

	test( 'the editor scan is scoped to the page edit area', async ( { page } ) => {
		// Requires the scan-scope change (context include on the Elementor edit
		// area). Against a build without it, this fails on purpose: the editor's own
		// UI and the theme template get flagged as page issues.
		const preview = await openElementorEditor( page, fixture.id, EDITOR_TIMEOUT );
		await expect( preview.locator( sel.highlighterPanel ) ).toBeAttached( { timeout: EDITOR_TIMEOUT } );

		const outside = await page.evaluate( ( iframeSelector ) => {
			const frame = document.querySelector( iframeSelector );
			const doc = frame.contentDocument;
			const area = doc.querySelector( '[data-elementor-type="wp-page"]' );
			if ( ! area ) {
				return null;
			}
			const flagged = Array.from(
				doc.querySelectorAll( '[class*="edac-highlight-panel-issue"], .edac-highlight-panel-description' )
			);
			return flagged.filter( ( node ) => ! area.contains( node ) ).length;
		}, sel.editorPreviewIframe );

		test.skip( outside === null, 'No Elementor edit area in the preview.' );
		expect( outside ).toBe( 0 );
	} );

	test.fixme( 'an autosave does not rescan the page', async () => {
		// Approach: the guard only matters for a real autosave, and Elementor will
		// not autosave an untouched document — `elementor.saver.doAutoSave()` on a
		// clean editor fires no save event at all. To reach it:
		//   1. open the editor and make a genuine edit (add a container/widget
		//      through the panel UI, or via Elementor's document model),
		//   2. clear the preview panel, then wait for `elementor.saver` autosave
		//      (or call `saveAutoSave()`) and assert the panel is still empty,
		//   3. then save normally and assert the rescan does happen, so the test
		//      cannot pass by accident of the rescan being broken.
		// Expected save options carry `status: 'autosave'`, which the listener skips.
	} );

	test.fixme( 'the save listener detaches when the preview unloads', async () => {
		// Approach: the listener is registered on the parent's saver from inside the
		// preview iframe and removed on the iframe's `pagehide`. Reload the preview
		// independently (switch preview device in the editor, or navigate the iframe)
		// so a stale iframe fires `pagehide`, then trigger a save and assert only one
		// rescan happens — the stale closure must not rescan a document it no longer owns.
	} );

	test.fixme( 'the listener still attaches when the editor initialises slowly', async () => {
		// Approach: the listener polls `window.parent.elementor` for 10s (20 x 500ms)
		// and then gives up silently. Delay the parent's `elementor` global past that
		// window (route-intercept the editor's scripts, or stub `elementor` behind a
		// delayed getter before the preview loads), then assert a subsequent save
		// still rescans. Failing here is a genuine product finding, not a test bug.
	} );
} );
