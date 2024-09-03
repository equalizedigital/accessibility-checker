const edacFrontendFixes = window.edac_frontend_fixes || {};

if ( edacFrontendFixes.skip_link.enabled ) {
	// lazy inport the module
	import( /* webpackChunkName: "skip-link" */ './Fixes/skipLinkFix' ).then( ( skipLinkFix ) => {
		skipLinkFix.default();
	} );
}

if ( edacFrontendFixes.lang_and_dir.enabled ) {
	// lazy inport the module
	import( /* webpackChunkName: "aria-hidden" */ './Fixes/langAndDirFix' ).then( ( langAndDirFix ) => {
		langAndDirFix.default();
	} );
}
