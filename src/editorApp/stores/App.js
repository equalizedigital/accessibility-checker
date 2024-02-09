import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore( 'appStore', () => {
	const isLoaded = ref( false );
	const postOptions = ref( [] );

	return {
		isLoaded,
		postOptions,
	};
} );
