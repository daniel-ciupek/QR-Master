import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

type SupportedLocale = 'pl' | 'en'

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
