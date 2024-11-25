/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
const path = require('path');

// eslint-disable-next-line no-undef
module.exports = {
    plugins: {
        'postcss-import': {
            path: path.resolve(process.cwd(), 'node_modules'),
        },
        'postcss-nested': {},
        'postcss-simple-vars': {},
        'postcss-calc': {},
        'postcss-hexrgba': {},
        'autoprefixer': {},
    },
};
