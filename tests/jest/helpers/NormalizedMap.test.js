import { NormalizedMap } from '../../../src/pageScanner/helpers/helpers';

describe( 'NormalizedMap', () => {
	let map;

	beforeEach( () => {
		map = new NormalizedMap();
	} );

	test( 'should store and retrieve keys in a case-insensitive manner', () => {
		map.set( 'TestKey', 'value' );
		expect( map.get( 'testkey' ) ).toBe( 'value' );
		expect( map.get( 'TESTKEY' ) ).toBe( 'value' );
		expect( map.get( 'TestKey' ) ).toBe( 'value' );
	} );

	test( 'should check existence of keys in a case-insensitive manner', () => {
		map.set( 'AnotherKey', 'value' );
		expect( map.has( 'anotherkey' ) ).toBe( true );
		expect( map.has( 'ANOTHERKEY' ) ).toBe( true );
		expect( map.has( 'AnotherKey' ) ).toBe( true );
	} );

	test( 'should delete keys in a case-insensitive manner', () => {
		map.set( 'KeyToDelete', 'value' );
		expect( map.has( 'keytodelete' ) ).toBe( true );

		map.delete( 'KEYTODELETE' );
		expect( map.has( 'KeyToDelete' ) ).toBe( false );
	} );

	test( 'should handle non-string keys without normalization', () => {
		const objKey = {};
		map.set( objKey, 'value' );
		expect( map.get( objKey ) ).toBe( 'value' );
		expect( map.has( objKey ) ).toBe( true );

		map.delete( objKey );
		expect( map.has( objKey ) ).toBe( false );
	} );

	test( 'should handle mixed string and non-string keys correctly', () => {
		map.set( 'StringKey', 'stringValue' );
		map.set( 123, 'numberValue' );

		expect( map.get( 'stringkey' ) ).toBe( 'stringValue' );
		expect( map.get( 123 ) ).toBe( 'numberValue' );
	} );
} );
