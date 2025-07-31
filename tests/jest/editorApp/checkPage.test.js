/**
 * Tests for the checkPage module functionality
 */

// Mock the helper functions
jest.mock('../../../src/editorApp/helpers', () => ({
	info: jest.fn(),
	debug: jest.fn(),
}));

jest.mock('../../../src/common/helpers', () => ({
	showNotice: jest.fn(),
}));

describe('checkPage functionality', () => {
	let originalEdacEditorApp;
	let originalFetch;
	let mockInjectIframe;

	beforeEach(() => {
		// Store original values
		originalEdacEditorApp = global.edac_editor_app;
		originalFetch = global.fetch;

		// Mock global edac_editor_app object
		global.edac_editor_app = {
			postID: '123',
			postStatus: 'draft',
			scannablePostStatuses: ['publish', 'future', 'draft', 'pending', 'private'],
			scanUrl: 'http://example.com/preview?post=123',
			active: true,
		};

		// Mock fetch
		global.fetch = jest.fn(() => 
			Promise.resolve({
				json: () => Promise.resolve({ success: true }),
			})
		);

		// Mock the injectIframe function since it manipulates DOM
		mockInjectIframe = jest.fn();

		// Clear document body
		document.body.innerHTML = '';
	});

	afterEach(() => {
		// Restore original values
		global.edac_editor_app = originalEdacEditorApp;
		global.fetch = originalFetch;
		jest.clearAllMocks();
	});

	describe('post status validation', () => {
		test('should not scan posts with auto-draft status', async () => {
			// Set post status to auto-draft (unsaved new post)
			global.edac_editor_app.postStatus = 'auto-draft';

			// Mock the module to test the isPostScannable function
			const checkPageModule = require('../../../src/editorApp/checkPage');
			
			// Get the module content as text to extract the isPostScannable function
			const fs = require('fs');
			const path = require('path');
			const checkPageContent = fs.readFileSync(
				path.join(__dirname, '../../../src/editorApp/checkPage.js'),
				'utf8'
			);

			// Extract and evaluate the isPostScannable function
			const isPostScannableMatch = checkPageContent.match(
				/const isPostScannable = \(\) => \{[\s\S]*?\};/
			);
			
			expect(isPostScannableMatch).toBeTruthy();
			
			// Create a function to test the logic
			const testFunction = new Function(`
				const edac_editor_app = arguments[0];
				${isPostScannableMatch[0]}
				return isPostScannable();
			`);

			const result = testFunction(global.edac_editor_app);
			expect(result).toBe(false);
		});

		test('should scan posts with draft status', async () => {
			// Set post status to draft (saved post)
			global.edac_editor_app.postStatus = 'draft';

			// Get the module content as text to extract the isPostScannable function
			const fs = require('fs');
			const path = require('path');
			const checkPageContent = fs.readFileSync(
				path.join(__dirname, '../../../src/editorApp/checkPage.js'),
				'utf8'
			);

			// Extract and evaluate the isPostScannable function
			const isPostScannableMatch = checkPageContent.match(
				/const isPostScannable = \(\) => \{[\s\S]*?\};/
			);
			
			expect(isPostScannableMatch).toBeTruthy();
			
			// Create a function to test the logic
			const testFunction = new Function(`
				const edac_editor_app = arguments[0];
				${isPostScannableMatch[0]}
				return isPostScannable();
			`);

			const result = testFunction(global.edac_editor_app);
			expect(result).toBe(true);
		});

		test('should scan posts with publish status', async () => {
			// Set post status to publish
			global.edac_editor_app.postStatus = 'publish';

			// Get the module content as text to extract the isPostScannable function
			const fs = require('fs');
			const path = require('path');
			const checkPageContent = fs.readFileSync(
				path.join(__dirname, '../../../src/editorApp/checkPage.js'),
				'utf8'
			);

			// Extract and evaluate the isPostScannable function
			const isPostScannableMatch = checkPageContent.match(
				/const isPostScannable = \(\) => \{[\s\S]*?\};/
			);
			
			expect(isPostScannableMatch).toBeTruthy();
			
			// Create a function to test the logic
			const testFunction = new Function(`
				const edac_editor_app = arguments[0];
				${isPostScannableMatch[0]}
				return isPostScannable();
			`);

			const result = testFunction(global.edac_editor_app);
			expect(result).toBe(true);
		});

		test('should not scan posts with empty or missing status', async () => {
			// Set post status to empty
			global.edac_editor_app.postStatus = '';

			// Get the module content as text to extract the isPostScannable function
			const fs = require('fs');
			const path = require('path');
			const checkPageContent = fs.readFileSync(
				path.join(__dirname, '../../../src/editorApp/checkPage.js'),
				'utf8'
			);

			// Extract and evaluate the isPostScannable function
			const isPostScannableMatch = checkPageContent.match(
				/const isPostScannable = \(\) => \{[\s\S]*?\};/
			);
			
			expect(isPostScannableMatch).toBeTruthy();
			
			// Create a function to test the logic
			const testFunction = new Function(`
				const edac_editor_app = arguments[0];
				${isPostScannableMatch[0]}
				return isPostScannable();
			`);

			const result = testFunction(global.edac_editor_app);
			expect(result).toBe(false);
		});

		test('should not scan when scannablePostStatuses is missing', async () => {
			// Remove scannablePostStatuses
			delete global.edac_editor_app.scannablePostStatuses;

			// Get the module content as text to extract the isPostScannable function
			const fs = require('fs');
			const path = require('path');
			const checkPageContent = fs.readFileSync(
				path.join(__dirname, '../../../src/editorApp/checkPage.js'),
				'utf8'
			);

			// Extract and evaluate the isPostScannable function
			const isPostScannableMatch = checkPageContent.match(
				/const isPostScannable = \(\) => \{[\s\S]*?\};/
			);
			
			expect(isPostScannableMatch).toBeTruthy();
			
			// Create a function to test the logic
			const testFunction = new Function(`
				const edac_editor_app = arguments[0];
				${isPostScannableMatch[0]}
				return isPostScannable();
			`);

			const result = testFunction(global.edac_editor_app);
			expect(result).toBe(false);
		});
	});
});