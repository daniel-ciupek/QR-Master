<script setup lang="ts">
import { CloudOff, RefreshCw, Trash2, Wifi } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import type { QrDraft } from '@/composables/useOfflineDrafts'

defineProps<{
    isOnline: boolean
    drafts: QrDraft[]
}>()

const emit = defineEmits<{
    sync: []
    deleteDraft: [id: string]
    loadDraft: [draft: QrDraft]
}>()

const { t } = useI18n()

function formatTime(ts: number): string {
    const d = new Date(ts)
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <!-- Offline warning -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="-translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-full opacity-0"
    >
        <div
            v-if="!isOnline"
            class="flex items-center gap-3 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-orange-800 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200"
        >
            <CloudOff class="size-4 shrink-0" />
            <span class="text-sm font-medium">{{ t('offline.noConnection') }}</span>
            <span class="text-sm opacity-75">{{ t('offline.draftsWillSync') }}</span>
        </div>
    </Transition>

    <!-- Pending drafts (when back online) -->
    <div
        v-if="isOnline && drafts.length > 0"
        class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                <Wifi class="size-4" />
                <span class="text-sm font-medium">
                    {{ t('offline.pendingDrafts', { n: drafts.length }) }}
                </span>
            </div>
            <Button
                size="sm"
                variant="outline"
                class="h-7 gap-1.5 text-xs"
                @click="emit('sync')"
            >
                <RefreshCw class="size-3" />
                {{ t('offline.syncNow') }}
            </Button>
        </div>

        <!-- Draft list -->
        <div class="mt-2 space-y-1">
            <div
                v-for="draft in drafts"
                :key="draft.id"
                class="flex items-center gap-2 rounded px-2 py-1 text-sm text-blue-700 hover:bg-blue-100 dark:text-blue-300 dark:hover:bg-blue-900"
            >
                <button
                    class="flex-1 truncate text-left"
                    @click="emit('loadDraft', draft)"
                >
                    {{ draft.title || t('offline.untitledDraft') }}
                    <span class="ml-2 text-xs opacity-60">{{ formatTime(draft.savedAt) }}</span>
                </button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-6 text-blue-500 hover:text-destructive"
                    @click="emit('deleteDraft', draft.id)"
                >
                    <Trash2 class="size-3" />
                </Button>
            </div>
        </div>
    </div>
</template>
