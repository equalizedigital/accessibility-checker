const preventLinksOpeningNewWindowFix = () => {
	const links = document.querySelectorAll( 'a[target="_blank"]:not(.allow-new-tab)' );
	links.forEach( ( link ) => {
		// If the link is in a container that allows new tabs, don't remove the target attribute.
		if ( link.closest( '.allow-new-tab' ) ) {
			return;
		}
		link.removeAttribute( 'target' );
		link.classList.add( 'edac-removed-target-blank' );
	} );
};

export default preventLinksOpeningNewWindowFix;
