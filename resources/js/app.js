import './bootstrap'
import '../css/app.css'
import Aura from '../css/presets/aura'
import "vue-scroll-picker/style.css";

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'
import { i18nVue } from 'laravel-vue-i18n'
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import VueScrollPicker from "vue-scroll-picker";

const appName =
    window.document.getElementsByTagName('title')[0]?.innerText || 'Luckyant Trading'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(i18nVue, {
                resolve: async lang => {
                    const langs = import.meta.glob('../../lang/*.json');
                    if (typeof langs[`../../lang/${lang}.json`] == "undefined") return; //Temporary workaround
                    return await langs[`../../lang/${lang}.json`]();
                }
            })
            .use(PrimeVue, {
                unstyled: true,
                pt: Aura                            //apply preset
            })
            .use(ConfirmationService)
            .use(VueScrollPicker)
            .mount(el)
    },
    progress: {
        color: '#074B9F'
    }
})
