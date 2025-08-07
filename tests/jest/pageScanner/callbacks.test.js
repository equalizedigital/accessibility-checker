/**
 * Test for pageScanner callback system
 *
 * This test verifies that the callback system allows filtering and modifying
 * rules, checks, and options before the accessibility scan runs.
 */
import {
	addRulesFilter,
	addChecksFilter,
	addRunOptionsFilter,
	addConfigOptionsFilter,
	applyRulesFilters,
	applyChecksFilters,
	applyRunOptionsFilters,
	applyConfigOptionsFilters,
	clearAllCallbacks,
	getCallbackCounts,
} from '../../../src/pageScanner/utils/callbacks';

describe( 'PageScanner Callback System', () => {
	beforeEach( () => {
		// Clear all callbacks before each test
		clearAllCallbacks();
	} );

	describe( 'Rules Filtering', () => {
		test( 'should allow filtering rules array', () => {
			const originalRules = [
				{ id: 'rule1', name: 'Rule 1' },
				{ id: 'rule2', name: 'Rule 2' },
				{ id: 'rule3', name: 'Rule 3' },
			];

			// Add a filter that removes rule2
			addRulesFilter( ( rules ) => {
				return rules.filter( ( rule ) => rule.id !== 'rule2' );
			} );

			const filteredRules = applyRulesFilters( originalRules );

			expect( filteredRules ).toHaveLength( 2 );
			expect( filteredRules.find( ( rule ) => rule.id === 'rule2' ) ).toBeUndefined();
			expect( filteredRules.find( ( rule ) => rule.id === 'rule1' ) ).toBeDefined();
			expect( filteredRules.find( ( rule ) => rule.id === 'rule3' ) ).toBeDefined();
		} );

		test( 'should handle multiple rule filters', () => {
			const originalRules = [
				{ id: 'rule1', name: 'Rule 1', priority: 'high' },
				{ id: 'rule2', name: 'Rule 2', priority: 'low' },
				{ id: 'rule3', name: 'Rule 3', priority: 'high' },
			];

			// Filter 1: Remove low priority rules
			addRulesFilter( ( rules ) => {
				return rules.filter( ( rule ) => rule.priority === 'high' );
			} );

			// Filter 2: Add a new rule
			addRulesFilter( ( rules ) => {
				return [ ...rules, { id: 'rule4', name: 'Rule 4', priority: 'high' } ];
			} );

			const filteredRules = applyRulesFilters( originalRules );

			expect( filteredRules ).toHaveLength( 3 );
			expect( filteredRules.find( ( rule ) => rule.id === 'rule2' ) ).toBeUndefined();
			expect( filteredRules.find( ( rule ) => rule.id === 'rule4' ) ).toBeDefined();
		} );

		test( 'should handle filter errors gracefully', () => {
			const originalRules = [ { id: 'rule1', name: 'Rule 1' } ];

			// Add a filter that throws an error
			addRulesFilter( () => {
				throw new Error( 'Filter error' );
			} );

			// Should not throw and should return original rules
			const filteredRules = applyRulesFilters( originalRules );
			expect( filteredRules ).toEqual( originalRules );
		} );

		test( 'should handle filter returning non-array', () => {
			const originalRules = [ { id: 'rule1', name: 'Rule 1' } ];

			// Add a filter that returns non-array
			addRulesFilter( () => 'not an array' );

			// Should return original rules
			const filteredRules = applyRulesFilters( originalRules );
			expect( filteredRules ).toEqual( originalRules );
		} );
	} );

	describe( 'Checks Filtering', () => {
		test( 'should allow filtering checks array', () => {
			const originalChecks = [
				{ id: 'check1', name: 'Check 1' },
				{ id: 'check2', name: 'Check 2' },
			];

			addChecksFilter( ( checks ) => {
				return checks.filter( ( check ) => check.id !== 'check1' );
			} );

			const filteredChecks = applyChecksFilters( originalChecks );

			expect( filteredChecks ).toHaveLength( 1 );
			expect( filteredChecks[ 0 ].id ).toBe( 'check2' );
		} );
	} );

	describe( 'Run Options Filtering', () => {
		test( 'should allow modifying run options', () => {
			const originalOptions = {
				runOnly: {
					type: 'rule',
					values: [ 'rule1', 'rule2' ],
				},
			};

			addRunOptionsFilter( ( options ) => {
				return {
					...options,
					runOnly: {
						...options.runOnly,
						values: [ 'rule1' ], // Remove rule2
					},
				};
			} );

			const filteredOptions = applyRunOptionsFilters( originalOptions );

			expect( filteredOptions.runOnly.values ).toEqual( [ 'rule1' ] );
		} );

		test( 'should handle run options filter errors gracefully', () => {
			const originalOptions = { timeout: 5000 };

			addRunOptionsFilter( () => {
				throw new Error( 'Filter error' );
			} );

			const filteredOptions = applyRunOptionsFilters( originalOptions );
			expect( filteredOptions ).toEqual( originalOptions );
		} );
	} );

	describe( 'Config Options Filtering', () => {
		test( 'should allow modifying config options', () => {
			const originalOptions = {
				reporter: 'raw',
				iframes: false,
			};

			addConfigOptionsFilter( ( options ) => {
				return {
					...options,
					iframes: true,
				};
			} );

			const filteredOptions = applyConfigOptionsFilters( originalOptions );

			expect( filteredOptions.iframes ).toBe( true );
		} );
	} );

	describe( 'Callback Management', () => {
		test( 'should track callback counts correctly', () => {
			expect( getCallbackCounts() ).toEqual( {
				rules: 0,
				checks: 0,
				runOptions: 0,
				configOptions: 0,
			} );

			addRulesFilter( () => [] );
			addChecksFilter( () => [] );
			addRunOptionsFilter( () => ( {} ) );

			expect( getCallbackCounts() ).toEqual( {
				rules: 1,
				checks: 1,
				runOptions: 1,
				configOptions: 0,
			} );
		} );

		test( 'should clear all callbacks', () => {
			addRulesFilter( () => [] );
			addChecksFilter( () => [] );

			expect( getCallbackCounts().rules ).toBe( 1 );
			expect( getCallbackCounts().checks ).toBe( 1 );

			clearAllCallbacks();

			expect( getCallbackCounts() ).toEqual( {
				rules: 0,
				checks: 0,
				runOptions: 0,
				configOptions: 0,
			} );
		} );

		test( 'should only accept function callbacks', () => {
			addRulesFilter( 'not a function' );
			addChecksFilter( null );
			addRunOptionsFilter( 123 );

			expect( getCallbackCounts() ).toEqual( {
				rules: 0,
				checks: 0,
				runOptions: 0,
				configOptions: 0,
			} );
		} );
	} );
} );
