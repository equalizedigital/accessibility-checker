import { act } from 'react';
import DismissPanel from '../../../src/issueModal/components/DismissPanel';
import { renderReact } from '../helpers/renderReact';

jest.mock( '../../../src/issueModal/api', () => ( {
	toggleIssueDismiss: jest.fn( () => Promise.resolve( { success: true } ) ),
} ) );

jest.mock( '../../../src/issueModal/index', () => ( {
	setPendingRefetch: jest.fn(),
} ) );

describe( 'DismissPanel', () => {
	test( 'hides global dismiss controls for free users', () => {
		window.edac_editor_app.pro = '0';

		const { container, unmount } = renderReact(
			<DismissPanel
				issue={ { id: 1, ignre: '0', ignre_global: 0 } }
				isOpen={ true }
				onToggle={ jest.fn() }
				onIgnore={ jest.fn() }
				onCloseModal={ jest.fn() }
				forceGlobal={ true }
				isPro={ false }
			/>,
		);

		expect( container.querySelector( 'button[aria-label="More dismiss options"]' ) ).toBeNull();
		expect( container.textContent ).not.toContain( 'Dismiss Globally' );

		unmount();
	} );

	test( 'keeps global undo available when an issue was globally dismissed', async () => {
		const { toggleIssueDismiss } = require( '../../../src/issueModal/api' );
		window.edac_editor_app.pro = '0';

		const { container, unmount } = renderReact(
			<DismissPanel
				issue={ {
					id: 2,
					ignre: '1',
					ignre_global: 1,
					ignre_reason: 'false_positive',
					ignre_comment: 'Global dismissal',
				} }
				isOpen={ true }
				onToggle={ jest.fn() }
				onIgnore={ jest.fn() }
				onCloseModal={ jest.fn() }
				isPro={ false }
			/>,
		);

		const button = Array.from( container.querySelectorAll( 'button' ) ).find(
			( el ) => el.textContent.includes( 'Remove Global Dismissal' ),
		);

		expect( button ).toBeDefined();

		await act( async () => {
			button.dispatchEvent( new MouseEvent( 'click', { bubbles: true } ) );
		} );

		expect( toggleIssueDismiss ).toHaveBeenCalledWith( 2, false, '', '', true );

		unmount();
	} );
} );
