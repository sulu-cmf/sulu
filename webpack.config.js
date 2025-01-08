/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable import/no-dynamic-require */
const fs = require('fs');
const path = require('path');

module.exports = (env, argv) => { // eslint-disable-line no-undef
    env = env ? env : {};
    argv = argv ? argv : {};

    const outputPath = env && env.output_path ? env.output_path : path.join('build', 'admin');
    // eslint-disable-next-line no-undef
    const projectRootPath = env && env.project_root_path ? env.project_root_path : __dirname;
    const nodeModulesPath = env && env.node_modules_path
        ? env.node_modules_path
        : path.resolve(projectRootPath, 'node_modules');

    let publicDir = 'public';
    const composerConfig = require(path.resolve(projectRootPath, 'composer.json'));
    if (composerConfig.extra && composerConfig.extra['public-dir']) {
        publicDir = composerConfig.extra['public-dir'];
    }

    // default value for version must match default value in SuluVersionPass.php
    let suluVersion = '_._._';
    if (fs.existsSync(path.resolve(projectRootPath, 'composer.lock'))) {
        const composerLock = JSON.parse(fs.readFileSync(path.resolve(projectRootPath, 'composer.lock')));
        const suluPackage = composerLock.packages.find((packageItem) => packageItem.name === 'sulu/sulu');
        suluVersion = suluPackage ? suluPackage.version : suluVersion;
    }

    const webpack = require(path.resolve(nodeModulesPath, 'webpack'));
    const ManifestPlugin = require(path.resolve(nodeModulesPath, 'webpack-manifest-plugin')).WebpackManifestPlugin;
    const MiniCssExtractPlugin = require(path.resolve(nodeModulesPath, 'mini-css-extract-plugin'));
    const CssMinimizerPlugin = require(path.resolve(nodeModulesPath, 'css-minimizer-webpack-plugin'));
    const {styles} = require(path.resolve(nodeModulesPath, '@ckeditor/ckeditor5-dev-utils'));

    return {
        entry: [path.resolve(__dirname, 'index.js')], // eslint-disable-line no-undef
        output: {
            clean: true,
            path: path.resolve(projectRootPath, publicDir, outputPath),
            filename: '[name].[chunkhash].js',
        },
        stats: 'minimal',
        performance: {
            hints: false,
        },
        snapshot: {
            // detect changes in "node_modules/@sulu": https://github.com/webpack/webpack/issues/11612
            managedPaths: [],
        },
        devtool: argv.mode === 'development' ? 'eval-source-map' : 'source-map',
        plugins: [
            new MiniCssExtractPlugin({
                filename: '[name].[chunkhash].css',
            }),
            new CssMinimizerPlugin(),
            new ManifestPlugin({
                map: (file) => {
                    // see https://github.com/shellscape/webpack-manifest-plugin/issues/229
                    file.path = file.path.replace(/^auto\//, '/' + outputPath + '/');
                    return file;
                },
            }),
            new webpack.DefinePlugin({
                SULU_ADMIN_BUILD_VERSION: JSON.stringify(suluVersion),
            }),
        ],
        optimization: {
            minimizer: [
                '...', // extend existing minimizers: https://webpack.js.org/plugins/css-minimizer-webpack-plugin/
                new CssMinimizerPlugin(),
            ],
        },
        resolve: {
            alias: {
                'fos-jsrouting': path.resolve(
                    projectRootPath,
                    'vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js'
                ),
            },
            symlinks: false, // @see https://github.com/sulu/sulu/pull/6117
        },
        resolveLoader: {
            symlinks: false, // @see https://github.com/sulu/sulu/pull/6117
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: [
                        // eslint-disable-next-line max-len
                        /node_modules[/\\](?!(sulu-(.*)-bundle|@ckeditor|ckeditor5|array-move|lodash-es|vanilla-colorful)[/\\])/,
                        /friendsofsymfony[/\\]jsrouting-bundle/,
                    ],
                    use: {
                        loader: 'babel-loader',
                        options: {
                            cacheDirectory: true,
                            cacheCompression: false,
                        },
                    },
                },
                {
                    test: /\.css/,
                    exclude: /ckeditor5-[^/\\]+[/\\]theme[/\\].+\.css$/,
                    use: [
                        {
                            loader: MiniCssExtractPlugin.loader,
                        },
                        // style loader not required: https://github.com/webpack-contrib/css-loader#recommend
                        // eslint-disable-next-line max-len
                        // https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/alternative-setups/integrating-from-source.html#option-extracting-css
                        'css-loader',
                    ],
                },
                {
                    test: /\.(scss)$/,
                    use: [
                        {
                            loader: MiniCssExtractPlugin.loader,
                        },
                        {
                            loader: 'css-loader',
                            options: {
                                modules: {
                                    localIdentName: '[local]--[contenthash:base64:10]',
                                    exportLocalsConvention: 'camelCase',
                                },
                                importLoaders: 1,
                            },
                        },
                        'postcss-loader',
                    ],
                },
                {
                    test: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
                    type: 'asset/source',
                },
                {
                    test: /ckeditor5-[^/\\]+[/\\]theme[/\\].+\.css$/,
                    use: [
                        {
                            loader: MiniCssExtractPlugin.loader,
                        },
                        {
                            loader: 'css-loader',
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: styles.getPostCssConfig({
                                    themeImporter: {
                                        themePath: require.resolve(
                                            path.resolve(nodeModulesPath, '@ckeditor/ckeditor5-theme-lark')
                                        ),
                                    },
                                    minify: true,
                                }),
                            },
                        },
                    ],
                },
                {
                    test: /\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                    exclude: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
                    type: 'asset/resource',
                    generator: {
                        filename: '[name].[contenthash][ext]',
                    },
                },
                {
                    test: /\.(jpg|gif|png)(\?.*$|$)/,
                    type: 'asset/resource',
                    generator: {
                        filename: 'images/[name].[contenthash][ext]',
                    },
                },
            ],
        },
    };
};
