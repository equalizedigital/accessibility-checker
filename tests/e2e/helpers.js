/* global module */
/**
 * Shared helpers for the Playwright drafts.
 *
 * Everything here goes through the real WordPress instance: content is created
 * over the REST API with a nonce taken from an admin page, and the plugin's own
 * scanner bundle is injected the same way the highlighter injects it.
 */

const PLUGIN_SLUG = 'accessibility-checker';

/** Selectors confirmed against the running plugin. */
const sel = {
	highlighterPanel: '#edac-highlight-panel',
	descriptionReference: 'a.edac-highlight-panel-description-reference',
	descriptionTitle: '.edac-highlight-panel-description-title',
	editorPreviewIframe: '#elementor-preview-iframe',
	thickboxWindow: '#TB_window',
	elementorEditArea: '[data-elementor-type="wp-page"]',
};

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
	sel,
	FIXTURE_CONTENT,
	getRestNonce,
	createPage,
	deletePage,
	runScan,
	openElementorEditor,
	saveInElementor,
	expectNoPhpErrors,
};
