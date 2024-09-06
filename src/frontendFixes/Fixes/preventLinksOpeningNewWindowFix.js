const preventLinksOpeningNewWindowFix = () => {
	const links = document.querySelectorAll( 'a[target="_blank"]' );
	links.forEach( ( link ) => {
		link.removeAttribute( 'target' );
		link.classList.add( 'edac-removed-target-blank' );
	} );
};

export default preventLinksOpeningNewWindowFix;
