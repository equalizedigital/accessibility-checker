/**
 * Hi-fi tests for Badge shared component.
 *
 * Badge composes Icon internally — both are pure React with no @wordpress/*
 * dependencies, so zero mocking required.
 */
import Badge from '../../../src/sidebar/components/Badge';
import { renderReact } from '../helpers/renderReact';
describe( 'Badge', () => {
	describe( 'label', () => {
		test( 'renders the label text', () => {
			const { container, unmount } = renderReact( <Badge label="Errors" /> );
			const labelSpan = container.querySelector( '.edac-badge__label' );
			expect( labelSpan ).not.toBeNull();
			expect( labelSpan.textContent ).toBe( 'Errors' );
			unmount();
		} );
		test( 'renders an empty label span when no label is given', () => {
			const { container, unmount } = renderReact( <Badge /> );
			const labelSpan = container.querySelector( '.edac-badge__label' );
			expect( labelSpan ).not.toBeNull();
			unmount();
		} );
	} );
	describe( 'type class', () => {
		test( 'includes the type class on the root span', () => {
			const { container, unmount } = renderReact( <Badge label="Warnings" type="warning" /> );
			const root = container.querySelector( '.edac-badge' );
			expect( root.classList.contains( 'edac-badge--warning' ) ).toBe( true );
			unmount();
		} );
		test( 'defaults to the info type class', () => {
			const { container, unmount } = renderReact( <Badge label="Info" /> );
			const root = container.querySelector( '.edac-badge' );
			expect( root.classList.contains( 'edac-badge--info' ) ).toBe( true );
			unmount();
		} );
		test.each( [ 'info', 'warning', 'error', 'success' ] )(
			'applies edac-badge--%s for type="%s"',
			( type ) => {
				const { container, unmount } = renderReact( <Badge label="Test" type={ type } /> );
				expect( container.querySelector( `.edac-badge--${ type }` ) ).not.toBeNull();
				unmount();
			},
		);
	} );
	describe( 'size class', () => {
		test( 'adds no extra size class when size is not provided', () => {
			const { container, unmount } = renderReact( <Badge label="Test" /> );
			const root = container.querySelector( '.edac-badge' );
			const sizeClasses = [ ...root.classList ].filter( ( c ) => c.startsWith( 'edac-badge--' ) );
			// Only the type class (edac-badge--info) should be present, no size class.
			expect( sizeClasses ).toHaveLength( 1 );
			unmount();
		} );
		test( 'adds edac-badge--small when size="small"', () => {
			const { container, unmount } = renderReact( <Badge label="Test" size="small" /> );
			const root = container.querySelector( '.edac-badge' );
			expect( root.classList.contains( 'edac-badge--small' ) ).toBe( true );
			unmount();
		} );
	} );
	describe( 'extra className', () => {
		test( 'appends a custom className to the root span', () => {
			const { container, unmount } = renderReact( <Badge label="Test" className="my-badge" /> );
			const root = container.querySelector( '.edac-badge' );
			expect( root.classList.contains( 'my-badge' ) ).toBe( true );
			unmount();
		} );
	} );
	describe( 'icon', () => {
		test( 'renders no icon element when icon prop is omitted', () => {
			const { container, unmount } = renderReact( <Badge label="No Icon" /> );
			expect( container.querySelector( '.edac-icon' ) ).toBeNull();
			unmount();
		} );
		test( 'renders an Icon when icon prop is provided', () => {
			const { container, unmount } = renderReact( <Badge label="Has Icon" icon="check" type="success" /> );
			expect( container.querySelector( '.edac-icon' ) ).not.toBeNull();
			expect( container.querySelector( 'svg' ) ).not.toBeNull();
			unmount();
		} );
		test( 'icon is aria-hidden inside a Badge (decorative)', () => {
			const { container, unmount } = renderReact( <Badge label="Has Icon" icon="warning" type="warning" /> );
			const iconSpan = container.querySelector( '.edac-icon' );
			expect( iconSpan.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			unmount();
		} );
		test( 'icon type matches the badge type', () => {
			const { container, unmount } = renderReact( <Badge label="Error badge" icon="error" type="error" /> );
			expect( container.querySelector( '.edac-icon--error' ) ).not.toBeNull();
			unmount();
		} );
	} );
} );
