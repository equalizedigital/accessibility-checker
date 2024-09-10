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

if ( edacFrontendFixes?.add_label_to_unlabeled_form_fields?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "add-label-to-unlabeled-form-fields" */ './Fixes/addLabelToUnlabelledFormFieldsFix' ).then( ( addLabelToUnlabledFormFieldsFix ) => {
		addLabelToUnlabledFormFieldsFix.AddLabelToUnlabelledFormFieldsFix();
	} );
}

if ( edacFrontendFixes?.tabindex?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "tabindex" */ './Fixes/tabindexFix' ).then( ( tabindexFix ) => {
		tabindexFix.default();
	} );
}

if ( edacFrontendFixes?.underline?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "underline" */ './Fixes/underlineFix' ).then( ( underlineFix ) => {
		underlineFix.default();
	} );
}

if ( edacFrontendFixes?.meta_viewport_scalable?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "meta-viewport-scalable" */ './Fixes/metaViewportScalableFix' ).then( ( metaViewportScalableFix ) => {
		metaViewportScalableFix.default();
	} );
}

if ( edacFrontendFixes?.prevent_links_opening_in_new_window?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "prevent-links-opening-in-new-window" */ './Fixes/preventLinksOpeningNewWindowFix' ).then( ( preventLinksOpeningNewWindowFix ) => {
		preventLinksOpeningNewWindowFix.default();
	} );
}

if ( edacFrontendFixes?.add_label_to_unlabelled_form_fields?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "add-label-to-unllabeled-form-fields" */ './Fixes/addLabelToUnlabelledFormFieldsFix' ).then( ( addLabelToUnlabelledFormFieldsFix ) => {
		addLabelToUnlabelledFormFieldsFix.default();
	} );
}
