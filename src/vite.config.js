import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Enable minification (esbuild is built into Vite)
        minify: 'esbuild',
        cssMinify: true,
        // Optimize chunk sizes
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios'],
                },
            },
        },
        // Generate source maps for production debugging (optional)
        sourcemap: false,
        // Target modern browsers for smaller bundles
        target: 'es2020',
    },
});
