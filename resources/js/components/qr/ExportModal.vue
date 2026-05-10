<script setup lang="ts">
import type { ErrorCorrectionLevel } from 'qr-code-styling'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import LivePreview from '@/components/qr/LivePreview.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

interface Props {
    open: boolean
    data: string
    eccLevel: ErrorCorrectionLevel
}

const props = defineProps<Props>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const { t } = useI18n()

type ExportFormat = 'png' | 'svg' | 'pdf' | 'eps'

const RESOLUTIONS = [512, 1024, 2048, 4096] as const
type Resolution = (typeof RESOLUTIONS)[number]

const resolution = ref<Resolution>(1024)
const downloading = ref<ExportFormat | null>(null)

// PNG scale: qr-code-styling renders at 1px/module by default at size=resolution
// We pass size directly to LivePreview which uses scale internally via the Canvas
const previewRef = ref<InstanceType<typeof LivePreview>>()

const formats: { id: ExportFormat; label: string; description: string }[] = [
    { id: 'png', label: 'PNG', description: 'Raster, ideal for web & print' },
    { id: 'svg', label: 'SVG', description: 'Vector, scales to any size' },
    { id: 'pdf', label: 'PDF', description: 'Print-ready, A-series compatible' },
    { id: 'eps', label: 'EPS', description: 'Professional pre-press format' },
]

async function download(format: ExportFormat): Promise<void> {
    if (downloading.value) return
    downloading.value = format

    try {
        if (format === 'png') {
            // Client-side: re-render at chosen resolution then download
            const tempQr = new (await import('qr-code-styling')).default({
                type: 'canvas',
                width: resolution.value,
                height: resolution.value,
                data: props.data,
                margin: Math.round(resolution.value * 0.03),
                qrOptions: { errorCorrectionLevel: props.eccLevel },
            })
            await tempQr.download({ extension: 'png', name: 'qrcode' })
        } else if (format === 'svg') {
            await previewRef.value?.download('svg', 'qrcode')
        } else {
            // PDF and EPS: backend POST form submit
            const csrfMeta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
            const form = document.createElement('form')
            form.method = 'POST'
            form.action = '/qr/export'

            const fields: Record<string, string> = {
                _token: csrfMeta?.content ?? '',
                data: props.data,
                ecc: props.eccLevel,
                format,
            }

            for (const [key, val] of Object.entries(fields)) {
                const input = document.createElement('input')
                input.type = 'hidden'
                input.name = key
                input.value = val
                form.appendChild(input)
            }

            document.body.appendChild(form)
            form.submit()
            document.body.removeChild(form)
        }
    } finally {
        setTimeout(() => { downloading.value = null }, 1200)
    }
}
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[540px]">
            <DialogHeader>
                <DialogTitle>{{ t('qr.export.title') }}</DialogTitle>
                <DialogDescription class="sr-only">
                    {{ t('qr.export.title') }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-6">
                <!-- QR preview -->
                <div class="flex justify-center">
                    <div class="rounded-xl border border-border bg-white p-4 dark:bg-white">
                        <LivePreview
                            ref="previewRef"
                            :data="props.data"
                            :size="200"
                            :error-correction-level="props.eccLevel"
                        />
                    </div>
                </div>

                <!-- Resolution selector (PNG only) -->
                <div class="space-y-2">
                    <p class="text-sm font-medium">{{ t('qr.export.resolution') }}</p>
                    <div class="flex gap-2">
                        <button
                            v-for="res in RESOLUTIONS"
                            :key="res"
                            type="button"
                            :class="[
                                'flex-1 rounded-md border px-2 py-1.5 text-xs font-mono font-semibold transition-colors',
                                resolution === res
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'border-border text-muted-foreground hover:border-ring hover:text-foreground',
                            ]"
                            @click="resolution = res"
                        >
                            {{ res }}px
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('qr.export.resolutionHint') }}</p>
                </div>

                <!-- Format download buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <Button
                        v-for="fmt in formats"
                        :key="fmt.id"
                        variant="outline"
                        :disabled="downloading !== null"
                        class="flex flex-col h-auto py-3 gap-0.5"
                        @click="download(fmt.id)"
                    >
                        <span class="text-sm font-bold">
                            {{ downloading === fmt.id ? t('qr.export.downloading') : t(`qr.export.${fmt.id}`) }}
                        </span>
                        <span class="text-xs text-muted-foreground font-normal">{{ fmt.description }}</span>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
