/* global process, module, __dirname */
/**
 * Draft Playwright config for end-to-end checks in WordPress Playground.
 *
 * Playground runs PHP as WASM in Node — very slow on small hardware — so the
 * timeouts here are deliberately huge and tunable through the environment.
 */
const path = require( 'path' );
const { defineConfig, devices } = require( '@playwright/test' );

const SLOW = Number( process.env.E2E_SLOW || 1 );
const scale = ( ms ) => Math.round( ms * SLOW );

module.exports = defineConfig( {
	testDir: __dirname,
	globalSetup: require.resolve( './global-setup.js' ),
	globalTeardown: require.resolve( './global-teardown.js' ),
	// One worker: Playground is a single-process PHP-WASM instance and each test
	// pays for a page load, not a container start.
	workers: 1,
	fullyParallel: false,
	// No retries: a retry re-pays the boot cost and hides real flakiness.
	retries: 0,
	timeout: scale( 300_000 ),
	expect: { timeout: scale( 30_000 ) },
	reporter: process.env.CI ? [ [ 'github' ] ] : [ [ 'list' ] ],
	use: {
		baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:9400',
		storageState: path.resolve( __dirname, '.auth', 'admin.json' ),
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		navigationTimeout: scale( 120_000 ),
		actionTimeout: scale( 60_000 ),
	},
	projects: [
		{ name: 'chromium', use: { ...devices[ 'Desktop Chrome' ], channel: 'chrome' } },
	],
} );
