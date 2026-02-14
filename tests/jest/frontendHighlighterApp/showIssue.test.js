import { AccessibilityCheckerHighlight } from '../../../src/frontendHighlighterApp/index';

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
	_n: ( single, plural, count ) => ( count === 1 ? single : plural ),
	sprintf: ( text ) => text,
} ) );

jest.mock(
	'@floating-ui/dom',
	() => ( {
		computePosition: jest.fn(),
		autoUpdate: jest.fn(),
	} ),
	{ virtual: true }
);

jest.mock(
	'focus-trap',
	() => ( {
		createFocusTrap: jest.fn( () => ( {
			activate: jest.fn(),
			deactivate: jest.fn(),
		} ) ),
	} ),
	{ virtual: true }
);

jest.mock(
	'tabbable',
	() => ( {
		isFocusable: jest.fn( () => true ),
	} ),
	{ virtual: true }
);

jest.mock( '../../../src/common/saveFixSettingsRest', () => ( {
	saveFixSettings: jest.fn(),
} ) );

jest.mock( '../../../src/frontendHighlighterApp/fixesModal', () => ( {
	fillFixesModal: jest.fn(),
	fixSettingsModalInit: jest.fn(),
	openFixesModal: jest.fn(),
} ) );

describe( 'AccessibilityCheckerHighlight.showIssue', () => {
	it( 'does not throw when issues are not initialized', () => {
		const subject = {
			removeSelectedClasses: jest.fn(),
			descriptionOpen: jest.fn(),
			issues: null,
			currentButtonIndex: null,
			currentIssueStatus: null,
		};

		expect( () => {
			AccessibilityCheckerHighlight.prototype.showIssue.call( subject, 'missing' );
		} ).not.toThrow();

		expect( subject.currentButtonIndex ).toBe( -1 );
		expect( subject.currentIssueStatus ).toBe( 'The element was not found on the page.' );
		expect( subject.descriptionOpen ).toHaveBeenCalledWith( 'missing' );
	} );

	it( 'does not throw when issue id is not found', () => {
		const subject = {
			removeSelectedClasses: jest.fn(),
			descriptionOpen: jest.fn(),
			issues: [],
			currentButtonIndex: null,
			currentIssueStatus: null,
		};

		expect( () => {
			AccessibilityCheckerHighlight.prototype.showIssue.call( subject, 'missing' );
		} ).not.toThrow();

		expect( subject.currentButtonIndex ).toBe( -1 );
		expect( subject.currentIssueStatus ).toBe( 'The element was not found on the page.' );
		expect( subject.descriptionOpen ).toHaveBeenCalledWith( 'missing' );
	} );
} );
