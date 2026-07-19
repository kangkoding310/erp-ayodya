import '../css/app.css';
import './bootstrap';

import { autoAnimatePlugin } from '@formkit/auto-animate/vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, DefineComponent, h, Transition } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Root from './Root.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () =>
                h(Root, null, {
                    default: () =>
                        h(App, props, {
                            default: ({
                                Component,
                                props: pageProps,
                                key,
                            }: {
                                Component: DefineComponent;
                                props: Record<string, unknown>;
                                key: string;
                            }) => h(Transition, { name: 'page', mode: 'out-in' }, () => h(Component, { key, ...pageProps })),
                        }),
                }),
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia())
            .use(autoAnimatePlugin)
            .mount(el);
    },
    progress: false,
});
