import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Lato, the typeface of Allpro's own website (allpro.ie loads Lato for body and headings), in
// three weights: 400 for reading, 700 for UI, 900 for the uppercase section titles.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Lato', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 700, 900],
                    styles: ['normal'],
                    subsets: ['latin'], // includes the Irish fadas; rarer letters fall back to the system font
                    preload: [{ weight: 400 }, { weight: 900 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
