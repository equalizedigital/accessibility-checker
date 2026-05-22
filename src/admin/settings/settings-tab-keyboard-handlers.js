/**
 * Settings Page Tab Keyboard Handlers
 *
 * Implements the W3C ARIA Authoring Practices Guide tab pattern for the
 * settings page. Arrow keys navigate between tabs (triggering page navigation
 * since panels are server-side rendered), while Home/End jump to the first
 * or last tab.
 *
 * @see https://www.w3.org/WAI/ARIA/apg/patterns/tabs/
 * @since 1.13.0
 */

/**
 * Initialize keyboard navigation for the settings page tab list.
 *
 * @since 1.13.0
 */
export const initSettingsTabKeyboardHandlers = () => {
	const tabList = document.querySelector( '.edac-settings [role="tablist"]' );
	if ( ! tabList ) {
		return;
	}

	const tabs = Array.from( tabList.querySelectorAll( '[role="tab"]' ) );
	if ( tabs.length <= 1 ) {
		return;
	}

	tabs.forEach( ( tab, index ) => {
		tab.addEventListener( 'keydown', ( event ) => {
			// Space doesn't activate <a> elements natively the way it does buttons,
			// so we handle it explicitly to satisfy the ARIA tab keyboard contract.
			if ( event.key === ' ' ) {
				event.preventDefault();
				window.location.href = tab.href;
				return;
			}

			let newIndex = null;

			if ( event.key === 'ArrowRight' ) {
				newIndex = ( index + 1 ) % tabs.length;
			} else if ( event.key === 'ArrowLeft' ) {
				newIndex = ( index - 1 + tabs.length ) % tabs.length;
			} else if ( event.key === 'Home' ) {
				newIndex = 0;
			} else if ( event.key === 'End' ) {
				newIndex = tabs.length - 1;
			}

			if ( newIndex !== null ) {
				event.preventDefault();
				window.location.href = tabs[ newIndex ].href;
			}
		} );
	} );
};
