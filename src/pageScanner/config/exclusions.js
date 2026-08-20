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
];
