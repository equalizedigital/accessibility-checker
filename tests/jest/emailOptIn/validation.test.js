/**
 * Email opt-in validation announcements. No subscription requests are sent.
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
	] )( 'announces the validation error for %j without subscribing', ( value, message ) => {
		const announcement = form.querySelector( '[role="alert"]' );
		expect( announcement ).not.toBeNull();
		expect( announcement.textContent ).toBe( '' );
		expect( announcement.getAttribute( 'aria-atomic' ) ).toBe( 'true' );
		email.value = value;
		expect( submit() ).toBe( false );
		jest.runOnlyPendingTimers();
		expect( announcement.textContent ).toBe( message );
		expect( form.querySelector( '._error' ).textContent ).toBe( message );
		expect( fetchMock ).not.toHaveBeenCalled();
		expect( document.querySelector( 'script[src]' ) ).toBeNull();
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
