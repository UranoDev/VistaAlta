import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Auto-hospedadas desde node_modules (@fontsource), no desde un CDN:
                // el sitio se lee en celular y no debe depender de un tercero.
                // Los alias no son `sans`/`mono` a propósito: así el plugin no
                // pisa las variables de Tailwind y `app.css` puede componer las
                // dos, quedándose con las métricas de respaldo de fontaine.
                fontsource('IBM Plex Sans', {
                    alias: 'plex-sans',
                    weights: [400, 500, 600, 700],
                    fallbacks: ['Segoe UI', 'Roboto', 'Arial'],
                }),
                fontsource('IBM Plex Mono', {
                    alias: 'plex-mono',
                    weights: [400, 500, 600, 700],
                    fallbacks: ['SFMono-Regular', 'Consolas'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
