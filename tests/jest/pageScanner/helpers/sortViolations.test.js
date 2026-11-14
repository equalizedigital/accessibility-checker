/**
 * Tests for sorting scan violations into document order.
 */
import { sortViolationsByDocumentOrder } from '../../../../src/pageScanner/helpers/sortViolations';

const ruleIds = ( violations ) => violations.map( ( violation ) => violation.ruleId );

describe( 'sortViolationsByDocumentOrder', () => {
	beforeEach( () => {
		document.body.innerHTML = '<p id="first"></p><div><img id="second"></div><a id="third"></a>';
	} );

	test( 'puts violations in the order their elements appear', () => {
		const violations = [
			{ ruleId: 'c', selector: '#third' },
			{ ruleId: 'a', selector: '#first' },
			{ ruleId: 'b', selector: [ '#second' ] },
		];

		expect( ruleIds( sortViolationsByDocumentOrder( violations ) ) ).toEqual( [ 'a', 'b', 'c' ] );
	} );

	test( 'keeps violations on the same element together', () => {
		const violations = [
			{ ruleId: 'c', selector: '#third' },
			{ ruleId: 'a1', selector: '#first' },
			{ ruleId: 'a2', selector: '#first' },
		];

		expect( ruleIds( sortViolationsByDocumentOrder( violations ) ) ).toEqual( [ 'a1', 'a2', 'c' ] );
	} );

	test( 'a violation whose element is gone is kept, after the ones that were found', () => {
		const violations = [
			{ ruleId: 'gone', selector: '#removed-by-a-script' },
			{ ruleId: 'c', selector: '#third' },
			{ ruleId: 'also-gone', selector: '#inside-a-frame' },
			{ ruleId: 'a', selector: '#first' },
		];

		expect( () => sortViolationsByDocumentOrder( violations ) ).not.toThrow();
		expect( ruleIds( violations ) ).toEqual( [ 'a', 'c', 'gone', 'also-gone' ] );
	} );

	test( 'a selector the browser rejects does not stop the sort', () => {
		const violations = [
			{ ruleId: 'bad', selector: 'div:::nope(' },
			{ ruleId: 'none', selector: undefined },
			{ ruleId: 'b', selector: '#second' },
		];

		expect( ruleIds( sortViolationsByDocumentOrder( violations ) ) ).toEqual( [ 'b', 'bad', 'none' ] );
	} );

	test( 'an empty list is left alone', () => {
		expect( sortViolationsByDocumentOrder( [] ) ).toEqual( [] );
	} );
} );
