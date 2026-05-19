<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Save, Sparkles, Globe, Calendar, Shield, Shuffle, Tag, Plus, QrCode } from 'lucide-vue-next'
import LivePreview from '@/components/qr/LivePreview.vue'
import LogoUpload from '@/components/qr/LogoUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import CollaborationPresence from '@/components/CollaborationPresence.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface TagItem {
    id: number
    name: string
    color: string
}

interface QrCodeProp {
    id: number
    title: string
    type: string
    short_hash: string
    custom_slug: string | null
    destination_url: string | null
    fallback_url: string
    is_active: boolean
    expires_at: string | null
    activates_at: string | null
    geo_allowed_countries: string[]
    scan_limit: number | null
    scan_count: number
    tag_ids: number[]
    logo_url: string | null
}

const props = defineProps<{
    qrCode: QrCodeProp
    userTags: TagItem[]
}>()

const { t } = useI18n()

const form = useForm({
    title: props.qrCode.title,
    destination_url: props.qrCode.destination_url ?? '',
    fallback_url: props.qrCode.fallback_url,
    is_active: props.qrCode.is_active,
    expires_at: props.qrCode.expires_at ?? '',
    activates_at: props.qrCode.activates_at ?? '',
    geo_allowed_countries: [...props.qrCode.geo_allowed_countries] as string[],
    scan_limit: props.qrCode.scan_limit as number | null,
    tag_ids: [...props.qrCode.tag_ids] as number[],
    custom_slug: props.qrCode.custom_slug ?? '',
})

function submit() {
    form.patch(`/qr/${props.qrCode.id}`)
}

const suggestingName = ref(false)

async function suggestName() {
    suggestingName.value = true
    try {
        const res = await fetch(`/qr/${props.qrCode.id}/ai/suggest-name`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                Accept: 'application/json',
            },
        })
        if (res.ok) {
            const data = await res.json() as { name: string }
            form.title = data.name
        }
    } finally {
        suggestingName.value = false
    }
}

// ── Tag picker ─────────────────────────────────────────────────────
function toggleTag(tagId: number) {
    const idx = form.tag_ids.indexOf(tagId)
    if (idx === -1) {
        form.tag_ids.push(tagId)
    } else {
        form.tag_ids.splice(idx, 1)
    }
}

// ── New tag form ───────────────────────────────────────────────────
const TAG_COLORS = [
    '#ef4444', '#f97316', '#eab308', '#22c55e',
    '#06b6d4', '#6366f1', '#a855f7', '#ec4899',
]

const newTagForm = useForm({ name: '', color: TAG_COLORS[5] })
const showNewTagForm = ref(false)

function submitNewTag() {
    newTagForm.post('/tags', {
        preserveScroll: true,
        onSuccess: () => {
            newTagForm.reset()
            showNewTagForm.value = false
        },
    })
}

// ── Logo ───────────────────────────────────────────────────────────
function onLogoUpload(file: File) {
    router.post(`/qr/${props.qrCode.id}/logo`, { logo: file }, { preserveScroll: true })
}

function onLogoRemove() {
    router.delete(`/qr/${props.qrCode.id}/logo`, { preserveScroll: true })
}

// ── Geofencing ─────────────────────────────────────────────────────
const geoInput = ref('')

function addCountry() {
    const code = geoInput.value.trim().toUpperCase().slice(0, 2)
    if (code.length === 2 && /^[A-Z]{2}$/.test(code) && !form.geo_allowed_countries.includes(code)) {
        form.geo_allowed_countries.push(code)
    }
    geoInput.value = ''
}

function removeCountry(code: string) {
    const idx = form.geo_allowed_countries.indexOf(code)
    if (idx !== -1) form.geo_allowed_countries.splice(idx, 1)
}

// ── QR preview ─────────────────────────────────────────────────────
const redirectUrl = computed(() => `${window.location.origin}/q/${props.qrCode.short_hash}`)
</script>

<template>
    <Head :title="t('qr.edit.headTitle')" />

    <div class="space-y-6 min-w-0">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" as-child class="shrink-0 hover:bg-muted/60 transition-colors duration-150">
                    <Link href="/qr">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20 shrink-0">
                        <QrCode class="size-5 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                            {{ t('qr.edit.title') }}
                        </h1>
                        <p class="text-sm text-muted-foreground mt-0.5">{{ t('qr.edit.subtitle') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <CollaborationPresence :qr-code-id="props.qrCode.id" />
                <Button variant="outline" as-child class="hover:border-border/80 transition-colors duration-150">
                    <Link href="/qr">
                        <span class="hidden sm:inline">{{ t('qr.edit.cancel') }}</span>
                        <ArrowLeft class="size-4 sm:hidden" />
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Two-column layout: mobile stack, desktop side-by-side -->
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <!-- Form column -->
            <div class="flex-1 min-w-0">
                <form class="space-y-4" @submit.prevent="submit">

                    <!-- Title section -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium leading-none" for="title">
                                {{ t('qr.edit.fields.title') }}
                            </label>
                            <div class="flex gap-2">
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    :placeholder="t('qr.edit.fields.titlePlaceholder')"
                                    :class="{ 'border-destructive': form.errors.title }"
                                    class="flex-1 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                    autocomplete="off"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="suggestingName"
                                    :title="t('ai.suggestName')"
                                    class="shrink-0 hover:border-primary/50 hover:text-primary transition-colors duration-150"
                                    @click="suggestName"
                                >
                                    <Sparkles v-if="!suggestingName" class="size-4" />
                                    <span v-else class="animate-spin size-4 inline-block">⏳</span>
                                </Button>
                            </div>
                            <p v-if="form.errors.title" class="text-xs text-destructive">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Type badge -->
                        <div class="mt-4 flex items-center gap-2">
                            <p class="text-sm text-muted-foreground">{{ t('qr.edit.fields.type') }}:</p>
                            <Badge variant="outline" class="text-xs border-primary/30 text-primary bg-primary/5">
                                {{ t(`qr.index.types.${qrCode.type}`) }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Destination & Fallback URL section -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex size-7 items-center justify-center rounded-md bg-cyan-400/10">
                                <Globe class="size-4 text-cyan-400" />
                            </div>
                            <p class="text-sm font-semibold">URLs</p>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="destination_url">
                                    {{ t('qr.edit.fields.destination') }}
                                </label>
                                <Input
                                    id="destination_url"
                                    v-model="form.destination_url"
                                    :placeholder="t('qr.edit.fields.destinationPlaceholder')"
                                    :class="{ 'border-destructive': form.errors.destination_url }"
                                    autocomplete="off"
                                    class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                />
                                <p v-if="form.errors.destination_url" class="text-xs text-destructive">
                                    {{ form.errors.destination_url }}
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="fallback_url">
                                    {{ t('qr.edit.fields.fallbackUrl') }}
                                </label>
                                <Input
                                    id="fallback_url"
                                    v-model="form.fallback_url"
                                    :placeholder="t('qr.edit.fields.fallbackUrlPlaceholder')"
                                    :class="{ 'border-destructive': form.errors.fallback_url }"
                                    autocomplete="off"
                                    class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                />
                                <p v-if="form.errors.fallback_url" class="text-xs text-destructive">
                                    {{ form.errors.fallback_url }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">{{ t('qr.edit.fields.fallbackUrlHint') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tags section -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/40 to-transparent" />
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex size-7 items-center justify-center rounded-md bg-primary/10">
                                <Tag class="size-4 text-primary" />
                            </div>
                            <p class="text-sm font-semibold">{{ t('qr.tags.label') }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in userTags"
                                :key="tag.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium border transition-colors duration-150',
                                    form.tag_ids.includes(tag.id)
                                        ? 'text-foreground border-transparent'
                                        : 'border-border text-muted-foreground hover:border-primary/40',
                                ]"
                                :style="form.tag_ids.includes(tag.id) ? { backgroundColor: tag.color, borderColor: tag.color } : {}"
                                @click="toggleTag(tag.id)"
                            >
                                <span class="size-2 rounded-full shrink-0" :style="{ backgroundColor: tag.color }" />
                                {{ tag.name }}
                            </button>

                            <button
                                v-if="!showNewTagForm"
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium border border-dashed border-border text-muted-foreground hover:border-primary/50 hover:text-foreground transition-colors duration-150"
                                @click="showNewTagForm = true"
                            >
                                <Plus class="size-3" />
                                {{ t('qr.tags.newTag') }}
                            </button>
                        </div>

                        <!-- New tag inline form -->
                        <div v-if="showNewTagForm" class="mt-3 rounded-lg border border-border p-3 space-y-3 bg-muted/30">
                            <p class="text-xs font-medium">{{ t('qr.tags.newTag') }}</p>
                            <Input
                                v-model="newTagForm.name"
                                :placeholder="t('qr.tags.tagNamePlaceholder')"
                                class="h-8 text-sm focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                autocomplete="off"
                                @keydown.enter.prevent="submitNewTag"
                            />
                            <div class="flex gap-1.5 flex-wrap">
                                <button
                                    v-for="color in TAG_COLORS"
                                    :key="color"
                                    type="button"
                                    :class="[
                                        'size-6 rounded-full transition-transform duration-150',
                                        newTagForm.color === color ? 'ring-2 ring-ring ring-offset-2 scale-110' : 'hover:scale-110',
                                    ]"
                                    :style="{ backgroundColor: color }"
                                    @click="newTagForm.color = color"
                                />
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    :disabled="newTagForm.processing || !newTagForm.name.trim()"
                                    @click="submitNewTag"
                                >
                                    {{ t('qr.tags.create') }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="showNewTagForm = false; newTagForm.reset()"
                                >
                                    {{ t('ui.cancel') }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Logo upload -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
                        <LogoUpload
                            :current-logo-url="qrCode.logo_url"
                            @upload="onLogoUpload"
                            @remove="onLogoRemove"
                        />
                    </div>

                    <!-- Status + Scheduling section -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex size-7 items-center justify-center rounded-md bg-cyan-400/10">
                                <Calendar class="size-4 text-cyan-400" />
                            </div>
                            <p class="text-sm font-semibold">Status &amp; Scheduling</p>
                        </div>

                        <!-- is_active toggle -->
                        <div class="space-y-1.5 mb-4">
                            <p class="text-sm font-medium leading-none">{{ t('qr.edit.fields.isActive') }}</p>
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="form.is_active"
                                    :class="[
                                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                                        form.is_active ? 'bg-primary' : 'bg-input',
                                    ]"
                                    @click="form.is_active = !form.is_active"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform duration-200',
                                            form.is_active ? 'translate-x-5' : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                                <span class="text-sm" :class="form.is_active ? 'text-foreground font-medium' : 'text-muted-foreground'">
                                    {{ form.is_active ? t('qr.index.status.active') : t('qr.index.status.inactive') }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">{{ t('qr.edit.fields.isActiveHint') }}</p>
                        </div>

                        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent mb-4" />

                        <!-- Scheduling dates -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="activates_at">
                                    {{ t('qr.edit.fields.activatesAt') }}
                                </label>
                                <Input
                                    id="activates_at"
                                    v-model="form.activates_at"
                                    type="date"
                                    :class="{ 'border-destructive': form.errors.activates_at }"
                                    class="w-full focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                />
                                <p v-if="form.errors.activates_at" class="text-xs text-destructive">
                                    {{ form.errors.activates_at }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">{{ t('qr.edit.fields.activatesAtHint') }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="expires_at">
                                    {{ t('qr.edit.fields.expiresAt') }}
                                </label>
                                <Input
                                    id="expires_at"
                                    v-model="form.expires_at"
                                    type="date"
                                    :class="{ 'border-destructive': form.errors.expires_at }"
                                    class="w-full focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                />
                                <p v-if="form.errors.expires_at" class="text-xs text-destructive">
                                    {{ form.errors.expires_at }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">{{ t('qr.edit.fields.expiresAtHint') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced section: Geo, Scan limit, Custom slug -->
                    <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent" />
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex size-7 items-center justify-center rounded-md bg-gold-500/10">
                                <Shield class="size-4 text-gold-500" />
                            </div>
                            <p class="text-sm font-semibold">Advanced Controls</p>
                        </div>

                        <div class="space-y-5">
                            <!-- Geofencing -->
                            <div class="space-y-2">
                                <p class="text-sm font-medium leading-none">{{ t('qr.edit.fields.geo.label') }}</p>
                                <p class="text-xs text-muted-foreground">{{ t('qr.edit.fields.geo.hint') }}</p>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="geoInput"
                                        :placeholder="t('qr.edit.fields.geo.placeholder')"
                                        class="w-28 uppercase focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                        maxlength="2"
                                        @keydown.enter.prevent="addCountry"
                                    />
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="hover:border-primary/50 transition-colors duration-150"
                                        @click="addCountry"
                                    >
                                        {{ t('qr.edit.fields.geo.add') }}
                                    </Button>
                                </div>
                                <div v-if="form.geo_allowed_countries.length > 0" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="code in form.geo_allowed_countries"
                                        :key="code"
                                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary ring-1 ring-primary/20"
                                    >
                                        {{ code }}
                                        <button
                                            type="button"
                                            class="ml-0.5 rounded-full hover:bg-primary/20 p-0.5 transition-colors duration-150"
                                            @click="removeCountry(code)"
                                        >×</button>
                                    </span>
                                </div>
                                <p v-if="form.errors.geo_allowed_countries" class="text-xs text-destructive">
                                    {{ form.errors.geo_allowed_countries }}
                                </p>
                            </div>

                            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                            <!-- Click cap -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="scan_limit">
                                    {{ t('qr.edit.fields.scanLimit.label') }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="scan_limit"
                                        type="number"
                                        :value="form.scan_limit ?? ''"
                                        min="1"
                                        max="1000000"
                                        :placeholder="t('qr.edit.fields.scanLimit.placeholder')"
                                        class="w-36 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                        :class="{ 'border-destructive': form.errors.scan_limit }"
                                        @input="form.scan_limit = ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null"
                                    />
                                    <span v-if="props.qrCode.scan_count > 0" class="text-xs text-muted-foreground">
                                        {{ t('qr.edit.fields.scanLimit.used', { count: props.qrCode.scan_count }) }}
                                    </span>
                                </div>
                                <p v-if="form.errors.scan_limit" class="text-xs text-destructive">
                                    {{ form.errors.scan_limit }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">{{ t('qr.edit.fields.scanLimit.hint') }}</p>
                            </div>

                            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                            <!-- Custom slug -->
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium leading-none" for="custom_slug">
                                    {{ t('qr.edit.fields.customSlug.label') }}
                                </label>
                                <div class="flex items-center gap-1">
                                    <span class="text-sm text-muted-foreground">/s/</span>
                                    <Input
                                        id="custom_slug"
                                        v-model="form.custom_slug"
                                        type="text"
                                        :placeholder="t('qr.edit.fields.customSlug.placeholder')"
                                        class="w-48 lowercase focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                        :class="{ 'border-destructive': form.errors.custom_slug }"
                                        pattern="[a-z0-9\-_]+"
                                        maxlength="100"
                                    />
                                </div>
                                <p v-if="form.errors.custom_slug" class="text-xs text-destructive">
                                    {{ form.errors.custom_slug }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">
                                    {{ t('qr.edit.fields.customSlug.hint') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature cards: A/B Test + Smart Redirect -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden hover:border-primary/30 hover:shadow-[0_0_16px_oklch(0.66_0.25_285/0.08)] transition-all duration-200">
                            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex size-6 items-center justify-center rounded bg-primary/10">
                                    <Shuffle class="size-3.5 text-primary" />
                                </div>
                                <p class="text-sm font-medium">{{ t('qr.edit.abTest.title') }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground mb-3">{{ t('qr.edit.abTest.hint') }}</p>
                            <Button variant="outline" size="sm" as-child class="hover:border-primary/50 transition-colors duration-150">
                                <Link :href="`/qr/${props.qrCode.id}/ab-test`">{{ t('qr.edit.abTest.manage') }}</Link>
                            </Button>
                        </div>

                        <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden hover:border-cyan-400/30 hover:shadow-[0_0_16px_oklch(0.72_0.15_200/0.08)] transition-all duration-200">
                            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex size-6 items-center justify-center rounded bg-cyan-400/10">
                                    <Globe class="size-3.5 text-cyan-400" />
                                </div>
                                <p class="text-sm font-medium">{{ t('qr.edit.smartRedirect.title') }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground mb-3">{{ t('qr.edit.smartRedirect.hint') }}</p>
                            <Button variant="outline" size="sm" as-child class="hover:border-cyan-400/50 transition-colors duration-150">
                                <Link :href="`/qr/${props.qrCode.id}/rules`">{{ t('qr.edit.smartRedirect.manage') }}</Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-3 pt-1">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        >
                            <Save class="size-4" />
                            <span>{{ form.processing ? t('qr.edit.saving') : t('qr.edit.save') }}</span>
                        </Button>
                        <Button variant="ghost" as-child class="hover:bg-muted/60 transition-colors duration-150">
                            <Link href="/qr">{{ t('qr.edit.cancel') }}</Link>
                        </Button>
                    </div>
                </form>
            </div>

            <!-- QR Preview sidebar -->
            <div class="w-full lg:w-72 xl:w-80 shrink-0 lg:sticky lg:top-[calc(3.5rem+1.5rem)]">
                <div class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-primary/30 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/60 to-transparent" />
                    <div class="p-4 md:p-5">
                        <p class="text-sm font-semibold mb-4">{{ t('qr.edit.preview.title') }}</p>

                        <!-- QR code on white bg (required for scanning) -->
                        <div class="rounded-lg overflow-hidden bg-foreground flex justify-center p-4">
                            <LivePreview
                                :data="redirectUrl"
                                :size="200"
                                error-correction-level="M"
                                :image="qrCode.logo_url ?? undefined"
                            />
                        </div>

                        <p class="text-xs text-muted-foreground text-center mt-2">{{ t('qr.edit.preview.hint') }}</p>

                        <!-- Short URL display -->
                        <div class="mt-3 rounded-lg bg-muted/60 border border-border px-3 py-2">
                            <p class="text-xs font-mono break-all text-muted-foreground">{{ redirectUrl }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
