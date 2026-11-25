/**
 * Keyboard support for the Accessibility Checker toolbar flyouts.
 *
 * WordPress core's admin bar only opens first-level flyouts from the keyboard: its
 * Enter handler ignores any item that sits inside another flyout, and nested flyouts
 * stay `display: none` until their <li> gets the `hover` class, which only the mouse
 * and touch handlers add. Items such as Settings > General, Fixes, Permissions would
 * be unreachable without this script.
 *
 * Behaviour, scoped to nested flyouts inside the Accessibility Checker item only:
 * - Enter on the parent item toggles the flyout (core does the same at the first level).
 * - The "open" arrow (Right, or Left in RTL) opens it and moves focus to the first item.
 * - Up and Down move between the items of an open flyout.
 * - Escape or the "close" arrow closes it and returns focus to the parent item.
 * - Focus leaving the flyout closes it.
 *
 * Mouse and touch behaviour is untouched: core's hover handling still applies.
 *
 * @package
 */
( function() {
	'use strict';

	const ROOT_ID = 'wp-admin-bar-accessibility-checker';

	/**
	 * Whether the page is laid out right-to-left.
	 *
	 * @return {boolean} True for RTL documents.
	 */
	function isRtl() {
		return 'rtl' === document.documentElement.dir || 'rtl' === document.body.dir;
	}

	/**
	 * Open a flyout and keep aria-expanded in sync.
	 *
	 * @param {HTMLElement} item       The flyout's <li>.
	 * @param {HTMLElement} parentLink The <a> that controls it.
	 */
	function open( item, parentLink ) {
		item.classList.add( 'hover' );
		parentLink.setAttribute( 'aria-expanded', 'true' );
	}

	/**
	 * Close a flyout and keep aria-expanded in sync.
	 *
	 * @param {HTMLElement} item       The flyout's <li>.
	 * @param {HTMLElement} parentLink The <a> that controls it.
	 */
	function close( item, parentLink ) {
		item.classList.remove( 'hover' );
		parentLink.setAttribute( 'aria-expanded', 'false' );
	}

	/**
	 * The focusable links of a flyout.
	 *
	 * @param {HTMLElement} item The flyout's <li>.
	 * @return {HTMLElement[]} Direct child links, in order.
	 */
	function getChildLinks( item ) {
		return Array.from(
			item.querySelectorAll( ':scope > .ab-sub-wrapper > ul > li > .ab-item' )
		);
	}

	/**
	 * Wire one nested flyout.
	 *
	 * @param {HTMLElement} item The nested <li class="menupop">.
	 */
	function wire( item ) {
		const parentLink = item.querySelector( ':scope > .ab-item' );

		if ( ! parentLink ) {
			return;
		}

		parentLink.addEventListener( 'keydown', function( event ) {
			if ( event.ctrlKey || event.shiftKey || event.altKey || event.metaKey ) {
				return;
			}

			const openKey = isRtl() ? 'ArrowLeft' : 'ArrowRight';

			if ( 'Enter' === event.key ) {
				// Without this the link would be followed and the flyout never opens.
				event.preventDefault();
				if ( item.classList.contains( 'hover' ) ) {
					close( item, parentLink );
				} else {
					open( item, parentLink );
				}
			} else if ( openKey === event.key ) {
				event.preventDefault();
				open( item, parentLink );
				const links = getChildLinks( item );
				if ( links.length ) {
					links[ 0 ].focus();
				}
			}
		} );

		// Escape on the parent item while its flyout is open closes only that flyout.
		// This runs in the capture phase because core registers its own Escape handler on
		// every item, and that one closes the flyout first. By the time a bubbling handler
		// here ran, the flyout would look closed and the key would go on to close the flyout
		// this item sits in as well. With the flyout already closed, the key is left alone,
		// so a second Escape closes the outer flyout.
		item.addEventListener(
			'keydown',
			function( event ) {
				if ( 'Escape' !== event.key || event.target !== parentLink || ! item.classList.contains( 'hover' ) ) {
					return;
				}

				event.stopPropagation();
				close( item, parentLink );
			},
			true
		);

		item.addEventListener( 'keydown', function( event ) {
			// Only keys pressed inside the flyout, not on the parent link itself.
			if ( event.target === parentLink || event.ctrlKey || event.altKey || event.metaKey ) {
				return;
			}

			const closeKey = isRtl() ? 'ArrowRight' : 'ArrowLeft';
			const links = getChildLinks( item );
			const index = links.indexOf( event.target );

			// A key handled here must not also be handled by a flyout this one sits in,
			// or closing a deeper flyout would close its ancestors too.
			if ( 'Escape' === event.key || closeKey === event.key ) {
				event.preventDefault();
				event.stopPropagation();
				close( item, parentLink );
				parentLink.focus();
			} else if ( 'ArrowDown' === event.key && -1 !== index ) {
				event.preventDefault();
				event.stopPropagation();
				links[ ( index + 1 ) % links.length ].focus();
			} else if ( 'ArrowUp' === event.key && -1 !== index ) {
				event.preventDefault();
				event.stopPropagation();
				links[ ( index - 1 + links.length ) % links.length ].focus();
			}
		} );

		item.addEventListener( 'focusout', function( event ) {
			// relatedTarget is null when focus leaves the window; leave the flyout open then.
			if ( event.relatedTarget && ! item.contains( event.relatedTarget ) ) {
				close( item, parentLink );
			}
		} );
	}

	/**
	 * Wire every nested flyout under the Accessibility Checker toolbar item.
	 */
	function init() {
		const root = document.getElementById( ROOT_ID );

		if ( ! root ) {
			return;
		}

		root.querySelectorAll( 'li.menupop' ).forEach( wire );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
