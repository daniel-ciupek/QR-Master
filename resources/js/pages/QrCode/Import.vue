<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold">{{ t('csvImport.title') }}</h1>
            <p class="text-muted-foreground mt-1 text-sm">{{ t('csvImport.subtitle') }}</p>
        </div>

        <!-- Stepper -->
        <div class="flex items-center gap-2 text-sm">
            <template v-for="(s, i) in steps" :key="s">
                <div
                    :class="[
                        'flex items-center gap-1.5 rounded-full px-3 py-1 font-medium transition-colors',
                        step === s
                            ? 'bg-primary text-primary-foreground'
                            : steps.indexOf(step) > i
                                ? 'text-muted-foreground line-through'
                                : 'text-muted-foreground',
                    ]"
                >
                    <span>{{ i + 1 }}.</span>
                    <span>{{ stepLabels[s] }}</span>
                </div>
                <div v-if="i < 3" class="bg-border h-px flex-1" />
            </template>
        </div>

        <!-- Error banner -->
        <div
            v-if="error"
            class="border-destructive/50 bg-destructive/10 text-destructive rounded-lg border px-4 py-3 text-sm"
        >
            {{ error }}
        </div>

        <!-- ── Step 1: Upload ──────────────────────────────────────────── -->
        <Card v-if="step === 'upload'">
            <CardHeader>
                <CardTitle>{{ t('csvImport.stepUpload') }}</CardTitle>
                <CardDescription>{{ t('csvImport.dropzoneHint') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    class="border-border hover:border-primary/50 flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-6 py-12 transition-colors"
                    :class="{ 'border-primary bg-primary/5': isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = false"
                    @drop.prevent="onDrop"
                    @click="($refs.fileInput as HTMLInputElement).click()"
                >
                    <svg
                        class="text-muted-foreground h-10 w-10"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <span v-if="!selectedFile" class="text-muted-foreground text-sm">
                        {{ t('csvImport.dropzone') }}
                    </span>
                    <div v-else class="text-center">
                        <p class="text-sm font-medium">{{ selectedFile.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".csv,text/csv"
                        class="hidden"
                        @change="onFileInput"
                    >
                </div>

                <Button :disabled="!selectedFile || isUploading" class="w-full" @click="uploadFile">
                    {{ isUploading ? t('csvImport.importing') : t('csvImport.uploadBtn') }}
                </Button>
            </CardContent>
        </Card>

        <!-- ── Step 2: Mapping ────────────────────────────────────────── -->
        <template v-if="step === 'mapping' && uploadResult">
            <!-- Preview -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('csvImport.previewRows') }}</CardTitle>
                    <CardDescription>{{ uploadResult.total_rows }} {{ t('csvImport.totalRows') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b">
                                    <th
                                        v-for="h in uploadResult.headers"
                                        :key="h"
                                        class="text-muted-foreground px-2 py-1.5 text-left font-medium"
                                    >
                                        {{ h }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, i) in uploadResult.preview_rows"
                                    :key="i"
                                    class="border-b last:border-0"
                                >
                                    <td
                                        v-for="h in uploadResult.headers"
                                        :key="h"
                                        class="max-w-[120px] truncate px-2 py-1"
                                        :title="row[h]"
                                    >
                                        {{ row[h] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Mapping -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('csvImport.mappingTitle') }}</CardTitle>
                    <CardDescription>{{ t('csvImport.mappingHint') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
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
                            <div class="space-y-1">
                                <label class="text-sm font-medium">{{ label }}</label>
                                <select
                                    v-model="mapping[field]"
                                    class="border-input bg-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
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
                        <div class="col-span-2 space-y-1">
                            <label class="text-sm font-medium">{{ t('csvImport.defaultType') }}</label>
                            <select
                                v-model="defaultType"
                                class="border-input bg-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                            >
                                <option v-for="qt in qrTypes" :key="qt" :value="qt">{{ qt }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button variant="outline" @click="step = 'upload'">← Back</Button>
                        <Button :disabled="!canStartImport || isProcessing" class="flex-1" @click="startImport">
                            {{ isProcessing ? t('csvImport.importing') : t('csvImport.startImport') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </template>

        <!-- ── Step 3: Progress ───────────────────────────────────────── -->
        <Card v-if="step === 'progress'">
            <CardHeader>
                <CardTitle>{{ t('csvImport.stepProgress') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- Progress bar -->
                <div class="bg-secondary h-3 w-full overflow-hidden rounded-full">
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-500"
                        :style="{ width: `${progressData.progress_percent}%` }"
                    />
                </div>

                <div class="flex items-center justify-between text-sm">
                    <span>
                        <span class="text-2xl font-bold">{{ progressData.processed }}</span>
                        <span class="text-muted-foreground">
                            / {{ progressData.total }} {{ t('csvImport.progressLabel') }}
                        </span>
                    </span>
                    <Badge v-if="progressData.failed > 0" variant="destructive">
                        {{ progressData.failed }} {{ t('csvImport.failedLabel') }}
                    </Badge>
                </div>

                <p class="text-muted-foreground text-center text-sm">{{ t('csvImport.importing') }}</p>
            </CardContent>
        </Card>

        <!-- ── Step 4: Done ───────────────────────────────────────────── -->
        <Card v-if="step === 'done'">
            <CardHeader>
                <CardTitle>{{ t('csvImport.doneTitle') }}</CardTitle>
                <CardDescription>
                    {{
                        batchStatus === 'finished_with_errors'
                            ? t('csvImport.finishedWithErrors')
                            : t('csvImport.finished')
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex gap-6 text-center">
                    <div class="flex-1 rounded-lg bg-green-50 p-4 dark:bg-green-950/30">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ progressData.processed - progressData.failed }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">{{ t('csvImport.progressLabel') }}</p>
                    </div>
                    <div v-if="progressData.failed > 0" class="flex-1 rounded-lg bg-red-50 p-4 dark:bg-red-950/30">
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ progressData.failed }}</p>
                        <p class="text-muted-foreground mt-1 text-sm">{{ t('csvImport.failedLabel') }}</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button variant="outline" class="flex-1" @click="reset">
                        {{ t('csvImport.importAnother') }}
                    </Button>
                    <Button class="flex-1" @click="router.visit('/qr')">
                        {{ t('csvImport.goToList') }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
