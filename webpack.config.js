const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .addEntry('calendar', './assets/calendrier.js') // Vérifiez bien le chemin
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabel((babelConfig) => {
        babelConfig.presets.push(['@babel/preset-env', {
            useBuiltIns: 'usage',
            corejs: 3 // Assurez-vous que core-js est installé
        }]);
    })
    .enablePostCssLoader();

module.exports = Encore.getWebpackConfig();
