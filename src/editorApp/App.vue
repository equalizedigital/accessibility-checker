<template>
	<div v-if="isLoaded">
		<SummaryPanel />
		<DetailsPanel />
		<ReadabilityPanel />
	</div>
</template>
	
<script setup >

	import { storeToRefs } from 'pinia';
	import { useAppStore } from './stores/App';
	import { SummaryPanel, DetailsPanel, ReadabilityPanel } from './components';

	
	const appStore = useAppStore();
	const { isLoaded } = storeToRefs(appStore);

</script>
