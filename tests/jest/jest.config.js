/* global module */
module.exports = {
	testEnvironment: 'jsdom',
	transform: {
		'^.+\\.[jt]sx?$': [ 'babel-jest', { configFile: require.resolve( './babel.config.js' ) } ],
	},
	transformIgnorePatterns: [
		'node_modules/(?!(axe-core|@wordpress)/)',
	],
	moduleNameMapper: {
		'\\.(css|scss)$': '<rootDir>/styleMock.js',
		'^@wordpress/components$': '<rootDir>/__mocks__/emptyModule.js',
	},
	setupFilesAfterEnv: [
		'<rootDir>/setupTests.js',
	],
	testMatch: [
		'**/*.test.js',
	],
};
