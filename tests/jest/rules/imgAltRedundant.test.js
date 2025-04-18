import axe from 'axe-core';
import { altTextMap } from '../../../src/pageScanner/checks/img-alt-redundant-check';

beforeAll( async () => {
	// Dynamically import the modules for the new rule.
	const imgAltRedundantRuleModule = await import( '../../../src/pageScanner/rules/img-alt-redundant' );
	const imgAltRedundantCheckModule = await import( '../../../src/pageScanner/checks/img-alt-redundant-check' );

	const imgAltRedundantRule = imgAltRedundantRuleModule.default;
	const imgAltRedundantCheck = imgAltRedundantCheckModule.default;

	axe.configure( {
		rules: [ imgAltRedundantRule ],
		checks: [ imgAltRedundantCheck ],
	} );
} );

// Reset the document between tests.
beforeEach( () => {
	document.body.innerHTML = '';
	altTextMap.clear();
} );

describe( 'Image Alt Redundant Validation', () => {
	const testCases = [
		{
			name: 'should fail when two images have the same alt text',
			html: '<img src="image1.jpg" alt="redundant"><img src="image2.jpg" alt="redundant">',
			shouldPass: false,
		},
		{
			name: 'should fail when image alt and title are identical',
			html: '<img src="image.jpg" alt="sample" title="sample">',
			shouldPass: false,
		},
		{
			name: 'should fail when an image inside an anchor has alt matching the anchor text',
			html: '<a href="#">Link Text <img src="image.jpg" alt="Link Text"></a>',
			shouldPass: false,
		},
		{
			name: 'should fail when a figure has an image whose alt equals figcaption text',
			html: '<figure><img src="image.jpg" alt="caption"><figcaption>caption</figcaption></figure>',
			shouldPass: false,
		},
		{
			name: 'should pass when alt text is unique and not redundant',
			html: '<img src="image.jpg" alt="unique description">',
			shouldPass: true,
		},
		{
			name: 'should pass when image alt and title are different',
			html: '<img src="image.jpg" alt="description" title="different description">',
			shouldPass: true,
		},
		// Case sensitivity tests
		{
			name: 'should fail when two images have the same alt text with different casing',
			html: '<img src="image1.jpg" alt="Redundant"><img src="image2.jpg" alt="redundant">',
			shouldPass: false,
		},

		// Whitespace handling
		{
			name: 'should fail when alt and title are identical except for whitespace',
			html: '<img src="image.jpg" alt="sample text" title="   sample    text   ">',
			shouldPass: false,
		},

		// Complex nesting structures
		{
			name: 'should fail when deeply nested inside multiple containers',
			html: '<div><a href="#"><span>Caption text</span> <div><img src="image.jpg" alt="Caption text"></div></a></div>',
			shouldPass: false,
		},

		// Multiple redundancies in single image
		{
			name: 'should fail when image has multiple forms of redundancy',
			html: '<figure><a href="#">Caption <img src="image.jpg" alt="Caption" title="Caption"></a><figcaption>Caption</figcaption></figure>',
			shouldPass: false,
		},

		// Special characters
		{
			name: 'should fail with special characters in redundant alt text',
			html: '<img src="image1.jpg" alt="special & < > characters"><img src="image2.jpg" alt="special & < > characters">',
			shouldPass: false,
		},

		// Unicode/international characters
		{
			name: 'should fail with international characters in redundant alt text',
			html: '<img src="image1.jpg" alt="国际文本"><img src="image2.jpg" alt="国际文本">',
			shouldPass: false,
		},

		// Empty alt text should be ignored (decorative images)
		{
			name: 'should pass when multiple images have empty alt text (decorative)',
			html: '<img src="image1.jpg" alt=""><img src="image2.jpg" alt="">',
			shouldPass: true,
		},

		// Very long alt text
		{
			name: 'should fail with very long redundant alt text',
			html: '<img src="image1.jpg" alt="This is a very long description of an image that contains many words and should still be caught as a duplicate"><img src="image2.jpg" alt="This is a very long description of an image that contains many words and should still be caught as a duplicate">',
			shouldPass: false,
		},

		// Many duplicates (not just two)
		{
			name: 'should fail when many images share the same alt text',
			html: '<img src="1.jpg" alt="duplicate"><img src="2.jpg" alt="duplicate"><img src="3.jpg" alt="duplicate"><img src="4.jpg" alt="duplicate">',
			shouldPass: false,
		},

		// Similar but not identical (these should pass)
		{
			name: 'should pass when alt texts are similar but not identical',
			html: '<img src="image1.jpg" alt="dog running"><img src="image2.jpg" alt="dog is running">',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;
			// Run axe scan only for the img_alt_redundant rule.
			const results = await axe.run( document.body, {
				runOnly: [ 'img_alt_redundant' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
describe( 'altTextMap functionality', () => {
	test( 'should initialize as empty', () => {
		// Check that altTextMap starts empty
		expect( altTextMap.size ).toBe( 0 );
	} );

	test( 'should store values when added', () => {
		// Add values to the map
		altTextMap.set( 'test-alt-text', [ { id: 'test-id-1' } ] );
		altTextMap.set( 'another-alt-text', [ { id: 'test-id-2' } ] );

		// Verify values are stored
		expect( altTextMap.size ).toBe( 2 );
		expect( altTextMap.has( 'test-alt-text' ) ).toBe( true );
		expect( altTextMap.get( 'test-alt-text' ) ).toEqual( [ { id: 'test-id-1' } ] );
	} );

	test( 'should clear all values when clear() is called', () => {
		// Add values to the map
		altTextMap.set( 'test-alt', [ { id: 'id-1' } ] );
		expect( altTextMap.size ).toBe( 1 );

		// Clear the map
		altTextMap.clear();

		// Verify it's empty
		expect( altTextMap.size ).toBe( 0 );
		expect( altTextMap.has( 'test-alt' ) ).toBe( false );
	} );

	test( 'should clear between tests due to beforeEach hook', () => {
		// This test relies on the beforeEach hook clearing the map
		// Even though previous tests added items, this should be empty
		expect( altTextMap.size ).toBe( 0 );
	} );
} );
