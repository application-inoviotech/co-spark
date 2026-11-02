import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// Only the admin panel (Inertia + Vue) is bundled; the public site uses the approved static assets.
export default defineConfig({
    plugins: [
        laravel({ input: ['resources/js/admin/app.js'], refresh: true }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
    ],
    resolve: { alias: { '@': '/resources/js/admin' } },
});
