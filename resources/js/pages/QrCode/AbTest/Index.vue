<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, FlaskConical, Plus, Trophy, Trash2, Pencil, X, TrendingUp } from 'lucide-vue-next'
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
    const se = Math.sqrt(0.25 / n)
    if (se === 0) return null
    const z = Math.abs(p1 - p2) / (2 * se)

    if (z >= 2.58) return { z, label: t('abTest.significance.high'), color: 'text-emerald-400' }
    if (z >= 1.96) return { z, label: t('abTest.significance.medium'), color: 'text-gold-500' }
    return { z, label: t('abTest.significance.low'), color: 'text-muted-foreground' }
})

const leader = computed<VariantProp | null>(() => {
    if (props.variants.length === 0 || props.totalScans === 0) return null
    return [...props.variants].sort((a, b) => b.pct_scans - a.pct_scans)[0] ?? null
})
</script>

<template>
    <Head :title="`A/B Test — ${props.qrCode.title}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-4 md:p-6">

        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <FlaskConical class="size-5 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('abTest.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ props.qrCode.title }} · {{ t('abTest.description') }}</p>
                </div>
            </div>
            <Button
                variant="outline"
                size="sm"
                as="a"
                :href="`/qr/${props.qrCode.id}/edit`"
                class="shrink-0 hover:border-primary/40 transition-colors duration-150"
            >
                <ArrowLeft class="size-4 mr-1.5" />
                <span class="hidden sm:inline">{{ t('abTest.backToEdit') }}</span>
                <span class="sm:hidden">{{ t('common.back') }}</span>
            </Button>
        </div>

        <!-- Settings card -->
        <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden hover:border-primary/30 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.08)] transition-all duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <p class="text-sm font-semibold">{{ t('abTest.settings') }}</p>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 cursor-pointer select-none group">
                    <input
                        id="ab-active"
                        v-model="settingsForm.is_active"
                        type="checkbox"
                        class="rounded accent-primary"
                    >
                    <span class="text-sm group-hover:text-foreground transition-colors duration-150">{{ t('abTest.active') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none group">
                    <input
                        id="ab-auto"
                        v-model="settingsForm.auto_select_winner"
                        type="checkbox"
                        class="rounded accent-primary"
                    >
                    <span class="text-sm group-hover:text-foreground transition-colors duration-150">{{ t('abTest.autoSelectWinner') }}</span>
                </label>
            </div>
            <div v-if="settingsForm.auto_select_winner" class="flex items-center gap-2">
                <label class="text-sm text-muted-foreground">{{ t('abTest.threshold') }}</label>
                <Input
                    v-model.number="settingsForm.auto_select_threshold"
                    type="number"
                    min="10"
                    class="w-28 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                />
                <span class="text-sm text-muted-foreground">{{ t('abTest.scans') }}</span>
            </div>
            <Button
                size="sm"
                :disabled="settingsForm.processing"
                class="shadow-[0_0_12px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                @click="saveSettings"
            >
                {{ t('common.save') }}
            </Button>
        </div>

        <!-- Stats summary -->
        <div v-if="props.totalScans > 0" class="relative rounded-xl border border-border bg-card/50 px-5 py-4 space-y-2 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <TrendingUp class="size-4 text-cyan-400" />
                    <span class="text-muted-foreground">{{ t('abTest.totalScans') }}</span>
                    <strong class="text-foreground">{{ props.totalScans.toLocaleString() }}</strong>
                </div>
                <span v-if="significance" :class="significance.color" class="text-xs font-medium tabular-nums">
                    {{ significance.label }} <span class="text-muted-foreground">(Z={{ significance.z.toFixed(2) }})</span>
                </span>
            </div>
            <p v-if="leader && !props.abTest.has_winner" class="text-xs text-muted-foreground">
                {{ t('abTest.leading') }}:
                <strong class="text-foreground">{{ leader.name }}</strong>
                <span class="ml-1 text-cyan-400">{{ leader.pct_scans }}%</span>
            </p>
        </div>

        <!-- Variants -->
        <div class="space-y-3">
            <p class="text-sm font-semibold">{{ t('abTest.variants') }}</p>

            <!-- Empty state -->
            <div v-if="props.variants.length === 0" class="rounded-xl border border-dashed border-border p-10 text-center">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 mx-auto mb-3">
                    <FlaskConical class="size-7 text-primary" />
                </div>
                <p class="text-sm font-medium mb-1">{{ t('abTest.noVariants') }}</p>
                <p class="text-xs text-muted-foreground">{{ t('abTest.description') }}</p>
            </div>

            <div
                v-for="v in props.variants"
                :key="v.id"
                class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-primary/30 transition-all duration-200"
            >
                <!-- View mode -->
                <div v-if="editingId !== v.id" class="p-4 space-y-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-sm">{{ v.name }}</span>
                        <span
                            v-if="v.is_winner"
                            class="inline-flex items-center gap-1 rounded-full bg-gold-500/10 px-2.5 py-0.5 text-xs font-medium text-gold-500 ring-1 ring-gold-500/20"
                        >
                            <Trophy class="size-3 fill-gold-500" />
                            {{ t('abTest.winner') }}
                        </span>
                        <span class="ml-auto text-xs text-muted-foreground tabular-nums">
                            {{ t('abTest.weight') }}: <strong class="text-foreground">{{ v.weight }}</strong>
                            <span class="text-muted-foreground/60 ml-1">({{ v.pct_weight }}%)</span>
                        </span>
                    </div>
                    <p class="truncate font-mono text-xs text-muted-foreground bg-muted/40 rounded px-2 py-1">{{ v.destination_url }}</p>

                    <!-- Scan bar -->
                    <div v-if="props.totalScans > 0" class="space-y-1.5">
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">{{ v.scan_count }} {{ t('abTest.scans') }}</span>
                            <span class="font-semibold tabular-nums" :class="v.is_winner ? 'text-gold-500' : 'text-primary'">{{ v.pct_scans }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted/60">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="v.is_winner ? 'bg-gold-500' : 'bg-primary'"
                                :style="{ width: `${v.pct_scans}%` }"
                            />
                        </div>
                    </div>

                    <div class="flex gap-1.5 pt-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="hover:bg-primary/10 hover:text-primary transition-colors duration-150"
                            @click="startEdit(v)"
                        >
                            <Pencil class="size-4 mr-1.5" />
                            {{ t('common.edit') }}
                        </Button>
                        <Button
                            v-if="!props.abTest.has_winner && props.totalScans > 0"
                            variant="ghost"
                            size="sm"
                            class="hover:bg-gold-500/10 hover:text-gold-500 transition-colors duration-150"
                            @click="selectWinner(v)"
                        >
                            <Trophy class="size-4 mr-1.5" />
                            {{ t('abTest.selectWinner') }}
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:bg-destructive/10 transition-colors duration-150 ml-auto"
                            @click="deleteVariant(v)"
                        >
                            <Trash2 class="size-4 mr-1.5" />
                            {{ t('common.delete') }}
                        </Button>
                    </div>
                </div>

                <!-- Edit mode -->
                <div v-else class="p-4 space-y-3">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <p class="text-sm font-semibold text-primary">{{ t('common.edit') }}</p>
                    <div class="grid grid-cols-3 gap-2">
                        <Input
                            v-model="editForm.name"
                            :placeholder="t('abTest.variantName')"
                            class="col-span-2 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <Input
                            v-model.number="editForm.weight"
                            type="number"
                            min="1"
                            max="100"
                            :placeholder="t('abTest.variantWeight')"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                    </div>
                    <Input
                        v-model="editForm.destination_url"
                        type="url"
                        placeholder="https://…"
                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                    />
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            :disabled="editForm.processing"
                            class="shadow-[0_0_12px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                            @click="submitEdit(v)"
                        >
                            {{ t('common.save') }}
                        </Button>
                        <Button size="sm" variant="ghost" @click="editingId = null">
                            <X class="size-4 mr-1.5" />
                            {{ t('common.cancel') }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Add variant form -->
            <div v-if="showAddForm" class="relative rounded-xl border border-primary/40 bg-card p-5 space-y-3 overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <p class="text-sm font-semibold text-primary">{{ t('abTest.addVariant') }}</p>
                <div class="grid grid-cols-3 gap-2">
                    <Input
                        v-model="addForm.name"
                        :placeholder="t('abTest.variantName')"
                        class="col-span-2 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                    />
                    <Input
                        v-model.number="addForm.weight"
                        type="number"
                        min="1"
                        max="100"
                        :placeholder="t('abTest.variantWeight')"
                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                    />
                </div>
                <Input
                    v-model="addForm.destination_url"
                    type="url"
                    placeholder="https://…"
                    class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                />
                <div class="flex gap-2">
                    <Button
                        size="sm"
                        :disabled="!addForm.name || !addForm.destination_url || addForm.processing"
                        class="shadow-[0_0_12px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                        @click="submitAdd"
                    >
                        <Plus class="size-4 mr-1.5" />
                        {{ t('abTest.addVariant') }}
                    </Button>
                    <Button size="sm" variant="ghost" @click="showAddForm = false; addForm.reset()">
                        <X class="size-4 mr-1.5" />
                        {{ t('common.cancel') }}
                    </Button>
                </div>
            </div>

            <Button
                v-if="!showAddForm"
                variant="outline"
                class="hover:border-primary/40 hover:text-primary transition-colors duration-150"
                @click="showAddForm = true"
            >
                <Plus class="size-4 mr-1.5" />
                {{ t('abTest.addVariant') }}
            </Button>
        </div>
    </div>
</template>
