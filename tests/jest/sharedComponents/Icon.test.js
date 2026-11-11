/**
 * Hi-fi tests for Icon shared component.
 *
 * Icon is pure React with no @wordpress/* dependencies — zero mocking required.
 */
import Icon from '../../../src/sidebar/components/Icon';
import { renderReact } from '../helpers/renderReact';

describe( 'Icon', () => {
	describe( 'rendering known icons', () => {
		test.each( [ 'check', 'warning', 'error', 'info' ] )(
			'renders an svg for the "%s" icon name',
			( name ) => {
				const { container, unmount } = renderReact( <Icon name={ name } /> );
				expect( container.querySelector( 'svg' ) ).not.toBeNull();
				unmount();
			},
		);

		test( 'returns null for an unknown icon name', () => {
			const { container, unmount } = renderReact( <Icon name="does-not-exist" /> );
			expect( container.querySelector( 'svg' ) ).toBeNull();
			expect( container.querySelector( '.edac-icon' ) ).toBeNull();
			unmount();
		} );
	} );

	describe( 'wrapper element', () => {
		test( 'wraps the svg in a span with the base edac-icon class', () => {
			const { container, unmount } = renderReact( <Icon name="check" /> );
			const span = container.querySelector( 'span.edac-icon' );
			expect( span ).not.toBeNull();
			unmount();
		} );

		test( 'appends an extra className when provided', () => {
			const { container, unmount } = renderReact( <Icon name="check" className="my-custom-class" /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.classList.contains( 'my-custom-class' ) ).toBe( true );
			unmount();
		} );
	} );

	describe( 'type / colour class', () => {
		test( 'adds the default type class when no type prop is given — check → success', () => {
			const { container, unmount } = renderReact( <Icon name="check" /> );
			expect( container.querySelector( '.edac-icon--success' ) ).not.toBeNull();
			unmount();
		} );

		test( 'adds edac-icon--warning for the warning icon by default', () => {
			const { container, unmount } = renderReact( <Icon name="warning" /> );
			expect( container.querySelector( '.edac-icon--warning' ) ).not.toBeNull();
			unmount();
		} );

		test( 'adds edac-icon--error for the error icon by default', () => {
			const { container, unmount } = renderReact( <Icon name="error" /> );
			expect( container.querySelector( '.edac-icon--error' ) ).not.toBeNull();
			unmount();
		} );

		test( 'adds edac-icon--info for the info icon by default', () => {
			const { container, unmount } = renderReact( <Icon name="info" /> );
			expect( container.querySelector( '.edac-icon--info' ) ).not.toBeNull();
			unmount();
		} );

		test( 'overrides the default type with an explicit type prop', () => {
			const { container, unmount } = renderReact( <Icon name="check" type="error" /> );
			expect( container.querySelector( '.edac-icon--error' ) ).not.toBeNull();
			expect( container.querySelector( '.edac-icon--success' ) ).toBeNull();
			unmount();
		} );

		test( 'falls back to the default icon type when type is an empty string', () => {
			// An empty string is falsy — the component treats it the same as "not provided"
			// and resolves the default type from the icon name.
			const { container, unmount } = renderReact( <Icon name="info" type="" /> );
			expect( container.querySelector( '.edac-icon--info' ) ).not.toBeNull();
			unmount();
		} );
	} );

	describe( 'accessibility attributes', () => {
		test( 'is aria-hidden by default', () => {
			const { container, unmount } = renderReact( <Icon name="check" /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			unmount();
		} );

		test( 'is NOT aria-hidden when ariaHidden is explicitly false', () => {
			const { container, unmount } = renderReact( <Icon name="check" ariaHidden={ false } /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
			unmount();
		} );

		test( 'automatically clears aria-hidden when an ariaLabel is supplied', () => {
			const { container, unmount } = renderReact( <Icon name="warning" ariaLabel="Warning" /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
			unmount();
		} );

		test( 'sets the aria-label attribute when ariaLabel is supplied', () => {
			const { container, unmount } = renderReact( <Icon name="error" ariaLabel="Error icon" /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.getAttribute( 'aria-label' ) ).toBe( 'Error icon' );
			unmount();
		} );

		test( 'does not set aria-label attribute when no ariaLabel prop is given', () => {
			const { container, unmount } = renderReact( <Icon name="info" /> );
			const span = container.querySelector( '.edac-icon' );
			expect( span.getAttribute( 'aria-label' ) ).toBeNull();
			unmount();
		} );
	} );
} );

