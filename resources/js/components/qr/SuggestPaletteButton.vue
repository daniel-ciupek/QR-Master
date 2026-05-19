<script setup lang="ts">
import { ref } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { useI18n } from 'vue-i18n'

interface Palette {
    name: string
    dotColor: string
    bgColor: string
}

const props = defineProps<{
    logoDataUrl?: string | null
}>()

const emit = defineEmits<{
    apply: [dotColor: string, bgColor: string]
}>()

const { t } = useI18n()

const open = ref(false)
const loading = ref(false)
const palettes = ref<Palette[]>([])
const wrapperRef = ref<HTMLElement>()

onClickOutside(wrapperRef, () => { open.value = false })

function parseDataUrl(dataUrl: string): { base64: string; mimeType: string } | null {
    const match = dataUrl.match(/^data:([^;]+);base64,(.+)$/)
    if (!match || !match[1] || !match[2]) return null
    return { mimeType: match[1], base64: match[2] }
}

async function suggest() {
    if (loading.value) return
    loading.value = true
    open.value = false

    try {
        const body: Record<string, string> = {}
        if (props.logoDataUrl) {
            const parsed = parseDataUrl(props.logoDataUrl)
            if (parsed) {
                body.logo_base64 = parsed.base64
                body.mime_type = parsed.mimeType
            }
        }

        const res = await fetch('/api/ai/suggest-palette', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
        const data = await res.json() as { palettes: Palette[] }
        palettes.value = data.palettes
        open.value = true
    } finally {
        loading.value = false
    }
}

function apply(p: Palette) {
    emit('apply', p.dotColor, p.bgColor)
    open.value = false
}
</script>

<template>
    <div ref="wrapperRef" class="relative inline-block">
        <button
            type="button"
            :disabled="loading"
            class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1.5 text-xs font-medium hover:bg-muted/50 transition-colors disabled:opacity-50"
            @click="suggest"
        >
            <span class="text-sm">✨</span>
            {{ loading ? t('qr.ai.suggesting') : t('qr.ai.suggest') }}
        </button>

        <!-- Palette popover -->
        <Transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open && palettes.length > 0"
                class="absolute top-full left-0 mt-1.5 z-50 w-56 rounded-xl border border-border bg-popover p-3 shadow-lg space-y-2"
            >
                <p class="text-xs font-medium text-muted-foreground">{{ t('qr.ai.pickPalette') }}</p>
                <div class="space-y-1">
                    <button
                        v-for="p in palettes"
                        :key="p.name"
                        type="button"
                        class="flex items-center gap-2 w-full rounded-md px-2 py-1.5 text-xs hover:bg-muted/50 transition-colors text-left"
                        @click="apply(p)"
                    >
                        <span class="flex gap-0.5 shrink-0">
                            <span class="size-4 rounded-sm border border-black/10" :style="{ backgroundColor: p.dotColor }" />
                            <span class="size-4 rounded-sm border border-black/10" :style="{ backgroundColor: p.bgColor }" />
                        </span>
                        {{ p.name }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
