const edacFrontendFixes = window.edac_frontend_fixes || {};

if ( edacFrontendFixes.skip_link.enabled ) {
	// lazy inport the module
	import( /* webpackChunkName: "skip-link" */ './Fixes/skipLinkFix' ).then( ( skipLinkFix ) => {
		skipLinkFix.default();
	} );
}
