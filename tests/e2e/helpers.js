/* global module, __dirname */
/**
 * Shared helpers for the Playwright drafts.
 *
 * Everything here goes through the real WordPress instance: content is created
 * over the REST API with a nonce taken from an admin page, and the plugin's own
 * scanner bundle is injected the same way the highlighter injects it.
 */

const path = require( 'path' );
const { expect } = require( '@playwright/test' );

const PLUGIN_SLUG = 'accessibility-checker';

/**
 * Marker global-setup.js writes when this run booted the Playground instance
 * itself, and removes when it reused one that was already running.
 *
 * A marker rather than a check for the pid file: the pid file can be left behind
 * by a run that was interrupted, while this one says something about *this* run.
 */
const DISPOSABLE_MARKER = path.resolve( __dirname, '.auth', 'disposable-instance' );

/** Selectors confirmed against the running plugin. */
const sel = {
	highlighterPanel: '#edac-highlight-panel',
	descriptionReference: 'a.edac-highlight-panel-description-reference',
	descriptionTitle: '.edac-highlight-panel-description-title',
	editorPreviewIframe: '#elementor-preview-iframe',
	thickboxWindow: '#TB_window',
	elementorEditArea: '[data-elementor-type="wp-page"]',
	metaBox: '#edac-meta-box',
	summaryTab: '#edac-summary-tab',
	detailsTab: '#edac-details-tab',
	detailsRuleTitle: '.edac-details-rule-title',
	sidebarContent: '.edac-sidebar__content',
	statusCard: '.edac-status-card',
};

/** Rules the fixture page is known to trip, by their displayed titles. */
const FIXTURE_RULE_TITLES = [
	'Empty Button',
	'Empty Link',
	'Image Missing Alternative Text',
	'Incorrect Heading Order',
];

const SETTINGS_PAGE = '/wp-admin/admin.php?page=accessibility_checker_settings';

/** A page with deliberate, findable accessibility problems. */
const FIXTURE_CONTENT = [
	'<h3>Subheading out of order</h3>',
	'<img src="/wp-content/plugins/accessibility-checker/assets/images/logo.svg">',
	'<button></button>',
	'<p>Some body copy for the scan.</p>',
	'<a href="/"></a>',
].join( '' );

/**
 * Read the REST nonce from an admin page.
 *
 * @param {Object} page Playwright page.
 * @return {Promise<string>} Nonce.
 */
async function getRestNonce( page ) {
	await page.goto( '/wp-admin/index.php', { waitUntil: 'domcontentloaded' } );
	return page.evaluate( () => window.wpApiSettings?.nonce ?? null );
}

/**
 * Create a page over the REST API.
 *
 * @param {Object} page             Playwright page.
 * @param {Object} options          Page options.
 * @param {string} options.title    Page title.
 * @param {string} options.content  Page content.
 * @param {string} [options.status] Post status.
 * @return {Promise<{id: number, link: string}>} Created page.
 */
async function createPage( page, { title, content, status = 'publish' } ) {
	const nonce = await getRestNonce( page );
	const created = await page.evaluate(
		async ( args ) => {
			const res = await fetch( '/wp-json/wp/v2/pages', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': args.nonce },
				body: JSON.stringify( { title: args.title, content: args.content, status: args.status } ),
			} );
			const json = await res.json();
			return { id: json.id, link: json.link, error: json.code || null };
		},
		{ nonce, title, content, status }
	);

	if ( ! created.id ) {
		throw new Error( `Could not create fixture page: ${ created.error }` );
	}
	return created;
}

/**
 * Delete a page over the REST API.
 *
 * @param {Object} page Playwright page.
 * @param {number} id   Page id.
 */
async function deletePage( page, id ) {
	if ( ! id ) {
		return;
	}
	// A fresh Playwright page starts at about:blank, which has no base URL to
	// resolve a relative fetch() against and no wpApiSettings global — go to an
	// admin page first, same as getRestNonce().
	const nonce = await getRestNonce( page );
	await page.evaluate( async ( args ) => {
		await fetch( `/wp-json/wp/v2/pages/${ args.pageId }?force=true`, {
			method: 'DELETE',
			headers: { 'X-WP-Nonce': args.nonce },
		} );
	}, { pageId: id, nonce } );
}

/**
 * Run the plugin's scanner in the current document, exactly as the highlighter
 * does — by injecting its bundle and calling the runner it exposes.
 *
 * `window.runAccessibilityScan()` resolves `{ rules, rulesMin, violations }`
 * (see `scan()` in src/pageScanner/index.js); `violations` is the already
 * flattened, already-failed-only list, each shaped by `processViolation()`
 * with a camelCase `ruleId`.
 *
 * @param {Object} page Playwright page.
 * @return {Promise<Array<{rule: string, selector: string}>>} Findings.
 */
async function runScan( page ) {
	await page.addScriptTag( {
		url: `/wp-content/plugins/${ PLUGIN_SLUG }/build/pageScanner.bundle.js`,
	} );
	await page.waitForFunction( () => typeof window.runAccessibilityScan === 'function', null, {
		timeout: 120_000,
	} );

	return page.evaluate( async () => {
		const results = await window.runAccessibilityScan( {} );
		return ( results?.violations || [] ).map( ( item ) => ( {
			rule: item.ruleId,
			selector: String( item.selector || '' ),
		} ) );
	} );
}

/**
 * Open the Elementor editor for a post and wait for the preview to render the page.
 *
 * @param {Object} page    Playwright page.
 * @param {number} postId  Post id.
 * @param {number} timeout How long to wait for the editor.
 * @return {Promise<Object>} frameLocator for the preview iframe.
 */
async function openElementorEditor( page, postId, timeout = 240_000 ) {
	await page.goto( `/wp-admin/post.php?post=${ postId }&action=elementor`, {
		waitUntil: 'domcontentloaded',
	} );
	await page.waitForSelector( sel.editorPreviewIframe, { timeout } );

	const preview = page.frameLocator( sel.editorPreviewIframe );
	await preview.locator( 'body' ).waitFor( { timeout } );
	await page.waitForFunction(
		( selector ) => {
			// The preview iframe reloads more than once during editor init, so
			// contentDocument.body is transiently null between those reloads — an
			// uncaught throw here would fail waitForFunction immediately instead
			// of retrying, so every step is optional-chained.
			const frame = document.querySelector( selector );
			return Boolean( frame?.contentDocument?.body?.className?.includes( 'elementor-page' ) );
		},
		sel.editorPreviewIframe,
		{ timeout }
	);

	return preview;
}

/**
 * Ask Elementor's own saver to save, which is what the editor's Publish/Update
 * button does.
 *
 * `elementor.saver.saveEditor()` is hard-deprecated since Elementor 2.9.0; on
 * 4.3.1 it actually throws (`ElementorCommonApp.beforeSave` reads `.toJSON()`
 * off something undefined) rather than just logging a deprecation notice, so
 * this goes through the command it forwards to instead: `$e.run(
 * 'document/save/' + status )`. That said — see the note on the "saving in the
 * Elementor editor rescans the page" test.fixme in elementor.spec.js — the
 * same crash reproduces there too, from a genuinely dirtied document and even
 * via the real Publish button, so this appears to be a real Elementor
 * 4.3.1 / WordPress Playground incompatibility, not something this helper can
 * route around.
 *
 * @param {Object} page   Playwright page.
 * @param {string} status Status to save with (`publish`, `update`, or `draft`).
 */
async function saveInElementor( page, status = 'publish' ) {
	await page.evaluate( ( saveStatus ) => {
		window.$e.run( `document/save/${ saveStatus }` );
	}, status );
}

/**
 * Reset the admin's email opt-in user meta so the opt-in modal shows again.
 *
 * Goes through a test-only route installed by global-setup.js (e2e-helpers.php),
 * which hands back what it found so `restoreOptIn()` can put it back.
 *
 * @param {Object} page Playwright page.
 * @return {Promise<Object>} Whether each meta key was set before the reset.
 */
async function resetOptIn( page ) {
	const nonce = await getRestNonce( page );
	const result = await page.evaluate( async ( restNonce ) => {
		const res = await fetch( '/wp-json/edac-e2e/v1/reset-opt-in', {
			method: 'POST',
			headers: { 'X-WP-Nonce': restNonce },
		} );
		return res.json();
	}, nonce );

	if ( ! result.success ) {
		throw new Error( 'Could not reset the opt-in user meta.' );
	}

	return result.previous;
}

/**
 * Put the opt-in user meta back the way `resetOptIn()` found it.
 *
 * Worth doing even though the modal has to be un-seen for the test to run: on a
 * reused instance the admin is a real user, and deleting their saved opt-in state
 * shows them the subscription form again.
 *
 * @param {Object} page     Playwright page.
 * @param {Object} previous The state `resetOptIn()` returned.
 */
async function restoreOptIn( page, previous ) {
	if ( ! previous ) {
		return;
	}

	const nonce = await getRestNonce( page );
	await page.evaluate( async ( args ) => {
		await fetch( '/wp-json/edac-e2e/v1/restore-opt-in', {
			method: 'POST',
			headers: { 'X-WP-Nonce': args.restNonce, 'Content-Type': 'application/json' },
			body: JSON.stringify( { previous: args.previous } ),
		} );
	}, { restNonce: nonce, previous } );
}

/**
 * Open a post in the block editor and wait for the plugin's meta box to be in the DOM.
 *
 * Block-editor-only: the meta box is injected by the "Block Editor Metabox" setting, so
 * callers that turn that off should use `openBlockEditorRaw()` and not wait for it.
 *
 * @param {Object} page   Playwright page.
 * @param {number} postId Post id.
 */
async function openBlockEditor( page, postId ) {
	await openBlockEditorRaw( page, postId );
	await page.waitForSelector( sel.summaryTab, { state: 'attached', timeout: 180_000 } );
}

/**
 * Open a post in the block editor and wait for the editor chrome only.
 *
 * @param {Object} page   Playwright page.
 * @param {number} postId Post id.
 */
async function openBlockEditorRaw( page, postId ) {
	await page.goto( `/wp-admin/post.php?post=${ postId }&action=edit`, { waitUntil: 'load' } );
	await waitForEditorChrome( page );

	if ( await dismissWelcomeGuide( page ) ) {
		// The guide is off now, but it was already rendered on this page — see
		// dismissWelcomeGuide() — so load the editor again to get one without it.
		await page.reload( { waitUntil: 'load' } );
		await waitForEditorChrome( page );
	}
}

/**
 * Wait for the block editor's chrome to be up.
 *
 * @param {Object} page Playwright page.
 */
async function waitForEditorChrome( page ) {
	await page.waitForSelector( '.editor-post-publish-button', { timeout: 180_000 } );
}

/**
 * Switch off the block editor's onboarding guide, and say whether the page it was found
 * on has to be reloaded to clear it.
 *
 * On a site whose admin has never dismissed it — every fresh Playground instance, so
 * every CI run — the guide opens over the canvas as soon as the editor is up. It is a
 * modal: its overlay swallows pointer events and the editor behind it is inert, so the
 * editor's own controls (the "Meta Boxes" pane toggle, the Accessibility Checker header
 * button, the save button) can be neither clicked nor found by role while it is open.
 *
 * Dispatching the preference is what the guide's own close button does, and the
 * preferences store persists it to the user's meta, so the guide is gone from the *next*
 * editor load. It does not reliably leave the load it has already rendered on, which is
 * why the caller reloads: the saved choice makes the reloaded editor come up without it.
 *
 * @param {Object} page Playwright page.
 * @return {Promise<boolean>} Whether the caller needs to reload the editor.
 */
async function dismissWelcomeGuide( page ) {
	await page.waitForFunction(
		() => Boolean( window.wp?.data?.select?.( 'core/preferences' ) ),
		null,
		{ timeout: 60_000 }
	);

	// The editor booted with the guide already switched off, i.e. every open after the
	// first one in a run: nothing is on screen and nothing needs reloading.
	const switchedOffOnLoad = await page.evaluate(
		() => window.wp.data.select( 'core/preferences' ).get( 'core/edit-post', 'welcomeGuide' ) === false
	);
	if ( switchedOffOnLoad ) {
		return false;
	}

	// Wait for the choice to reach the server before the caller reloads, or the reloaded
	// editor would boot from the old value and put the guide back.
	const saved = page
		.waitForResponse(
			( res ) => res.url().includes( '/wp/v2/users/me' ) && res.request().method() === 'POST',
			{ timeout: 30_000 }
		)
		.catch( () => null );

	// The store applies the user's saved state while the editor boots, which can land
	// after the first dispatch, so dispatch until it reads back.
	await expect.poll( async () => {
		await page.evaluate( () => {
			const { dispatch } = window.wp.data;
			dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuideStyles', false );
		} );
		return page.evaluate( () => window.wp.data.select( 'core/preferences' ).get( 'core/edit-post', 'welcomeGuide' ) );
	}, { timeout: 60_000 } ).toBe( false );

	await saved;

	return true;
}

/**
 * Expand the collapsed "Meta Boxes" pane at the bottom of the block editor.
 *
 * Activated from the keyboard: the pane's resize handle overlaps the toggle button, so
 * a pointer click is intercepted by the separator.
 *
 * @param {Object} page Playwright page.
 */
async function expandMetaBoxes( page ) {
	const toggle = page.getByRole( 'button', { name: 'Meta Boxes' } );
	if ( ( await toggle.getAttribute( 'aria-expanded' ) ) !== 'true' ) {
		await toggle.focus();
		await page.keyboard.press( 'Enter' );
	}
}

/**
 * Start counting the plugin's own scan-save events before the page navigates.
 *
 * The block editor scans the post as soon as it opens and dispatches
 * `edac_js_scan_save_complete` on `top` once the results are stored (see
 * `saveScanResults()` in src/editorApp/checkPage.js). Counting from a frame init
 * script means a later wait can tell whether that first scan has already
 * finished, instead of inferring it from how long the page took to load.
 *
 * Must be called before the navigation it should see, i.e. before
 * `openBlockEditor()`.
 *
 * @param {Object} page Playwright page.
 */
async function trackScanSaveEvents( page ) {
	await page.addInitScript( () => {
		window.__edacScanSaveEvents = 0;
		window.addEventListener( 'edac_js_scan_save_complete', () => {
			window.__edacScanSaveEvents += 1;
		} );
	} );
}

/**
 * Wait for the plugin to have saved scan results `count` times since the page
 * navigated. Requires `trackScanSaveEvents()` to have run before navigation.
 *
 * @param {Object} page    Playwright page.
 * @param {number} count   How many saves to wait for.
 * @param {number} timeout Milliseconds to wait.
 */
async function waitForScanSaveEvents( page, count = 1, timeout = 180_000 ) {
	await page.waitForFunction(
		( expected ) => ( window.__edacScanSaveEvents || 0 ) >= expected,
		count,
		{ timeout }
	);
}

/**
 * Submit the plugin's settings form after applying a change to it.
 *
 * @param {Object}   page   Playwright page.
 * @param {Function} change Async callback that edits the form fields.
 */
async function saveSettings( page, change ) {
	await page.goto( SETTINGS_PAGE, { waitUntil: 'load' } );
	await change( page );
	await Promise.all( [
		page.waitForURL( /settings-updated=true/ ),
		page.locator( '#submit' ).click(),
	] );
}

/**
 * Assert a WordPress admin response contains no PHP diagnostics.
 *
 * @param {string} html Response body.
 */
function expectNoPhpErrors( html ) {
	const patterns = [
		/Fatal error/i,
		/Parse error/i,
		/Warning:\s/,
		/Notice:\s/,
		/Deprecated:\s/,
	];
	for ( const pattern of patterns ) {
		if ( pattern.test( html ) ) {
			throw new Error( `PHP diagnostics in response: ${ html.match( pattern )[ 0 ] }` );
		}
	}
}

module.exports = {
	PLUGIN_SLUG,
	DISPOSABLE_MARKER,
	sel,
	FIXTURE_CONTENT,
	FIXTURE_RULE_TITLES,
	SETTINGS_PAGE,
	getRestNonce,
	createPage,
	deletePage,
	runScan,
	openElementorEditor,
	saveInElementor,
	openBlockEditor,
	openBlockEditorRaw,
	dismissWelcomeGuide,
	waitForEditorChrome,
	expandMetaBoxes,
	trackScanSaveEvents,
	waitForScanSaveEvents,
	saveSettings,
	resetOptIn,
	restoreOptIn,
	expectNoPhpErrors,
};
