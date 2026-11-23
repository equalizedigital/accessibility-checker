import SidebarPanelBody from '../../../src/sidebar/components/SidebarPanelBody';
import { renderReact } from '../helpers/renderReact';

jest.mock( '@wordpress/components', () => {
	const { forwardRef } = require( 'react' );
	return {
		PanelBody: forwardRef( ( { title, children }, ref ) => (
			<div ref={ ref } className="components-panel__body">
				<h2 className="components-panel__body-title">
					<button type="button">{ title }</button>
				</h2>
				{ children }
			</div>
		) ),
	};
} );

describe( 'SidebarPanelBody', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'sets the accordion heading to level 3', () => {
		const { container, unmount } = renderReact( <SidebarPanelBody title="Status" /> );
		const heading = container.querySelector( '.components-panel__body-title' );
		expect( heading.tagName ).toBe( 'H2' );
		expect( heading.getAttribute( 'aria-level' ) ).toBe( '3' );
		unmount();
	} );

	it( 'keeps the heading at level 3 after a re-render', () => {
		const { container, render, unmount } = renderReact( <SidebarPanelBody title="Status" /> );
		render( <SidebarPanelBody title="Status (2)" /> );
		const heading = container.querySelector( '.components-panel__body-title' );
		expect( heading.textContent ).toBe( 'Status (2)' );
		expect( heading.getAttribute( 'aria-level' ) ).toBe( '3' );
		unmount();
	} );

	it( 'does not change other headings inside the panel', () => {
		const { container, unmount } = renderReact(
			<SidebarPanelBody title="Status"><h4 className="inner">Inner</h4></SidebarPanelBody>,
		);
		expect( container.querySelector( '.inner' ).hasAttribute( 'aria-level' ) ).toBe( false );
		unmount();
	} );
} );
