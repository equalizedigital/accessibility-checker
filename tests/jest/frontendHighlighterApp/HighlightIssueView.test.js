import '../../../src/frontendHighlighterApp/components/HighlightIssueView.js';

const baseIssue = {
	rule_title: 'Missing alt text',
	rule_type: 'error',
	wcag: '1.1.1',
	wcag_title: 'Non-text Content',
	link: 'https://example.com/wcag',
	severity: 1,
	summary: 'Image is missing alt text.',
	object: '<img src="foo.jpg">',
	slug: 'img_alt_missing',
};

const mountView = ( overrides = {} ) => {
	const el = document.createElement( 'edac-highlight-issue-view' );
	document.body.appendChild( el );
	el.issue = { ...baseIssue, ...overrides };
	return el;
};

describe( 'edac-highlight-issue-view', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'registers the custom element', () => {
		expect( customElements.get( 'edac-highlight-issue-view' ) ).toBeDefined();
	} );

	test( 'renders title, summary and WCAG reference for the given issue', () => {
		const el = mountView();

		const title = el.shadowRoot.querySelector( '.edac-highlight-panel-description-title-text' );
		expect( title.textContent ).toBe( 'Missing alt text' );

		const summary = el.shadowRoot.querySelector( '.edac-highlight-panel-description-summary' );
		expect( summary.textContent ).toBe( 'Image is missing alt text.' );

		const wcagLink = el.shadowRoot.querySelector( '.edac-highlight-panel-description-wcag a' );
		expect( wcagLink.getAttribute( 'href' ) ).toBe( 'https://example.com/wcag' );
	} );

	test( 'renders a severity badge for the mapped severity level', () => {
		const el = mountView( { severity: 2 } );
		const badge = el.shadowRoot.querySelector( '.edac-badge--severity-high' );
		expect( badge ).not.toBeNull();
	} );

	test( 'shows the affected-code snippet, normalized to outerHTML', () => {
		const el = mountView( { object: '<button class="x">Click</button>' } );
		const code = el.shadowRoot.querySelector( '.edac-highlight-panel-description-code code' );
		expect( code.innerText ).toContain( '<button class="x">Click</button>' );
	} );

	test( 'code toggle flips aria-expanded and visibility, and persists across issue changes', () => {
		const el = mountView();
		const codeButton = el.shadowRoot.querySelector( '.edac-highlight-panel-description-code-button' );
		const codeContainer = el.shadowRoot.querySelector( '.edac-highlight-panel-description-code' );

		expect( codeContainer.style.display ).toBe( 'none' );
		codeButton.click();
		expect( codeContainer.style.display ).toBe( 'block' );
		expect( codeButton.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		// Switching to a different issue should keep the expanded state (same
		// behavior as the original controller-level codeExpanded field).
		el.issue = { ...baseIssue, rule_title: 'Second issue' };
		const codeContainerAfter = el.shadowRoot.querySelector( '.edac-highlight-panel-description-code' );
		expect( codeContainerAfter.style.display ).toBe( 'block' );
	} );

	test( 'Pro explanation accordion only renders when isPro and explanation content exist', () => {
		const el = mountView( { why_it_matters: 'Because reasons.' } );
		expect( el.shadowRoot.querySelector( '.edac-highlight-panel-description-explanation-toggle' ) ).toBeNull();

		el.isPro = true;
		expect( el.shadowRoot.querySelector( '.edac-highlight-panel-description-explanation-toggle' ) ).not.toBeNull();
		expect( el.shadowRoot.querySelector( '.edac-highlight-panel-description-how-to-fix-content' ).textContent ).toBe( 'Because reasons.' );
	} );

	test( 'fix-settings button only renders when a fix exists and the user can fix', () => {
		const el = mountView();
		expect( el.shadowRoot.querySelector( '.edac-fix-settings--button--open' ) ).toBeNull();

		el.fix = { fields: '<input type="text" />', group1: { group_name: 'Alt Text' } };
		expect( el.shadowRoot.querySelector( '.edac-fix-settings--button--open' ) ).toBeNull();

		el.userCanFix = true;
		expect( el.shadowRoot.querySelector( '.edac-fix-settings--button--open' ) ).not.toBeNull();
	} );

	test( 'clicking "Fix Issue" detaches the fix-settings container and dispatches edac-open-fix-settings with it', () => {
		const el = mountView();
		el.fix = { fields: '<input type="text" />', group1: { group_name: 'Alt Text' } };
		el.userCanFix = true;

		const handler = jest.fn();
		document.addEventListener( 'edac-open-fix-settings', handler );

		const openButton = el.shadowRoot.querySelector( '.edac-fix-settings--button--open' );
		openButton.click();

		expect( handler ).toHaveBeenCalledTimes( 1 );
		const { container, openingElement } = handler.mock.calls[ 0 ][ 0 ].detail;
		expect( container.classList.contains( 'edac-fix-settings' ) ).toBe( true );
		expect( container.isConnected ).toBe( false );
		expect( openingElement ).toBe( openButton );

		// A placeholder should be left behind so the container can be restored later.
		expect( el.shadowRoot.querySelector( '.edac-fix-settings--origin-placeholder' ) ).not.toBeNull();

		document.removeEventListener( 'edac-open-fix-settings', handler );
	} );

	test( 'a status notice renders above the title when status is set', () => {
		const el = mountView();
		el.status = 'The element is not visible. Try disabling styles.';

		const notice = el.shadowRoot.querySelector( '.edac-highlight-panel-description-notice' );
		expect( notice.textContent ).toBe( 'The element is not visible. Try disabling styles.' );
	} );
} );
