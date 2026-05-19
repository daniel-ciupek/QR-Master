<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { FileUp, Check, Loader2, AlertCircle, CheckCircle2, ArrowLeft, Play } from 'lucide-vue-next'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { PageProps } from '@/types'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

// Ziggy's route() is injected globally via Blade @routes — safe to cast here
const ziggyRoute = window.route as ((name: string, params?: Record<string, string | number | boolean>) => string)
const page = usePage<PageProps>()
const userId = computed(() => page.props.auth.user?.id ?? 0)

// ── Types ────────────────────────────────────────────────────────────────────

type Step = 'upload' | 'mapping' | 'progress' | 'done'
type BatchStatus = 'processing' | 'finished' | 'finished_with_errors' | 'cancelled'

interface UploadResult {
    session_key: string
    headers: string[]
    preview_rows: Record<string, string>[]
    total_rows: number
}

interface Mapping {
    title: string
    destination_url: string
    type: string
    is_active: string
    expires_at: string
    scan_limit: string
}

// ── State ────────────────────────────────────────────────────────────────────

const step = ref<Step>('upload')
const isDragging = ref(false)
const isUploading = ref(false)
const isProcessing = ref(false)
const error = ref('')

const selectedFile = ref<File | null>(null)
const uploadResult = ref<UploadResult | null>(null)

const mapping = ref<Mapping>({
    title: '',
    destination_url: '',
    type: '',
    is_active: '',
    expires_at: '',
    scan_limit: '',
})
const defaultType = ref('url')

const batchId = ref('')
const progressData = ref({ processed: 0, total: 0, failed: 0, progress_percent: 0 })
const batchStatus = ref<BatchStatus>('processing')

let pollInterval: ReturnType<typeof setInterval> | null = null
let echoChannel: { stopListening: (e: string) => void; unsubscribe: () => void } | null = null

// ── Helpers ──────────────────────────────────────────────────────────────────

const qrTypes = ['url', 'text', 'email', 'phone', 'sms', 'vcard', 'wifi', 'geo', 'app', 'calendar', 'crypto', 'review']

const steps: Step[] = ['upload', 'mapping', 'progress', 'done']

const stepLabels: Record<Step, string> = {
    upload: t('csvImport.stepUpload'),
    mapping: t('csvImport.stepMapping'),
    progress: t('csvImport.stepProgress'),
    done: t('csvImport.stepDone'),
}

const canStartImport = computed(() => !!mapping.value.title)

const mappingPayload = computed(() => {
    const m: Record<string, string> = {}
    const entries = Object.entries(mapping.value) as [string, string][]
    for (const [field, col] of entries) {
        if (col) m[field] = col
    }
    return m
})

// ── Step 1: Upload ───────────────────────────────────────────────────────────

function onDrop(e: DragEvent) {
    isDragging.value = false
    const file = e.dataTransfer?.files[0]
    if (file) selectFile(file)
}

function onFileInput(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) selectFile(file)
}

function selectFile(file: File) {
    selectedFile.value = file
    error.value = ''
}

async function uploadFile() {
    if (!selectedFile.value) return
    isUploading.value = true
    error.value = ''

    const form = new FormData()
    form.append('csv_file', selectedFile.value)
    form.append('_token', document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '')

    try {
        const res = await fetch(ziggyRoute('qr.import.upload'), { method: 'POST', body: form })
        const data = await res.json() as UploadResult & { message?: string }

        if (!res.ok) {
            error.value = data.message ?? t('csvImport.errorUpload')
            return
        }

        uploadResult.value = data
        autoDetectMapping(data.headers)
        step.value = 'mapping'
    } catch {
        error.value = t('csvImport.errorUpload')
    } finally {
        isUploading.value = false
    }
}

function autoDetectMapping(headers: string[]) {
    const find = (...candidates: string[]) =>
        headers.find((h) => candidates.some((c) => h.toLowerCase().includes(c))) ?? ''

    mapping.value.title = find('title', 'name', 'nazwa')
    mapping.value.destination_url = find('url', 'link', 'destination', 'href')
    mapping.value.type = find('type', 'typ')
    mapping.value.is_active = find('active', 'aktywny', 'enabled')
    mapping.value.expires_at = find('expire', 'wygasa', 'expiry')
    mapping.value.scan_limit = find('limit', 'scan_limit')
}

// ── Step 2: Process ──────────────────────────────────────────────────────────

async function startImport() {
    if (!uploadResult.value || !canStartImport.value) return
    isProcessing.value = true
    error.value = ''

    try {
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
        const res = await fetch(ziggyRoute('qr.import.process'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                session_key: uploadResult.value.session_key,
                mapping: mappingPayload.value,
                default_type: defaultType.value,
            }),
        })

        const data = (await res.json()) as { batch_id: string; total: number; message?: string }

        if (!res.ok) {
            error.value = data.message ?? t('csvImport.errorProcess')
            return
        }

        batchId.value = data.batch_id
        progressData.value = { processed: 0, total: data.total, failed: 0, progress_percent: 0 }
        step.value = 'progress'

        subscribeReverb()
        startPolling()
    } catch {
        error.value = t('csvImport.errorProcess')
    } finally {
        isProcessing.value = false
    }
}

// ── Step 3: Progress via Reverb + polling ────────────────────────────────────

interface ProgressPayload {
    batch_id: string
    processed: number
    total: number
    failed: number
    progress_percent: number
}

function subscribeReverb() {
    try {
        const ch = window.Echo.private(`csv-import.${userId.value}`)
        ch.listen('.progress', (data: ProgressPayload) => {
            progressData.value = {
                processed: data.processed,
                total: data.total,
                failed: data.failed,
                progress_percent: data.progress_percent,
            }
            if (data.processed >= data.total) finalizeBatch('finished')
        })
        echoChannel = ch
    } catch {
        // Reverb unavailable — polling only
    }
}

function startPolling() {
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch(ziggyRoute('qr.import.status', { batchId: batchId.value }), {
                headers: { Accept: 'application/json' },
            })
            const d = (await res.json()) as {
                status: BatchStatus
                total: number
                processed: number
                failed: number
                progress_percent: number
            }

            progressData.value = {
                processed: d.processed,
                total: d.total,
                failed: d.failed,
                progress_percent: d.progress_percent,
            }

            if (d.status !== 'processing') finalizeBatch(d.status)
        } catch {
            // ignore
        }
    }, 2000)
}

function finalizeBatch(status: BatchStatus) {
    clearInterval(pollInterval!)
    pollInterval = null
    echoChannel?.stopListening('.progress')
    echoChannel?.unsubscribe()
    echoChannel = null
    batchStatus.value = status
    step.value = 'done'
}

onUnmounted(() => {
    clearInterval(pollInterval!)
    echoChannel?.stopListening('.progress')
    echoChannel?.unsubscribe()
})

function reset() {
    step.value = 'upload'
    selectedFile.value = null
    uploadResult.value = null
    error.value = ''
    batchId.value = ''
    progressData.value = { processed: 0, total: 0, failed: 0, progress_percent: 0 }
    batchStatus.value = 'processing'
}
</script>

<template>
    <Head :title="t('csvImport.pageTitle')" />

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-4 md:py-8">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20 shrink-0">
                    <FileUp class="size-5 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('csvImport.title') }}
                    </h1>
                    <p class="text-muted-foreground mt-0.5 text-sm">{{ t('csvImport.subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- Stepper -->
        <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
            <div class="flex items-center gap-1 overflow-x-auto">
                <template v-for="(s, i) in steps" :key="s">
                    <div
                        :class="[
                            'flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-all duration-200 shrink-0',
                            step === s
                                ? 'bg-primary text-primary-foreground shadow-[0_0_12px_oklch(0.66_0.25_285/0.3)]'
                                : steps.indexOf(step) > i
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-muted-foreground',
                        ]"
                    >
                        <span
                            v-if="steps.indexOf(step) > i"
                            class="flex size-4 items-center justify-center rounded-full bg-primary/20"
                        >
                            <Check class="size-3 text-primary" />
                        </span>
                        <span v-else class="text-xs font-mono">{{ i + 1 }}</span>
                        <span>{{ stepLabels[s] }}</span>
                    </div>
                    <div
                        v-if="i < 3"
                        class="h-px flex-1 min-w-4 transition-colors duration-300"
                        :class="steps.indexOf(step) > i ? 'bg-primary/30' : 'bg-border'"
                    />
                </template>
            </div>
        </div>

        <!-- Error banner -->
        <div
            v-if="error"
            class="relative rounded-xl border border-destructive/40 bg-destructive/10 p-4 overflow-hidden"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/50 to-transparent" />
            <div class="flex items-start gap-3">
                <AlertCircle class="size-4 text-destructive shrink-0 mt-0.5" />
                <p class="text-sm text-destructive">{{ error }}</p>
            </div>
        </div>

        <!-- ── Step 1: Upload ──────────────────────────────────────────── -->
        <div v-if="step === 'upload'" class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
            <div class="p-5 md:p-6">
                <p class="text-base font-semibold mb-1">{{ t('csvImport.stepUpload') }}</p>
                <p class="text-sm text-muted-foreground mb-5">{{ t('csvImport.dropzoneHint') }}</p>

                <!-- Dropzone -->
                <div
                    class="flex cursor-pointer flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed px-6 py-12 text-center transition-all duration-200"
                    :class="isDragging
                        ? 'border-primary bg-primary/5 shadow-[0_0_20px_oklch(0.66_0.25_285/0.15)]'
                        : 'border-border hover:border-primary/50 hover:bg-primary/5'"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = false"
                    @drop.prevent="onDrop"
                    @click="($refs.fileInput as HTMLInputElement).click()"
                >
                    <div
                        class="flex size-16 items-center justify-center rounded-2xl ring-1 transition-all duration-200"
                        :class="selectedFile
                            ? 'bg-primary/10 ring-primary/30'
                            : isDragging
                                ? 'bg-primary/10 ring-primary/30'
                                : 'bg-muted ring-border'"
                    >
                        <FileUp
                            class="size-8 transition-colors duration-200"
                            :class="selectedFile || isDragging ? 'text-primary' : 'text-muted-foreground'"
                        />
                    </div>

                    <div v-if="!selectedFile">
                        <p class="text-sm font-medium text-foreground">{{ t('csvImport.dropzone') }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">CSV, max 5MB</p>
                    </div>
                    <div v-else class="text-center">
                        <p class="text-sm font-semibold text-foreground">{{ selectedFile.name }}</p>
                        <p class="text-muted-foreground text-xs mt-1">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                    </div>

                    <input
                        ref="fileInput"
                        type="file"
                        accept=".csv,text/csv"
                        class="hidden"
                        @change="onFileInput"
                    >
                </div>

                <Button
                    :disabled="!selectedFile || isUploading"
                    class="mt-5 w-full gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    @click="uploadFile"
                >
                    <Loader2 v-if="isUploading" class="size-4 animate-spin" />
                    <FileUp v-else class="size-4" />
                    <span>{{ isUploading ? t('csvImport.importing') : t('csvImport.uploadBtn') }}</span>
                </Button>
            </div>
        </div>

        <!-- ── Step 2: Mapping ────────────────────────────────────────── -->
        <template v-if="step === 'mapping' && uploadResult">
            <!-- Preview -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm font-semibold">{{ t('csvImport.previewRows') }}</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-cyan-400/10 px-2.5 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/20">
                            {{ uploadResult.total_rows }} {{ t('csvImport.totalRows') }}
                        </span>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-border">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th
                                        v-for="h in uploadResult.headers"
                                        :key="h"
                                        class="text-muted-foreground px-3 py-2 text-left font-medium whitespace-nowrap"
                                    >
                                        {{ h }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, i) in uploadResult.preview_rows"
                                    :key="i"
                                    class="border-t border-border/60 hover:bg-muted/30 transition-colors duration-100"
                                >
                                    <td
                                        v-for="h in uploadResult.headers"
                                        :key="h"
                                        class="max-w-[140px] truncate px-3 py-1.5 text-muted-foreground"
                                        :title="row[h]"
                                    >
                                        {{ row[h] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mapping -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <div class="p-5">
                    <p class="text-sm font-semibold mb-1">{{ t('csvImport.mappingTitle') }}</p>
                    <p class="text-xs text-muted-foreground mb-5">{{ t('csvImport.mappingHint') }}</p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <template
                            v-for="[field, label] in ([
                                ['title', t('csvImport.fieldTitle')],
                                ['destination_url', t('csvImport.fieldDestUrl')],
                                ['type', t('csvImport.fieldType')],
                                ['expires_at', t('csvImport.fieldExpiresAt')],
                                ['is_active', t('csvImport.fieldIsActive')],
                                ['scan_limit', t('csvImport.fieldScanLimit')],
                            ] as [keyof Mapping, string][])"
                            :key="field"
                        >
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">{{ label }}</label>
                                <select
                                    v-model="mapping[field]"
                                    class="border-input bg-background focus:ring-primary/50 focus:border-primary/50 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:outline-none transition-[border-color,box-shadow] duration-150"
                                >
                                    <option value="">{{ t('csvImport.noColumn') }}</option>
                                    <option
                                        v-for="h in uploadResult.headers"
                                        :key="h"
                                        :value="h"
                                    >
                                        {{ h }}
                                    </option>
                                </select>
                            </div>
                        </template>

                        <!-- Default type -->
                        <div class="col-span-1 sm:col-span-2 space-y-1.5">
                            <label class="text-sm font-medium">{{ t('csvImport.defaultType') }}</label>
                            <select
                                v-model="defaultType"
                                class="border-input bg-background focus:ring-primary/50 focus:border-primary/50 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:outline-none transition-[border-color,box-shadow] duration-150"
                            >
                                <option v-for="qt in qrTypes" :key="qt" :value="qt">{{ qt }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-4 sm:flex-row">
                        <Button
                            variant="outline"
                            class="gap-2 hover:border-border/80 transition-colors duration-150"
                            @click="step = 'upload'"
                        >
                            <ArrowLeft class="size-4" />
                            <span>{{ t('ui.back') ?? 'Back' }}</span>
                        </Button>
                        <Button
                            :disabled="!canStartImport || isProcessing"
                            class="flex-1 gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                            @click="startImport"
                        >
                            <Loader2 v-if="isProcessing" class="size-4 animate-spin" />
                            <Play v-else class="size-4" />
                            <span>{{ isProcessing ? t('csvImport.importing') : t('csvImport.startImport') }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </template>

        <!-- ── Step 3: Progress ───────────────────────────────────────── -->
        <div v-if="step === 'progress'" class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <div class="p-5 md:p-6 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                        <Loader2 class="size-5 text-primary animate-spin" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ t('csvImport.stepProgress') }}</p>
                        <p class="text-xs text-muted-foreground">{{ t('csvImport.importing') }}</p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="bg-muted h-3 w-full overflow-hidden rounded-full">
                    <div
                        class="bg-gradient-to-r from-primary to-violet-400 h-full rounded-full transition-all duration-500 shadow-[0_0_8px_oklch(0.66_0.25_285/0.4)]"
                        :style="{ width: `${progressData.progress_percent}%` }"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm">
                        <span class="text-2xl font-bold text-foreground">{{ progressData.processed }}</span>
                        <span class="text-muted-foreground ml-1">
                            / {{ progressData.total }} {{ t('csvImport.progressLabel') }}
                        </span>
                    </span>
                    <Badge
                        v-if="progressData.failed > 0"
                        class="bg-destructive/10 text-destructive border-destructive/30 border"
                    >
                        {{ progressData.failed }} {{ t('csvImport.failedLabel') }}
                    </Badge>
                </div>
            </div>
        </div>

        <!-- ── Step 4: Done ───────────────────────────────────────────── -->
        <div v-if="step === 'done'" class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div
                :class="batchStatus === 'finished_with_errors'
                    ? 'bg-gradient-to-r from-transparent via-gold-500/50 to-transparent'
                    : 'bg-gradient-to-r from-transparent via-green-500/50 to-transparent'"
                class="absolute inset-x-0 top-0 h-px"
            />
            <div class="p-5 md:p-6 space-y-5">
                <!-- Status icon -->
                <div class="flex items-center gap-3">
                    <div
                        :class="batchStatus === 'finished_with_errors'
                            ? 'bg-gold-500/10 ring-gold-500/20'
                            : 'bg-green-500/10 ring-green-500/20'"
                        class="flex size-10 items-center justify-center rounded-full ring-1"
                    >
                        <CheckCircle2
                            :class="batchStatus === 'finished_with_errors' ? 'text-gold-500' : 'text-green-400'"
                            class="size-5"
                        />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ t('csvImport.doneTitle') }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                batchStatus === 'finished_with_errors'
                                    ? t('csvImport.finishedWithErrors')
                                    : t('csvImport.finished')
                            }}
                        </p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="relative rounded-xl border border-green-500/20 bg-green-500/5 p-4 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-green-500/40 to-transparent" />
                        <p class="text-3xl font-bold text-green-400">
                            {{ progressData.processed - progressData.failed }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">{{ t('csvImport.progressLabel') }}</p>
                    </div>
                    <div v-if="progressData.failed > 0" class="relative rounded-xl border border-destructive/20 bg-destructive/5 p-4 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/40 to-transparent" />
                        <p class="text-3xl font-bold text-destructive">{{ progressData.failed }}</p>
                        <p class="text-muted-foreground mt-1 text-sm">{{ t('csvImport.failedLabel') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Button
                        variant="outline"
                        class="flex-1 hover:border-border/80 transition-colors duration-150"
                        @click="reset"
                    >
                        {{ t('csvImport.importAnother') }}
                    </Button>
                    <Button
                        class="flex-1 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="router.visit('/qr')"
                    >
                        {{ t('csvImport.goToList') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
