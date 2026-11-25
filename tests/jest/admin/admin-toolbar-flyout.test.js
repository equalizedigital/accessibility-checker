/**
 * Tests for admin/js/admin-toolbar-flyout.js — keyboard support for the nested
 * Accessibility Checker toolbar flyouts.
 *
 * Core's admin bar cannot open nested flyouts from the keyboard, so this script
 * adds that. It is a self-executing IIFE with no exports, so each test builds the
 * markup WordPress renders for the toolbar, then uses jest.isolateModules() and a
 * fresh require() to run the script against it.
 */

/**
 * Build the toolbar markup WordPress renders for the Accessibility Checker item:
 * a first-level flyout containing a plain item and a nested Settings flyout.
 */
function buildToolbar() {
	document.body.innerHTML = `
		<div id="wpadminbar">
			<ul>
				<li id="wp-admin-bar-accessibility-checker" class="menupop">
					<a class="ab-item" href="/ac" role="menuitem" aria-expanded="false">Accessibility Checker</a>
					<div class="ab-sub-wrapper">
						<ul>
							<li id="wp-admin-bar-accessibility-checker-issues-explorer">
								<a class="ab-item" href="/issues">Issues Explorer</a>
							</li>
							<li id="wp-admin-bar-accessibility-checker-settings" class="menupop">
								<a class="ab-item" href="/settings" role="menuitem" aria-expanded="false">Settings</a>
								<div class="ab-sub-wrapper">
									<ul>
										<li><a class="ab-item" href="/settings">General</a></li>
										<li><a class="ab-item" href="/settings?tab=fixes">Fixes</a></li>
										<li><a class="ab-item" href="/settings?tab=permissions">Permissions</a></li>
									</ul>
								</div>
							</li>
						</ul>
					</div>
				</li>
				<li id="wp-admin-bar-other" class="menupop">
					<a class="ab-item" href="/other" aria-expanded="false">Other plugin</a>
					<div class="ab-sub-wrapper">
						<ul>
							<li id="wp-admin-bar-other-nested" class="menupop">
								<a class="ab-item" href="/nested" aria-expanded="false">Nested</a>
								<div class="ab-sub-wrapper">
									<ul><li><a class="ab-item" href="/child">Child</a></li></ul>
								</div>
							</li>
						</ul>
					</div>
				</li>
			</ul>
		</div>
	`;
}

/**
 * Run the script fresh against the current document.
 */
function loadScript() {
	jest.isolateModules( () => {
		require( '../../../admin/js/admin-toolbar-flyout.js' );
	} );
}

/**
 * Dispatch a keydown on an element and report whether it was left uncancelled.
 *
 * @param {Element} element The element to press the key on.
 * @param {string}  key     The KeyboardEvent key value.
 * @param {Object}  options Extra event init, e.g. modifier keys.
 * @return {boolean} False when the handler called preventDefault().
 */
function press( element, key, options = {} ) {
	return element.dispatchEvent(
		new KeyboardEvent( 'keydown', { key, bubbles: true, cancelable: true, ...options } ),
	);
}

const settingsItem = () => document.getElementById( 'wp-admin-bar-accessibility-checker-settings' );
const settingsLink = () => settingsItem().querySelector( ':scope > .ab-item' );
const childLinks = () => Array.from( settingsItem().querySelectorAll( ':scope > .ab-sub-wrapper .ab-item' ) );

describe( 'admin toolbar flyout keyboard support', () => {
	beforeEach( () => {
		document.documentElement.removeAttribute( 'dir' );
		buildToolbar();
		loadScript();
	} );

	test( 'Enter on the parent item opens the flyout instead of following the link', () => {
		settingsLink().focus();

		const notPrevented = press( settingsLink(), 'Enter' );

		expect( notPrevented ).toBe( false );
		expect( settingsItem().classList.contains( 'hover' ) ).toBe( true );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'true' );
	} );

	test( 'Enter again closes the flyout', () => {
		press( settingsLink(), 'Enter' );
		press( settingsLink(), 'Enter' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	test( 'Right arrow opens the flyout and focuses the first item', () => {
		settingsLink().focus();

		press( settingsLink(), 'ArrowRight' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( true );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe( childLinks()[ 0 ] );
	} );

	test( 'Down and Up arrows move between items and wrap', () => {
		press( settingsLink(), 'ArrowRight' );
		const [ general, fixes, permissions ] = childLinks();

		press( general, 'ArrowDown' );
		expect( document.activeElement ).toBe( fixes );

		press( fixes, 'ArrowDown' );
		expect( document.activeElement ).toBe( permissions );

		press( permissions, 'ArrowDown' );
		expect( document.activeElement ).toBe( general );

		press( general, 'ArrowUp' );
		expect( document.activeElement ).toBe( permissions );
	} );

	test( 'Left arrow closes the flyout and returns focus to the parent item', () => {
		press( settingsLink(), 'ArrowRight' );

		press( childLinks()[ 1 ], 'ArrowLeft' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( document.activeElement ).toBe( settingsLink() );
	} );

	test( 'Escape inside the flyout closes it and returns focus to the parent item', () => {
		press( settingsLink(), 'ArrowRight' );

		press( childLinks()[ 2 ], 'Escape' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( document.activeElement ).toBe( settingsLink() );
	} );

	test( 'Escape on the parent item closes an open flyout', () => {
		press( settingsLink(), 'Enter' );

		press( settingsLink(), 'Escape' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	test( 'focus leaving the flyout closes it, focus moving within it does not', () => {
		press( settingsLink(), 'ArrowRight' );
		const [ general, fixes ] = childLinks();

		general.dispatchEvent( new FocusEvent( 'focusout', { bubbles: true, relatedTarget: fixes } ) );
		expect( settingsItem().classList.contains( 'hover' ) ).toBe( true );

		const outside = document.querySelector( '#wp-admin-bar-other > .ab-item' );
		fixes.dispatchEvent( new FocusEvent( 'focusout', { bubbles: true, relatedTarget: outside } ) );
		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( settingsLink().getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	test( 'focus leaving the window leaves the flyout open', () => {
		press( settingsLink(), 'ArrowRight' );

		childLinks()[ 0 ].dispatchEvent( new FocusEvent( 'focusout', { bubbles: true, relatedTarget: null } ) );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( true );
	} );

	test( 'Enter with a modifier key still follows the link', () => {
		const notPrevented = press( settingsLink(), 'Enter', { ctrlKey: true } );

		expect( notPrevented ).toBe( true );
		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
	} );

	test( 'other keys are left alone', () => {
		expect( press( settingsLink(), 'a' ) ).toBe( true );
		expect( press( settingsLink(), 'ArrowDown' ) ).toBe( true );
		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
	} );

	test( 'flyouts from other plugins are not touched', () => {
		const nested = document.getElementById( 'wp-admin-bar-other-nested' );
		const nestedLink = nested.querySelector( ':scope > .ab-item' );

		const notPrevented = press( nestedLink, 'Enter' );

		expect( notPrevented ).toBe( true );
		expect( nested.classList.contains( 'hover' ) ).toBe( false );
	} );

	test( 'the first-level item is left to core', () => {
		const top = document.getElementById( 'wp-admin-bar-accessibility-checker' );
		const topLink = top.querySelector( ':scope > .ab-item' );

		expect( press( topLink, 'Enter' ) ).toBe( true );
		expect( top.classList.contains( 'hover' ) ).toBe( false );
	} );
} );

describe( 'admin toolbar flyout keyboard support in right-to-left layouts', () => {
	beforeEach( () => {
		document.documentElement.setAttribute( 'dir', 'rtl' );
		buildToolbar();
		loadScript();
	} );

	afterEach( () => {
		document.documentElement.removeAttribute( 'dir' );
	} );

	test( 'Left arrow opens the flyout and focuses the first item', () => {
		press( settingsLink(), 'ArrowLeft' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( true );
		expect( document.activeElement ).toBe( childLinks()[ 0 ] );
	} );

	test( 'Right arrow closes the flyout and returns focus to the parent item', () => {
		press( settingsLink(), 'ArrowLeft' );

		press( childLinks()[ 0 ], 'ArrowRight' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
		expect( document.activeElement ).toBe( settingsLink() );
	} );

	test( 'Right arrow does not open the flyout', () => {
		press( settingsLink(), 'ArrowRight' );

		expect( settingsItem().classList.contains( 'hover' ) ).toBe( false );
	} );
} );

describe( 'admin toolbar flyout script when there is nothing to wire', () => {
	test( 'does nothing and does not throw when the toolbar item is absent', () => {
		document.body.innerHTML = '<div id="wpadminbar"></div>';

		expect( () => loadScript() ).not.toThrow();
	} );
} );
