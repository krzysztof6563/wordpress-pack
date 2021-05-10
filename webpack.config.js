var Encore = require('@symfony/webpack-encore');
var webpack = require('webpack');
var BrowserSyncPlugin = require('browser-sync-webpack-plugin')

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('build/')
    // public path used by the web server to access the output path
    .setPublicPath('/wp-content/themes/timber-starter-theme/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    //.addEntry('page1', './assets/page1.js')
    //.addEntry('page2', './assets/page2.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    // .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    /*
     * LOADERS 
    */
    .enableSassLoader()
    // .enableVueLoader()

    /*
     * FILE COPYING 
    */
    //.copyFiles({
    //    from: "./assets/images",
    //    to: "images/[name].[ext]"
    //})

    // .copyFiles({
    //     from: "./assets/icons",
    //     to: "icons/[name].[ext]"
    //  })

    //  .copyFiles({
    //     from: "./assets/fonts",
    //     to: "fonts/[name].[ext]"
    //  })
 
    /*
     *   BROWSER SYNC
    */
    //  .addPlugin(new BrowserSyncPlugin({
    //      host: 'localhost',
    //      port: 3000,
    //      proxy: 'http://xxx.localhost/',
   //       snippetOptions: {
    //          ignorePaths: "**wp-admin/**"
     //     },
    //      files: [
    //          {
    //              match: [
    //                  '**/*.php', '**/*.twig'
    //              ],
    //              fn: function(event, file) {
    //                  if (event === "change") {
    //                      const bs = require('browser-sync').get('bs-webpack-plugin');
    //                      bs.reload();
    //                  }
    //              }
    //          }
    //      ],
    //      open: false,
    //  }))
;

module.exports = Encore.getWebpackConfig();
