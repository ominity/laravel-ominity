import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        sourcemap: true,
        outDir: 'dist',
        minify: 'esbuild',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                js: 'resources/ts/index.ts',
                css: 'resources/scss/ominity.scss',
            },
            output: {
                entryFileNames: (chunk) => {
                    if (chunk.name === 'js') return 'ominity.js';
                    if (chunk.name === 'css') return 'ominity.css';
                    return '[name].js';
                },
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name == 'ominity.scss') {
                        return 'ominity.css';
                    }
                    return '[name].[ext]';
                },
                globals: {
                    jquery: '$',
                },
            },
            external: ['jquery'],
        }
    },
});
