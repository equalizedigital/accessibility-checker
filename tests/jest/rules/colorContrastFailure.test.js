/**
 * color-contrast-failure delegates entirely to axe-core built-ins:
 * the `color-contrast-matches` matcher and the `color-contrast` check.
 * Both require canvas pixel-sampling that JSDOM cannot provide, so
 * behavioral testing (pass/fail on actual contrast values) requires a
 * real browser (e.g. Playwright). These tests verify the rule's static
 * configuration — id, tags, and which built-ins it uses — so that
 * accidental renames or misconfiguration are caught.
 *
 * One runtime smoke test is also included: it confirms the rule's selector
 * and matcher actually evaluate text elements (not silently skip them). In
 * JSDOM, axe cannot sample canvas pixels so it marks evaluated elements as
 * `incomplete` rather than `violated` — that incomplete result proves the
 * rule ran rather than being disabled or matching nothing.
 */
import axe from 'axe-core';
import colorContrastRule from '../../../src/pageScanner/rules/color-contrast-failure.js';

describe( 'color-contrast-failure rule config', () => {
	test( 'has the correct rule id', () => {
		expect( colorContrastRule.id ).toBe( 'color_contrast_failure' );
	} );

	test( 'uses the built-in color-contrast-matches matcher', () => {
		expect( colorContrastRule.matches ).toBe( 'color-contrast-matches' );
	} );

	test( 'delegates to the built-in color-contrast check', () => {
		expect( colorContrastRule.any ).toEqual( [ 'color-contrast' ] );
		expect( colorContrastRule.all ).toHaveLength( 0 );
		expect( colorContrastRule.none ).toHaveLength( 0 );
	} );

	test( 'targets WCAG 2 AA success criterion 1.4.3', () => {
		expect( colorContrastRule.tags ).toContain( 'wcag2aa' );
		expect( colorContrastRule.tags ).toContain( 'wcag143' );
	} );

	test( 'includes accessibility framework tags', () => {
		expect( colorContrastRule.tags ).toContain( 'TTv5' );
		expect( colorContrastRule.tags ).toContain( 'EN-301-549' );
		expect( colorContrastRule.tags ).toContain( 'ACT' );
	} );
} );

describe( 'color-contrast-failure rule execution', () => {
	beforeAll( async () => {
		// The color-contrast-matches built-in matcher calls _isIconLigature, which
		// needs HTMLCanvasElement.getContext. Without it, axe throws and excludes
		// every element before the check runs. Provide a minimal mock so the matcher
		// completes (equal measureText widths → not an icon ligature → element passes
		// through to the contrast check).
		HTMLCanvasElement.prototype.getContext = function() {
			return {
				font: '',
				measureText: ( text ) => ( { width: text.length * 8 } ),
				fillText: () => {},
				clearRect: () => {},
				fillRect: () => {},
				drawImage: () => {},
				getImageData: () => ( { data: new Uint8ClampedArray( 4 ) } ),
			};
		};

		axe.configure( { rules: [ colorContrastRule ] } );
	} );

	afterAll( () => {
		delete HTMLCanvasElement.prototype.getContext;
		axe.reset();
	} );

	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'rule evaluates text elements (returns incomplete in JSDOM, not inapplicable)', async () => {
		// Any visible text element is sufficient — the exact contrast value does not
		// matter here. What matters is that axe marks it `incomplete` (evaluated but
		// unable to determine a result) rather than `inapplicable` (selector/matcher
		// never matched). An `inapplicable` result would mean the rule is broken.
		document.body.innerHTML =
			'<p style="color: #777; background-color: #fff; font-size: 16px;">Sample text</p>';

		const results = await axe.run( document.body, {
			runOnly: [ 'color_contrast_failure' ],
		} );

		expect( results.inapplicable.some( ( r ) => r.id === 'color_contrast_failure' ) ).toBe( false );
		expect( results.incomplete.some( ( r ) => r.id === 'color_contrast_failure' ) ).toBe( true );
	} );
} );
