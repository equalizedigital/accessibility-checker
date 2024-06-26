/**
 * Summary Tab Input Event Handlers
 *
 * @since 1.12.0
 */

/**
 * Initialize the Summary Tab keyboard and click event handlers.
 *
 * Gets all tabs, adds click and keydown event listeners to each tab to support
 * proper keyboard navigation and aria attributes.
 *
 * @since 1.12.0
 */
export const initSummaryTabKeyboardAndClickHandlers = () => {
	const tabs = document.querySelectorAll( '.edac-tab button' );

	tabs.forEach( ( tab, index ) => {
		tab.addEventListener( 'click', ( event ) => {
			if (
				! ( event.target instanceof HTMLButtonElement ) &&
				'undefined' !== event.target.getAttribute( 'aria-controls' )
			) {
				return;
			}

			const panel = document.querySelector( '#' + event.target.getAttribute( 'aria-controls' ) );
			if ( ! ( panel instanceof HTMLElement ) ) {
				return;
			}

			event.preventDefault();
			clearAllTabsAndPanelState();

			panel.style.display = 'block';
			panel.classList.add( 'active' );
			event.target.classList.add( 'active' );
			event.target.setAttribute( 'aria-selected', true );
			event.target.removeAttribute( 'tabindex' );
			event.target.focus();
		} );

		// all the events that result in true evaluations simply click the tab in question,
		// because the tab click handler is already setup and not worth currently fully refactoring.
		tab.addEventListener( 'keydown', ( event ) => {
			if (
				( event.key === 'Enter' || event.keyCode === 13 ) ||
				( event.key === 'Space' || event.keyCode === 32 )
			) {
				tabs[ index ].click();
			}

			if ( event.key === 'ArrowRight' || event.keyCode === 39 ) {
				let newTabIndex = index + 1;
				if ( newTabIndex > tabs.length ) {
					newTabIndex = 0;
				}
				tabs[ newTabIndex ].click();
			}

			if ( event.key === 'ArrowLeft' || event.keyCode === 37 ) {
				let newTabIndex = index - 1;
				if ( newTabIndex < 0 ) {
					newTabIndex = tabs.length - 1;
				}
				tabs[ newTabIndex ].click();
			}

			if ( event.key === 'Home' || event.keyCode === 36 ) {
				tabs[ 0 ].click();
				event.preventDefault();
			}

			if ( event.key === 'End' || event.keyCode === 35 ) {
				tabs[ tabs.length - 1 ].click();
				event.preventDefault();
			}
		} );
	} );
};

/**
 * Clear all tabs and panels state to inactive then set a default active tab and panel.
 *
 * @since 1.12.0
 */
export const clearAllTabsAndPanelState = () => {
	const panels = document.querySelectorAll( '.edac-panel' );
	if ( ! panels.length ) {
		return;
	}

	panels.forEach( ( panel ) => {
		panel.style.display = 'none';
		panel.classList.remove( 'active' );
		panel.setAttribute( 'aria-selected', 'false' );
		const panelTab = document.querySelector( '#' + panel.getAttribute( 'aria-labelledby' ) );
		if ( panelTab ) {
			panelTab.classList.remove( 'active' );
			panelTab.setAttribute( 'aria-selected', 'false' );
			panelTab.setAttribute( 'tabindex', '-1' );
		}
	} );
};
