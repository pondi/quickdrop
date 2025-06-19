import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Bundle analyzer in development
        process.env.ANALYZE && visualizer({
            open: true,
            filename: 'dist/stats.html',
            gzipSize: true,
            brotliSize: true,
        }),
    ].filter(Boolean),
    
    build: {
        // Enable code splitting
        rollupOptions: {
            output: {
                // Manual chunks for better caching
                manualChunks: {
                    // Vendor chunk for dependencies
                    vendor: [
                        'vue',
                        '@inertiajs/vue3',
                        'axios',
                    ],
                    // UI components chunk
                    ui: [
                        '@headlessui/vue',
                        '@heroicons/vue/24/outline',
                        '@heroicons/vue/24/solid',
                    ],
                    // Form handling
                    forms: [
                        '@vueform/vueform',
                    ],
                    // Utilities
                    utils: [
                        'lodash-es',
                        'date-fns',
                    ],
                },
                // Optimize chunk names
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId ? chunkInfo.facadeModuleId.split('/').pop() : 'chunk';
                    return `js/${facadeModuleId}-[hash].js`;
                },
                // Optimize asset names
                assetFileNames: (assetInfo) => {
                    const info = assetInfo.name.split('.');
                    const ext = info[info.length - 1];
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
                        return `images/[name]-[hash][extname]`;
                    } else if (/woff|woff2|eot|ttf|otf/i.test(ext)) {
                        return `fonts/[name]-[hash][extname]`;
                    } else if (ext === 'css') {
                        return `css/[name]-[hash][extname]`;
                    }
                    return `assets/[name]-[hash][extname]`;
                },
            },
        },
        
        // Optimize build
        target: 'es2015',
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        
        // Chunk size warnings
        chunkSizeWarningLimit: 500,
        
        // Source maps for production debugging
        sourcemap: process.env.NODE_ENV === 'production' ? 'hidden' : true,
        
        // CSS code splitting
        cssCodeSplit: true,
    },
    
    // Optimize dependencies
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            '@headlessui/vue',
            '@heroicons/vue/24/outline',
            '@heroicons/vue/24/solid',
        ],
        exclude: ['@vueform/vueform'],
    },
    
    // Performance optimizations
    server: {
        hmr: {
            overlay: false,
        },
    },
});
