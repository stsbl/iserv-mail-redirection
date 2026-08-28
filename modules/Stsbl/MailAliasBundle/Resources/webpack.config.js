const base = require(process.env.WEBPACK_BASE_PATH + '/webpack.config.base.js');

module.exports = base.merge(__dirname, {
    entry: {
        'img/mail-alias.svg': './assets/img/mail-alias.svg',
        'css/recipients': './assets/css/recipients.css',
    },
});
