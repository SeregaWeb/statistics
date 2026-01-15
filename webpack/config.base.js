/* eslint-disable @typescript-eslint/no-var-requires */
/**
 * This holds the configuration that is being used for both development and production.
 * This is being imported and extended in the config.development.js and config.production.js files
 *
 * @since 1.1.0
 */
const magicImporter = require('node-sass-magic-importer'); // Add magic import functionalities to SASS
const MiniCssExtractPlugin = require('mini-css-extract-plugin'); // Extracts the CSS files into public/css
const BrowserSyncPlugin = require('browser-sync-webpack-plugin'); // Synchronising URLs, interactions and code changes across devices
const WebpackBar = require('webpackbar'); // Display elegant progress bar while building or watch
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin'); // To optimize (compress) all images using
const CopyPlugin = require('copy-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

module.exports = (projectOptions) => {
    /**
     * CSS Rules
     */
    const cssRules = {
        test:
            projectOptions.projectCss.use === 'scss'
                ? projectOptions.projectCss.rules.scss.test
                : projectOptions.projectCss.rules.postcss.test,
        exclude: /(node_modules|bower_components|vendor)/,
        use: [
            MiniCssExtractPlugin.loader,
            // Creates `style` nodes
            // from JS strings. Will create separate file for styles
            'css-loader', // Translates CSS into CommonJS
            {
                // loads the PostCSS loader
                loader: 'postcss-loader',
                options: require(projectOptions.projectCss.postCss)(
                    projectOptions
                ),
            },
        ],
    };

    if (projectOptions.projectCss.use === 'scss') {
        // if chosen Sass then we're going to add the Sass loader
        cssRules.use.push({
            // Compiles Sass to CSS
            loader: 'sass-loader',
            options: {
                sassOptions: {
                    importer: magicImporter(), // add magic import functionalities to sass
                    silenceDeprecations: ['mixed-decls'], // suppress deprecation warnings from Bootstrap
                },
            },
        });
    }

    /**
     * JavaScript rules
     */
    const jsRules = {
        test: projectOptions.projectJs.rules.test,
        include: projectOptions.projectJsPath,
        exclude: /(node_modules|bower_components|vendor)/,
        use: ['babel-loader', 'ts-loader'], // Configurations in "webpack/babel.config.js" and "webpack/tsconfig.js"
    };

    /**
     * Images rules
     */
    const imageRules = {
        test: projectOptions.projectImages.rules.test,
        use: [
            {
                loader: 'file-loader',
                options: {
                    outputPath: 'images/',
                    publicPath: '../images/',
                    name: '[name].[ext]',
                    // name: '[name].[ext]?[hash]',
                },
            },
        ],
    };

    /**
     * Font rules
     */
    const fontRules = {
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        type: 'asset/resource',
        generator: {
            publicPath: '../fonts/', // setting how it will be in css file
            outputPath: 'fonts/', // setting where should be placed file
            filename: '[name][ext]',
        },
    };

    /**
     * Optimization rules
     */
    const optimizations = {
        minimizer: [
            // For webpack@5 you can use the `...` syntax to extend existing minimizers (i.e. `terser-webpack-plugin`), uncomment the next line
            // `...`,
            new CssMinimizerPlugin(),
        ],
        minimize: true,
    };

    /**
     * Plugins
     */
    const plugins = [
        new WebpackBar(), // Adds loading bar during builds
        // Uncomment this to enable profiler https://github.com/nuxt-contrib/webpackbar#options
        // { reporters: [ 'profile' ], profile: true }
        new MiniCssExtractPlugin({
            // Extracts CSS files
            filename: projectOptions.projectCss.filename,
        }),
        new CopyPlugin({
            // Copies images from src to public
            patterns: [
                {
                    from: projectOptions.projectImagesPath,
                    to: `${projectOptions.projectOutput}/images`,
                },
            ],
        }),
        new ImageMinimizerPlugin({
            // Optimizes images
            minimizerOptions: projectOptions.projectImages.minimizerOptions,
        }),
    ];

    // Add browserSync to plugins if enabled
    if (projectOptions.browserSync.enable === true) {
        const browserSyncOptions = {
            files: projectOptions.browserSync.files,
            host: projectOptions.browserSync.host,
            port: projectOptions.browserSync.port,
        };
        if (projectOptions.browserSync.mode === 'server') {
            Object.assign(browserSyncOptions, {
                server: projectOptions.browserSync.server,
            });
        } else {
            Object.assign(browserSyncOptions, {
                proxy: projectOptions.browserSync.proxy,
            });
        }
        plugins.push(
            new BrowserSyncPlugin(browserSyncOptions, {
                reload: projectOptions.browserSync.reload,
            })
        );
    }

    return {
        cssRules,
        jsRules,
        imageRules,
        optimizations,
        plugins,
        fontRules,
    };
};
