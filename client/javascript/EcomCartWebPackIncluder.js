const webpack = require('webpack')
const path = require('path')

module.exports = {

  resolve: {
    extensions: ['', '.js'],
    alias: {
      EcomCart: path.resolve('./EcomCartWebPack')
    }
  },

  plugins: [
    new webpack.ProvidePlugin({
      EcomCart: 'EcomCart'
    })
  ]

}
