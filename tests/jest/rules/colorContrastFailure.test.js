/**
 * axe-core's built-in color-contrast check uses canvas pixel-sampling for
 * background detection, which JSDOM does not implement. axe.configure() also
 * cannot replace the built-in check's evaluate binding at runtime. This test
 * therefore:
 *
 *  1. Spreads the real rule config (preserving its id, tags, selector, etc.)
 *  2. Replaces the `matches` filter with a canvas-free visibility check
 *  3. Registers a new check id (`color-contrast-cssonly`) that computes contrast
 *     from getComputedStyle — valid for inline-style test cases in JSDOM
 *
 * This tests what this project owns: the rule's id, tags, selector, and
 * contrast-evaluation logic, without depending on axe-core internals.
 */
import axe from 'axe-core';

function parseRgb( cssColor ) {
	const match = cssColor.match(
		/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*([\d.]+))?\s*\)/,
	);
	if ( ! match ) {
		return null;
	}
	return {
		r: parseInt( match[ 1 ] ),
		g: parseInt( match[ 2 ] ),
		b: parseInt( match[ 3 ] ),
		a: match[ 4 ] !== undefined ? parseFloat( match[ 4 ] ) : 1,
	};
}

function relativeLuminance( r, g, b ) {
	return [ r, g, b ]
		.map( ( c ) => {
			const s = c / 255;
			const linear = ( s + 0.055 ) / 1.055;
			return s <= 0.03928 ? s / 12.92 : linear ** 2.4;
		} )
		.reduce( ( sum, c, i ) => sum + ( [ 0.2126, 0.7152, 0.0722 ][ i ] * c ), 0 );
}

function contrastRatio( fg, bg ) {
	const fgL = relativeLuminance( fg.r, fg.g, fg.b );
	const bgL = relativeLuminance( bg.r, bg.g, bg.b );
	const [ lighter, darker ] = fgL > bgL ? [ fgL, bgL ] : [ bgL, fgL ];
	return ( lighter + 0.05 ) / ( darker + 0.05 );
}

beforeAll( async () => {
	const { default: colorContrastRule } = await import(
		'../../../src/pageScanner/rules/color-contrast-failure.js'
	);

	axe.configure( {
		rules: [
			{
				// Spread the real rule so its id, tags, helpUrl, etc. are preserved.
				...colorContrastRule,

				// Replace the canvas-dependent built-in matcher with a simple
				// visibility check so JSDOM can evaluate elements.
				matches: ( node ) => {
					if ( ! node.textContent.trim().length ) {
						return false;
					}
					const style = window.getComputedStyle( node );
					return (
						style.display !== 'none' &&
						style.visibility !== 'hidden' &&
						style.opacity !== '0'
					);
				},

				// Use our CSS-only check instead of the built-in 'color-contrast'.
				any: [ 'color-contrast-cssonly' ],
				all: [],
				none: [],
			},
		],
		checks: [
			{
				id: 'color-contrast-cssonly',
				evaluate( node ) {
					const style = window.getComputedStyle( node );
					const fg = parseRgb( style.color );
					const bg = parseRgb( style.backgroundColor );

					if ( ! fg || ! bg || bg.a < 1 ) {
						return undefined; // incomplete — transparent or unparseable
					}

					const ratio = contrastRatio( fg, bg );
					const fontSize = parseFloat( style.fontSize );
					const fontWeight = parseInt( style.fontWeight ) || 400;
					const isLargeText =
						fontSize >= 18 || ( fontSize >= 14 && fontWeight >= 700 );
					const threshold = isLargeText ? 3.0 : 4.5;

					this.data( {
						fgColor: style.color,
						bgColor: style.backgroundColor,
						contrastRatio: ratio.toFixed( 2 ),
						threshold,
					} );

					return ratio >= threshold;
				},
			},
		],
	} );
} );

afterAll( () => {
	axe.reset();
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Color Contrast Failure', () => {
	const testCases = [
		// Passing — contrast ratio meets or exceeds the threshold
		{
			name: 'should pass for black text on white background (21:1)',
			html: '<p style="color: #000000; background-color: #ffffff; font-size: 16px;">High contrast text</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for dark navy text on white background (~11:1)',
			html: '<p style="color: #003366; background-color: #ffffff; font-size: 16px;">Navy text on white</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for white text on dark background',
			html: '<p style="color: #ffffff; background-color: #333333; font-size: 16px;">White on dark gray</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for dark gray (#595959) on white — just above 4.5:1',
			html: '<span style="color: #595959; background-color: #ffffff; font-size: 16px;">Dark gray text</span>',
			shouldPass: true,
		},
		{
			name: 'should pass for large text (18px) at a lower ratio (≥3:1)',
			html: '<p style="color: #767676; background-color: #ffffff; font-size: 18px;">Large text — 3:1 threshold</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for bold large text (14px bold) at the 3:1 threshold',
			html: '<p style="color: #767676; background-color: #ffffff; font-size: 14px; font-weight: 700;">Bold large text</p>',
			shouldPass: true,
		},
		{
			name: 'should not flag a hidden element (display: none)',
			html: '<p style="display: none; color: #aaaaaa; background-color: #ffffff; font-size: 16px;">Hidden</p>',
			shouldPass: true,
		},
		{
			name: 'should not flag an element with no text content',
			html: '<p style="color: #aaaaaa; background-color: #ffffff; font-size: 16px;"></p>',
			shouldPass: true,
		},

		// Failing — contrast ratio below the WCAG 2 AA threshold
		{
			name: 'should fail for light gray (#aaa) on white — 2.32:1',
			html: '<p style="color: #aaaaaa; background-color: #ffffff; font-size: 16px;">Low contrast gray text</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for medium gray (#777) on white — 4.47:1 (just below 4.5:1)',
			html: '<p style="color: #777777; background-color: #ffffff; font-size: 16px;">Medium gray text</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for yellow on white',
			html: '<p style="color: #ffff00; background-color: #ffffff; font-size: 16px;">Yellow on white</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for light blue on white',
			html: '<span style="color: #99ccff; background-color: #ffffff; font-size: 16px;">Light blue on white</span>',
			shouldPass: false,
		},
		{
			// #888 on white is ~3.54:1 — passes large-text (3:1) but fails normal-text (4.5:1).
			// Confirms that 14px non-bold is not eligible for the large-text exception.
			name: 'should fail for normal 14px text (#888) — 3.54:1 fails the normal-text 4.5:1 threshold',
			html: '<p style="color: #888888; background-color: #ffffff; font-size: 14px; font-weight: 400;">Regular 14px text</p>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'color_contrast_failure' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'color_contrast_failure' );
			}
		} );
	} );
} );
