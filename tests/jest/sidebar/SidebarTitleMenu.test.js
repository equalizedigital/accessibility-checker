import { act } from 'react';
import { speak } from '@wordpress/a11y';
import SidebarTitleMenu from '../../../src/sidebar/components/SidebarTitleMenu';
import { renderReact } from '../helpers/renderReact';

const mockSetLastFocusedIssue = jest.fn();

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );

jest.mock( '@wordpress/components', () => ( {
	DropdownMenu: ( { label, children } ) => (
		<div>
			<button type="button">{ label }</button>
			{ children( { onClose: jest.fn() } ) }
		</div>
	),
	MenuGroup: ( { children } ) => <div>{ children }</div>,
	MenuItem: ( { children, onClick } ) => (
		<button type="button" onClick={ onClick }>{ children }</button>
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn( () => ( {
		setLastFocusedIssue: mockSetLastFocusedIssue,
	} ) ),
} ), { virtual: true } );

jest.mock( '@wordpress/icons', () => ( {
	moreVertical: null,
	search: null,
	update: null,
	trash: null,
} ) );

jest.mock( '../../../src/sidebar/store/accessibility-checker-store', () => ( {
	STORE_NAME: 'accessibility-checker/data',
} ) );

const getRefreshButton = ( container ) => Array.from( container.querySelectorAll( 'button' ) )
	.find( ( button ) => button.textContent === 'Refresh' );

describe( 'SidebarTitleMenu', () => {
	let requestAnimationFrame;

	beforeEach( () => {
		jest.clearAllMocks();
		requestAnimationFrame = window.requestAnimationFrame;
		window.requestAnimationFrame = jest.fn();
	} );

	afterEach( () => {
		window.requestAnimationFrame = requestAnimationFrame;
	} );

	test( 'announces when a user-triggered refresh succeeds', async () => {
		const refetchData = jest.fn().mockResolvedValue( true );
		const { container, unmount } = renderReact(
			<SidebarTitleMenu postId={ 42 } refetchData={ refetchData } />,
		);

		await act( async () => {
			getRefreshButton( container ).click();
			await Promise.resolve();
		} );

		expect( refetchData ).toHaveBeenCalledWith( 42 );
		expect( speak ).toHaveBeenCalledWith(
			'Accessibility analysis refreshed.',
			'polite',
		);

		unmount();
	} );

	test( 'announces when a user-triggered refresh fails', async () => {
		const refetchData = jest.fn().mockResolvedValue( false );
		const { container, unmount } = renderReact(
			<SidebarTitleMenu postId={ 42 } refetchData={ refetchData } />,
		);

		await act( async () => {
			getRefreshButton( container ).click();
			await Promise.resolve();
		} );

		expect( speak ).toHaveBeenCalledWith(
			'Accessibility analysis could not be refreshed.',
			'assertive',
		);

		unmount();
	} );

	test( 'announces failure if the refresh action rejects', async () => {
		const refetchData = jest.fn().mockRejectedValue( new Error( 'Unexpected error' ) );
		const { container, unmount } = renderReact(
			<SidebarTitleMenu postId={ 42 } refetchData={ refetchData } />,
		);

		await act( async () => {
			getRefreshButton( container ).click();
			await Promise.resolve();
		} );

		expect( speak ).toHaveBeenCalledWith(
			'Accessibility analysis could not be refreshed.',
			'assertive',
		);

		unmount();
	} );

	test( 'does not refresh or announce without a post ID', async () => {
		const refetchData = jest.fn();
		const { container, unmount } = renderReact(
			<SidebarTitleMenu postId={ null } refetchData={ refetchData } />,
		);

		await act( async () => {
			getRefreshButton( container ).click();
			await Promise.resolve();
		} );

		expect( refetchData ).not.toHaveBeenCalled();
		expect( speak ).not.toHaveBeenCalled();

		unmount();
	} );
} );
