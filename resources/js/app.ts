import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createI18n } from 'vue-i18n'
import type { DefineComponent } from 'vue'
import en from './locales/en'
import pl from './locales/pl'

type SupportedLocale = 'pl' | 'en'

const savedLocale = (localStorage.getItem('qrmaster-locale') as SupportedLocale | null) ?? 'pl'

const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'pl',
    messages: { pl, en },
})

createInertiaApp({
    title: (title) => `${title} — QR-Master`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el)
    },
    progress: {
        color: '#6366f1',
    },
})
