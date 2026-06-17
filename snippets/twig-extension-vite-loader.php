<?php
/**
 * Extension for adding Vite manifest support to Timber.
 */
class TwigExtensionViteLoader extends \Twig\Extension\AbstractExtension {
    public function __construct() {
        $this->themeDirectory = rtrim(get_template_directory(), "/");
        $this->themeUri = rtrim(get_template_directory_uri(), "/");
        $this->buildUri = $this->themeUri . "/build";
        $this->manifestPath = $this->themeDirectory . "/build/manifest.json";
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
        foreach ($this->assetCandidates($asset) as $candidate) {
            if (isset($this->manifest[$candidate]["file"])) {
                return $this->buildUri . "/" . $this->manifest[$candidate]["file"];
            }
        }

        return $this->themeUri . "/" . ltrim($asset, "/");
    }

    public function rawAsset(string $asset): string {
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

    private array $manifest = [];
    private string $themeDirectory;
    private string $themeUri;
    private string $buildUri;
    private string $manifestPath;
}
