/**
 * Hi-fi tests for RichTextarea shared component.
 *
 * RichTextarea is pure React with no @wordpress/* dependencies —
 * zero mocking required.
 */
import { act } from 'react';
import RichTextarea from '../../../src/issueModal/components/RichTextarea';
import { renderReact } from '../helpers/renderReact';

describe( 'RichTextarea', () => {
	test( 'renders a textarea with the supplied value', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="hello world" onChange={ jest.fn() } />,
		);

		const textarea = container.querySelector( 'textarea' );
		expect( textarea ).not.toBeNull();
		expect( textarea.value ).toBe( 'hello world' );

		unmount();
	} );

	test( 'renders an empty string when value is undefined', () => {
		const { container, unmount } = renderReact(
			<RichTextarea onChange={ jest.fn() } />,
		);

		const textarea = container.querySelector( 'textarea' );
		expect( textarea.value ).toBe( '' );

		unmount();
	} );

	test( 'calls onChange with the new value when user types', () => {
		const onChange = jest.fn();
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ onChange } />,
		);

		const textarea = container.querySelector( 'textarea' );

		// Use the native HTMLTextAreaElement value setter so that React's
		// synthetic onChange fires with the correct e.target.value.
		const nativeSetter = Object.getOwnPropertyDescriptor(
			window.HTMLTextAreaElement.prototype,
			'value',
		).set;

		act( () => {
			nativeSetter.call( textarea, 'typed text' );
			textarea.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );

		expect( onChange ).toHaveBeenCalledWith( 'typed text' );

		unmount();
	} );

	test( 'renders label element when label prop is provided', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } label="Notes" labelId="notes-label" />,
		);

		const label = container.querySelector( 'label' );
		expect( label ).not.toBeNull();
		expect( label.textContent ).toBe( 'Notes' );
		expect( label.id ).toBe( 'notes-label' );

		unmount();
	} );

	test( 'omits label element when no label prop is given', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } />,
		);

		expect( container.querySelector( 'label' ) ).toBeNull();

		unmount();
	} );

	test( 'sets aria-labelledby on textarea when label and labelId are both provided', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } label="Notes" labelId="notes-label" />,
		);

		const textarea = container.querySelector( 'textarea' );
		expect( textarea.getAttribute( 'aria-labelledby' ) ).toBe( 'notes-label' );

		unmount();
	} );

	test( 'does not set aria-labelledby when labelId is missing', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } label="Notes" />,
		);

		const textarea = container.querySelector( 'textarea' );
		expect( textarea.getAttribute( 'aria-labelledby' ) ).toBeNull();

		unmount();
	} );

	test( 'renders help text element when help prop is provided', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } help="Enter details here." helpId="notes-help" />,
		);

		const help = container.querySelector( 'p' );
		expect( help ).not.toBeNull();
		expect( help.textContent ).toBe( 'Enter details here.' );
		expect( help.id ).toBe( 'notes-help' );

		unmount();
	} );

	test( 'sets aria-describedby on textarea when help and helpId are both provided', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } help="Enter details here." helpId="notes-help" />,
		);

		const textarea = container.querySelector( 'textarea' );
		expect( textarea.getAttribute( 'aria-describedby' ) ).toBe( 'notes-help' );

		unmount();
	} );

	test( 'disables the textarea when disabled prop is true', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } disabled={ true } />,
		);

		expect( container.querySelector( 'textarea' ).disabled ).toBe( true );

		unmount();
	} );

	test( 'respects the rows prop', () => {
		const { container, unmount } = renderReact(
			<RichTextarea value="" onChange={ jest.fn() } rows={ 6 } />,
		);

		expect( container.querySelector( 'textarea' ).rows ).toBe( 6 );

		unmount();
	} );
} );

