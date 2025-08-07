/**
 * Integration test for pageScanner callback system
 *
 * This test verifies that callbacks work when integrated with the actual
 * pageScanner scanning functionality.
 */

import {
	addRulesFilter,
	addChecksFilter,
	addRunOptionsFilter,
	addConfigOptionsFilter,
	clearAllCallbacks,
} from '../../../src/pageScanner/utils/callbacks';

// Import the callbacks directly to test the core functionality
describe( 'PageScanner Callback System Integration', () => {
	beforeEach( () => {
		// Clear all callbacks before each test
		clearAllCallbacks();
	} );

	test( 'should have callback functions exposed on window object after pageScanner loads', async () => {
		// Import the pageScanner module to load it
		await import( '../../../src/pageScanner/index.js' );

		// Verify the callback functions are available globally
		expect( typeof window.addPageScannerRulesFilter ).toBe( 'function' );
		expect( typeof window.addPageScannerChecksFilter ).toBe( 'function' );
		expect( typeof window.addPageScannerRunOptionsFilter ).toBe( 'function' );
		expect( typeof window.addPageScannerConfigOptionsFilter ).toBe( 'function' );
	} );

	test( 'should have runAccessibilityScan function exposed on window object', async () => {
		// Import the pageScanner module to load it
		await import( '../../../src/pageScanner/index.js' );

		// Verify the scan function is available globally
		expect( typeof window.runAccessibilityScan ).toBe( 'function' );
	} );

	test( 'should be able to register callbacks via global functions', async () => {
		// Import the pageScanner module to load it
		await import( '../../../src/pageScanner/index.js' );

		// Test that we can register callbacks without errors
		let rulesCallbackCalled = false;
		let checksCallbackCalled = false;
		let runOptionsCallbackCalled = false;
		let configOptionsCallbackCalled = false;

		window.addPageScannerRulesFilter( ( rules ) => {
			rulesCallbackCalled = true;
			return rules;
		} );

		window.addPageScannerChecksFilter( ( checks ) => {
			checksCallbackCalled = true;
			return checks;
		} );

		window.addPageScannerRunOptionsFilter( ( options ) => {
			runOptionsCallbackCalled = true;
			return options;
		} );

		window.addPageScannerConfigOptionsFilter( ( options ) => {
			configOptionsCallbackCalled = true;
			return options;
		} );

		// Now test that the callbacks are actually called by directly testing the apply functions
		const { rulesArray, checksArray } = await import( '../../../src/pageScanner/config/rules.js' );
		const {
			applyRulesFilters,
			applyChecksFilters,
			applyRunOptionsFilters,
			applyConfigOptionsFilters,
		} = await import( '../../../src/pageScanner/utils/callbacks.js' );

		// Apply filters to test they were registered
		applyRulesFilters( rulesArray );
		applyChecksFilters( checksArray );
		applyRunOptionsFilters( { test: true } );
		applyConfigOptionsFilters( { test: true } );

		expect( rulesCallbackCalled ).toBe( true );
		expect( checksCallbackCalled ).toBe( true );
		expect( runOptionsCallbackCalled ).toBe( true );
		expect( configOptionsCallbackCalled ).toBe( true );
	} );

	test( 'should filter rules correctly when applied', async () => {
		const { rulesArray } = await import( '../../../src/pageScanner/config/rules.js' );
		const { applyRulesFilters } = await import( '../../../src/pageScanner/utils/callbacks.js' );

		// Add a filter that removes the first rule
		const originalRulesCount = rulesArray.length;
		const firstRuleId = rulesArray[ 0 ].id;

		addRulesFilter( ( rules ) => {
			return rules.filter( ( rule ) => rule.id !== firstRuleId );
		} );

		const filteredRules = applyRulesFilters( rulesArray );

		expect( filteredRules.length ).toBe( originalRulesCount - 1 );
		expect( filteredRules.find( ( rule ) => rule.id === firstRuleId ) ).toBeUndefined();
	} );

	test( 'should filter checks correctly when applied', async () => {
		const { checksArray } = await import( '../../../src/pageScanner/config/rules.js' );
		const { applyChecksFilters } = await import( '../../../src/pageScanner/utils/callbacks.js' );

		// Add a filter that removes the first check
		const originalChecksCount = checksArray.length;
		const firstCheckId = checksArray[ 0 ].id;

		addChecksFilter( ( checks ) => {
			return checks.filter( ( check ) => check.id !== firstCheckId );
		} );

		const filteredChecks = applyChecksFilters( checksArray );

		expect( filteredChecks.length ).toBe( originalChecksCount - 1 );
		expect( filteredChecks.find( ( check ) => check.id === firstCheckId ) ).toBeUndefined();
	} );

	test( 'should modify run options correctly when applied', async () => {
		const { applyRunOptionsFilters } = await import( '../../../src/pageScanner/utils/callbacks.js' );

		const originalOptions = {
			runOnly: {
				type: 'rule',
				values: [ 'rule1', 'rule2', 'rule3' ],
			},
			timeout: 5000,
		};

		// Add a filter that modifies the values and timeout
		addRunOptionsFilter( ( options ) => {
			return {
				...options,
				runOnly: {
					...options.runOnly,
					values: [ 'rule1' ], // Only keep first rule
				},
				timeout: 10000, // Increase timeout
			};
		} );

		const filteredOptions = applyRunOptionsFilters( originalOptions );

		expect( filteredOptions.runOnly.values ).toEqual( [ 'rule1' ] );
		expect( filteredOptions.timeout ).toBe( 10000 );
	} );

	test( 'should modify config options correctly when applied', async () => {
		const { applyConfigOptionsFilters } = await import( '../../../src/pageScanner/utils/callbacks.js' );

		const originalOptions = {
			reporter: 'raw',
			iframes: false,
			customProperty: 'original',
		};

		// Add a filter that modifies the options
		addConfigOptionsFilter( ( options ) => {
			return {
				...options,
				iframes: true,
				customProperty: 'modified',
			};
		} );

		const filteredOptions = applyConfigOptionsFilters( originalOptions );

		expect( filteredOptions.iframes ).toBe( true );
		expect( filteredOptions.customProperty ).toBe( 'modified' );
		expect( filteredOptions.reporter ).toBe( 'raw' ); // Should preserve other properties
	} );
} );
