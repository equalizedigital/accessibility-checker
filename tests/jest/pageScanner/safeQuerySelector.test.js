import { safeQuerySelector } from '../../../src/pageScanner/index';

describe( 'safeQuerySelector', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'returns null for invalid selectors without throwing', () => {
		expect( () => safeQuerySelector( 'div>>' ) ).not.toThrow();
		expect( safeQuerySelector( 'div>>' ) ).toBeNull();
	} );

	test( 'returns element for a valid selector', () => {
		document.body.innerHTML = '<div class="target"></div>';
		expect( safeQuerySelector( '.target' ) ).not.toBeNull();
	} );
} );
