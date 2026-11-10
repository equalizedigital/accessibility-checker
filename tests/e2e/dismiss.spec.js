/**
 * Dismissing an issue from the meta box Details tab — the ignore panel, its persistence,
 * and undoing it.
 */
const { test, expect } = require( '@playwright/test' );
const {
	sel,
	FIXTURE_CONTENT,
	createPage,
	deletePage,
	openBlockEditor,
	expandMetaBoxes,
} = require( './helpers' );

const SUBMIT = '.edac-details-rule-records-record-ignore-submit';

/**
 * Open the Details tab and expand the first record of a rule.
 *
 * The panel re-renders (and falls back to the Summary tab) when scan data arrives, so
 * the whole sequence is retried until the record's ignore button is visible.
 *
 * @param {Object} page      Playwright page.
 * @param {string} ruleTitle Displayed rule title.
 * @return {Object} Locator for the rule's first record ignore-action button.
 */
async function openFirstRecord( page, ruleTitle ) {
	const title = page.locator( sel.detailsRuleTitle, { hasText: ruleTitle } ).first();
	const action = page.locator( '.edac-details-rule', { has: title } ).locator( '.edac-details-rule-records-record-actions-ignore' ).first();

	await expect( async () => {
		await page.locator( sel.detailsTab ).click();
		if ( ! ( await action.isVisible() ) ) {
			await title.click();
		}
		await expect( action ).toBeVisible( { timeout: 2_000 } );
	} ).toPass( { timeout: 120_000 } );

	return action;
}

test.describe( 'dismissing an issue', () => {
	test.use( { viewport: { width: 1440, height: 1100 } } );

	let fixture;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		fixture = await createPage( page, { title: 'Dismiss fixture', content: FIXTURE_CONTENT } );
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await deletePage( page, fixture.id );
		await page.close();
	} );

	test( 'dismisses with a reason, persists across reload, and can be undone', async ( { page } ) => {
		await openBlockEditor( page, fixture.id );
		await expandMetaBoxes( page );

		await ( await openFirstRecord( page, 'Empty Button' ) ).click();

		const panel = page.locator( '.edac-details-rule-records-record-ignore:visible' ).first();
		await panel.getByLabel( /false positive/i ).check();
		await panel.locator( '.edac-details-rule-records-record-ignore-comment' ).fill( 'e2e dismissal' );

		const dismissed = page.waitForResponse( ( res ) => /dismiss-issue\/\d+/.test( res.url() ) && res.request().method() === 'POST' );
		await panel.locator( SUBMIT ).click();
		expect( ( await dismissed ).status() ).toBe( 200 );

		// Reload: the record comes back already dismissed, with the saved comment.
		await openBlockEditor( page, fixture.id );
		await expandMetaBoxes( page );
		await ( await openFirstRecord( page, 'Empty Button' ) ).click();

		// The panel is server-rendered from the saved dismissal, so the comment
		// alone only proves one field round-tripped: the reason it was dismissed
		// with, and the action the button now offers, come from the same row.
		const reloaded = page.locator( '.edac-details-rule-records-record-ignore:visible' ).first();
		await expect( reloaded.locator( '.edac-details-rule-records-record-ignore-comment' ) ).toHaveValue( 'e2e dismissal' );
		await expect( reloaded.getByLabel( /false positive/i ) ).toBeChecked();
		await expect( reloaded.locator( SUBMIT ) ).toHaveAttribute( 'data-action', 'disable' );

		// Undo, so the fixture goes back to its original state.
		const undone = page.waitForResponse( ( res ) => /dismiss-issue\/\d+/.test( res.url() ) && res.request().method() === 'POST' );
		await reloaded.locator( SUBMIT ).click();
		expect( ( await undone ).status() ).toBe( 200 );

		// Reload once more: the undo persisted, so the issue is dismissible again.
		await openBlockEditor( page, fixture.id );
		await expandMetaBoxes( page );
		await ( await openFirstRecord( page, 'Empty Button' ) ).click();

		const reopened = page.locator( '.edac-details-rule-records-record-ignore:visible' ).first();
		await expect( reopened.locator( SUBMIT ) ).toHaveAttribute( 'data-action', 'enable' );
		await expect( reopened.locator( '.edac-details-rule-records-record-ignore-comment' ) ).toHaveValue( '' );
	} );
} );
