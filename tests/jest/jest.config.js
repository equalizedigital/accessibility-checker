/* global module */
module.exports = {
	testEnvironment: 'jsdom',
	transform: {
		'^.+\\.js$': [ 'babel-jest', { configFile: require.resolve( '../jest/babel.config.js' ) } ]	},
	transformIgnorePatterns: [
		'node_modules/(?!(axe-core)/)',
	],
	testMatch: [
		'**/tests/jest/**/*.test.js',
	],
};
