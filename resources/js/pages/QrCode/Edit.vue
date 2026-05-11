<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import LivePreview from '@/components/qr/LivePreview.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface QrCodeProp {
    id: number
    title: string
    type: string
    short_hash: string
    destination_url: string | null
    is_active: boolean
    expires_at: string | null
}

const props = defineProps<{ qrCode: QrCodeProp }>()

const { t } = useI18n()

const form = useForm({
    title: props.qrCode.title,
    destination_url: props.qrCode.destination_url ?? '',
    is_active: props.qrCode.is_active,
    expires_at: props.qrCode.expires_at ?? '',
})

function submit() {
    form.patch(`/qr/${props.qrCode.id}`)
}

// QR always encodes the redirect URL — not the destination
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
            </form>

            <!-- QR Preview -->
            <div class="space-y-3">
                <p class="text-sm font-medium">{{ t('qr.edit.preview.title') }}</p>
                <div class="rounded-xl border border-border bg-white p-4 dark:bg-white flex justify-center">
                    <LivePreview
                        :data="redirectUrl"
                        :size="240"
                        error-correction-level="M"
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
