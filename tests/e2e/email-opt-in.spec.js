/**
 * Email opt-in modal — dialog semantics and the inert background.
 *
 * Mirrors what was verified by hand: on a real trigger the Thickbox window becomes
 * a labelled modal dialog, the background is made inert, focus lands inside, and
 * closing restores the page.
 */
const { test, expect } = require( '@playwright/test' );

const WELCOME_PAGE = '/wp-admin/admin.php?page=accessibility_checker';

test.describe( 'email opt-in modal', () => {
	test( 'opens as a labelled dialog with an inert background, and restores on close', async ( { page } ) => {
		// initOptInModal() only binds its one-shot mousemove/scroll trigger inside its
		// own `window.addEventListener('load', ...)` handler — waiting for
		// 'domcontentloaded' races that: a mouse move before 'load' finishes firing
		// reaches the page before the listener exists and does nothing.
		await page.goto( WELCOME_PAGE, { waitUntil: 'load' } );

		// The modal markup is only printed while the current user has not seen it.
		// On a reused Playground instance, reset the user meta between runs.
		const hasMarkup = await page.locator( '#edac-opt-in-modal' ).count();
		test.skip( hasMarkup === 0, 'Opt-in modal markup absent (user has already seen it).' );

		// Any real user gesture opens it: the module binds a one-shot mousemove/scroll.
		await page.mouse.move( 30, 30 );

		const dialog = page.locator( '#TB_window.edac-email-opt-in-modal' );
		await expect( dialog ).toBeVisible();
		await expect( dialog ).toHaveAttribute( 'role', 'dialog' );
		await expect( dialog ).toHaveAttribute( 'aria-modal', 'true' );
		await expect( dialog ).toHaveAttribute( 'aria-labelledby', 'TB_ajaxWindowTitle' );

		// Background made inert while open (PRO-1013).
		await expect( page.locator( '[inert]' ) ).not.toHaveCount( 0 );

		// Focus is placed inside the dialog once the trap binds.
		await expect
			.poll( () => page.evaluate( () => {
				const win = document.getElementById( 'TB_window' );
				return Boolean( win && win.contains( document.activeElement ) );
			} ) )
			.toBe( true );

		await page.locator( '#TB_closeWindowButton' ).click();

		await expect( page.locator( '#TB_window' ) ).toHaveCount( 0 );
		await expect( page.locator( '[inert]' ) ).toHaveCount( 0 );
	} );

	test.fixme( 'keeps keyboard focus inside the dialog', async () => {
		// Approach: open the modal, then press Tab past the last focusable element
		// (and Shift+Tab before the first) and assert focus wraps within #TB_window.
		// Also assert Escape closes it, if that behaviour is intended — needs a
		// decision before it can be asserted.
	} );
} );
