/* global __dirname, module */
const webpack = require( 'webpack' ); // to access built-in plugins
const path = require( 'path' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );

module.exports = {
	mode: 'production', //development | production
	watch: false,
	entry: {
		admin: [
			'./src/admin/index.js',
			'./src/admin/sass/accessibility-checker-admin.scss',
		],
		editorApp: [
			'./src/editorApp/index.js',
		],
		frontendHighlighterApp: [
			'./src/frontendHighlighterApp/index.js',
			'./src/frontendHighlighterApp/sass/app.scss',
		],
		pageScanner: [
			'./src/pageScanner/index.js',
		],
		emailOptIn: [
			'./src/emailOptIn/index.js',
			'./src/emailOptIn/sass/email-opt-in.scss',
		],
		frontendFixes: [
			'./src/frontendFixes/index.js',
		],
		'blocks/simplified-summary': [
			'./src/blocks/simplified-summary/index.js',
			'./src/blocks/simplified-summary/style.css',
		],

	},
	optimization: {
		splitChunks: {
			cacheGroups: {
				specificScript: {
					test: /[\\/]src[\\/]frontendFixes[\\/]/,
					name: 'frontendFixes',
					chunks: 'all',
				},
			},
		},
		minimizer: [
			new TerserPlugin( {
				parallel: true,
				terserOptions: {
					mangle: {
						reserved: [ '__', '_n', '_x', '_nx' ], // Prevent webpack from using these translation function names and mangling them in the source.
					},
					keep_fnames: /(__|_n|_x|_nx)$/,
				},
			} ),
			new CssMinimizerPlugin(),
		],
	},
	output: {
		filename: '[name].bundle.js',
		path: path.resolve( __dirname, 'build' ),
		chunkFilename: 'chunks/[name].[chunkhash].js', // Store split bundles in 'chunks' directory
	},

	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: [ 'babel-loader' ],
			},
			{
				test: /\.(s(a|c)ss)$/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader',
				],
			},
			// test for just css files so that they are extracted from css in js.
			{
				test: /\.css$/i,
				use: [ MiniCssExtractPlugin.loader, 'css-loader' ],
			},
			// loader for images and icons (required if css references image files)
			{
				test: /\.(svg|png|jpg|gif)$/,
				type: 'asset/resource',
				generator: {
					filename: './img/[name][ext]',
				},
			},
		],
	},
	plugins: [
		new webpack.ProgressPlugin(),
		new CleanWebpackPlugin(),
		new MiniCssExtractPlugin( {
			filename: './css/[name].css',
		} ),
	],
	externals: {
		// Exclude WordPress core scripts and styles from the build.
		'@wordpress/i18n': [ 'wp', 'i18n' ],
		'@wordpress/blocks': [ 'wp', 'blocks' ],
		'@wordpress/data': [ 'wp', 'data' ],
		'@wordpress/element': [ 'wp', 'element' ],
	},
};
