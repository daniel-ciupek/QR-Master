<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Activity, ExternalLink, Plus, Trash2, Webhook } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface WebhookRow {
    id: number
    url: string
    events: string[]
    is_active: boolean
    deliveries_count: number
    created_at: string
}

defineProps<{ webhooks: WebhookRow[] }>()

// ── Create form ──────────────────────────────────────────────────────────────

const showCreate = ref(false)

const form = useForm({
    url: '',
    events: ['qr.scanned'],
})

function submit() {
    form.post('/webhooks', {
        onSuccess: () => {
            showCreate.value = false
            form.reset()
        },
    })
}

// ── Delete ───────────────────────────────────────────────────────────────────

function deleteWebhook(id: number) {
    if (!confirm(t('webhooks.deleteConfirm'))) return
    useForm({}).delete(`/webhooks/${id}`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('webhooks.pageTitle')" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <Webhook class="size-5 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('webhooks.title') }}
                    </h1>
                    <p class="text-muted-foreground mt-0.5 text-sm">{{ t('webhooks.subtitle') }}</p>
                </div>
            </div>

            <Dialog v-model:open="showCreate">
                <DialogTrigger as-child>
                    <Button class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200 shrink-0">
                        <Plus class="size-4" />
                        <span class="hidden sm:inline">{{ t('webhooks.addBtn') }}</span>
                        <span class="sm:hidden">{{ t('webhooks.addBtn') }}</span>
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ t('webhooks.addBtn') }}</DialogTitle>
                        <DialogDescription>{{ t('webhooks.subtitle') }}</DialogDescription>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <label for="wh-url" class="text-sm font-medium">{{ t('webhooks.urlLabel') }}</label>
                            <Input
                                id="wh-url"
                                v-model="form.url"
                                type="url"
                                :placeholder="t('webhooks.urlPlaceholder')"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                                :class="{ 'border-destructive': form.errors.url }"
                            />
                            <p v-if="form.errors.url" class="text-destructive text-xs">{{ form.errors.url }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">{{ t('webhooks.eventsLabel') }}</label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm rounded-lg border border-border p-2.5 hover:bg-secondary/50 transition-colors duration-150">
                                <input
                                    v-model="form.events"
                                    type="checkbox"
                                    value="qr.scanned"
                                    class="accent-primary"
                                >
                                <code class="bg-muted rounded px-1.5 py-0.5 text-xs font-mono text-cyan-400">qr.scanned</code>
                                — {{ t('webhooks.eventQrScanned').split(' — ')[1] }}
                            </label>
                        </div>

                        <!-- Signature note -->
                        <p class="text-muted-foreground text-xs rounded-lg border border-border bg-muted/30 px-3 py-2">
                            {{ t('webhooks.signatureNote') }}
                        </p>

                        <DialogFooter>
                            <Button
                                type="submit"
                                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                                :disabled="form.processing || !form.url"
                            >
                                {{ t('webhooks.createBtn') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Section divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

        <!-- Empty state -->
        <div v-if="webhooks.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-14 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 mb-4">
                <Webhook class="size-8 text-primary" />
            </div>
            <h3 class="text-base font-semibold mb-1">{{ t('webhooks.noWebhooks') }}</h3>
            <p class="text-sm text-muted-foreground max-w-xs mb-5">
                Connect your QR scan events to any external service using webhooks.
            </p>
            <Button
                class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                @click="showCreate = true"
            >
                <Plus class="size-4" />
                {{ t('webhooks.addBtn') }}
            </Button>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="wh in webhooks"
                :key="wh.id"
                class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-primary/30 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.08)] transition-all duration-200"
            >
                <!-- Gradient top-border -->
                <div
                    class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                    :class="wh.is_active ? 'via-primary/50' : 'via-border/80'"
                />

                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Webhook icon in circle -->
                                <div
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full"
                                    :class="wh.is_active ? 'bg-primary/10 ring-1 ring-primary/20' : 'bg-muted ring-1 ring-border'"
                                >
                                    <Webhook
                                        class="size-3.5"
                                        :class="wh.is_active ? 'text-primary' : 'text-muted-foreground'"
                                    />
                                </div>
                                <code class="bg-muted max-w-xs truncate rounded-md px-2 py-0.5 text-xs font-mono">
                                    {{ wh.url }}
                                </code>
                                <a
                                    :href="wh.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-muted-foreground hover:text-primary transition-colors duration-150"
                                >
                                    <ExternalLink class="size-3.5" />
                                </a>
                            </div>
                            <p class="mt-1.5 text-xs text-muted-foreground">
                                {{ t('webhooks.colCreated') }}: {{ wh.created_at }}
                                &middot;
                                <span class="text-cyan-400">{{ wh.deliveries_count }}</span>
                                {{ t('webhooks.colDeliveries').toLowerCase() }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge
                                class="text-xs shrink-0"
                                :class="{
                                    'bg-primary/10 text-primary border-primary/20': wh.is_active,
                                    'bg-muted text-muted-foreground border-border': !wh.is_active,
                                }"
                                variant="outline"
                            >
                                <Activity class="size-2.5 mr-1" :class="wh.is_active ? 'text-primary' : ''" />
                                {{ wh.is_active ? t('webhooks.active') : t('webhooks.inactive') }}
                            </Badge>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-xs hover:text-primary transition-colors duration-150"
                                as-child
                            >
                                <Link :href="`/webhooks/${wh.id}/deliveries`">
                                    <span class="hidden sm:inline">{{ t('webhooks.viewDeliveries') }}</span>
                                    <Activity class="size-3.5 sm:hidden" />
                                </Link>
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                                @click="deleteWebhook(wh.id)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Events chips -->
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <code
                            v-for="event in wh.events"
                            :key="event"
                            class="rounded-full bg-cyan-400/10 border border-cyan-400/20 px-2.5 py-0.5 text-xs font-mono text-cyan-400"
                        >
                            {{ event }}
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
