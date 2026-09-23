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
	'.elementor-element-overlay',
	'.elementor-editor-element-settings',
	'.elementor-add-section',
	'.elementor-add-section-inline',
	'#elementor-add-new-section',
	'.elementor-first-add',
	'.elementor-empty-view',
	'.elementor-sortable-placeholder',
	'.elementor-document-handle',
	'.pen-menu',
];
