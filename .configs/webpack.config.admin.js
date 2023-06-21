const path = require('path');

//Thanks to: https://taylor.callsen.me/using-webpack-5-and-sass-with-wordpress/

// css extraction and minification
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

// clean out build dir in-between builds
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = {
  mode: "none", //development | production
  watch: true,
  entry: {
    'admin': [
      './assets/sass/accessibility-checker-admin.scss',
    ]
  },

  module: {
    rules: [
    
      {
         test: /\.(js|jsx)$/,
         exclude: /node_modules/,
         use: ['babel-loader']
      },
      {
        test: /\.(s(a|c)ss)$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader']
      },
      // loader for images and icons (required if css references image files)
 
      {
        test: /\.(svg|png|jpg|gif)$/,
        type: 'asset/resource',
        generator: {
          filename: './images/[name][ext]',
        }
      },
     
    ]
  },
  plugins: [
    // clear out build directories on each build
    new CleanWebpackPlugin({
      cleanOnceBeforeBuildPatterns: [
        './build/admin/*',
      ]
    }),
    // css extraction into dedicated file
    new MiniCssExtractPlugin({
      filename: './css/accessibility-checker-admin.css'
    }),
    
  ],
  optimization: {
    // minification - only performed when mode = production
    minimizer: [
      // js minification - special syntax enabling webpack 5 default terser-webpack-plugin 
      `...`,
      // css minification
      new CssMinimizerPlugin(),
    ]
  },

  
  output: {
    filename: '[name].bundle.not_currently_used.js',
    path: path.resolve(__dirname, './../assets/'),
  },

 
}