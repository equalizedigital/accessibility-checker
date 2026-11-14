import { findElement } from './findElement';

/**
 * Sort violations into the order their elements appear in the document.
 *
 * Violations whose element cannot be found keep their relative order and go
 * last. They are still reported: one missing element must not cost the page
 * the rest of its results.
 *
 * @param {Object[]} violations Violations, each with a `selector`.
 * @param {Document} root       The document the violations were found in.
 * @return {Object[]} The same array, sorted in place.
 */
export const sortViolationsByDocumentOrder = ( violations, root = document ) => {
	// Looked up once each, not on every comparison.
	const elements = new Map( violations.map( ( violation ) => [ violation, findElement( violation.selector, root ) ] ) );

	return violations.sort( ( first, second ) => {
		const a = elements.get( first );
		const b = elements.get( second );

		if ( a === b ) {
			return 0;
		}
		if ( ! a ) {
			return 1;
		}
		if ( ! b ) {
			return -1;
		}

		// eslint-disable-next-line no-bitwise
		return a.compareDocumentPosition( b ) & 2 ? 1 : -1; // 2: b comes before a.
	} );
};
