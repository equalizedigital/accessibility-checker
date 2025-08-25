import { __ } from '@wordpress/i18n';
export const inlineSettingsProUpsell = () => {
	// find elements with 'edac-fix--upsell' class
	const upsellElements = document.querySelectorAll( '.edac-fix--upsell, .edac-setting--upsell' );

	// loop through each element
	upsellElements.forEach( ( element ) => {
		// create a link with the upsell url
		const upsellLink = document.createElement( 'a' );
		const url = window.edac_script_vars?.proUrl || 'https://equalizedigital.com/accessibility-checker/pricing/';
		upsellLink.href = url.replace( '__name__', element.querySelector( 'input' )?.getAttribute( 'name' ) );
		upsellLink.target = '_blank';
		upsellLink.rel = 'noopener noreferrer';
		upsellLink.textContent = __( 'Get Pro' );
		upsellLink.classList.add( 'edac-setting--upsell-link' );
		upsellLink.setAttribute( 'aria-label', __( 'Get Pro to unlock this feature, opens in a new window.', 'accessibility-checker' ) );

		const tableHead = element.closest( 'tr' )?.querySelector( 'th' );
		if ( ! tableHead ) {
			return;
		}
		tableHead.appendChild( document.createTextNode( ' ' ) );
		tableHead.appendChild( upsellLink );
	} );
};
