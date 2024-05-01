export const initSummaryTabKeyboardHandler = () => {
	// Get all tab elements
	const tabs = document.querySelectorAll( '.edac-tab' );

	// Loop through each tab element
	tabs.forEach( ( tab, index ) => {
		// all the events that result in true evaluations simply click the tab in question,
		// because the tab click handler is already setup and not worth currently fully refactoring.
		tab.addEventListener( 'keydown', ( event ) => {
			if (
				( event.key === 'Enter' || event.keyCode === 13 ) ||
				( event.key === 'Space' || event.keyCode === 32 )
			) {
				tab[ index ].click();
			}

			if ( event.key === 'ArrowRight' || event.keyCode === 39 ) {
				let newTabIndex = index - 1;
				if ( newTabIndex < 0 ) {
					newTabIndex = tabs.length - 1;
				}
				tab[ newTabIndex ].click().focus();
			}

			if ( event.key === 'ArrowLeft' || event.keyCode === 37 ) {
				let newTabIndex = index + 1;
				if ( newTabIndex > tabs.length ) {
					newTabIndex = 0;
				}
				tab[ newTabIndex ].click().focus();
			}
		} );
	} );
};
