<?php
/**
 * Extension for adding Vite manifest support to Timber.
 */
class TwigExtensionViteLoader extends \Twig\Extension\AbstractExtension {
    public function __construct() {
        $this->themeDirectory = rtrim(get_template_directory(), "/");
        $this->themeUri = rtrim(get_template_directory_uri(), "/");
        $this->buildUri = $this->themeUri . "/build";
        $this->devBasePath = wp_parse_url($this->buildUri, PHP_URL_PATH) ?: "/build";
        $this->manifestPath = $this->themeDirectory . "/build/manifest.json";
        $this->devServerUrl = rtrim($this->env("VITE_DEV_SERVER_URL", "http://localhost:5173"), "/");
        $this->devServerInternalUrl = rtrim($this->env("VITE_DEV_SERVER_INTERNAL_URL", "http://host.docker.internal:5173"), "/");
        $this->devServerEnabled = $this->detectDevServer();
        $this->loadManifest();
    }

    /**
     * Returns a list of functions to add to Twig.
     *
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array {
        return [
            new \Twig\TwigFunction("asset_entry_scripts", [$this, "getEntryScripts"]),
            new \Twig\TwigFunction("asset_entry_styles", [$this, "getEntryStyles"]),
            new \Twig\TwigFunction("asset", [$this, "assetPath"]),
            new \Twig\TwigFunction("raw_asset", [$this, "rawAsset"]),
        ];
    }

    public function getEntryScripts(string $entryName): string {
        if ($this->devServerEnabled) {
            return implode("\n", [
                sprintf(
                    '<script type="module" src="%s"></script>',
                    esc_url($this->devServerClientUrl())
                ),
                sprintf(
                    '<script type="module" src="%s"></script>',
                    esc_url($this->devServerAssetUrl($entryName))
                ),
            ]);
        }

        $entry = $this->getEntry($entryName);
        $tags = [];

        foreach ($this->collectImportedEntries($entry) as $import) {
            if (!isset($import["file"])) {
                continue;
            }

            $tags[] = sprintf(
                '<link rel="modulepreload" href="%s" />',
                esc_url($this->buildUri . "/" . $import["file"])
            );
        }

        $tags[] = sprintf(
            '<script type="module" src="%s"></script>',
            esc_url($this->buildUri . "/" . $entry["file"])
        );

        return implode("\n", $tags);
    }

    public function getEntryStyles(string $entryName): string {
        if ($this->devServerEnabled) {
            return "";
        }

        $entry = $this->getEntry($entryName);
        $cssFiles = [];

        foreach ($this->collectCssFiles($entry) as $cssFile) {
            $cssFiles[] = sprintf(
                '<link rel="stylesheet" href="%s" />',
                esc_url($this->buildUri . "/" . $cssFile)
            );
        }

        return implode("\n", $cssFiles);
    }

    public function assetPath(string $asset): string {
        if ($this->devServerEnabled) {
            return $this->devServerAssetUrl($asset);
        }

        foreach ($this->assetCandidates($asset) as $candidate) {
            if (isset($this->manifest[$candidate]["file"])) {
                return $this->buildUri . "/" . $this->manifest[$candidate]["file"];
            }
        }

        return $this->themeUri . "/" . ltrim($asset, "/");
    }

    public function rawAsset(string $asset): string {
        if ($this->devServerEnabled) {
            return "";
        }

        $assetUrl = $this->assetPath($asset);
        $assetPath = wp_parse_url($assetUrl, PHP_URL_PATH);

        if (!$assetPath) {
            return "";
        }

        $absolutePath = ABSPATH . ltrim($assetPath, "/");
        if (!file_exists($absolutePath)) {
            return "";
        }

        return file_get_contents($absolutePath) ?: "";
    }

    private function loadManifest(): void {
        $this->manifest = [];

        if ($this->devServerEnabled) {
            return;
        }

        if (!file_exists($this->manifestPath)) {
            return;
        }

        $decoded = json_decode(file_get_contents($this->manifestPath), true);
        if (is_array($decoded)) {
            $this->manifest = $decoded;
        }
    }

    private function getEntry(string $entryName): array {
        foreach ($this->entryCandidates($entryName) as $candidate) {
            if (isset($this->manifest[$candidate])) {
                return $this->manifest[$candidate];
            }
        }

        throw new Exception("Entrypoint {$entryName} was not found. Did you run npm scripts?");
    }

    private function detectDevServer(): bool {
        $cacheKey = "vite_dev_server_status_" . md5($this->devServerInternalUrl);
        $cached = get_transient($cacheKey);
        if (false !== $cached) {
            return "1" === $cached;
        }

        $response = wp_remote_get(
            $this->devServerInternalClientUrl(),
            [
                "timeout" => 0.5,
                "sslverify" => false,
            ]
        );

        $isAvailable = !is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response);
        set_transient($cacheKey, $isAvailable ? "1" : "0", 2);

        return $isAvailable;
    }

    private function entryCandidates(string $entryName): array {
        $trimmedName = ltrim($entryName, "/");
        $candidates = [$trimmedName];

        if (!str_contains($trimmedName, ".")) {
            $candidates[] = "assets/{$trimmedName}.js";
            $candidates[] = "assets/{$trimmedName}.ts";
            $candidates[] = "assets/{$trimmedName}.scss";
            $candidates[] = "assets/{$trimmedName}.css";
        }

        return array_values(array_unique($candidates));
    }

    private function assetCandidates(string $asset): array {
        $trimmedAsset = ltrim($asset, "/");
        $candidates = [$trimmedAsset];

        if (!str_starts_with($trimmedAsset, "assets/")) {
            $candidates[] = "assets/{$trimmedAsset}";
        }

        return array_values(array_unique($candidates));
    }

    private function devServerAssetUrl(string $asset): string {
        $assetPath = $this->normalizeAssetPath($asset);

        return $this->devServerBaseUrl() . "/" . $assetPath;
    }

    private function normalizeAssetPath(string $asset): string {
        $trimmedAsset = ltrim($asset, "/");
        if (str_contains($trimmedAsset, ".")) {
            return $trimmedAsset;
        }

        return "assets/{$trimmedAsset}.js";
    }

    private function devServerBaseUrl(): string {
        return $this->devServerUrl . $this->devBasePath;
    }

    private function devServerClientUrl(): string {
        return $this->devServerBaseUrl() . "/@vite/client";
    }

    private function devServerInternalClientUrl(): string {
        return $this->devServerInternalUrl . $this->devBasePath . "/@vite/client";
    }

    private function collectImportedEntries(array $entry, array &$seen = []): array {
        $imports = [];

        foreach ($entry["imports"] ?? [] as $importKey) {
            if (isset($seen[$importKey]) || !isset($this->manifest[$importKey])) {
                continue;
            }

            $seen[$importKey] = true;
            $import = $this->manifest[$importKey];
            $imports[] = $import;

            foreach ($this->collectImportedEntries($import, $seen) as $childImport) {
                $imports[] = $childImport;
            }
        }

        return $imports;
    }

    private function collectCssFiles(array $entry): array {
        $cssFiles = $entry["css"] ?? [];
        $seen = [];

        foreach ($this->collectImportedEntries($entry, $seen) as $import) {
            foreach ($import["css"] ?? [] as $cssFile) {
                $cssFiles[] = $cssFile;
            }
        }

        return array_values(array_unique($cssFiles));
    }

    private function env(string $key, string $default): string {
        $value = getenv($key);

        return false === $value || "" === trim($value) ? $default : trim($value);
    }

    private array $manifest = [];
    private string $themeDirectory;
    private string $themeUri;
    private string $buildUri;
    private string $devBasePath;
    private string $manifestPath;
    private string $devServerUrl;
    private string $devServerInternalUrl;
    private bool $devServerEnabled;
}

add_filter("timber/twig", function ($twig) {
    $twig->addExtension(new TwigExtensionViteLoader());

    return $twig;
});
