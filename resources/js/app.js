import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (titulo) => titulo ? `${titulo} · SAVIX POS` : 'SAVIX POS',
    resolve: (nombre) => resolvePageComponent(`./paginas/${nombre}.vue`, import.meta.glob('./paginas/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#6C63FF' },
});
