// Define the list of exclusions for accessibility scans.
export const exclusionsArray = [
	'#wpadminbar',
	'#edac-highlight-panel',
	// Query Monitor. It renders debug output on the front end for logged in
	// users only, and the markup varies by version: older releases use
	// #query-monitor-main, newer ones mount the panel into
	// #query-monitor-container and print server-rendered fallback panels into
	// #query-monitor-fallbacks. The .qm-panel-container class catches the
	// individual fallback panels since their IDs differ between versions.
	'#query-monitor-main',
	'#query-monitor-container',
	'#query-monitor-fallbacks',
	'.qm-panel-container',
	'#qm-icon-container',
	// Elementor editor UI. When the front-end highlighter rescans inside
	// Elementor's live-preview iframe, the editor injects its own controls into
	// the previewed page: per-element handles/overlays, the "add section"
	// drop zones, empty-state placeholders, drag placeholders, theme builder
	// document handles and the inline text-editing toolbar. None of it is
	// rendered to visitors, so it must not be reported as page issues.
	// The elementor-prefixed classes are Elementor's own; the inline toolbar
	// uses the generic Pen editor class, so it is scoped to the
	// .elementor-editor-active class Elementor adds to the preview body.
	'.elementor-element-overlay',
	'.elementor-editor-element-settings',
	'.elementor-add-section',
	'.elementor-add-section-inline',
	'#elementor-add-new-section',
	'.elementor-first-add',
	'.elementor-empty-view',
	'.elementor-sortable-placeholder',
	'.elementor-document-handle',
	'.elementor-editor-active .pen-menu',
	// The editor renders an empty aria-hidden shape divider placeholder in
	// every container; the front end only outputs one when a shape is set.
	'.elementor-editor-active .elementor-shape',
	// Likewise, widget editor templates render an empty <i class=""> when no
	// icon is chosen, which the front end omits.
	'.elementor-editor-active i[class=""][aria-hidden="true"]',
];

// Overlays hidden (not just excluded) while a scan runs, because axe still
// treats excluded elements as covering the content beneath them when it
// checks color contrast. See helpers/hideOverlaysDuringScan.js.
export const overlayHideSelectors = [
	'.elementor-element-overlay',
	'.edac-highlight-btn',
];
