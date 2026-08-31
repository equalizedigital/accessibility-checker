/**
 * Hi-fi tests for IssueImage shared component and extractImageUrls utility.
 *
 * Uses real @wordpress/i18n, @wordpress/element (useMemo), and
 * @wordpress/html-entities (decodeEntities) — no WordPress mocks.
 */
import IssueImage, { extractImageUrls } from '../../../src/issueModal/components/IssueImage';
import { renderReact } from '../helpers/renderReact';

// ---------------------------------------------------------------------------
// extractImageUrls (pure utility — no rendering needed)
// ---------------------------------------------------------------------------

describe( 'extractImageUrls', () => {
	test( 'returns empty array for null input', () => {
		expect( extractImageUrls( null ) ).toEqual( [] );
	} );

	test( 'returns empty array for empty string', () => {
		expect( extractImageUrls( '' ) ).toEqual( [] );
	} );

	test( 'extracts src URL from a plain img tag', () => {
		const markup = '<img src="https://example.com/image.png" alt="test">';
		expect( extractImageUrls( markup ) ).toContain( 'https://example.com/image.png' );
	} );

	test( 'extracts src URL from an img tag with single-quoted attributes', () => {
		const markup = "<img src='https://example.com/photo.jpg'>";
		expect( extractImageUrls( markup ) ).toContain( 'https://example.com/photo.jpg' );
	} );

	test( 'extracts multiple src URLs from multiple img tags', () => {
		const markup = '<img src="https://a.com/1.png"><img src="https://b.com/2.png">';
		const urls = extractImageUrls( markup );
		expect( urls ).toContain( 'https://a.com/1.png' );
		expect( urls ).toContain( 'https://b.com/2.png' );
	} );

	test( 'extracts first URL from a srcset attribute', () => {
		const markup = '<img src="https://a.com/small.png" srcset="https://a.com/medium.png 800w, https://a.com/large.png 1200w">';
		const urls = extractImageUrls( markup );
		expect( urls ).toContain( 'https://a.com/medium.png' );
	} );

	test( 'does not duplicate a URL that appears in both src and srcset', () => {
		const markup = '<img src="https://a.com/img.png" srcset="https://a.com/img.png 1x">';
		const urls = extractImageUrls( markup );
		const count = urls.filter( ( u ) => u === 'https://a.com/img.png' ).length;
		expect( count ).toBe( 1 );
	} );

	test( 'extracts background-image URL from inline style', () => {
		const markup = '<div style="background-image: url(\'https://example.com/bg.jpg\')"></div>';
		expect( extractImageUrls( markup ) ).toContain( 'https://example.com/bg.jpg' );
	} );

	test( 'extracts data-src attribute URL (lazy-loaded images)', () => {
		const markup = '<img data-src="https://example.com/lazy.jpg">';
		expect( extractImageUrls( markup ) ).toContain( 'https://example.com/lazy.jpg' );
	} );

	test( 'extracts inline SVG as a data URI', () => {
		const markup = '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="5"/></svg>';
		const urls = extractImageUrls( markup );
		expect( urls.length ).toBeGreaterThan( 0 );
		expect( urls[ 0 ] ).toMatch( /^data:image\/svg\+xml,/ );
	} );

	test( 'decodes HTML entities in markup before extracting URLs', () => {
		// &amp; in a src attribute should be decoded before the regex runs.
		const markup = '<img src="https://example.com/img.png?a=1&amp;b=2">';
		const urls = extractImageUrls( markup );
		// The decoded URL with & should be present.
		expect( urls ).toContain( 'https://example.com/img.png?a=1&b=2' );
	} );
} );

// ---------------------------------------------------------------------------
// IssueImage component
// ---------------------------------------------------------------------------

describe( 'IssueImage', () => {
	test( 'renders null when markup has no images', () => {
		const { container, unmount } = renderReact(
			<IssueImage markup="<p>No images here.</p>" />,
		);

		expect( container.querySelector( 'img' ) ).toBeNull();
		// The wrapper div should not be rendered either.
		expect( container.querySelector( '.edac-analysis__issue-image' ) ).toBeNull();

		unmount();
	} );

	test( 'renders null when markup is empty', () => {
		const { container, unmount } = renderReact( <IssueImage markup="" /> );

		expect( container.querySelector( 'img' ) ).toBeNull();

		unmount();
	} );

	test( 'renders an img element for each extracted URL', () => {
		const markup = '<img src="https://a.com/1.png"><img src="https://b.com/2.png">';
		const { container, unmount } = renderReact( <IssueImage markup={ markup } /> );

		const images = container.querySelectorAll( 'img' );
		expect( images ).toHaveLength( 2 );

		unmount();
	} );

	test( 'sets the src attribute of rendered images to the extracted URL', () => {
		const markup = '<img src="https://example.com/test.png">';
		const { container, unmount } = renderReact( <IssueImage markup={ markup } /> );

		const img = container.querySelector( 'img' );
		expect( img.getAttribute( 'src' ) ).toBe( 'https://example.com/test.png' );

		unmount();
	} );

	test( 'uses the alt prop on rendered images', () => {
		const markup = '<img src="https://example.com/shot.png">';
		const { container, unmount } = renderReact(
			<IssueImage markup={ markup } alt="Screenshot of issue" />,
		);

		const img = container.querySelector( 'img' );
		expect( img.getAttribute( 'alt' ) ).toBe( 'Screenshot of issue' );

		unmount();
	} );

	test( 'falls back to the default alt text when alt prop is not supplied', () => {
		const markup = '<img src="https://example.com/shot.png">';
		const { container, unmount } = renderReact( <IssueImage markup={ markup } /> );

		const img = container.querySelector( 'img' );
		// Default @wordpress/i18n string.
		expect( img.getAttribute( 'alt' ) ).toBe( 'Issue related image' );

		unmount();
	} );

	test( 'wraps images in the expected container class', () => {
		const markup = '<img src="https://example.com/img.png">';
		const { container, unmount } = renderReact( <IssueImage markup={ markup } /> );

		expect( container.querySelector( '.edac-analysis__issue-image' ) ).not.toBeNull();

		unmount();
	} );

	test( 'applies the item class to each image element', () => {
		const markup = '<img src="https://example.com/img.png">';
		const { container, unmount } = renderReact( <IssueImage markup={ markup } /> );

		const img = container.querySelector( 'img' );
		expect( img.classList.contains( 'edac-analysis__issue-image-item' ) ).toBe( true );

		unmount();
	} );
} );

