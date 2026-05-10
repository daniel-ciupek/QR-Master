import { computed, ref } from 'vue'

type ColorMode = 'light' | 'dark' | 'system'

const STORAGE_KEY = 'qrmaster-color-mode'

function getStored(): ColorMode {
    return (localStorage.getItem(STORAGE_KEY) as ColorMode) ?? 'system'
}

function isDarkPreferred(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function applyDarkClass(m: ColorMode): void {
    const dark = m === 'dark' || (m === 'system' && isDarkPreferred())
    document.documentElement.classList.toggle('dark', dark)
}

// Module-level singleton — shared across all component instances
const mode = ref<ColorMode>(getStored())

applyDarkClass(mode.value)

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (mode.value === 'system') applyDarkClass('system')
})

export function useColorMode() {
    function setMode(newMode: ColorMode): void {
        mode.value = newMode
        localStorage.setItem(STORAGE_KEY, newMode)
        applyDarkClass(newMode)
    }

    return {
        mode: computed(() => mode.value),
        setMode,
    }
}
