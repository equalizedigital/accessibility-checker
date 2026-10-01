/**
 * Tests for the frontend highlighter dismiss helpers.
 */

import {
	applyDismissToIssues,
	buildDismissMarkup,
	escapeHtml,
	requestDismiss,
} from '../../../src/frontendHighlighterApp/dismissIssue';

const reasons = {
	accessible: { label: 'Confirmed accessible', description: 'Reviewed and verified.' },
	false_positive: { label: 'False positive', description: 'Does not apply.' },
};

const render = ( html ) => {
	const wrapper = document.createElement( 'div' );
	wrapper.innerHTML = html;
	return wrapper;
};

describe( 'escapeHtml', () => {
	test( 'escapes markup characters', () => {
		expect( escapeHtml( '<img src=x onerror="a">&\'' ) ).toBe( '&lt;img src=x onerror=&quot;a&quot;&gt;&amp;&#039;' );
	} );

	test( 'handles null and undefined', () => {
		expect( escapeHtml( null ) ).toBe( '' );
		expect( escapeHtml( undefined ) ).toBe( '' );
	} );
} );

describe( 'buildDismissMarkup', () => {
	describe( 'for an open issue', () => {
		const issue = { id: '5', ignored: '0' };

		test( 'renders nothing when the user cannot dismiss', () => {
			expect( buildDismissMarkup( { issue, reasons, canDismiss: false } ) ).toBe( '' );
		} );

		test( 'renders a collapsed form with a radio per reason, first one checked', () => {
			const el = render( buildDismissMarkup( { issue, reasons, canDismiss: true } ) );
			const toggle = el.querySelector( '.edac-highlight-panel-description-dismiss-toggle' );
			const form = el.querySelector( 'form' );
			const radios = el.querySelectorAll( 'input[type="radio"]' );

			expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
			expect( toggle.getAttribute( 'aria-controls' ) ).toBe( form.id );
			expect( form.hidden ).toBe( true );
			expect( radios ).toHaveLength( 2 );
			expect( radios[ 0 ].value ).toBe( 'accessible' );
			expect( radios[ 0 ].checked ).toBe( true );
			expect( el.querySelector( `label[for="${ radios[ 1 ].id }"]` ).textContent ).toBe( 'False positive' );
			expect( el.querySelector( `label[for="edac-highlight-dismiss-comment"]` ) ).not.toBeNull();
		} );

		test( 'pre-selects the previous reason and comment after a reopen', () => {
			const el = render( buildDismissMarkup( {
				issue: { ...issue, ignre_reason: 'false_positive', ignre_comment: 'Decorative' },
				reasons,
				canDismiss: true,
			} ) );

			expect( el.querySelector( 'input[value="false_positive"]' ).checked ).toBe( true );
			expect( el.querySelector( 'textarea' ).value ).toBe( 'Decorative' );
		} );

		test( 'only shows the global button when allowed', () => {
			const withoutGlobal = render( buildDismissMarkup( { issue, reasons, canDismiss: true } ) );
			const withGlobal = render( buildDismissMarkup( { issue, reasons, canDismiss: true, canDismissGlobal: true } ) );

			expect( withoutGlobal.querySelector( '[data-scope="global"]' ) ).toBeNull();
			expect( withGlobal.querySelector( '[data-scope="global"]' ) ).not.toBeNull();
		} );

		test( 'a global-only user gets the form with only the global action', () => {
			const el = render( buildDismissMarkup( { issue, reasons, canDismiss: false, canDismissGlobal: true } ) );

			expect( el.querySelector( 'form' ) ).not.toBeNull();
			expect( el.querySelectorAll( 'input[type="radio"]' ) ).toHaveLength( 2 );
			expect( el.querySelector( '[data-scope="single"]' ) ).toBeNull();
			expect( el.querySelector( '[data-scope="global"]' ) ).not.toBeNull();
		} );

		test( 'escapes reason labels from the filterable reasons list', () => {
			const el = render( buildDismissMarkup( {
				issue,
				reasons: { custom: { label: '<b>Bold</b>', description: '' } },
				canDismiss: true,
			} ) );

			expect( el.querySelector( 'b' ) ).toBeNull();
			expect( el.querySelector( 'label' ).textContent ).toBe( '<b>Bold</b>' );
		} );
	} );

	describe( 'for a dismissed issue', () => {
		const dismissed = {
			id: '5',
			ignored: '1',
			ignre_reason: 'false_positive',
			ignre_comment: '<script>alert(1)</script>',
			ignre_user_name: 'admin',
			ignre_date: 'September 30, 2026 10:00 am',
			ignre_global: 0,
		};

		test( 'shows the reason, who, when and the comment as text', () => {
			const el = render( buildDismissMarkup( { issue: dismissed, reasons, canDismiss: true } ) );

			expect( el.querySelector( '[role="heading"]' ).textContent ).toBe( 'Issue Dismissed — False positive' );
			expect( el.querySelector( 'dl' ).textContent ).toContain( 'admin' );
			expect( el.querySelector( 'dl' ).textContent ).toContain( 'September 30, 2026' );
			expect( el.querySelector( 'script' ) ).toBeNull();
			expect( el.querySelector( '.edac-highlight-dismissed-comment-body' ).textContent ).toBe( '<script>alert(1)</script>' );
		} );

		test( 'shows a reopen button only when the user can dismiss', () => {
			expect( render( buildDismissMarkup( { issue: dismissed, reasons, canDismiss: true } ) )
				.querySelector( '.edac-highlight-dismiss-reopen' ).textContent ).toBe( 'Reopen Issue' );
			expect( render( buildDismissMarkup( { issue: dismissed, reasons, canDismiss: false } ) )
				.querySelector( '.edac-highlight-dismiss-reopen' ) ).toBeNull();
		} );

		test( 'a global dismissal needs the global capability to be removed', () => {
			const global = { ...dismissed, ignre_global: '1' };

			const withoutCap = render( buildDismissMarkup( { issue: global, reasons, canDismiss: true, canDismissGlobal: false } ) );
			const withCap = render( buildDismissMarkup( { issue: global, reasons, canDismiss: true, canDismissGlobal: true } ) );

			expect( withoutCap.querySelector( '.edac-highlight-dismiss-reopen' ) ).toBeNull();
			expect( withCap.querySelector( '.edac-highlight-dismiss-reopen' ).textContent ).toBe( 'Remove Global Dismissal' );
			expect( withCap.querySelector( 'dl' ).textContent ).toContain( 'All pages' );
		} );
	} );
} );

describe( 'requestDismiss', () => {
	afterEach( () => {
		delete window.fetch;
	} );

	test( 'posts a dismiss to the REST endpoint with the nonce', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: () => Promise.resolve( { success: true, ignre_user_name: 'admin' } ),
		} );

		const body = await requestDismiss( {
			restUrl: 'https://example.com/wp-json/accessibility-checker/v1',
			restNonce: 'abc',
			issueId: '12',
			dismiss: true,
			reason: 'accessible',
			comment: 'ok',
			global: false,
		} );

		expect( body.ignre_user_name ).toBe( 'admin' );
		const [ url, options ] = window.fetch.mock.calls[ 0 ];
		expect( url ).toBe( 'https://example.com/wp-json/accessibility-checker/v1/dismiss-issue/12' );
		expect( options.headers[ 'X-WP-Nonce' ] ).toBe( 'abc' );
		expect( JSON.parse( options.body ) ).toEqual( {
			action: 'dismiss',
			reason: 'accessible',
			comment: 'ok',
			ignore_global: 0,
			largeBatch: false,
		} );
	} );

	test( 'a reopen drops the reason and comment', async () => {
		window.fetch = jest.fn().mockResolvedValue( { ok: true, json: () => Promise.resolve( { success: true } ) } );

		await requestDismiss( { restUrl: '/r', restNonce: 'n', issueId: '1', dismiss: false, reason: 'accessible', comment: 'x', global: true } );

		expect( JSON.parse( window.fetch.mock.calls[ 0 ][ 1 ].body ) ).toEqual( {
			action: 'undismiss',
			reason: '',
			comment: '',
			ignore_global: 0,
			largeBatch: true,
		} );
	} );

	test( 'rejects with the server message on failure', async () => {
		window.fetch = jest.fn().mockResolvedValue( {
			ok: false,
			json: () => Promise.resolve( { message: 'Sorry, you are not allowed to dismiss issues.' } ),
		} );

		await expect( requestDismiss( { restUrl: '/r', restNonce: 'n', issueId: '1', dismiss: true } ) )
			.rejects.toThrow( 'Sorry, you are not allowed to dismiss issues.' );
	} );
} );

describe( 'applyDismissToIssues', () => {
	const makeIssues = () => [
		{ id: '1', slug: 'img_alt_missing', object: '<img src="a.png">', rule_type: 'error', base_rule_type: 'error', ignored: '0' },
		{ id: '2', slug: 'img_alt_missing', object: '<img src="a.png">', rule_type: 'error', base_rule_type: 'error', ignored: '0' },
		{ id: '3', slug: 'img_alt_missing', object: '<img src="b.png">', rule_type: 'error', base_rule_type: 'error', ignored: '0' },
	];

	test( 'a single dismiss only changes the target issue', () => {
		const issues = makeIssues();
		const updated = applyDismissToIssues( issues, issues[ 0 ], { dismiss: true, global: false, reason: 'accessible', comment: 'c' } );

		expect( updated ).toHaveLength( 1 );
		expect( updated[ 0 ].previousRuleType ).toBe( 'error' );
		expect( issues[ 0 ] ).toMatchObject( { ignored: '1', rule_type: 'ignored', ignre_reason: 'accessible', ignre_comment: 'c', ignre_global: 0 } );
		expect( issues[ 1 ].ignored ).toBe( '0' );
	} );

	test( 'a global dismiss changes every issue with the same rule and markup', () => {
		const issues = makeIssues();
		applyDismissToIssues( issues, issues[ 0 ], { dismiss: true, global: true, reason: 'accessible' } );

		expect( issues.map( ( i ) => i.ignored ) ).toEqual( [ '1', '1', '0' ] );
		expect( issues[ 1 ].ignre_global ).toBe( 1 );
	} );

	test( 'a reopen restores the original rule type and keeps the reason for re-dismissing', () => {
		const issues = makeIssues();
		applyDismissToIssues( issues, issues[ 2 ], { dismiss: true, global: false, reason: 'false_positive', comment: 'c' } );
		applyDismissToIssues( issues, issues[ 2 ], { dismiss: false, global: false } );

		expect( issues[ 2 ] ).toMatchObject( { ignored: '0', rule_type: 'error', ignre_reason: 'false_positive', ignre_comment: 'c', ignre_user_name: '' } );
	} );
} );
