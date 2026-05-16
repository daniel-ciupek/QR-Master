import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

export type SupportedLocale = 'pl' | 'en' | 'de' | 'es' | 'fr' | 'it'

export function useLocale() {
    const { locale } = useI18n()

    function setLocale(lang: SupportedLocale): void {
        locale.value = lang
        localStorage.setItem('qrmaster-locale', lang)
        router.post('/locale', { locale: lang }, { preserveState: true, preserveScroll: true })
    }

    return {
        locale,
        setLocale,
    }
}
