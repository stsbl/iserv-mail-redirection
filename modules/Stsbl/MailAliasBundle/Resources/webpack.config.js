// src/Stsbl/MailAliasBundle/Resources/webpack.config.js
let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js'));

let webpackConfig = {
    entry: {
        'js/mailaliases_autocomplete': './assets/js/mailaliases_autocomplete.js',
    },
};

module.exports = merge(baseConfig.get(__dirname), webpackConfig);