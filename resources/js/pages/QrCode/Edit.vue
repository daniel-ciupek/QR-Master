<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import LivePreview from '@/components/qr/LivePreview.vue'
import LogoUpload from '@/components/qr/LogoUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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
    destination_url: string | null
    is_active: boolean
    expires_at: string | null
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
    is_active: props.qrCode.is_active,
    expires_at: props.qrCode.expires_at ?? '',
    tag_ids: [...props.qrCode.tag_ids] as number[],
})

function submit() {
    form.patch(`/qr/${props.qrCode.id}`)
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

// ── QR preview ─────────────────────────────────────────────────────
const redirectUrl = computed(() => `${window.location.origin}/q/${props.qrCode.short_hash}`)
</script>

<template>
    <Head :title="t('qr.edit.headTitle')" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ t('qr.edit.title') }}</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ t('qr.edit.subtitle') }}</p>
            </div>
            <Button variant="outline" as-child>
                <Link href="/qr">{{ t('qr.edit.cancel') }}</Link>
            </Button>
        </div>

        <!-- Two-column layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_300px]">
            <!-- Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Title -->
                <div class="space-y-1.5">
                    <label class="text-sm font-medium leading-none" for="title">
                        {{ t('qr.edit.fields.title') }}
                    </label>
                    <Input
                        id="title"
                        v-model="form.title"
                        :placeholder="t('qr.edit.fields.titlePlaceholder')"
                        :class="{ 'border-destructive': form.errors.title }"
                        autocomplete="off"
                    />
                    <p v-if="form.errors.title" class="text-xs text-destructive">
                        {{ form.errors.title }}
                    </p>
                </div>

                <!-- Type (read-only) -->
                <div class="space-y-1.5">
                    <p class="text-sm font-medium leading-none">{{ t('qr.edit.fields.type') }}</p>
                    <Badge variant="outline" class="text-xs">
                        {{ t(`qr.index.types.${qrCode.type}`) }}
                    </Badge>
                </div>

                <!-- Destination URL -->
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
                    />
                    <p v-if="form.errors.destination_url" class="text-xs text-destructive">
                        {{ form.errors.destination_url }}
                    </p>
                </div>

                <!-- Tags -->
                <div class="space-y-2">
                    <p class="text-sm font-medium leading-none">{{ t('qr.tags.label') }}</p>

                    <!-- Existing tags -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in userTags"
                            :key="tag.id"
                            type="button"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium border transition-colors',
                                form.tag_ids.includes(tag.id)
                                    ? 'text-white border-transparent'
                                    : 'border-border text-muted-foreground hover:border-ring',
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
                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium border border-dashed border-border text-muted-foreground hover:border-ring hover:text-foreground transition-colors"
                            @click="showNewTagForm = true"
                        >
                            + {{ t('qr.tags.newTag') }}
                        </button>
                    </div>

                    <!-- New tag inline form -->
                    <div v-if="showNewTagForm" class="rounded-lg border border-border p-3 space-y-3 bg-muted/30">
                        <p class="text-xs font-medium">{{ t('qr.tags.newTag') }}</p>
                        <Input
                            v-model="newTagForm.name"
                            :placeholder="t('qr.tags.tagNamePlaceholder')"
                            class="h-8 text-sm"
                            autocomplete="off"
                            @keydown.enter.prevent="submitNewTag"
                        />
                        <div class="flex gap-1.5 flex-wrap">
                            <button
                                v-for="color in TAG_COLORS"
                                :key="color"
                                type="button"
                                :class="[
                                    'size-6 rounded-full transition-transform',
                                    newTagForm.color === color ? 'ring-2 ring-ring ring-offset-2 scale-110' : '',
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
                <LogoUpload
                    :current-logo-url="qrCode.logo_url"
                    @upload="onLogoUpload"
                    @remove="onLogoRemove"
                />

                <!-- is_active toggle -->
                <div class="space-y-1.5">
                    <p class="text-sm font-medium leading-none">{{ t('qr.edit.fields.isActive') }}</p>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="form.is_active"
                            :class="[
                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                                form.is_active ? 'bg-primary' : 'bg-input',
                            ]"
                            @click="form.is_active = !form.is_active"
                        >
                            <span
                                :class="[
                                    'pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform',
                                    form.is_active ? 'translate-x-5' : 'translate-x-0',
                                ]"
                            />
                        </button>
                        <span class="text-sm text-muted-foreground">
                            {{ form.is_active ? t('qr.index.status.active') : t('qr.index.status.inactive') }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('qr.edit.fields.isActiveHint') }}</p>
                </div>

                <!-- expires_at -->
                <div class="space-y-1.5">
                    <label class="text-sm font-medium leading-none" for="expires_at">
                        {{ t('qr.edit.fields.expiresAt') }}
                    </label>
                    <Input
                        id="expires_at"
                        v-model="form.expires_at"
                        type="date"
                        :class="{ 'border-destructive': form.errors.expires_at }"
                        class="w-48"
                    />
                    <p v-if="form.errors.expires_at" class="text-xs text-destructive">
                        {{ form.errors.expires_at }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">{{ t('qr.edit.fields.expiresAtHint') }}</p>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? t('qr.edit.saving') : t('qr.edit.save') }}
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link href="/qr">{{ t('qr.edit.cancel') }}</Link>
                    </Button>
                </div>

                <!-- Smart Redirect Rules link -->
                <div class="rounded-lg border border-border bg-muted/30 px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">{{ t('qr.edit.smartRedirect.title') }}</p>
                        <p class="text-xs text-muted-foreground">{{ t('qr.edit.smartRedirect.hint') }}</p>
                    </div>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/qr/${props.qrCode.id}/rules`">{{ t('qr.edit.smartRedirect.manage') }}</Link>
                    </Button>
                </div>
            </form>

            <!-- QR Preview -->
            <div class="space-y-3">
                <p class="text-sm font-medium">{{ t('qr.edit.preview.title') }}</p>
                <div class="rounded-xl border border-border bg-white p-4 dark:bg-white flex justify-center">
                    <LivePreview
                        :data="redirectUrl"
                        :size="240"
                        error-correction-level="M"
                        :image="qrCode.logo_url ?? undefined"
                    />
                </div>
                <p class="text-xs text-muted-foreground text-center">{{ t('qr.edit.preview.hint') }}</p>
                <div class="rounded-md bg-muted px-3 py-2">
                    <p class="text-xs font-mono break-all text-muted-foreground">{{ redirectUrl }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
