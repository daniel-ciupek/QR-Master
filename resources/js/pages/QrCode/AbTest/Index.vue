<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface VariantProp {
    id: number
    name: string
    destination_url: string
    weight: number
    scan_count: number
    is_winner: boolean
    pct_weight: number
    pct_scans: number
}

interface AbTestProp {
    id: number
    is_active: boolean
    auto_select_winner: boolean
    auto_select_threshold: number
    has_winner: boolean
}

interface QrCodeProp {
    id: number
    title: string
    destination_url: string | null
}

const props = defineProps<{
    qrCode: QrCodeProp
    abTest: AbTestProp
    variants: VariantProp[]
    totalScans: number
}>()

// ── Settings form ─────────────────────────────────────────────────────
const settingsForm = useForm({
    is_active: props.abTest.is_active,
    auto_select_winner: props.abTest.auto_select_winner,
    auto_select_threshold: props.abTest.auto_select_threshold,
})

function saveSettings() {
    settingsForm.patch(`/qr/${props.qrCode.id}/ab-test`)
}

// ── Variant forms ─────────────────────────────────────────────────────
const showAddForm = ref(false)
const editingId = ref<number | null>(null)

const addForm = useForm({ name: '', destination_url: '', weight: 50 })
const editForm = useForm({ name: '', destination_url: '', weight: 50 })

function submitAdd() {
    addForm.post(`/qr/${props.qrCode.id}/ab-test/variants`, {
        preserveScroll: true,
        onSuccess: () => { addForm.reset(); showAddForm.value = false },
    })
}

function startEdit(v: VariantProp) {
    editingId.value = v.id
    editForm.name = v.name
    editForm.destination_url = v.destination_url
    editForm.weight = v.weight
}

function submitEdit(v: VariantProp) {
    editForm.patch(`/qr/${props.qrCode.id}/ab-test/variants/${v.id}`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; editForm.reset() },
    })
}

function deleteVariant(v: VariantProp) {
    if (!confirm(t('abTest.deleteVariantConfirm'))) return
    router.delete(`/qr/${props.qrCode.id}/ab-test/variants/${v.id}`, { preserveScroll: true })
}

function selectWinner(v: VariantProp) {
    if (!confirm(t('abTest.selectWinnerConfirm', { name: v.name }))) return
    router.post(`/qr/${props.qrCode.id}/ab-test/winner`, { variant_id: v.id }, { preserveScroll: true })
}

// ── Statistical significance (z-test for 2 proportions) ──────────────
const significance = computed<{ z: number; label: string; color: string } | null>(() => {
    if (props.variants.length !== 2 || props.totalScans < 30) return null
    const [a, b] = props.variants as [VariantProp, VariantProp]
    const n = props.totalScans
    const p1 = a.scan_count / n
    const p2 = b.scan_count / n
    const pPool = 0.5
    const se = Math.sqrt(pPool * (1 - pPool) * (2 / n))
    if (se === 0) return null
    const z = Math.abs(p1 - p2) / se

    if (z >= 2.58) return { z, label: t('abTest.significance.high'), color: 'text-green-600 dark:text-green-400' }
    if (z >= 1.96) return { z, label: t('abTest.significance.medium'), color: 'text-yellow-600 dark:text-yellow-400' }
    return { z, label: t('abTest.significance.low'), color: 'text-muted-foreground' }
})

const leader = computed<VariantProp | null>(() => {
    if (props.variants.length === 0 || props.totalScans === 0) return null
    return [...props.variants].sort((a, b) => b.pct_scans - a.pct_scans)[0] ?? null
})
</script>

<template>
    <Head :title="`A/B Test — ${props.qrCode.title}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">{{ t('abTest.title') }}: {{ props.qrCode.title }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('abTest.description') }}</p>
            </div>
            <Button
                variant="outline"
                size="sm"
                as="a"
                :href="`/qr/${props.qrCode.id}/edit`"
            >
                ← {{ t('abTest.backToEdit') }}
            </Button>
        </div>

        <!-- Settings -->
        <div class="rounded-lg border border-border bg-card p-4 space-y-3">
            <p class="text-sm font-semibold">{{ t('abTest.settings') }}</p>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <input
                        id="ab-active"
                        v-model="settingsForm.is_active"
                        type="checkbox"
                        class="rounded"
                    >
                    <label for="ab-active" class="text-sm">{{ t('abTest.active') }}</label>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        id="ab-auto"
                        v-model="settingsForm.auto_select_winner"
                        type="checkbox"
                        class="rounded"
                    >
                    <label for="ab-auto" class="text-sm">{{ t('abTest.autoSelectWinner') }}</label>
                </div>
            </div>
            <div v-if="settingsForm.auto_select_winner" class="flex items-center gap-2">
                <label class="text-sm text-muted-foreground">{{ t('abTest.threshold') }}</label>
                <Input
                    v-model.number="settingsForm.auto_select_threshold"
                    type="number"
                    min="10"
                    class="w-28"
                />
                <span class="text-sm text-muted-foreground">{{ t('abTest.scans') }}</span>
            </div>
            <Button size="sm" :disabled="settingsForm.processing" @click="saveSettings">{{ t('common.save') }}</Button>
        </div>

        <!-- Stats summary -->
        <div v-if="props.totalScans > 0" class="rounded-lg border border-border bg-muted/30 px-4 py-3 space-y-1">
            <div class="flex items-center justify-between text-sm">
                <span>{{ t('abTest.totalScans') }}: <strong>{{ props.totalScans }}</strong></span>
                <span v-if="significance" :class="significance.color" class="text-xs font-medium">
                    {{ significance.label }} (Z={{ significance.z.toFixed(2) }})
                </span>
            </div>
            <p v-if="leader && !props.abTest.has_winner" class="text-xs text-muted-foreground">
                {{ t('abTest.leading') }}: <strong>{{ leader.name }}</strong> ({{ leader.pct_scans }}%)
            </p>
        </div>

        <!-- Variants -->
        <div class="space-y-3">
            <p class="text-sm font-semibold">{{ t('abTest.variants') }}</p>

            <div v-if="props.variants.length === 0" class="rounded-lg border border-dashed border-border p-6 text-center">
                <p class="text-sm text-muted-foreground">{{ t('abTest.noVariants') }}</p>
            </div>

            <div v-for="v in props.variants" :key="v.id" class="rounded-lg border border-border bg-card">
                <!-- View mode -->
                <div v-if="editingId !== v.id" class="p-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-sm">{{ v.name }}</span>
                        <span v-if="v.is_winner" class="rounded bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            🏆 {{ t('abTest.winner') }}
                        </span>
                        <span class="ml-auto text-xs text-muted-foreground">{{ t('abTest.weight') }}: {{ v.weight }} ({{ v.pct_weight }}%)</span>
                    </div>
                    <p class="truncate font-mono text-xs text-muted-foreground">{{ v.destination_url }}</p>

                    <!-- Scan bar -->
                    <div v-if="props.totalScans > 0" class="space-y-1">
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>{{ v.scan_count }} {{ t('abTest.scans') }}</span>
                            <span>{{ v.pct_scans }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="v.is_winner ? 'bg-yellow-500' : 'bg-primary'"
                                :style="{ width: `${v.pct_scans}%` }"
                            />
                        </div>
                    </div>

                    <div class="flex gap-1.5 pt-1">
                        <Button variant="ghost" size="sm" @click="startEdit(v)">{{ t('common.edit') }}</Button>
                        <Button
                            v-if="!props.abTest.has_winner && props.totalScans > 0"
                            variant="ghost"
                            size="sm"
                            @click="selectWinner(v)"
                        >🏆 {{ t('abTest.selectWinner') }}</Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="deleteVariant(v)"
                        >{{ t('common.delete') }}</Button>
                    </div>
                </div>

                <!-- Edit mode -->
                <div v-else class="p-4 space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        <Input v-model="editForm.name" :placeholder="t('abTest.variantName')" class="col-span-2" />
                        <Input
                            v-model.number="editForm.weight"
                            type="number"
                            min="1"
                            max="100"
                            :placeholder="t('abTest.variantWeight')"
                        />
                    </div>
                    <Input v-model="editForm.destination_url" type="url" placeholder="https://…" />
                    <div class="flex gap-2">
                        <Button size="sm" :disabled="editForm.processing" @click="submitEdit(v)">{{ t('common.save') }}</Button>
                        <Button size="sm" variant="ghost" @click="editingId = null">{{ t('common.cancel') }}</Button>
                    </div>
                </div>
            </div>

            <!-- Add variant form -->
            <div v-if="showAddForm" class="rounded-lg border border-primary/30 bg-card p-4 space-y-3">
                <p class="text-sm font-semibold">{{ t('abTest.addVariant') }}</p>
                <div class="grid grid-cols-3 gap-2">
                    <Input v-model="addForm.name" :placeholder="t('abTest.variantName')" class="col-span-2" />
                    <Input
                        v-model.number="addForm.weight"
                        type="number"
                        min="1"
                        max="100"
                        :placeholder="t('abTest.variantWeight')"
                    />
                </div>
                <Input v-model="addForm.destination_url" type="url" placeholder="https://…" />
                <div class="flex gap-2">
                    <Button size="sm" :disabled="!addForm.name || !addForm.destination_url || addForm.processing" @click="submitAdd">
                        {{ t('abTest.addVariant') }}
                    </Button>
                    <Button size="sm" variant="ghost" @click="showAddForm = false; addForm.reset()">{{ t('common.cancel') }}</Button>
                </div>
            </div>

            <Button v-if="!showAddForm" variant="outline" @click="showAddForm = true">
                + {{ t('abTest.addVariant') }}
            </Button>
        </div>
    </div>
</template>
