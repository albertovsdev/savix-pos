import '../css/app.css';
import '../css/tema-premium.css';
import { createApp, h, watchEffect } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const aplicarApariencia = (apariencia) => {
    if (!apariencia) {
        return;
    }

    const raiz = document.documentElement;
    raiz.style.setProperty('--savix-pos-marca', apariencia.color_primario);
    raiz.style.setProperty('--savix-pos-color-deep', apariencia.color_secundario);
    raiz.style.setProperty('--savix-pos-color-violet-soft', apariencia.color_acento);
    raiz.dataset.tema = apariencia.tema_predeterminado;
};

createInertiaApp({
    title: (titulo) => titulo ? `${titulo} · SAVIX POS` : 'SAVIX POS',
    resolve: (nombre) => resolvePageComponent(`./paginas/${nombre}.vue`, import.meta.glob('./paginas/**/*.vue')),
    setup({ el, App, props, plugin }) {
        watchEffect(() => aplicarApariencia(props.initialPage.props.apariencia));

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#6C63FF' },
});
