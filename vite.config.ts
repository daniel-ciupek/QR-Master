import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
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
        tailwindcss(),
        VitePWA({
            strategies: 'generateSW',
            registerType: 'autoUpdate',
            injectRegister: 'script',
            manifest: {
                name: 'QR-Master',
                short_name: 'QR-Master',
                description: 'QR code management SaaS — create, track and analyze QR codes',
                theme_color: '#6366f1',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait-primary',
                scope: '/',
                start_url: '/dashboard',
                icons: [
                    { src: '/icons/icon-72x72.png', sizes: '72x72', type: 'image/png' },
                    { src: '/icons/icon-96x96.png', sizes: '96x96', type: 'image/png' },
                    { src: '/icons/icon-128x128.png', sizes: '128x128', type: 'image/png' },
                    { src: '/icons/icon-144x144.png', sizes: '144x144', type: 'image/png' },
                    { src: '/icons/icon-152x152.png', sizes: '152x152', type: 'image/png' },
                    { src: '/icons/icon-192x192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/icons/icon-384x384.png', sizes: '384x384', type: 'image/png' },
                    { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.(googleapis|gstatic)\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 },
                        },
                    },
                    {
                        urlPattern: /\/api\/v1\/.*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: { maxEntries: 50, maxAgeSeconds: 60 * 5 },
                            networkTimeoutSeconds: 10,
                        },
                    },
                ],
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
})
