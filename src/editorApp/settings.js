let debug = false;

if(typeof(edac_editor_app) !== 'undefined'){
	debug = edac_editor_app.debug === '1';
}

export const settings = {
	JS_SCAN_ENABLED: true,
	INFO_ENABLED : debug,
	DEBUG_ENABLED: debug
};

