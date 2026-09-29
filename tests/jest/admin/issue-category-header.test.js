import axe from 'axe-core';

afterEach( () => {
	axe.teardown();
	document.body.innerHTML = '';
} );

test( 'the issue category button name includes the category, count, and severity', () => {
	// Mirrors the header in admin/class-ajax.php; tests DOM naming, not PHP rendering.
	document.body.innerHTML = `
		<div class="edac-details-rule-title">
			<h3 id="edac-details-rule-heading-empty_link">
				Empty Link
				<span class="edac-details-rule-count">
					<span aria-hidden="true">(</span>3<span aria-hidden="true">)</span>
					<span class="screen-reader-text"> total</span>
				</span>
				<span class="edac-badge edac-badge--severity-critical">
					<span class="edac-badge__label">Critical</span>
				</span>
			</h3>
			<button class="edac-details-rule-title-arrow" aria-expanded="false"
				aria-controls="edac-details-rule-records-empty_link"
				aria-labelledby="edac-details-rule-heading-empty_link">
				<i class="dashicons dashicons-arrow-down-alt2"></i>
			</button>
		</div>
		<div id="edac-details-rule-records-empty_link" hidden></div>
	`;

	axe.setup( document );
	const button = document.querySelector( 'button' );
	expect( axe.commons.text.accessibleText( button ) ).toBe( 'Empty Link 3 total Critical' );
} );
