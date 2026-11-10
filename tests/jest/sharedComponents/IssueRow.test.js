import IssueRow from '../../../src/sidebar/components/IssueRow';
import { renderReact } from '../helpers/renderReact';

jest.mock( '@wordpress/components', () => ( {
	DropdownMenu: ( { children, label } ) => (
		<div className="mock-dropdown-menu" aria-label={ label }>
			{ children( { onClose: jest.fn() } ) }
		</div>
	),
	MenuGroup: ( { children, className } ) => <div className={ className }>{ children }</div>,
	MenuItem: ( { children, onClick } ) => (
		<button type="button" onClick={ onClick }>
			{ children }
		</button>
	),
} ) );

jest.mock( '../../../src/sidebar/utils/dismissHelpers', () => ( {
	getDismissReasonLabel: jest.fn( () => 'False positive' ),
	userCanDismiss: jest.fn( () => false ),
} ) );

describe( 'IssueRow', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'hides the dismiss/reopen menu item when the user cannot dismiss', () => {
		const { userCanDismiss } = require( '../../../src/sidebar/utils/dismissHelpers' );
		userCanDismiss.mockReturnValue( false );

		const { container, unmount } = renderReact(
			<IssueRow issue={ { id: 1 } } rule={ {} } onAction={ jest.fn() } />,
		);

		expect( container.textContent ).not.toContain( 'Dismiss issue' );
		expect( container.textContent ).not.toContain( 'Reopen issue' );

		unmount();
	} );

	test( 'shows "Dismiss issue" when the user can dismiss and the issue is not shown as ignored', () => {
		const { userCanDismiss } = require( '../../../src/sidebar/utils/dismissHelpers' );
		userCanDismiss.mockReturnValue( true );

		const { container, unmount } = renderReact(
			<IssueRow issue={ { id: 2 } } rule={ {} } onAction={ jest.fn() } showIgnored={ false } />,
		);

		expect( container.textContent ).toContain( 'Dismiss issue' );
		expect( container.textContent ).not.toContain( 'Reopen issue' );

		unmount();
	} );

	test( 'shows "Reopen issue" instead of "Dismiss issue" when showIgnored is true', () => {
		const { userCanDismiss } = require( '../../../src/sidebar/utils/dismissHelpers' );
		userCanDismiss.mockReturnValue( true );

		const { container, unmount } = renderReact(
			<IssueRow issue={ { id: 3 } } rule={ {} } onAction={ jest.fn() } showIgnored={ true } />,
		);

		expect( container.textContent ).toContain( 'Reopen issue' );
		expect( container.textContent ).not.toContain( 'Dismiss issue' );

		unmount();
	} );

	test( 'clicking the dismiss/reopen menu item calls onAction with "ignore" and the issue', () => {
		const { userCanDismiss } = require( '../../../src/sidebar/utils/dismissHelpers' );
		userCanDismiss.mockReturnValue( true );
		const onAction = jest.fn();
		const issue = { id: 4 };

		const { container, unmount } = renderReact(
			<IssueRow issue={ issue } rule={ {} } onAction={ onAction } />,
		);

		const dismissButton = Array.from( container.querySelectorAll( 'button' ) ).find(
			( el ) => el.textContent.includes( 'Dismiss issue' ),
		);
		dismissButton.click();

		expect( onAction ).toHaveBeenCalledWith( 'ignore', issue );

		unmount();
	} );

	test( 'shows the fix action only when the rule has fixes', () => {
		const { container: withoutFixes, unmount: unmountWithout } = renderReact(
			<IssueRow issue={ { id: 5 } } rule={ {} } onAction={ jest.fn() } />,
		);
		expect( withoutFixes.textContent ).not.toContain( 'Apply fix' );
		unmountWithout();

		const { container: withFixes, unmount: unmountWith } = renderReact(
			<IssueRow issue={ { id: 6 } } rule={ { fixes: [ { id: 'fix-1' } ] } } onAction={ jest.fn() } />,
		);
		expect( withFixes.textContent ).toContain( 'Apply fix' );
		unmountWith();
	} );

	test( 'shows a dismiss-reason badge only when showIgnored is true and the issue has a reason', () => {
		const { container, unmount } = renderReact(
			<IssueRow
				issue={ { id: 7, ignre_reason: 'false_positive' } }
				rule={ {} }
				onAction={ jest.fn() }
				showIgnored={ true }
			/>,
		);

		expect( container.textContent ).toContain( 'False positive' );

		unmount();
	} );
} );
