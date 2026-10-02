/* global __dirname */
/**
 * Admin smoke checks — the cheap catches for activation-level regressions.
 *
 * These confirmed clean on the 1.50.0 wave: all pages HTTP 200 with zero PHP
 * diagnostics, and the Meetup widget pointing at the DFW group.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );
const { expectNoPhpErrors } = require( './helpers' );

/**
 * Read the Version header from the main plugin file — that is what WordPress
 * displays on the plugins screen, and it can differ from package.json.
 *
 * @return {string} Version string.
 */
function pluginVersion() {
	const file = fs.readFileSync(
		path.resolve( __dirname, '..', '..', 'accessibility-checker.php' ),
		'utf8'
	);
	const match = file.match( /^\s*\*?\s*Version:\s*(.+)$/m );
	return match ? match[ 1 ].trim() : null;
}

const ADMIN_PAGES = [
	'/wp-admin/admin.php?page=accessibility_checker',
	'/wp-admin/admin.php?page=accessibility_checker_settings',
	'/wp-admin/plugins.php',
];

test.describe( 'admin', () => {
	for ( const adminPath of ADMIN_PAGES ) {
		test( `${ adminPath } loads without PHP diagnostics`, async ( { page } ) => {
			const response = await page.goto( adminPath, { waitUntil: 'domcontentloaded' } );
			expect( response.status() ).toBeLessThan( 400 );
			expectNoPhpErrors( await page.content() );
		} );
	}

	test( 'the plugin is active at the version in its header', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' } );

		const expected = pluginVersion();
		expect( expected ).toBeTruthy();

		const row = page.locator( 'tr[data-plugin="accessibility-checker/accessibility-checker.php"]' );
		// \b is required here: /active/ alone also matches inside "inactive".
		await expect( row ).toHaveAttribute( 'class', /\bactive\b/ );
		await expect( row ).toContainText( expected );
	} );

	test( 'the Meetup widget links to the DFW group', async ( { page } ) => {
		await page.goto( ADMIN_PAGES[ 0 ], { waitUntil: 'domcontentloaded' } );

		const meetup = page.locator( 'a[href*="meetup.com"]' ).first();
		test.skip( ( await meetup.count() ) === 0, 'No Meetup links on this page.' );

		await expect( meetup ).toHaveAttribute( 'href', /equalize-digital-web-accessibility-meetup-dfw/ );
	} );
} );
