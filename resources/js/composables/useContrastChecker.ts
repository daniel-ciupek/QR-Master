import { computed } from 'vue'
import type { MaybeRefOrGetter } from 'vue'
import { toValue } from 'vue'

function hexToRgb(hex: string): [number, number, number] | null {
    const clean = hex.replace('#', '')
    if (clean.length !== 6 && clean.length !== 3) return null

    const full = clean.length === 3
        ? clean.split('').map((c) => c + c).join('')
        : clean

    const num = parseInt(full, 16)
    return [(num >> 16) & 255, (num >> 8) & 255, num & 255]
}

function relativeLuminance(r: number, g: number, b: number): number {
    const [rs, gs, bs] = [r, g, b].map((c) => {
        const s = c / 255
        return s <= 0.04045 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4)
    }) as [number, number, number]
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs
}

function contrastRatio(l1: number, l2: number): number {
    const lighter = Math.max(l1, l2)
    const darker = Math.min(l1, l2)
    return (lighter + 0.05) / (darker + 0.05)
}

export type ContrastLevel = 'good' | 'warn' | 'fail'

export interface ContrastResult {
    ratio: number
    level: ContrastLevel
}

export function useContrastChecker(
    dotColor: MaybeRefOrGetter<string>,
    bgColor: MaybeRefOrGetter<string>,
) {
    return computed<ContrastResult | null>(() => {
        const dot = hexToRgb(toValue(dotColor))
        const bg = hexToRgb(toValue(bgColor))

        if (!dot || !bg) return null

        const ratio = contrastRatio(
            relativeLuminance(...dot),
            relativeLuminance(...bg),
        )

        const level: ContrastLevel =
            ratio >= 4.5 ? 'good' : ratio >= 3 ? 'warn' : 'fail'

        return { ratio: Math.round(ratio * 100) / 100, level }
    })
}
