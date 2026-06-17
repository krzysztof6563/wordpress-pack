import path from "node:path";
import { defineConfig } from "vite";

export default defineConfig(({ mode }) => ({
    base: "/wp-content/themes/timber-starter-theme/build/",
    server: {
        allowedHosts: ["host.docker.internal", "localhost", "127.0.0.1"],
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        origin: "http://localhost:5173",
        hmr: {
            host: "localhost",
            port: 5173,
        },
    },
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
