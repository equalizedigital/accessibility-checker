/**
 * Email opt-in validation and failure messages. No subscription requests are sent.
 */

jest.mock( '../../../src/emailOptIn/modal', () => ( { initOptInModal: jest.fn() } ) );

describe( 'email opt-in validation', () => {
	let form;
	let email;
	let fetchMock;

	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML = `
			<form id="_form_1_" novalidate>
				<div class="_field-wrapper">
					<label for="email">Email</label>
					<input id="email" name="email" type="text" required>
				</div>
				<button id="_form_1_submit" type="submit">Subscribe</button>
			</form>
		`;
		window.edac_email_opt_in_form = { showModal: false };
		fetchMock = jest.fn();
		window.fetch = fetchMock;
		jest.isolateModules( () => require( '../../../src/emailOptIn/index' ) );
		form = document.getElementById( '_form_1_' );
		email = document.getElementById( 'email' );
	} );

	afterEach( () => {
		jest.clearAllTimers();
		jest.useRealTimers();
		delete window.fetch;
	} );

	const submit = () => form.dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) );

	test.each( [
		[ '', 'This field is required.' ],
		[ 'invalid', 'Enter a valid email address.' ],
	] )( 'focuses the email field and describes the error for %j without subscribing', ( value, message ) => {
		email.value = value;
		expect( submit() ).toBe( false );

		// VoiceOver reads a focused invalid field with its error, so focus moves there.
		expect( document.activeElement ).toBe( email );
		expect( email.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		const describedBy = document.getElementById( email.getAttribute( 'aria-describedby' ) );
		expect( describedBy.textContent ).toBe( message );
		expect( form.querySelector( '._error' ).textContent ).toBe( message );

		// Focus supplies the announcement, so the live region is not also filled.
		jest.runOnlyPendingTimers();
		expect( form.querySelector( '[role="alert"]' ).textContent ).toBe( '' );
		expect( fetchMock ).not.toHaveBeenCalled();
		expect( document.querySelector( 'script[src]' ) ).toBeNull();
	} );

	test( 'keeps the live region present, empty and atomic before any error', () => {
		const announcement = form.querySelector( '[role="alert"]' );
		expect( announcement ).not.toBeNull();
		expect( announcement.textContent ).toBe( '' );
		expect( announcement.getAttribute( 'aria-atomic' ) ).toBe( 'true' );
	} );

	test( 'removes aria-invalid and the description link when the email is corrected', () => {
		email.value = 'invalid';
		submit();
		expect( email.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		email.value = 'reader@example.test';
		email.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		expect( email.hasAttribute( 'aria-invalid' ) ).toBe( false );
		expect( email.hasAttribute( 'aria-describedby' ) ).toBe( false );
		expect( form.querySelector( '._error' ) ).toBeNull();
	} );

	test( 'keeps focus on the field for a repeated error', () => {
		email.value = 'invalid';
		submit();
		submit();
		expect( document.activeElement ).toBe( email );
		expect( email.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( form.querySelectorAll( '._error' ) ).toHaveLength( 1 );
	} );

	describe( 'when focus cannot be moved to the invalid field', () => {
		beforeEach( () => {
			jest.spyOn( email, 'focus' ).mockImplementation( () => {} );
		} );

		test( 'falls back to the live region', () => {
			email.value = 'invalid';
			submit();
			const announcement = form.querySelector( '[role="alert"]' );
			jest.runOnlyPendingTimers();
			expect( announcement.textContent ).toBe( 'Enter a valid email address.' );
		} );

		test( 'clears and announces the same error on another submission', () => {
			email.value = 'invalid';
			submit();
			jest.runOnlyPendingTimers();
			const announcement = form.querySelector( '[role="alert"]' );
			expect( announcement.textContent ).toBe( 'Enter a valid email address.' );
			submit();
			expect( announcement.textContent ).toBe( '' );
			jest.runOnlyPendingTimers();
			expect( announcement.textContent ).toBe( 'Enter a valid email address.' );
			expect( form.querySelectorAll( '[role="alert"]' ) ).toHaveLength( 1 );
		} );

		test( 'cancels a pending announcement when the email is corrected', () => {
			email.value = 'invalid';
			submit();
			email.value = 'reader@example.test';
			email.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			jest.runOnlyPendingTimers();
			expect( form.querySelector( '[role="alert"]' ).textContent ).toBe( '' );
			expect( form.querySelector( '._error' ) ).toBeNull();
			expect( fetchMock ).not.toHaveBeenCalled();
		} );

		test( 'clears an announced error after correcting the email', () => {
			email.value = 'invalid';
			submit();
			jest.runOnlyPendingTimers();
			email.value = 'reader@example.test';
			email.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			expect( form.querySelector( '[role="alert"]' ).textContent ).toBe( '' );
		} );
	} );

	describe( 'when the whole submission fails', () => {
		test( 'focuses the failure message and re-enables the button', () => {
			const button = document.getElementById( '_form_1_submit' );
			button.disabled = true;
			button.classList.add( 'processing' );

			window._show_error( '1', 'Sorry, your submission failed. Please try again.' );

			const error = form.querySelector( '._form_error' );
			expect( error.textContent ).toBe( 'Sorry, your submission failed. Please try again.' );
			// A plain inserted div is not announced, so focus is what makes it read out.
			expect( error.getAttribute( 'tabindex' ) ).toBe( '-1' );
			expect( document.activeElement ).toBe( error );
			expect( button.disabled ).toBe( false );
			expect( button.classList.contains( 'processing' ) ).toBe( false );
		} );

		test( 'a failed script load shows and focuses the failure message', () => {
			window._load_script( 'https://example.test/proc.php?x=1', null, true );
			const script = document.querySelector( 'script[src]' );
			script.onerror();

			const error = form.querySelector( '._form_error' );
			expect( error.textContent ).toBe( 'Sorry, your submission failed. Please try again.' );
			expect( document.activeElement ).toBe( error );
		} );

		test( 'replaces an earlier failure message instead of stacking them', () => {
			window._show_error( '1', 'First failure' );
			window._show_error( '1', 'Second failure' );
			expect( form.querySelectorAll( '._form_error' ) ).toHaveLength( 1 );
			expect( form.querySelector( '._form_error' ).textContent ).toBe( 'Second failure' );
		} );
	} );
} );
