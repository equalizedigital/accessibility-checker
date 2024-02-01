<template>
	<details>
    <summary>Readability</summary>
    <p>{{ postOptions }}</p>
  </details>
</template>
	
<script setup >

	import { storeToRefs } from 'pinia';
	import { useAppStore } from './../stores/App';

	const appStore = useAppStore();
	const { postOptions } = storeToRefs(appStore);

</script>
