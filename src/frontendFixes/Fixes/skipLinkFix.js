import { __ } from '@wordpress/i18n';

const findFirstLinkOutsideContainer = ( containerSelector ) => {
	const links = document.querySelectorAll( 'body a:not(.ab-item)' );
	for ( const link of links ) {
		if ( ! link.closest( containerSelector ) ) {
			return link;
		}
	}
	return null;
};

const tryDetectSkipLink = () => {
	// get the very first link on the page.
	const firstLink = findFirstLinkOutsideContainer( '#wpadminbar' );

	// does the first link point to an anchor on the page?
	if ( firstLink && firstLink.href && firstLink.href.indexOf( '#' ) !== -1 ) {
		// if it does, then does that anchor id exist on the page?
		const anchorTarget = firstLink.href.split( '#' )[ 1 ];
		const anchor = document.getElementById( anchorTarget );
		if ( anchor ) {
			// if it does, then we don't need to add a skip link.
			return true;
		}
		return false;
	}
	return false;
};

function handleSkipLinkFocusShift( foundMainTarget, skipLink ) {
	const skipLinkTargetEl = document.querySelector( foundMainTarget );
	if ( ! skipLinkTargetEl ) {
		return;
	}

	// Make sure the target is focusable so we can move focus to it. Needed for some browsers.
	if ( skipLinkTargetEl.tabIndex === -1 ) {
		skipLinkTargetEl.setAttribute( 'tabindex', '0' );
	}

	// Bind to the click (also cover keyboard enter) event to move focus to the target.
	skipLink.querySelector( '.edac-skip-link--content' )?.addEventListener( 'click', () => {
		try {
			// Make the history reflect the move.
			history.pushState( {}, '', foundMainTarget );
		} catch ( e ) {
			// eslint-disable-next-line
			console.error( __( 'EDAC: Error updating history for skip link.', 'accessibility-checker' ) );
		}
		// Focus on the target element.
		skipLinkTargetEl.focus();
	}, { once: true } );
}

const SkipLinkFixInit = () => {
	const skipLinkTemplate = document.getElementById( 'skip-link-template' );
	if ( ! skipLinkTemplate ) {
		return;
	}

	if ( ! window.edac_frontend_fixes.skip_link.targets ) {
		return;
	}

	const skipLinkFound = tryDetectSkipLink();

	if ( skipLinkFound ) {
		return;
	}

	// try to find one the targets on the page.
	const foundMainTarget = window.edac_frontend_fixes.skip_link.targets.find( ( target ) => document.querySelector( target ) );

	if ( ! foundMainTarget ) {
		// eslint-disable-next-line
		console.log( __( 'EDAC: Did not find a matching target ID on the page for the skip link.', 'accessibility-checker' ) );
	}

	const skipLink = skipLinkTemplate.content.cloneNode( true );
	// set the href to the first target if found or remove it if not.
	if ( foundMainTarget ) {
		skipLink.querySelector( '.edac-skip-link--content' ).href = foundMainTarget;
		// To override any bad programatic smooth scroll or focus override manually
		// move the focus and update history.
		handleSkipLinkFocusShift( foundMainTarget, skipLink );
	} else {
		skipLink.querySelector( '.edac-skip-link--content' ).remove();
	}
	document.body.prepend( skipLink );
};

export default SkipLinkFixInit;
