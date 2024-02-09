/* eslint-disable */
const edacEditorApp = edac_editor_app;
/* eslint-enable */

import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import { createPinia, storeToRefs } from 'pinia';
import { useAppStore } from './stores/App';
import axios from 'axios';
import App from './App.vue';

const app = createApp( App );
const pinia = createPinia();
const i18n = createI18n( {
	globalInjection: true,
	legacy: false,
	//TODO: locale: edacEditorApp.locale,
	fallbackLocale: 'en',
	missingWarn: false,
	//TODO: messages: edacEditorApp.i18n,
} );

app.use( i18n );
app.use( pinia );

const appStore = useAppStore();

const { postOptions, isLoaded } = storeToRefs( appStore );

export const init = () => {
	load( edacEditorApp.postID );

	const root = document.getElementById(
		'edac_editor_app'
	);
	if ( root ) {
		app.mount( root );
	}
};

export const load = ( id ) => {
	//TODO: remove when ready for go-live.
	/* eslint-disable no-unreachable */
	return;

	isLoaded.value = false;

	axios.get(
		edacEditorApp.edacApiUrl + '/get-post-options/' + id, {
			headers: { 'X-WP-Nonce': edacEditorApp.restNonce },
		} ).then( ( response ) => {
		if ( response.status === 200 ) {
			postOptions.value = response.data.data;
			isLoaded.value = true;
		}
	} );
};
