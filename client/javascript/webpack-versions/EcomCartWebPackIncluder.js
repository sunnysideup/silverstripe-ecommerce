const webpack = require('webpack')
const path = require('path')

module.exports = {
  resolve: {
    extensions: ['', '.js'],
    alias: {
      EcomCart: path.resolve('./EcomCart')
    }
  },

  plugins: [
    new webpack.ProvidePlugin({
      EcomCart: 'EcomCart'
    })
  ]
}
