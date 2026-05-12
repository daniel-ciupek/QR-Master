<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'

defineProps<{
    currentLogoUrl?: string | null
}>()

const emit = defineEmits<{
    upload: [file: File]
    remove: []
}>()

const { t } = useI18n()
const fileInput = ref<HTMLInputElement>()
const clientError = ref<string | null>(null)

const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']
const MAX_BYTES = 2 * 1024 * 1024

function pickFile() {
    clientError.value = null
    fileInput.value?.click()
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    if (!ALLOWED_TYPES.includes(file.type)) {
        clientError.value = t('qr.logo.errorMime')
        return
    }
    if (file.size > MAX_BYTES) {
        clientError.value = t('qr.logo.errorSize')
        return
    }

    clientError.value = null
    emit('upload', file)

    // Reset input so the same file can be re-selected
    if (fileInput.value) fileInput.value.value = ''
}
</script>

<template>
    <div class="space-y-2">
        <p class="text-sm font-medium leading-none">{{ t('qr.logo.label') }}</p>

        <!-- Preview -->
        <div
            v-if="currentLogoUrl"
            class="flex items-center gap-3 rounded-lg border border-border p-3 bg-muted/30"
        >
            <div class="size-12 rounded-md border border-border bg-white flex items-center justify-center overflow-hidden shrink-0">
                <img
                    :src="currentLogoUrl"
                    class="max-h-full max-w-full object-contain"
                    alt="Logo"
                >
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-muted-foreground truncate">{{ t('qr.logo.uploaded') }}</p>
            </div>
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive shrink-0"
                type="button"
                @click="emit('remove')"
            >
                {{ t('qr.logo.remove') }}
            </Button>
        </div>

        <!-- Upload button -->
        <div v-else class="flex items-center gap-3">
            <Button
                variant="outline"
                size="sm"
                type="button"
                @click="pickFile"
            >
                {{ t('qr.logo.upload') }}
            </Button>
            <span class="text-xs text-muted-foreground">PNG, JPG, SVG, WEBP · max 2 MB</span>
        </div>

        <p v-if="clientError" class="text-xs text-destructive">{{ clientError }}</p>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/png,image/jpeg,image/webp,image/svg+xml"
            class="hidden"
            @change="onFileChange"
        >
    </div>
</template>
