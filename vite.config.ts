import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: 'resources/ts/index.ts',
            name: 'Ominity',
            fileName: (format) => `ominity.${format}.js`,
        },
        rollupOptions: {
            output: {
                globals: {
                    jquery: '$',
                },
            },
            external: ['jquery'], // keep jQuery external
        },
        minify: 'esbuild',
    },
});
