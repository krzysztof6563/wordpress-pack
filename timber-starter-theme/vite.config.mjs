import path from "node:path";
import { defineConfig } from "vite";

export default defineConfig(({ mode }) => ({
    base: "/wp-content/themes/timber-starter-theme/build/",
    build: {
        emptyOutDir: true,
        manifest: "manifest.json",
        outDir: "build",
        rollupOptions: {
            input: {
                app: path.resolve("assets/app.js"),
            },
        },
        sourcemap: mode !== "production",
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ["legacy-js-api"],
            },
        },
    },
}));
