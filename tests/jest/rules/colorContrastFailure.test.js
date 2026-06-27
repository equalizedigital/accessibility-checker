/**
 * color-contrast-failure delegates entirely to axe-core built-ins:
 * the `color-contrast-matches` matcher and the `color-contrast` check.
 * Both require canvas pixel-sampling that JSDOM cannot provide, so
 * behavioral testing (pass/fail on actual contrast values) requires a
 * real browser (e.g. Playwright). These tests verify the rule's static
 * configuration — id, tags, and which built-ins it uses — so that
 * accidental renames or misconfiguration are caught.
 */
import colorContrastRule from '../../../src/pageScanner/rules/color-contrast-failure.js';

describe( 'color-contrast-failure rule config', () => {
	test( 'has the correct rule id', () => {
		expect( colorContrastRule.id ).toBe( 'color_contrast_failure' );
	} );

	test( 'uses the built-in color-contrast-matches matcher', () => {
		expect( colorContrastRule.matches ).toBe( 'color-contrast-matches' );
	} );

	test( 'delegates to the built-in color-contrast check', () => {
		expect( colorContrastRule.any ).toContain( 'color-contrast' );
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
