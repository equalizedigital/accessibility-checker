import { settings }  from './settings';
import { init as initCheckPage } from './checkPage';


window.addEventListener('DOMContentLoaded', () => {
	if(settings.JS_SCAN_ENABLED){

		setTimeout(function(){
			initCheckPage();
		}, 250); // Allow page load to fire before init, otherwise we'll have to wait for iframe to load.

	}
});


