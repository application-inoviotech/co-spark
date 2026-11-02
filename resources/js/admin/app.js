import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy';
import AdminLayout from './Layouts/AdminLayout.vue';

createInertiaApp({
    title: title => (title ? `${title} — COSPARK Admin` : 'COSPARK Admin'),
    resolve: name => {
        const page = import.meta.glob('./Pages/**/*.vue', { eager: true })[`./Pages/${name}.vue`];
        page.default.layout ??= AdminLayout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).use(ZiggyVue).mount(el);
    },
    progress: { color: '#C8753D' },
});
