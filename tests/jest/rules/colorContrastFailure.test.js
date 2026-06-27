/**
 * Note: axe-core's color-contrast check uses canvas pixel-sampling to determine
 * background colors. JSDOM does not implement HTMLCanvasElement.getContext, so
 * axe returns checked elements as "incomplete" rather than "violated". Violation
 * detection for this rule requires a real browser environment (e2e or Playwright).
 *
 * These tests confirm the rule registers correctly and does not produce false
 * positives for elements where no contrast check is triggered (elements excluded
 * by the color-contrast-matches built-in matcher in JSDOM).
 */
import axe from 'axe-core';

beforeAll( async () => {
	const colorContrastRuleModule = await import( '../../../src/pageScanner/rules/color-contrast-failure.js' );

	axe.configure( {
		rules: [ colorContrastRuleModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Color Contrast Failure', () => {
	describe( 'rule registration', () => {
		test( 'rule is registered with the correct id', () => {
			const rules = axe.getRules( [ 'cat.color' ] );
			const rule = rules.find( ( r ) => r.ruleId === 'color_contrast_failure' );
			expect( rule ).toBeDefined();
		} );

		test( 'rule targets the correct WCAG criteria', () => {
			const rules = axe.getRules();
			const rule = rules.find( ( r ) => r.ruleId === 'color_contrast_failure' );
			expect( rule.tags ).toContain( 'wcag2aa' );
			expect( rule.tags ).toContain( 'wcag143' );
		} );
	} );

	describe( 'no false positives for non-text elements', () => {
		const testCases = [
			{
				name: 'should not flag an empty paragraph',
				html: '<p></p>',
			},
			{
				name: 'should not flag a hidden element',
				html: '<p style="display: none;">Hidden text</p>',
			},
			{
				name: 'should not flag a div with no text content',
				html: '<div></div>',
			},
		];

		testCases.forEach( ( testCase ) => {
			test( testCase.name, async () => {
				document.body.innerHTML = testCase.html;

				const results = await axe.run( document.body, {
					runOnly: [ 'color_contrast_failure' ],
				} );

				expect( results.violations.length ).toBe( 0 );
			} );
		} );
	} );
} );
