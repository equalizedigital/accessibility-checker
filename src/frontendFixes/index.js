const edacFrontendFixes = window.edac_frontend_fixes || {};

if ( edacFrontendFixes?.skip_link?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "skip-link" */ './Fixes/skipLinkFix' ).then( ( skipLinkFix ) => {
		skipLinkFix.default();
	} );
}

if ( edacFrontendFixes?.lang_and_dir?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "aria-hidden" */ './Fixes/langAndDirFix' ).then( ( langAndDirFix ) => {
		langAndDirFix.default();
	} );
}

if ( edacFrontendFixes?.tabindex?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "tabindex" */ './Fixes/tabindexFix' ).then( ( tabindexFix ) => {
		tabindexFix.default();
	} );
}

if ( edacFrontendFixes?.meta_viewport_scalable?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "meta-viewport-scalable" */ './Fixes/metaViewportScalableFix' ).then( ( metaViewportScalableFix ) => {
		metaViewportScalableFix.default();
	} );
}

if ( edacFrontendFixes?.remove_title_if_preferred_accessible_name?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "remove-title-if-preferred-accessible-name" */ './Fixes/removeTitleIfPrefferedAccessibleNameFix' ).then( ( removeTitleIfPreferredAccessibleNameFix ) => {
		removeTitleIfPreferredAccessibleNameFix.default();
	} );
}
