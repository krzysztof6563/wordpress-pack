<?php
/**
 * Extension for adding basic Webpack support to Twig.
 * Should be placed inside inc folder in current theme.
 *
 * @author Krzysztof Michalski <krzysztofmichalski42@gmail.com>
 *
 */
class TwigWebpackLoader extends \Twig\Extension\AbstractExtension {
    public function __construct() {
        $this->folder = __DIR__;
        $this->buildPath = $this->findOption('setOutputPath');
        $this->entrypoints = $this->loadEntrypoints();
        $this->assetsPath = $this->findOption('setPublicPath');
        $this->buildAssetMap();
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions() {
        return [
            new \Twig\TwigFunction('webpack_scripts', [$this, 'getWebapckScript']),
            new \Twig\TwigFunction('webpack_styles', [$this, 'getWebpackStyle']),
            new \Twig\TwigFunction('asset', [$this, 'assetPath']),
            new \Twig\TwigFunction('raw_asset', [$this, 'rawAsset']),
        ];
    }

    /**
     * Reads manifest paths
     */
    public function buildAssetMap() {
        $this->manifestPaths = json_decode(file_get_contents($this->folder."/../".$this->buildPath."manifest.json"), true);
    }

    /**
     * Returns html for including all scripts for given entry
     *
     * @param string $entryName name of entry in /build folder
     * @return string HTML fragment with <script> tags
     */
    public function getWebapckScript($entryName) {
        if (!isset($this->entrypoints->{$entryName}) || !isset($this->entrypoints->{$entryName}->js)) {
            throw new Exception("Entypoint $entryName was not found or doesn't contain JS entries");
        }

        ob_start();
        foreach ($this->entrypoints->{$entryName}->js as $script) : ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach;
        return ob_get_clean();
    }

    /**
     * Returns html for including all styles for given entry
     *
     * @param string $entryName name of entry in /build folder
     * @return string HTML fragment with <link> tags
     */
    public function getWebpackStyle($entryName) {
        if (!isset($this->entrypoints->{$entryName}) || !isset($this->entrypoints->{$entryName}->css)) {
            throw new Exception("Entypoint $entryName was not found or doesn't contain CSS entries");
        }

        ob_start();
        foreach ($this->entrypoints->{$entryName}->css as $cssFile) : ?>
            <link rel="stylesheet" href="<?= $cssFile ?>" />
        <?php endforeach;
        return ob_get_clean();
    }

    /**
     * Return relative URL for asset
     *
     * @param string $asset path to asset, relative to assets folder
     * @return string Relative URL for $asset
     */
    public function assetPath($asset) {
        return $this->manifestPaths[$asset] ?? $this->assetsPath."/".$asset;
    }
    
    /**
     * Return content of file
     *
     * @param string $asset path to asset, relative to assets folder
     * @return string Content of file
     */
    public function rawAsset($asset) {
        return file_get_contents(ABSPATH . $this->assetPath($asset));
    }

    /**
     * Load and parse entrypoints generated by Webpack
     *
     * @return Object containing entrypoints and files
     */
    private function loadEntrypoints() {
        return json_decode(file_get_contents($this->folder."/../".$this->buildPath."entrypoints.json"))->entrypoints;
    }

    /**
     * Extracts option from webpack.config.js in theme root directory
     *
     * @return string Value of option
     */
    private function findOption($optionName) {
        $webpackConfig = @file_get_contents($this->folder."/../webpack.config.js");
        if (!$webpackConfig) {
            return;
        }

        $i1 = strpos($webpackConfig, $optionName) + strlen($optionName) + 2;
        $i2 = strpos($webpackConfig, ")", $i1);
        return substr($webpackConfig, $i1, $i2 - $i1 - 1);
    }

    private $manifestPaths;
}
