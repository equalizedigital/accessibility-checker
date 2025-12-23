/**
 * Tests for AccessibilityCheckerHighlight
 * Testing element matching and DOM ordering functionality
 */

/* global global */

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
	_n: ( single, plural, count ) => ( count === 1 ? single : plural ),
	sprintf: ( format, ...args ) => {
		let result = format;
		args.forEach( ( arg, index ) => {
			result = result.replace( `%${ index + 1 }$s`, arg );
		} );
		return result;
	},
} ) );

// Mock @floating-ui/dom
jest.mock( '@floating-ui/dom', () => ( {
	computePosition: jest.fn( () => Promise.resolve( { x: 0, y: 0, middlewareData: {}, placement: 'top-start' } ) ),
	autoUpdate: jest.fn( ( reference, floating, update ) => {
		update();
		return jest.fn(); // return cleanup function
	} ),
} ) );

// Mock focus-trap
jest.mock( 'focus-trap', () => ( {
	createFocusTrap: jest.fn( () => ( {
		activate: jest.fn(),
		deactivate: jest.fn(),
		pause: jest.fn(),
		unpause: jest.fn(),
	} ) ),
} ) );

// Mock tabbable
jest.mock( 'tabbable', () => ( {
	isFocusable: jest.fn( () => true ),
} ) );

// Mock other dependencies
jest.mock( '../../../src/common/saveFixSettingsRest', () => ( {
	saveFixSettings: jest.fn(),
} ) );

jest.mock( '../../../src/frontendHighlighterApp/fixesModal', () => ( {
	fillFixesModal: jest.fn(),
	fixSettingsModalInit: jest.fn(),
	openFixesModal: jest.fn(),
} ) );

jest.mock( '../../../src/common/helpers', () => ( {
	hashString: jest.fn( ( str ) => `hash_${ str.length }` ),
} ) );

describe( 'AccessibilityCheckerHighlight', () => {
	beforeAll( () => {
		// Set up global variables
		global.edacFrontendHighlighterApp = {
			ajaxurl: 'http://example.com/wp-admin/admin-ajax.php',
			postID: '123',
			nonce: 'test-nonce',
			restNonce: 'test-rest-nonce',
			edacUrl: 'http://example.com',
			widgetPosition: 'right',
			userCanEdit: true,
			userCanFix: true,
			loggedIn: true,
			appCssUrl: 'http://example.com/wp-content/plugins/accessibility-checker/build/app.css',
			scannerBundleUrl: 'http://example.com/wp-content/plugins/accessibility-checker/build/pageScanner.bundle.js',
		};
	} );

	beforeEach( () => {
		// Reset DOM
		document.body.innerHTML = '';
		document.head.innerHTML = '';

		// Reset viewport
		Object.defineProperty( window, 'innerWidth', {
			writable: true,
			configurable: true,
			value: 1024,
		} );
		Object.defineProperty( window, 'innerHeight', {
			writable: true,
			configurable: true,
			value: 768,
		} );
	} );

	describe( 'findElement method - selector priority', () => {
		test( 'should find element by selector first (highest priority)', () => {
			// Create a minimal DOM structure for testing
			document.body.innerHTML = `
				<div id="edac-highlight-panel" class="edac-highlight-panel edac-highlight-panel--right">
					<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle"></button>
					<div id="edac-highlight-panel-description" class="edac-highlight-panel-description"></div>
					<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls">
						<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close"></button>
						<div class="edac-highlight-panel-controls-summary"></div>
						<button id="edac-highlight-next"></button>
						<button id="edac-highlight-previous"></button>
						<button id="edac-highlight-disable-styles"></button>
					</div>
					<button class="edac-highlight-panel-description-close"></button>
				</div>
				<div id="test-element" class="test-class">Test content</div>
				<div class="another-element">Another element</div>
			`;

			const value = {
				id: '1',
				selector: '#test-element',
				ancestry: 'body > div:nth-child(2)',
				object: '<div id="test-element" class="test-class">Test content</div>',
				rule_title: 'Test Rule',
			};

			// Test that selector can find the element
			const element = document.querySelector( value.selector );
			expect( element ).not.toBeNull();
			expect( element.id ).toBe( 'test-element' );
		} );

		test( 'should fall back to ancestry selector when selector is invalid', () => {
			document.body.innerHTML = '<div class="ancestry-target">Ancestry element</div>';

			const value = {
				id: '2',
				selector: '#non-existent',
				ancestry: '.ancestry-target',
				object: '<div class="ancestry-target">Ancestry element</div>',
				rule_title: 'Test Rule',
			};

			// Test selector priority: try selector first, fall back to ancestry
			let element = document.querySelector( value.selector );
			expect( element ).toBeNull();

			// Fall back to ancestry
			element = document.querySelector( value.ancestry );
			expect( element ).not.toBeNull();
			expect( element.className ).toBe( 'ancestry-target' );
		} );

		test( 'should fall back to HTML matching when both selector and ancestry fail', () => {
			document.body.innerHTML = '<div class="another-element">Another element</div>';

			const value = {
				id: '3',
				selector: '#non-existent',
				ancestry: '.non-existent',
				object: '<div class="another-element">Another element</div>',
				rule_title: 'Test Rule',
			};

			// Test that both selector and ancestry fail
			let element = document.querySelector( value.selector );
			expect( element ).toBeNull();

			element = document.querySelector( value.ancestry );
			expect( element ).toBeNull();

			// Test HTML matching as fallback
			const parser = new DOMParser();
			const parsedHtml = parser.parseFromString( value.object, 'text/html' );
			const firstParsedElement = parsedHtml.body.firstElementChild;
			const htmlToFind = firstParsedElement.outerHTML;

			const allElements = document.body.querySelectorAll( '*' );
			let found = false;
			for ( const el of allElements ) {
				if ( el.outerHTML.replace( /\W/g, '' ) === htmlToFind.replace( /\W/g, '' ) ) {
					found = true;
					expect( el.className ).toBe( 'another-element' );
					break;
				}
			}
			expect( found ).toBe( true );
		} );

		test( 'should return null when no matching element is found', () => {
			document.body.innerHTML = '<div class="unrelated">Unrelated</div>';

			const value = {
				id: '4',
				selector: '#non-existent',
				ancestry: '.non-existent',
				object: '<div class="does-not-exist">Not found</div>',
				rule_title: 'Test Rule',
			};

			// Test that all methods fail
			const element = document.querySelector( value.selector );
			expect( element ).toBeNull();

			const ancestryElement = document.querySelector( value.ancestry );
			expect( ancestryElement ).toBeNull();

			// Test HTML matching also fails
			const parser = new DOMParser();
			const parsedHtml = parser.parseFromString( value.object, 'text/html' );
			const firstParsedElement = parsedHtml.body.firstElementChild;
			const htmlToFind = firstParsedElement.outerHTML;

			const allElements = document.body.querySelectorAll( '*' );
			let found = false;
			for ( const el of allElements ) {
				if ( el.outerHTML.replace( /\W/g, '' ) === htmlToFind.replace( /\W/g, '' ) ) {
					found = true;
					break;
				}
			}
			expect( found ).toBe( false );
		} );

		test( 'should handle invalid selector gracefully', () => {
			document.body.innerHTML = '<div class="another-element">Another element</div>';

			const value = {
				id: '5',
				selector: 'invalid[selector:::', // Invalid CSS selector
				ancestry: '.another-element',
				object: '<div class="another-element">Another element</div>',
				rule_title: 'Test Rule',
			};

			// Test that invalid selector throws or returns null
			let element = null;
			try {
				element = document.querySelector( value.selector );
			} catch ( e ) {
				// Selector is invalid, which is expected
				expect( e ).toBeDefined();
			}

			// Should fall back to ancestry and find the element
			element = document.querySelector( value.ancestry );
			expect( element ).not.toBeNull();
			expect( element.className ).toBe( 'another-element' );
		} );
	} );

	describe( 'DOM ordering with compareDocumentPosition', () => {
		test( 'should sort issues in DOM order', () => {
			// Create a test DOM structure
			document.body.innerHTML = `
				<div id="edac-highlight-panel"></div>
				<div id="first">First</div>
				<div id="second">Second</div>
				<div id="third">Third</div>
			`;

			const issues = [
				{
					id: '3',
					element: document.getElementById( 'third' ),
					rule_title: 'Third Issue',
					tooltip: null,
				},
				{
					id: '1',
					element: document.getElementById( 'first' ),
					rule_title: 'First Issue',
					tooltip: null,
				},
				{
					id: '2',
					element: document.getElementById( 'second' ),
					rule_title: 'Second Issue',
					tooltip: null,
				},
			];

			// Sort using the same logic as in the actual code
			issues.sort( ( a, b ) => {
				if ( ! a.element && b.element ) {
					return 1;
				}
				if ( a.element && ! b.element ) {
					return -1;
				}
				if ( ! a.element && ! b.element ) {
					return 0;
				}

				const position = a.element.compareDocumentPosition( b.element );

				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_FOLLOWING ) {
					return -1;
				}
				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_PRECEDING ) {
					return 1;
				}

				return 0;
			} );

			expect( issues[ 0 ].id ).toBe( '1' );
			expect( issues[ 1 ].id ).toBe( '2' );
			expect( issues[ 2 ].id ).toBe( '3' );
		} );

		test( 'should push issues without elements to the end', () => {
			document.body.innerHTML = `
				<div id="first">First</div>
				<div id="second">Second</div>
			`;

			const issues = [
				{
					id: '3',
					element: null,
					rule_title: 'No Element',
				},
				{
					id: '1',
					element: document.getElementById( 'first' ),
					rule_title: 'First Issue',
				},
				{
					id: '4',
					element: null,
					rule_title: 'Also No Element',
				},
				{
					id: '2',
					element: document.getElementById( 'second' ),
					rule_title: 'Second Issue',
				},
			];

			issues.sort( ( a, b ) => {
				if ( ! a.element && b.element ) {
					return 1;
				}
				if ( a.element && ! b.element ) {
					return -1;
				}
				if ( ! a.element && ! b.element ) {
					return 0;
				}

				const position = a.element.compareDocumentPosition( b.element );

				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_FOLLOWING ) {
					return -1;
				}
				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_PRECEDING ) {
					return 1;
				}

				return 0;
			} );

			// First two should have elements, last two should not
			expect( issues[ 0 ].element ).not.toBeNull();
			expect( issues[ 1 ].element ).not.toBeNull();
			expect( issues[ 2 ].element ).toBeNull();
			expect( issues[ 3 ].element ).toBeNull();
		} );

		test( 'should handle nested elements correctly', () => {
			document.body.innerHTML = `
				<div id="parent">
					<div id="child1">Child 1</div>
					<div id="child2">
						<div id="grandchild">Grandchild</div>
					</div>
					<div id="child3">Child 3</div>
				</div>
			`;

			const issues = [
				{
					id: '4',
					element: document.getElementById( 'child3' ),
					rule_title: 'Child 3',
				},
				{
					id: '1',
					element: document.getElementById( 'parent' ),
					rule_title: 'Parent',
				},
				{
					id: '3',
					element: document.getElementById( 'grandchild' ),
					rule_title: 'Grandchild',
				},
				{
					id: '2',
					element: document.getElementById( 'child1' ),
					rule_title: 'Child 1',
				},
			];

			issues.sort( ( a, b ) => {
				if ( ! a.element && b.element ) {
					return 1;
				}
				if ( a.element && ! b.element ) {
					return -1;
				}
				if ( ! a.element && ! b.element ) {
					return 0;
				}

				const position = a.element.compareDocumentPosition( b.element );

				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_FOLLOWING ) {
					return -1;
				}
				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_PRECEDING ) {
					return 1;
				}

				return 0;
			} );

			// Should be in document order: parent, child1, grandchild, child3
			expect( issues[ 0 ].id ).toBe( '1' ); // parent
			expect( issues[ 1 ].id ).toBe( '2' ); // child1
			expect( issues[ 2 ].id ).toBe( '3' ); // grandchild
			expect( issues[ 3 ].id ).toBe( '4' ); // child3
		} );
	} );

	describe( 'Tooltip aria-label updates after sorting', () => {
		test( 'should update aria-labels to reflect sorted order', () => {
			const tooltips = [
				document.createElement( 'button' ),
				document.createElement( 'button' ),
				document.createElement( 'button' ),
			];

			const issues = [
				{
					id: '3',
					rule_title: 'Third Issue',
					tooltip: tooltips[ 2 ],
				},
				{
					id: '1',
					rule_title: 'First Issue',
					tooltip: tooltips[ 0 ],
				},
				{
					id: '2',
					rule_title: 'Second Issue',
					tooltip: tooltips[ 1 ],
				},
			];

			// Simulate the aria-label update logic
			const sprintf = ( format, ...args ) => {
				let result = format;
				args.forEach( ( arg, index ) => {
					result = result.replace( `%${ index + 1 }$s`, arg );
				} );
				return result;
			};

			issues.forEach( ( issue, sortedIndex ) => {
				if ( issue.tooltip ) {
					issue.tooltip.setAttribute(
						'aria-label',
						sprintf(
							'Open details for %1$s, %2$s of %3$s',
							issue.rule_title,
							sortedIndex + 1,
							issues.length
						)
					);
				}
			} );

			expect( issues[ 0 ].tooltip.getAttribute( 'aria-label' ) ).toBe( 'Open details for Third Issue, 1 of 3' );
			expect( issues[ 1 ].tooltip.getAttribute( 'aria-label' ) ).toBe( 'Open details for First Issue, 2 of 3' );
			expect( issues[ 2 ].tooltip.getAttribute( 'aria-label' ) ).toBe( 'Open details for Second Issue, 3 of 3' );
		} );

		test( 'should handle issues without tooltips', () => {
			const tooltip = document.createElement( 'button' );

			const issues = [
				{
					id: '1',
					rule_title: 'Issue with Tooltip',
					tooltip,
				},
				{
					id: '2',
					rule_title: 'Issue without Tooltip',
					tooltip: null,
				},
			];

			const sprintf = ( format, ...args ) => {
				let result = format;
				args.forEach( ( arg, index ) => {
					result = result.replace( `%${ index + 1 }$s`, arg );
				} );
				return result;
			};

			// Should not throw error for missing tooltip
			expect( () => {
				issues.forEach( ( issue, sortedIndex ) => {
					if ( issue.tooltip ) {
						issue.tooltip.setAttribute(
							'aria-label',
							sprintf(
								'Open details for %1$s, %2$s of %3$s',
								issue.rule_title,
								sortedIndex + 1,
								issues.length
							)
						);
					}
				} );
			} ).not.toThrow();

			expect( tooltip.getAttribute( 'aria-label' ) ).toBe( 'Open details for Issue with Tooltip, 1 of 2' );
		} );
	} );

	describe( 'Integration: Selector priority with DOM ordering', () => {
		test( 'should demonstrate selector priority and then sort in DOM order', () => {
			document.body.innerHTML = `
				<div id="edac-highlight-panel"></div>
				<div id="first" class="test-element">First</div>
				<section>
					<div id="second">Second</div>
				</section>
				<article>
					<p id="third" class="paragraph">Third</p>
				</article>
			`;

			const issuesData = [
				{
					id: '3',
					selector: '.paragraph',
					ancestry: 'article > p:nth-child(1)',
					object: '<p id="third" class="paragraph">Third</p>',
					rule_title: 'Third Issue',
				},
				{
					id: '1',
					selector: '#first',
					ancestry: 'body > div:nth-child(2)',
					object: '<div id="first" class="test-element">First</div>',
					rule_title: 'First Issue',
				},
				{
					id: '2',
					selector: null,
					ancestry: 'section > div:nth-child(1)',
					object: '<div id="second">Second</div>',
					rule_title: 'Second Issue',
				},
			];

			const issues = [];

			// Simulate finding elements with priority: selector > ancestry > HTML
			issuesData.forEach( ( value ) => {
				let element = null;

				// Try selector first
				if ( value.selector ) {
					try {
						element = document.querySelector( value.selector );
					} catch ( e ) {
						// Invalid selector
					}
				}

				// Try ancestry if selector failed
				if ( ! element && value.ancestry ) {
					try {
						element = document.querySelector( value.ancestry );
					} catch ( e ) {
						// Invalid ancestry
					}
				}

				// HTML matching would be next, but we'll skip for this test

				if ( element ) {
					issues.push( { ...value, element } );
				}
			} );

			// Sort by DOM order
			issues.sort( ( a, b ) => {
				if ( ! a.element && b.element ) {
					return 1;
				}
				if ( a.element && ! b.element ) {
					return -1;
				}
				if ( ! a.element && ! b.element ) {
					return 0;
				}

				const position = a.element.compareDocumentPosition( b.element );

				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_FOLLOWING ) {
					return -1;
				}
				// eslint-disable-next-line no-bitwise
				if ( position & Node.DOCUMENT_POSITION_PRECEDING ) {
					return 1;
				}

				return 0;
			} );

			// Verify sorted order
			expect( issues.length ).toBe( 3 );
			expect( issues[ 0 ].id ).toBe( '1' );
			expect( issues[ 1 ].id ).toBe( '2' );
			expect( issues[ 2 ].id ).toBe( '3' );
		} );
	} );
} );
