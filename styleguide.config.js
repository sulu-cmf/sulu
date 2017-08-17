/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
const path = require('path');
const glob = require('glob');

const firstLetterIsUppercase = (string) => {
    const first = string.charAt(0);
    return first === first.toUpperCase();
};

module.exports = { // eslint-disable-line
    require: [
        './src/Sulu/Bundle/AdminBundle/Resources/js/containers/Application/global.scss',
    ],
    sections: [
        {
            name: 'Components',
            components: function() {
                let folders = glob.sync('./src/Sulu/Bundle/*/Resources/js/components/*');
                // filter out higher order components
                folders = folders.filter((folder) => firstLetterIsUppercase(path.basename(folder)));

                return folders.map((folder) => {
                    const component = path.basename(folder);
                    return path.join(folder, component + '.js');
                });
            },
        },
        {
            name: 'Higher-order components',
            sections: (function() {
                let folders = glob.sync('./src/Sulu/Bundle/*/Resources/js/components/*');
                folders = folders.filter((folder) => !firstLetterIsUppercase(path.basename(folder)));
                return folders.map((folder) => {
                    const component = path.basename(folder);
                    return {name: component, content: folder + '/README.md'};
                });
            })(),
        },
        {
            name: 'Containers',
            components: function() {
                let folders = glob.sync('./src/Sulu/Bundle/*/Resources/js/containers/*');
                // filter out containers
                folders = folders.filter((folder) => firstLetterIsUppercase(path.basename(folder)));

                return folders.map((folder) => {
                    const component = path.basename(folder);
                    return path.join(folder, component + '.js');
                });
            },
        },
    ],
    webpackConfig: {
        devServer: {
            disableHostCheck: true,
        },
        module: {
            loaders: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    loader: 'babel-loader',
                },
                {
                    test: /\.css/,
                    use: [
                        'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                modules: false,
                            },
                        },
                    ],
                },
                {
                    test: /\.(scss)$/,
                    use: [
                        'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                modules: true,
                                camelCase: true,
                                importLoaders: 1,
                            },
                        },
                        'postcss-loader',
                    ],
                },
                {
                    test:/\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                    use: [
                        {
                            loader: 'file-loader',
                        },
                    ],
                },
            ],
        },
    },
};
