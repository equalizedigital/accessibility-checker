import { act } from 'react';
import DismissPanel from '../../../src/issueModal/components/DismissPanel';
import { renderReact } from '../helpers/renderReact';

jest.mock( '@wordpress/components', () => ( {
	Panel: ( { children } ) => <div className="mock-panel">{ children }</div>,
	PanelBody: ( { title, opened, onToggle, children } ) => (
		<section className="mock-panel-body" data-open={ opened }>
			<button type="button" onClick={ onToggle }>{ title }</button>
			{ opened && children }
		</section>
	),
	Button: ( { children, onClick, type = 'button', disabled, className } ) => (
		<button type={ type } onClick={ onClick } disabled={ disabled } className={ className }>
			{ children }
		</button>
	),
	Spinner: () => <span className="mock-spinner" aria-hidden="true">...</span>,
	Notice: ( { children } ) => <div className="mock-notice">{ children }</div>,
	RadioControl: ( { label, selected, options, onChange } ) => (
		<div className="mock-radio-control">
			<div>{ label }</div>
			{ options.map( ( option ) => (
				<label key={ option.value }>
					<input
						type="radio"
						name="dismiss-reason"
						checked={ selected === option.value }
						onChange={ () => onChange( option.value ) }
					/>
					{ option.label }
				</label>
			) ) }
		</div>
	),
	Dropdown: ( { renderToggle, renderContent } ) => (
		<div className="mock-dropdown">
			{ renderToggle( { isOpen: false, onToggle: jest.fn() } ) }
			{ renderContent( { onClose: jest.fn() } ) }
		</div>
	),
} ) );

jest.mock( '../../../src/issueModal/api', () => ( {
	toggleIssueDismiss: jest.fn( () => Promise.resolve( { success: true } ) ),
} ) );

jest.mock( '../../../src/issueModal/index', () => ( {
	setPendingRefetch: jest.fn(),
} ) );

describe( 'DismissPanel', () => {
	test( 'defaults dismiss reason to confirmed accessible for new dismissals', async () => {
		const { toggleIssueDismiss } = require( '../../../src/issueModal/api' );
		const { container, unmount } = renderReact(
			<DismissPanel
				issue={ { id: 3, ignre: '0', ignre_global: 0 } }
				isOpen={ true }
				onToggle={ jest.fn() }
				onIgnore={ jest.fn() }
				onCloseModal={ jest.fn() }
				isPro={ false }
			/>,
		);

		await act( async () => {
			container.querySelector( 'form' ).dispatchEvent(
				new Event( 'submit', { bubbles: true, cancelable: true } ),
			);
		} );
		expect( toggleIssueDismiss ).toHaveBeenCalledWith( 3, true, 'accessible', '', false );

		unmount();
	} );

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
			button.click();
		} );

		expect( toggleIssueDismiss ).toHaveBeenCalledWith( 2, false, '', '', true );

		unmount();
	} );
} );
