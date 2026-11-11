/**
 * Tests for hiding overlays while a scan runs.
 *
 * Elementor's element overlay sits above widget content in the editor preview,
 * which made axe report that content's color contrast as incomplete (bgOverlap)
 * rather than as a violation. jsdom has no layout, so these tests cover the
 * hide/restore behavior rather than axe's stacking.
 */
import { hideOverlaysDuringScan } from '../../../../src/pageScanner/helpers/hideOverlaysDuringScan';

describe( 'hideOverlaysDuringScan', () => {
	beforeEach( () => {
		document.head.innerHTML = '';
		document.body.innerHTML = `
			<div class="elementor-element">
				<div class="elementor-element-overlay"></div>
				<a class="elementor-button" href="#"><span class="elementor-button-text">Click here</span></a>
			</div>
			<button class="edac-highlight-btn"></button>
		`;
	} );

	test( 'hides Elementor overlays and highlight markers until restored', () => {
		const overlay = document.querySelector( '.elementor-element-overlay' );
		const marker = document.querySelector( '.edac-highlight-btn' );
		const content = document.querySelector( '.elementor-button' );

		const restore = hideOverlaysDuringScan();

		expect( window.getComputedStyle( overlay ).display ).toBe( 'none' );
		expect( window.getComputedStyle( marker ).display ).toBe( 'none' );
		expect( window.getComputedStyle( content ).display ).not.toBe( 'none' );

		restore();

		expect( window.getComputedStyle( overlay ).display ).not.toBe( 'none' );
		expect( window.getComputedStyle( marker ).display ).not.toBe( 'none' );
		expect( document.getElementById( 'edac-scan-hide-overlays' ) ).toBeNull();
	} );

	test( 'does not stack a second style when already hidden', () => {
		const restore = hideOverlaysDuringScan();
		const restoreNested = hideOverlaysDuringScan();

		expect( document.querySelectorAll( '#edac-scan-hide-overlays' ) ).toHaveLength( 1 );

		// The nested call is a no-op, so only the outer restore removes the style.
		restoreNested();
		expect( document.getElementById( 'edac-scan-hide-overlays' ) ).not.toBeNull();
		restore();
		expect( document.getElementById( 'edac-scan-hide-overlays' ) ).toBeNull();
	} );
} );
