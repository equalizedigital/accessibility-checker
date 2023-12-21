export default {
	id: 'always-fail',
	metadata: {
		impact: 'critical',
		messages: {
			pass: 'This test passed.',
			fail: 'This test failed.',
		},
	},
	evaluate() {
		return false;
	},
};
