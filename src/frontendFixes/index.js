const edacFrontendFixes = window.edac_frontend_fixes || {};

// Fixes that are safe to run more than once listen for this event to pick up
// content added after page load. Other code can also fire it on document.
const RERUN_EVENT = 'edac-fixes-rerun';

const rerunEvents = Array.isArray( edacFrontendFixes.rerunEvents ) ? edacFrontendFixes.rerunEvents : [];
rerunEvents.forEach( ( eventName ) => {
	// Skip the rerun event itself, it would loop forever.
	if ( typeof eventName !== 'string' || eventName === RERUN_EVENT ) {
		return;
	}
	document.addEventListener( eventName, () => {
		document.dispatchEvent( new CustomEvent( RERUN_EVENT ) );
	} );
} );

/**
 * Run a fix now and again each time the rerun event fires.
 *
 * @param {Function} fix The fix function.
 */
const runAndRerun = ( fix ) => {
	fix();
	document.addEventListener( RERUN_EVENT, () => fix() );
};

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
		runAndRerun( tabindexFix.default );
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
		runAndRerun( removeTitleIfPreferredAccessibleNameFix.default );
	} );
}

if ( edacFrontendFixes?.underline?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "underline" */ './Fixes/underlineFix' ).then( ( underlineFix ) => {
		runAndRerun( underlineFix.default );
	} );
}

if ( edacFrontendFixes?.prevent_links_opening_new_windows?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "prevent-links-opening-in-new-window" */ './Fixes/preventLinksOpeningNewWindowFix' ).then( ( preventLinksOpeningNewWindowFix ) => {
		runAndRerun( preventLinksOpeningNewWindowFix.default );
	} );
}

if ( edacFrontendFixes?.new_window_warning?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "new-window-warning" */ './Fixes/newWindowWarning' ).then( ( newWindowWarning ) => {
		newWindowWarning.default();
	} );
}

if ( edacFrontendFixes?.empty_paragraph_tag?.enabled ) {
	// lazy import the module
	import( /* webpackChunkName: "empty-paragraph-tag" */ './Fixes/emptyParagraphTagFix' ).then( ( emptyParagraphTagFix ) => {
		emptyParagraphTagFix.default();
	} );
}
