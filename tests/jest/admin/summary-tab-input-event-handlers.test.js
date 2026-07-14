jest.mock( 'focus-trap', () => ( {
	createFocusTrap: () => ( {
		activate: jest.fn(),
		deactivate: jest.fn(),
	} ),
} ), { virtual: true } );

import { initSummaryTabKeyboardAndClickHandlers } from '../../../src/admin/summary/summary-tab-input-event-handlers';

const buildSummaryTabsDom = () => {
	document.body.innerHTML = `
		<div id="edac-tabs">
			<ul class="edac-tabs" role="tablist" aria-labelledby="edac-tabs-label">
				<li class="edac-tab">
					<button
						role="tab"
						aria-selected="true"
						aria-controls="edac-summary-panel"
						id="edac-summary-tab"
						class="active"
					>
						Summary
					</button>
				</li>
				<li class="edac-tab">
					<button
						role="tab"
						aria-selected="false"
						aria-controls="edac-details-panel"
						id="edac-details-tab"
					>
						Details
					</button>
				</li>
				<li class="edac-tab">
					<button
						role="tab"
						aria-selected="false"
						aria-controls="edac-readability-panel"
						id="edac-readability-tab"
					>
						Readability
					</button>
				</li>
			</ul>
		</div>
		<div role="tabpanel" aria-labelledby="edac-summary-tab" id="edac-summary-panel" class="edac-panel edac-summary"></div>
		<div role="tabpanel" aria-labelledby="edac-details-tab" id="edac-details-panel" class="edac-panel edac-details" style="display: none;"></div>
		<div role="tabpanel" aria-labelledby="edac-readability-tab" id="edac-readability-panel" class="edac-panel edac-readability" style="display: none;"></div>
	`;
};

describe( 'initSummaryTabKeyboardAndClickHandlers', () => {
	beforeEach( () => {
		buildSummaryTabsDom();
		initSummaryTabKeyboardAndClickHandlers();
	} );

	it( 'wraps ArrowRight from last tab to first tab without throwing', () => {
		const tabs = document.querySelectorAll( '.edac-tab button' );
		const firstTab = tabs[ 0 ];
		const lastTab = tabs[ tabs.length - 1 ];

		// Activate the last tab to test a state change
		lastTab.click();

		expect( () => {
			lastTab.dispatchEvent( new KeyboardEvent( 'keydown', { key: 'ArrowRight', keyCode: 39, bubbles: true } ) );
		} ).not.toThrow();

		// The first tab should now be active, and the last tab should be inactive
		expect( firstTab.classList.contains( 'active' ) ).toBe( true );
		expect( lastTab.classList.contains( 'active' ) ).toBe( false );
	} );
} );
