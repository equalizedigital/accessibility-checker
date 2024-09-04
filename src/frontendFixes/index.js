const edacFrontendFixes = window.edac_frontend_fixes || {};

if ( edacFrontendFixes.skip_link.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "skip-link" */ './Fixes/skipLinkFix' ).then( ( skipLinkFix ) => {
		skipLinkFix.default();
	} );
}

if ( edacFrontendFixes.lang_and_dir.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "aria-hidden" */ './Fixes/langAndDirFix' ).then( ( langAndDirFix ) => {
		langAndDirFix.default();
	} );
}

if ( edacFrontendFixes.tabindex.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "tabindex" */ './Fixes/tabindexFix' ).then( ( tabindexFix ) => {
		tabindexFix.default();
	} );
}

if ( edacFrontendFixes?.prevent_links_opening_in_new_window?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "prevent-links-opening-in-new-window" */ './Fixes/preventLinksOpeningNewWindowFix' ).then( ( preventLinksOpeningInNewWindow ) => {
		preventLinksOpeningInNewWindow.preventLinksOpeningNewWindowFix();
	} );
}
