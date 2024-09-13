import { __ } from '@wordpress/i18n';

export const inlineFixesProUpsell = () => {
	// find elements with 'edac-fix--upsell' class
	const upsellElements = document.querySelectorAll( '.edac-fix--upsell' );

	// loop through each element
	upsellElements.forEach( ( element ) => {
		// create a link with the upsell url
		const upsellLink = document.createElement( 'a' );
		const url = window.edac_script_vars?.fixesProUrl || 'https://equalizedigital.com/accessibility-checker/pricing/';
		upsellLink.href = url.replace( '__fix__', element.querySelector( 'input' )?.getAttribute( 'name' ) );
		upsellLink.target = '_blank';
		upsellLink.rel = 'noopener noreferrer';
		upsellLink.textContent = __( 'Get Pro' );
		upsellLink.classList.add( 'edac-fix--upsell-link' );

		const tableHead = element.closest( 'tr' )?.querySelector( 'th' );
		if ( ! tableHead ) {
			return;
		}
		tableHead.appendChild( document.createTextNode( ' ' ) );
		tableHead.appendChild( upsellLink );
	} );
};
