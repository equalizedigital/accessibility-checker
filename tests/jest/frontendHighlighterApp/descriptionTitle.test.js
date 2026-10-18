/**
 * Tests for buildDescriptionTitle().
 */

import { buildDescriptionTitle } from '../../../src/frontendHighlighterApp/descriptionTitle';

describe( 'buildDescriptionTitle', () => {
	describe( 'with a status notice', () => {
		const notice = 'The element was not found on the page.';

		test( 'reports the notice so the container can stack below it', () => {
			const { hasNotice } = buildDescriptionTitle( {
				title: 'Link to PDF',
				notice,
			} );

			expect( hasNotice ).toBe( true );
		} );

		test( 'renders the notice before the issue title', () => {
			const { html } = buildDescriptionTitle( {
				title: 'Link to PDF',
				notice,
			} );

			const noticeIndex = html.indexOf( 'edac-highlight-panel-description-notice' );
			const titleIndex = html.indexOf( 'edac-highlight-panel-description-title-text' );

			expect( noticeIndex ).toBeGreaterThan( -1 );
			expect( titleIndex ).toBeGreaterThan( noticeIndex );
		} );

		test( 'keeps the title as a level 3 heading after the notice', () => {
			const { html } = buildDescriptionTitle( {
				title: 'Link to PDF',
				notice,
			} );

			expect( html ).toContain( 'role="heading" aria-level="3"' );
			expect( html ).toContain( 'Link to PDF' );
			expect( html ).toContain( notice );
		} );

		test( 'appends the type badge after the title', () => {
			const typeBadgeHtml = '<span class="edac-badge edac-badge--error">Error</span>';

			const { html } = buildDescriptionTitle( {
				title: 'Link to PDF',
				notice,
				typeBadgeHtml,
			} );

			expect( html ).toContain( typeBadgeHtml );
			expect( html.indexOf( 'edac-badge' ) ).toBeGreaterThan( html.indexOf( 'Link to PDF' ) );
		} );
	} );

	describe( 'without a status notice', () => {
		test( 'reports no notice', () => {
			const { hasNotice } = buildDescriptionTitle( { title: 'Link to PDF' } );

			expect( hasNotice ).toBe( false );
		} );

		test( 'renders only the title when the notice is an empty string', () => {
			const { html } = buildDescriptionTitle( { title: 'Link to PDF', notice: '' } );

			expect( html ).not.toContain( 'edac-highlight-panel-description-notice' );
			expect( html ).toContain( 'Link to PDF' );
		} );

		test( 'renders only the title when the notice is null', () => {
			const { html } = buildDescriptionTitle( { title: 'Link to PDF', notice: null } );

			expect( html ).not.toContain( 'edac-highlight-panel-description-notice' );
		} );
	} );
} );
