var webpack = require("webpack");
var path = require("path");

module.exports = {

  // ...

  resolve: {
    extensions: ['', '.js'],
    alias: {
      'EcomCart': path.resolve(__dirname, './EcomCartWebPack')  // <-- When you build or restart dev-server, you'll get an error if the path to your utils.js file is incorrect.
    }
  },

  plugins: [
    new webpack.ProvidePlugin({
      'EcomCart': 'EcomCart'
    })
  ]

}
