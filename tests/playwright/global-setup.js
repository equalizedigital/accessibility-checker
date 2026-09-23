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
 * @param {Object} child   Child process.
 * @param {RegExp} pattern Pattern to wait for.
 * @param {number} timeout Milliseconds to wait.
 * @return {Promise<void>} Resolves when matched.
 */
function waitForLog( child, pattern, timeout ) {
	return new Promise( ( resolve, reject ) => {
		const timer = setTimeout( () => reject( new Error( `Timed out waiting for ${ pattern }` ) ), timeout );
		const scan = ( chunk ) => {
			const text = String( chunk );
			if ( pattern.test( text ) ) {
				clearTimeout( timer );
				resolve();
			}
		};
		child.stdout.on( 'data', scan );
		child.stderr.on( 'data', scan );
		child.on( 'exit', ( code ) => {
			clearTimeout( timer );
			reject( new Error( `Playground exited early with code ${ code }` ) );
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
				`--mount=${ REPO_ROOT }:/wordpress/wp-content/plugins/accessibility-checker`,
			],
			{ stdio: [ 'ignore', 'pipe', 'pipe' ], detached: true }
		);
		fs.writeFileSync( PID_FILE, String( child.pid ) );
		await waitForLog( child, /Ready! WordPress is running/, BOOT_TIMEOUT );
	}

	const browser = await chromium.launch();
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
