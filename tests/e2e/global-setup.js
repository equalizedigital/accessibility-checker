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
const { DISPOSABLE_MARKER } = require( './helpers' );

const PORT = process.env.E2E_PORT || 9400;
const BASE_URL = process.env.E2E_BASE_URL || `http://127.0.0.1:${ PORT }`;
const REPO_ROOT = path.resolve( __dirname, '..', '..' );
const AUTH_DIR = path.resolve( __dirname, '.auth' );
const PID_FILE = path.join( AUTH_DIR, 'playground.pid' );
// REST namespace registered by e2e-helpers.php, installed as a mu-plugin below.
const E2E_REST_NAMESPACE = 'edac-e2e/v1';
// Pinned so the npx install is cacheable in CI; bump deliberately.
const PLAYGROUND_VERSION = process.env.E2E_PLAYGROUND_VERSION || '3.1.62';
// Playground warns below 6 workers (deadlocks on file locks), so default to that.
const PLAYGROUND_WORKERS = process.env.E2E_PLAYGROUND_WORKERS || 6;
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
			// Test-only REST routes (see e2e-helpers.php).
			{
				step: 'writeFile',
				path: '/wordpress/wp-content/mu-plugins/e2e-helpers.php',
				data: fs.readFileSync( path.join( __dirname, 'e2e-helpers.php' ), 'utf8' ),
			},
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
 * Does the running instance register the test-only REST namespace?
 *
 * The blueprint's `writeFile` step only runs when this process boots the instance,
 * so an instance started by an earlier run (or a different branch) has no
 * `edac-e2e/v1` routes and `resetOptIn()` would fail mid-test instead. There is no
 * way to install the mu-plugin into an instance that is already up, so the only
 * answer is to say so and stop.
 *
 * @param {string} url Base URL.
 * @return {Promise<boolean>} Whether the namespace is registered.
 */
async function hasE2eRoutes( url ) {
	try {
		const res = await fetch( `${ url }/wp-json/` );
		const index = await res.json();
		return Array.isArray( index.namespaces ) && index.namespaces.includes( E2E_REST_NAMESPACE );
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

	// Only the instance this run boots itself is throwaway: global-teardown.js stops
	// that one and leaves a pre-existing one running, so the marker says which specs
	// may be destructive. Written/removed per run — the pid file can outlive an
	// interrupted run, so it cannot be used for this.
	fs.rmSync( DISPOSABLE_MARKER, { force: true } );

	if ( ! ( await isUp( BASE_URL ) ) ) {
		const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'edac-playground-' ) );
		const blueprintPath = path.join( dir, 'blueprint.json' );
		fs.writeFileSync( blueprintPath, JSON.stringify( blueprint(), null, 2 ) );

		const child = spawn(
			'npx',
			[
				'--yes',
				`@wp-playground/cli@${ PLAYGROUND_VERSION }`,
				'server',
				`--blueprint=${ blueprintPath }`,
				`--port=${ PORT }`,
				'--login',
				`--workers=${ PLAYGROUND_WORKERS }`,
				// mount-before-install (not the post-install --mount): the blueprint's
				// activatePlugin step below needs the plugin's files on disk already, and
				// --mount only attaches after the site + blueprint steps have run.
				`--mount-before-install=${ REPO_ROOT }:/wordpress/wp-content/plugins/accessibility-checker`,
			],
			{ stdio: [ 'ignore', 'pipe', 'pipe' ], detached: true }
		);
		fs.writeFileSync( PID_FILE, String( child.pid ) );
		await waitForLog( child, /Ready! WordPress is running/, BOOT_TIMEOUT );
		fs.writeFileSync( DISPOSABLE_MARKER, '' );
	} else if ( ! ( await hasE2eRoutes( BASE_URL ) ) ) {
		throw new Error(
			`The Playground instance on ${ BASE_URL } has no ${ E2E_REST_NAMESPACE } REST routes — ` +
				`it was booted before tests/e2e/e2e-helpers.php existed, and the blueprint that ` +
				`installs them only runs for an instance this process starts. Stop it and run again.`
		);
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
