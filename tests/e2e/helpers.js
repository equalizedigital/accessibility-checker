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
	await page.evaluate( async ( pageId ) => {
		await fetch( `/wp-json/wp/v2/pages/${ pageId }?force=true`, {
			method: 'DELETE',
			headers: { 'X-WP-Nonce': window.wpApiSettings?.nonce },
		} );
	}, id );
}

/**
 * Run the plugin's scanner in the current document, exactly as the highlighter
 * does — by injecting its bundle and calling the runner it exposes.
 *
 * Note: axe is configured with `reporter: 'raw'`, so the runner returns an array
 * of rule results rather than the usual `{ violations }` object.
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
		return ( results || [] ).map( ( item ) => ( {
			rule: item.rule_id || item.id,
			selector: String( item.dom_selector || item.selector || '' ),
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
			const frame = document.querySelector( selector );
			return Boolean( frame && frame.contentDocument && frame.contentDocument.body.className.includes( 'elementor-page' ) );
		},
		sel.editorPreviewIframe,
		{ timeout }
	);

	return preview;
}

/**
 * Ask Elementor's own saver to save, which is what the editor's Publish button does.
 *
 * `elementor.saver` is a custom event emitter in Elementor 4.x — `_events` is not
 * available, so assert on behaviour, never on a listener count.
 *
 * @param {Object} page   Playwright page.
 * @param {string} status Status to save with (`publish` or `draft`).
 */
async function saveInElementor( page, status = 'publish' ) {
	await page.evaluate( ( saveStatus ) => {
		window.elementor.saver.saveEditor( { status: saveStatus } );
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
