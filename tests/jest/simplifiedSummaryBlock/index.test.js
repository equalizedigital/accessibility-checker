/**
 * Tests for the simplified summary block registration and edit component.
 *
 * @wordpress/blocks and @wordpress/block-editor are webpack externals and not
 * installed as packages, so they are mocked here.
 */
import { renderReact } from '../helpers/renderReact';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ), { virtual: true } );

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn( ( props ) => props ),
} ), { virtual: true } );

import { registerBlockType } from '@wordpress/blocks';
import Edit from '../../../src/simplifiedSummaryBlock/edit';
import '../../../src/simplifiedSummaryBlock/index';

describe( 'simplified summary block registration', () => {
	test( 'registers the edac/simplified-summary block', () => {
		expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		expect( registerBlockType ).toHaveBeenCalledWith(
			'edac/simplified-summary',
			expect.objectContaining( {
				edit: Edit,
				save: expect.any( Function ),
			} ),
		);
	} );

	test( 'save returns null so the block is dynamic', () => {
		const { save } = registerBlockType.mock.calls[ 0 ][ 1 ];
		expect( save() ).toBeNull();
	} );
} );

describe( 'Edit', () => {
	test( 'renders the heading and placeholder text', () => {
		const { container, unmount } = renderReact( <Edit /> );
		expect( container.querySelector( 'h2' ).textContent ).toBe( 'Simplified Summary' );
		expect(
			container.querySelector( '.edac-simplified-summary-block__placeholder' ).textContent,
		).toContain( 'will display here' );
		unmount();
	} );

	test( 'applies the frontend wrapper class via block props', () => {
		const { container, unmount } = renderReact( <Edit /> );
		expect( container.querySelector( '.edac-simplified-summary' ) ).not.toBeNull();
		unmount();
	} );
} );
