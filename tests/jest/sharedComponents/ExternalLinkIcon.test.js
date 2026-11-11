/**
 * Hi-fi tests for ExternalLinkIcon shared component.
 *
 * Uses real @wordpress/i18n — no WordPress mocks.
 */
import ExternalLinkIcon from '../../../src/sidebar/components/ExternalLinkIcon';
import { renderReact } from '../helpers/renderReact';

describe( 'ExternalLinkIcon', () => {
	test( 'renders arrow glyph and screen reader text by default', () => {
		const { container, unmount } = renderReact( <ExternalLinkIcon /> );

		// Arrow glyph should be visible to sighted users.
		const arrowSpan = container.querySelector( '[aria-hidden="true"]' );
		expect( arrowSpan ).not.toBeNull();
		expect( arrowSpan.textContent ).toContain( '↗' );

		// Screen reader text should be present.
		const srSpan = container.querySelector( '.screen-reader-text' );
		expect( srSpan ).not.toBeNull();
		expect( srSpan.textContent ).toContain( 'opens a new window' );

		unmount();
	} );

	test( 'omits screen reader text when showScreenReaderText is false', () => {
		const { container, unmount } = renderReact( <ExternalLinkIcon showScreenReaderText={ false } /> );

		// Arrow glyph still present.
		const arrowSpan = container.querySelector( '[aria-hidden="true"]' );
		expect( arrowSpan ).not.toBeNull();

		// Screen reader text must be absent.
		const srSpan = container.querySelector( '.screen-reader-text' );
		expect( srSpan ).toBeNull();

		unmount();
	} );

	test( 'includes screen reader text when showScreenReaderText is explicitly true', () => {
		const { container, unmount } = renderReact( <ExternalLinkIcon showScreenReaderText={ true } /> );

		const srSpan = container.querySelector( '.screen-reader-text' );
		expect( srSpan ).not.toBeNull();

		unmount();
	} );
} );

