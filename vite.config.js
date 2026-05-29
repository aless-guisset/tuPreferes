import { defineConfig } from 'vite'
import { resolve } from 'path'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'null', // On enregistre manuellement dans app.js
            outDir: resolve(__dirname, 'public'), // SW + manifest à la racine /
            base: '/',
            includeAssets: ['favicon.ico', 'icons/*.png'],

            manifest: {
                name: 'TuPréfères',
                short_name: 'TuPréfères',
                description: 'Lance des dilemmes et découvre les choix de la communauté !',
                theme_color: '#f97316',
                background_color: '#0f0f13',
                display: 'standalone',
                orientation: 'portrait',
                start_url: 'https://tuPrefere.com/',
                scope: 'https://tuPrefere.com/',
                lang: 'fr',
                categories: ['games', 'social'],
                icons: [
                    { src: 'icons/pwa-72x72.png',   sizes: '72x72',   type: 'image/png' },
                    { src: 'icons/pwa-96x96.png',   sizes: '96x96',   type: 'image/png' },
                    { src: 'icons/pwa-128x128.png', sizes: '128x128', type: 'image/png' },
                    { src: 'icons/pwa-144x144.png', sizes: '144x144', type: 'image/png' },
                    { src: 'icons/pwa-152x152.png', sizes: '152x152', type: 'image/png' },
                    { src: 'icons/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: 'icons/pwa-384x384.png', sizes: '384x384', type: 'image/png' },
                    { src: 'icons/pwa-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
                ],
            },

            workbox: {
                // Important pour Laravel/Inertia : pas de fallback SPA
                // car Laravel gère le routing côté serveur
                navigateFallback: null,
                globPatterns: ['**/*.{js,css,ico,png,svg,woff,woff2}'],
                runtimeCaching: [
                    {
                        // Cache les assets statiques (images uploadées)
                        urlPattern: /\/storage\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'storage-assets',
                            expiration: { maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                ],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
