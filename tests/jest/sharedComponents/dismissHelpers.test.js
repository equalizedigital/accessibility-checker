/**
 * Hi-fi tests for dismissHelpers utilities.
 *
 * DISMISS_REASONS is a module-level constant read from
 * window.edac_sidebar_app.dismissReasons at import time, so we use
 * jest.isolateModules() to control what the module sees during load.
 */

const FIXTURE_REASONS = {
	false_positive: {
		label: 'False positive',
		description: 'This issue does not apply to this content.',
	},
	remediated: {
		label: 'Remediated',
		description: 'This issue has been fixed.',
	},
	accessible: {
		label: 'Accessible',
		description: 'This content is accessible by other means.',
	},
};

/**
 * Helper to load dismissHelpers with controlled window.edac_sidebar_app.
 *
 * @param {Object|undefined} dismissReasons - Value to assign to dismissReasons.
 * @return {Object} The freshly-loaded module exports.
 */
function loadHelpers( dismissReasons ) {
	let helpers;
	jest.isolateModules( () => {
		window.edac_sidebar_app = { dismissReasons };
		helpers = require( '../../../src/sidebar/utils/dismissHelpers' );
	} );
	return helpers;
}

describe( 'dismissHelpers', () => {
	afterEach( () => {
		// Restore the shared setup value so other suites are unaffected.
		window.edac_sidebar_app = { highlightNonce: 'test-highlight-nonce', canManageSettings: true };
	} );

	describe( 'DISMISS_REASONS', () => {
		test( 'equals the reasons object provided by PHP', () => {
			const { DISMISS_REASONS } = loadHelpers( FIXTURE_REASONS );
			expect( DISMISS_REASONS ).toEqual( FIXTURE_REASONS );
		} );

		test( 'falls back to empty object when dismissReasons is undefined', () => {
			const { DISMISS_REASONS } = loadHelpers( undefined );
			expect( DISMISS_REASONS ).toEqual( {} );
		} );
	} );

	describe( 'getDismissReasonOptions', () => {
		test( 'returns an array entry for each dismiss reason', () => {
			const { getDismissReasonOptions } = loadHelpers( FIXTURE_REASONS );
			const options = getDismissReasonOptions();

			expect( options ).toHaveLength( 3 );
		} );

		test( 'each option has value, label, and description keys', () => {
			const { getDismissReasonOptions } = loadHelpers( FIXTURE_REASONS );
			const options = getDismissReasonOptions();

			options.forEach( ( option ) => {
				expect( option ).toHaveProperty( 'value' );
				expect( option ).toHaveProperty( 'label' );
				expect( option ).toHaveProperty( 'description' );
			} );
		} );

		test( 'option values match the DISMISS_REASONS keys', () => {
			const { getDismissReasonOptions } = loadHelpers( FIXTURE_REASONS );
			const options = getDismissReasonOptions();
			const values = options.map( ( o ) => o.value );

			expect( values ).toContain( 'false_positive' );
			expect( values ).toContain( 'remediated' );
			expect( values ).toContain( 'accessible' );
		} );

		test( 'returns empty array when no dismiss reasons exist', () => {
			const { getDismissReasonOptions } = loadHelpers( {} );
			expect( getDismissReasonOptions() ).toEqual( [] );
		} );
	} );

	describe( 'getDismissReasonLabel', () => {
		test( 'returns the label for a known reason value', () => {
			const { getDismissReasonLabel } = loadHelpers( FIXTURE_REASONS );
			expect( getDismissReasonLabel( 'false_positive' ) ).toBe( 'False positive' );
			expect( getDismissReasonLabel( 'remediated' ) ).toBe( 'Remediated' );
			expect( getDismissReasonLabel( 'accessible' ) ).toBe( 'Accessible' );
		} );

		test( 'returns empty string for an unknown reason value', () => {
			const { getDismissReasonLabel } = loadHelpers( FIXTURE_REASONS );
			expect( getDismissReasonLabel( 'not_a_real_reason' ) ).toBe( '' );
		} );

		test( 'returns empty string when DISMISS_REASONS is empty', () => {
			const { getDismissReasonLabel } = loadHelpers( {} );
			expect( getDismissReasonLabel( 'false_positive' ) ).toBe( '' );
		} );
	} );

	describe( 'getDismissReasonDescription', () => {
		test( 'returns the description for a known reason value', () => {
			const { getDismissReasonDescription } = loadHelpers( FIXTURE_REASONS );
			expect( getDismissReasonDescription( 'false_positive' ) ).toBe( 'This issue does not apply to this content.' );
			expect( getDismissReasonDescription( 'remediated' ) ).toBe( 'This issue has been fixed.' );
		} );

		test( 'returns empty string for an unknown reason value', () => {
			const { getDismissReasonDescription } = loadHelpers( FIXTURE_REASONS );
			expect( getDismissReasonDescription( 'not_a_real_reason' ) ).toBe( '' );
		} );

		test( 'returns empty string when DISMISS_REASONS is empty', () => {
			const { getDismissReasonDescription } = loadHelpers( {} );
			expect( getDismissReasonDescription( 'false_positive' ) ).toBe( '' );
		} );
	} );
} );

