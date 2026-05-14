<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

// ── Types ────────────────────────────────────────────────────────────

type ConditionType = 'device' | 'country' | 'language' | 'time_range' | 'user_agent'

interface Condition {
    type: ConditionType
    operator?: string
    value?: string | string[]
    from?: string
    to?: string
    days?: number[]
    timezone?: string
}

interface RuleProp {
    id: number
    name: string | null
    conditions: Condition[]
    destination_url: string
    priority: number
    is_active: boolean
}

interface QrCodeProp {
    id: number
    title: string
    short_hash: string
    destination_url: string | null
}

const props = defineProps<{
    qrCode: QrCodeProp
    rules: RuleProp[]
}>()

// ── Constants ────────────────────────────────────────────────────────

const CONDITION_TYPES: ConditionType[] = ['device', 'country', 'language', 'time_range', 'user_agent']
const DEVICE_VALUES = ['mobile', 'tablet', 'desktop', 'ios', 'android']
const DAYS_ISO = [
    { value: 1, label: 'Mon' }, { value: 2, label: 'Tue' }, { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' }, { value: 5, label: 'Fri' }, { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
]

// ── Add/Edit form ────────────────────────────────────────────────────

const showForm = ref(false)
const editingId = ref<number | null>(null)

function emptyCondition(): Condition {
    return { type: 'device', operator: 'is', value: 'mobile' }
}

const ruleForm = useForm({
    name: '',
    destination_url: '',
    is_active: true,
    conditions: [emptyCondition()] as Condition[],
})

function openAdd() {
    editingId.value = null
    ruleForm.reset()
    ruleForm.conditions = [emptyCondition()]
    showForm.value = true
}

function openEdit(rule: RuleProp) {
    editingId.value = rule.id
    ruleForm.name = rule.name ?? ''
    ruleForm.destination_url = rule.destination_url
    ruleForm.is_active = rule.is_active
    ruleForm.conditions = rule.conditions.map((c) => ({ ...c }))
    showForm.value = true
}

function cancelForm() {
    showForm.value = false
    editingId.value = null
    ruleForm.reset()
}

function addCondition() {
    ruleForm.conditions.push(emptyCondition())
}

function removeCondition(idx: number) {
    ruleForm.conditions.splice(idx, 1)
}

function onConditionTypeChange(idx: number) {
    const type = ruleForm.conditions[idx]?.type ?? 'device'
    ruleForm.conditions[idx] = defaultConditionFor(type)
}

function defaultConditionFor(type: ConditionType): Condition {
    switch (type) {
        case 'device': return { type, operator: 'is', value: 'mobile' }
        case 'country': return { type, operator: 'in', value: '' }
        case 'language': return { type, operator: 'starts_with', value: '' }
        case 'time_range': return { type, from: '09:00', to: '17:00', days: [1, 2, 3, 4, 5], timezone: 'UTC' }
        case 'user_agent': return { type, operator: 'contains', value: '' }
    }
}

function conditionCountryValue(cond: Condition): string {
    const v = cond.value
    if (Array.isArray(v)) return v.join(', ')
    return (v as string | undefined) ?? ''
}

function setConditionCountryValue(idx: number, raw: string) {
    const codes = raw.split(',').map((s) => s.trim().toUpperCase()).filter(Boolean)
    ruleForm.conditions[idx] = { ...ruleForm.conditions[idx]!, value: codes }
}

function toggleDay(idx: number, day: number) {
    const cond = ruleForm.conditions[idx]!
    const days = [...(cond.days ?? [])]
    const i = days.indexOf(day)
    if (i >= 0) days.splice(i, 1)
    else days.push(day)
    ruleForm.conditions[idx] = { ...cond, days }
}

function submitForm() {
    if (editingId.value !== null) {
        ruleForm.patch(`/qr/${props.qrCode.id}/rules/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => cancelForm(),
        })
    } else {
        ruleForm.post(`/qr/${props.qrCode.id}/rules`, {
            preserveScroll: true,
            onSuccess: () => cancelForm(),
        })
    }
}

// ── Drag-and-drop reorder ─────────────────────────────────────────────

const draggedId = ref<number | null>(null)
const dragOverId = ref<number | null>(null)

function onDragStart(ruleId: number) {
    draggedId.value = ruleId
}

function onDragOver(event: DragEvent, ruleId: number) {
    event.preventDefault()
    dragOverId.value = ruleId
}

function onDragLeave() {
    dragOverId.value = null
}

function onDragEnd() {
    draggedId.value = null
    dragOverId.value = null
}

function onDrop(targetId: number) {
    if (draggedId.value === null || draggedId.value === targetId) {
        draggedId.value = null; dragOverId.value = null; return
    }
    const ids = props.rules.map((r) => r.id)
    const fromIdx = ids.indexOf(draggedId.value)
    const toIdx = ids.indexOf(targetId)
    if (fromIdx === -1 || toIdx === -1) { draggedId.value = null; dragOverId.value = null; return }
    ids.splice(fromIdx, 1)
    ids.splice(toIdx, 0, draggedId.value)
    draggedId.value = null; dragOverId.value = null
    router.post(`/qr/${props.qrCode.id}/rules/reorder`, { ids }, { preserveScroll: true })
}

function deleteRule(rule: RuleProp) {
    if (!confirm(t('rules.deleteConfirm'))) return
    router.delete(`/qr/${props.qrCode.id}/rules/${rule.id}`, { preserveScroll: true })
}

// ── Simulator "what would happen if" ─────────────────────────────────

interface SimParams {
    device: string
    country: string
    language: string
    time: string
    day: number
    userAgent: string
}

const _now = new Date()
const _isoDay = _now.getDay() === 0 ? 7 : _now.getDay()
const _hhmm = `${String(_now.getHours()).padStart(2, '0')}:${String(_now.getMinutes()).padStart(2, '0')}`

const showSim = ref(false)
const simParams = ref<SimParams>({
    device: 'mobile',
    country: 'PL',
    language: 'pl',
    time: _hhmm,
    day: _isoDay,
    userAgent: '',
})

interface SimResult {
    ruleId: number | null
    ruleName: string | null
    url: string
}

const simResult = computed<SimResult | null>(() => {
    if (!showSim.value) return null
    const p = simParams.value
    for (const rule of props.rules) {
        if (!rule.is_active) continue
        if (rule.conditions.every((c) => simMatchCondition(c, p))) {
            return { ruleId: rule.id, ruleName: rule.name, url: rule.destination_url }
        }
    }
    return { ruleId: null, ruleName: null, url: props.qrCode.destination_url ?? '—' }
})

function simMatchCondition(cond: Condition, p: SimParams): boolean {
    switch (cond.type) {
        case 'device': {
            const val = (cond.value as string) ?? ''
            const m = p.device === val || (val === 'mobile' && ['ios', 'android'].includes(p.device))
            return cond.operator === 'is_not' ? !m : m
        }
        case 'country': {
            const vals = (Array.isArray(cond.value) ? cond.value : []) as string[]
            const inList = vals.includes(p.country.toUpperCase())
            return cond.operator === 'not_in' ? !inList : inList
        }
        case 'language': {
            const primary = p.language.toLowerCase()
            const val = ((cond.value as string) || '').toLowerCase()
            return cond.operator === 'is'
                ? primary === val || primary.startsWith(`${val}-`)
                : primary.startsWith(val)
        }
        case 'time_range': {
            const days = (cond.days ?? []) as number[]
            if (!days.includes(p.day)) return false
            return p.time >= (cond.from ?? '00:00') && p.time <= (cond.to ?? '23:59')
        }
        case 'user_agent': {
            const val = ((cond.value as string) || '').toLowerCase()
            if (!val) return false
            const contains = p.userAgent.toLowerCase().includes(val)
            return cond.operator === 'not_contains' ? !contains : contains
        }
        default: return false
    }
}

// ── Condition summary label ───────────────────────────────────────────

function conditionLabel(cond: Condition): string {
    switch (cond.type) {
        case 'device':
            return `Device ${cond.operator === 'is_not' ? '≠' : '='} ${cond.value}`
        case 'country': {
            const val = Array.isArray(cond.value) ? cond.value.join(', ') : cond.value
            return `Country ${cond.operator === 'not_in' ? '∉' : '∈'} [${val}]`
        }
        case 'language':
            return `Language ${cond.operator === 'is' ? '=' : 'starts'} "${cond.value}"`
        case 'time_range': {
            const daysStr = (cond.days ?? []).map((d) => DAYS_ISO.find((x) => x.value === d)?.label ?? d).join(',')
            return `Time ${cond.from}–${cond.to} (${daysStr})`
        }
        case 'user_agent':
            return `UA ${cond.operator === 'not_contains' ? '!∋' : '∋'} "${cond.value}"`
        default:
            return cond.type
    }
}

// Non-null helper — always called within v-for bounds so index is always valid
function condAt(idx: number): Condition {
    return ruleForm.conditions[idx]!
}
</script>

<template>
    <Head :title="t('rules.pageTitle', { title: props.qrCode.title })" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">{{ t('rules.pageTitle', { title: props.qrCode.title }) }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('rules.description') }}</p>
            </div>
            <Button
                variant="outline"
                size="sm"
                as="a"
                :href="`/qr/${props.qrCode.id}/edit`"
            >
                ← {{ t('rules.backToEdit') }}
            </Button>
        </div>

        <!-- Default URL info -->
        <div class="rounded-lg border border-border bg-muted/30 px-4 py-3">
            <p class="text-xs text-muted-foreground">{{ t('rules.defaultUrl') }}</p>
            <p class="truncate text-sm font-mono">{{ props.qrCode.destination_url ?? '—' }}</p>
        </div>

        <!-- Simulator panel -->
        <details class="rounded-lg border border-border" :open="showSim" @toggle="showSim = ($event.target as HTMLDetailsElement).open">
            <summary class="cursor-pointer select-none px-4 py-3 text-sm font-medium">
                🔍 {{ t('rules.simulate.title') }}
            </summary>
            <div class="space-y-3 px-4 pb-4">
                <p class="text-xs text-muted-foreground">{{ t('rules.simulate.hint') }}</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.device') }}</label>
                        <select v-model="simParams.device" class="w-full rounded border border-input bg-background px-2 py-1 text-sm">
                            <option v-for="dv in DEVICE_VALUES" :key="dv" :value="dv">{{ t('rules.deviceValues.' + dv) }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.country') }}</label>
                        <Input
                            v-model="simParams.country"
                            placeholder="PL"
                            maxlength="2"
                            class="uppercase"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.language') }}</label>
                        <Input v-model="simParams.language" placeholder="pl-PL" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.time') }}</label>
                        <Input v-model="simParams.time" type="time" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.day') }}</label>
                        <select v-model.number="simParams.day" class="w-full rounded border border-input bg-background px-2 py-1 text-sm">
                            <option v-for="d in DAYS_ISO" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-muted-foreground">{{ t('rules.simulate.ua') }}</label>
                        <Input v-model="simParams.userAgent" placeholder="Mozilla/5.0…" />
                    </div>
                </div>

                <!-- Result -->
                <div
                    v-if="simResult !== null"
                    class="rounded-md px-3 py-2 text-sm"
                    :class="simResult.ruleId !== null ? 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-muted text-muted-foreground'"
                >
                    <span v-if="simResult.ruleId !== null">
                        ✓ {{ t('rules.simulate.matchedRule') }}: <strong>{{ simResult.ruleName ?? t('rules.unnamed', { n: props.rules.findIndex(r => r.id === simResult!.ruleId) + 1 }) }}</strong>
                    </span>
                    <span v-else>{{ t('rules.simulate.noMatch') }}</span>
                    <br>
                    → <span class="font-mono break-all">{{ simResult.url }}</span>
                </div>
            </div>
        </details>

        <!-- Rules list -->
        <div v-if="props.rules.length === 0 && !showForm" class="rounded-lg border border-dashed border-border p-8 text-center">
            <p class="text-sm text-muted-foreground">{{ t('rules.empty') }}</p>
        </div>

        <div
            v-for="(rule, idx) in props.rules"
            :key="rule.id"
            class="rounded-lg border bg-card transition-opacity"
            :class="[
                draggedId === rule.id ? 'opacity-40' : 'opacity-100',
                dragOverId === rule.id && draggedId !== rule.id ? 'border-primary' : 'border-border',
            ]"
            draggable="true"
            @dragstart="onDragStart(rule.id)"
            @dragover="onDragOver($event, rule.id)"
            @dragleave="onDragLeave"
            @drop="onDrop(rule.id)"
            @dragend="onDragEnd"
        >
            <div class="flex items-start gap-3 p-4">
                <!-- Drag handle -->
                <span
                    class="mt-0.5 shrink-0 cursor-grab text-muted-foreground/50 hover:text-muted-foreground select-none"
                    title="Drag to reorder"
                >⠿</span>

                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ rule.name ?? t('rules.unnamed', { n: idx + 1 }) }}</span>
                        <span
                            class="rounded px-1.5 py-0.5 text-xs"
                            :class="rule.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-muted text-muted-foreground'"
                        >
                            {{ rule.is_active ? t('rules.active') : t('rules.inactive') }}
                        </span>
                    </div>

                    <!-- Conditions chips -->
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="(cond, ci) in rule.conditions"
                            :key="ci"
                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground"
                        >
                            {{ conditionLabel(cond) }}
                        </span>
                    </div>

                    <p class="truncate text-xs text-muted-foreground">→ {{ rule.destination_url }}</p>
                </div>

                <div class="flex shrink-0 gap-1">
                    <Button variant="ghost" size="sm" @click="openEdit(rule)">{{ t('common.edit') }}</Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="deleteRule(rule)"
                    >{{ t('common.delete') }}</Button>
                </div>
            </div>
        </div>

        <!-- Add/Edit form -->
        <div v-if="showForm" class="rounded-lg border border-primary/30 bg-card p-4 space-y-4">
            <h2 class="text-sm font-semibold">{{ editingId !== null ? t('rules.editRule') : t('rules.addRule') }}</h2>

            <div class="space-y-1">
                <label class="text-xs text-muted-foreground">{{ t('rules.ruleName') }}</label>
                <Input v-model="ruleForm.name" :placeholder="t('rules.ruleNamePlaceholder')" />
            </div>

            <div class="space-y-1">
                <label class="text-xs text-muted-foreground">{{ t('rules.ruleUrl') }} *</label>
                <Input v-model="ruleForm.destination_url" type="url" placeholder="https://example.com" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="rule-active"
                    v-model="ruleForm.is_active"
                    type="checkbox"
                    class="rounded"
                >
                <label for="rule-active" class="text-sm">{{ t('rules.ruleActive') }}</label>
            </div>

            <!-- Conditions -->
            <div class="space-y-3">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ t('rules.conditions') }}</p>

                <div v-for="(cond, idx) in ruleForm.conditions" :key="idx" class="rounded-md border border-border bg-muted/30 p-3 space-y-2">
                    <div class="flex items-center gap-2">
                        <!-- Type selector -->
                        <select
                            v-model="condAt(idx).type"
                            class="rounded border border-input bg-background px-2 py-1 text-sm"
                            @change="onConditionTypeChange(idx)"
                        >
                            <option v-for="type in CONDITION_TYPES" :key="type" :value="type">
                                {{ t('rules.condTypes.' + type) }}
                            </option>
                        </select>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="ml-auto text-destructive"
                            @click="removeCondition(idx)"
                        >×</Button>
                    </div>

                    <!-- Device condition -->
                    <div v-if="cond.type === 'device'" class="flex gap-2">
                        <select v-model="condAt(idx).operator" class="rounded border border-input bg-background px-2 py-1 text-sm">
                            <option value="is">{{ t('rules.operators.is') }}</option>
                            <option value="is_not">{{ t('rules.operators.is_not') }}</option>
                        </select>
                        <select v-model="condAt(idx).value" class="rounded border border-input bg-background px-2 py-1 text-sm">
                            <option v-for="dv in DEVICE_VALUES" :key="dv" :value="dv">{{ t('rules.deviceValues.' + dv) }}</option>
                        </select>
                    </div>

                    <!-- Country condition -->
                    <div v-else-if="cond.type === 'country'" class="flex gap-2">
                        <select v-model="condAt(idx).operator" class="rounded border border-input bg-background px-2 py-1 text-sm">
                            <option value="in">{{ t('rules.operators.in') }}</option>
                            <option value="not_in">{{ t('rules.operators.not_in') }}</option>
                        </select>
                        <Input
                            :model-value="conditionCountryValue(cond)"
                            placeholder="PL, DE, FR"
                            class="flex-1"
                            @update:model-value="(v) => setConditionCountryValue(idx, String(v))"
                        />
                    </div>

                    <!-- Language condition -->
                    <div v-else-if="cond.type === 'language'" class="flex gap-2">
                        <select v-model="condAt(idx).operator" class="rounded border border-input bg-background px-2 py-1 text-sm">
                            <option value="starts_with">{{ t('rules.operators.starts_with') }}</option>
                            <option value="is">{{ t('rules.operators.is') }}</option>
                        </select>
                        <Input v-model="condAt(idx).value as string" placeholder="pl" class="w-24" />
                    </div>

                    <!-- Time range condition -->
                    <div v-else-if="cond.type === 'time_range'" class="space-y-2">
                        <div class="flex flex-wrap gap-2 items-center">
                            <Input v-model="condAt(idx).from" type="time" class="w-32" />
                            <span class="text-sm text-muted-foreground">–</span>
                            <Input v-model="condAt(idx).to" type="time" class="w-32" />
                            <Input v-model="condAt(idx).timezone" placeholder="UTC" class="w-36" />
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="day in DAYS_ISO"
                                :key="day.value"
                                type="button"
                                class="rounded px-2 py-1 text-xs transition-colors"
                                :class="(cond.days ?? []).includes(day.value) ? 'bg-primary text-primary-foreground' : 'border border-border hover:border-muted-foreground'"
                                @click="toggleDay(idx, day.value)"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <!-- User-agent condition -->
                    <div v-else-if="cond.type === 'user_agent'" class="flex gap-2">
                        <select v-model="condAt(idx).operator" class="rounded border border-input bg-background px-2 py-1 text-sm">
                            <option value="contains">{{ t('rules.operators.contains') }}</option>
                            <option value="not_contains">{{ t('rules.operators.not_contains') }}</option>
                        </select>
                        <Input v-model="condAt(idx).value as string" placeholder="Chrome" class="flex-1" />
                    </div>
                </div>

                <Button variant="outline" size="sm" @click="addCondition">+ {{ t('rules.addCondition') }}</Button>
            </div>

            <div class="flex gap-2">
                <Button
                    :disabled="!ruleForm.destination_url || ruleForm.conditions.length === 0 || ruleForm.processing"
                    @click="submitForm"
                >
                    {{ ruleForm.processing ? t('common.saving') : t('common.save') }}
                </Button>
                <Button variant="ghost" @click="cancelForm">{{ t('common.cancel') }}</Button>
            </div>
        </div>

        <Button v-if="!showForm" @click="openAdd">+ {{ t('rules.addRule') }}</Button>
    </div>
</template>
