/* global process, module, __dirname */
/**
 * Boots WordPress Playground and saves a logged-in storage state.
 *
 * Playground (CLI) runs PHP as WASM inside Node, so the WordPress instance lives
 * in memory for the duration of the run. Reuses an already-running instance on
 * the same port, which is what you want while iterating on specs locally.
 */
const { spawn } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

const PORT = process.env.E2E_PORT || 9400;
const BASE_URL = process.env.E2E_BASE_URL || `http://127.0.0.1:${ PORT }`;
const REPO_ROOT = path.resolve( __dirname, '..', '..' );
const AUTH_DIR = path.resolve( __dirname, '.auth' );
const PID_FILE = path.join( AUTH_DIR, 'playground.pid' );
const BOOT_TIMEOUT = Number( process.env.E2E_BOOT_TIMEOUT || 300_000 );

/**
 * Blueprint: Elementor plus this working copy, mounted over the plugin directory.
 *
 * @return {Object} Playground blueprint.
 */
function blueprint() {
	return {
		preferredVersions: { php: '8.3', wp: 'latest' },
		steps: [
			{ step: 'login' },
			{
				step: 'installPlugin',
				pluginData: { resource: 'wordpress.org/plugins', slug: 'elementor' },
				options: { activate: true },
			},
			// The working copy is only mounted over wp-content/plugins/accessibility-checker
			// (via --mount below) — mounting a plugin's files does not add it to
			// active_plugins, so it has to be activated explicitly or every one of its
			// own admin pages 403s and none of its hooks ever run.
			{
				step: 'activatePlugin',
				pluginPath: 'accessibility-checker/accessibility-checker.php',
			},
		],
	};
}

/**
 * Is something already serving the base URL?
 *
 * @param {string} url Base URL.
 * @return {Promise<boolean>} Whether it answered.
 */
async function isUp( url ) {
	try {
		const res = await fetch( url, { redirect: 'manual' } );
		return res.status > 0;
	} catch ( error ) {
		return false;
	}
}

/**
 * Wait for a line matching the pattern on a child process's output.
 *
 * The child's own output is echoed to this process's stderr as it arrives (not
 * just buffered for a failure message) so a slow boot can be watched live, and
 * the last portion of it is attached to a timeout/early-exit error — without
 * this, either failure mode was previously just "Timed out waiting for
 * /Ready!.../" or "exited early with code 1" with no way to tell why (e.g. npx
 * failing to resolve the CLI package, or a WASM/JSPI incompatibility on a very
 * new Node version).
 *
 * @param {Object} child   Child process.
 * @param {RegExp} pattern Pattern to wait for.
 * @param {number} timeout Milliseconds to wait.
 * @return {Promise<void>} Resolves when matched.
 */
function waitForLog( child, pattern, timeout ) {
	return new Promise( ( resolve, reject ) => {
		let recent = '';
		const remember = ( chunk ) => {
			recent = ( recent + String( chunk ) ).slice( -4000 );
		};

		const timer = setTimeout( () => {
			reject( new Error( `Timed out waiting for ${ pattern }. Last output:\n${ recent }` ) );
		}, timeout );
		const scan = ( chunk ) => {
			const text = String( chunk );
			process.stderr.write( `[playground] ${ text }` );
			remember( text );
			if ( pattern.test( text ) ) {
				clearTimeout( timer );
				resolve();
			}
		};
		child.stdout.on( 'data', scan );
		child.stderr.on( 'data', scan );
		child.on( 'exit', ( code ) => {
			clearTimeout( timer );
			reject( new Error( `Playground exited early with code ${ code }. Last output:\n${ recent }` ) );
		} );
	} );
}

module.exports = async () => {
	fs.mkdirSync( AUTH_DIR, { recursive: true } );

	if ( ! ( await isUp( BASE_URL ) ) ) {
		const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'edac-playground-' ) );
		const blueprintPath = path.join( dir, 'blueprint.json' );
		fs.writeFileSync( blueprintPath, JSON.stringify( blueprint(), null, 2 ) );

		const child = spawn(
			'npx',
			[
				'--yes',
				'@wp-playground/cli@latest',
				'server',
				`--blueprint=${ blueprintPath }`,
				`--port=${ PORT }`,
				'--login',
				'--workers=1',
				// mount-before-install (not the post-install --mount): the blueprint's
				// activatePlugin step below needs the plugin's files on disk already, and
				// --mount only attaches after the site + blueprint steps have run.
				`--mount-before-install=${ REPO_ROOT }:/wordpress/wp-content/plugins/accessibility-checker`,
			],
			{ stdio: [ 'ignore', 'pipe', 'pipe' ], detached: true }
		);
		fs.writeFileSync( PID_FILE, String( child.pid ) );
		await waitForLog( child, /Ready! WordPress is running/, BOOT_TIMEOUT );
	}

	const browser = await chromium.launch( { channel: 'chrome' } );
	const page = await browser.newPage();
	await page.goto( `${ BASE_URL }/wp-login.php`, { waitUntil: 'domcontentloaded' } );
	await page.fill( '#user_login', process.env.E2E_ADMIN_USER || 'admin' );
	await page.fill( '#user_pass', process.env.E2E_ADMIN_PASS || 'password' );
	await Promise.all( [
		page.waitForURL( /wp-admin/, { timeout: 120_000 } ),
		page.click( '#wp-submit' ),
	] );
	await page.context().storageState( { path: path.join( AUTH_DIR, 'admin.json' ) } );
	await browser.close();
};
