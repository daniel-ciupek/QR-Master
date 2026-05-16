<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ExternalLink, Plus, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

// ── Status badge ─────────────────────────────────────────────────────────────

const statusVariant = (active: boolean) => (active ? 'default' : 'secondary') as 'default' | 'secondary'
</script>

<template>
    <Head :title="t('webhooks.pageTitle')" />

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ t('webhooks.title') }}</h1>
                <p class="text-muted-foreground mt-1 text-sm">{{ t('webhooks.subtitle') }}</p>
            </div>

            <Dialog v-model:open="showCreate">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="mr-2 size-4" />
                        {{ t('webhooks.addBtn') }}
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
                                :class="{ 'border-destructive': form.errors.url }"
                            />
                            <p v-if="form.errors.url" class="text-destructive text-xs">{{ form.errors.url }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">{{ t('webhooks.eventsLabel') }}</label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    v-model="form.events"
                                    type="checkbox"
                                    value="qr.scanned"
                                    class="accent-primary"
                                >
                                <code class="bg-muted rounded px-1 text-xs">qr.scanned</code>
                                — {{ t('webhooks.eventQrScanned').split(' — ')[1] }}
                            </label>
                        </div>

                        <!-- Signature note -->
                        <p class="text-muted-foreground text-xs">
                            {{ t('webhooks.signatureNote') }}
                        </p>

                        <DialogFooter>
                            <Button type="submit" :disabled="form.processing || !form.url">
                                {{ t('webhooks.createBtn') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Webhooks list -->
        <Card v-if="webhooks.length === 0">
            <CardContent class="text-muted-foreground py-12 text-center text-sm">
                {{ t('webhooks.noWebhooks') }}
            </CardContent>
        </Card>

        <div v-else class="space-y-3">
            <Card v-for="wh in webhooks" :key="wh.id">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <CardTitle class="flex items-center gap-2 text-sm font-medium">
                                <code class="bg-muted max-w-sm truncate rounded px-2 py-0.5 text-xs font-mono">
                                    {{ wh.url }}
                                </code>
                                <a :href="wh.url" target="_blank" rel="noopener noreferrer">
                                    <ExternalLink class="text-muted-foreground size-3.5" />
                                </a>
                            </CardTitle>
                            <CardDescription class="mt-1">
                                {{ t('webhooks.colCreated') }}: {{ wh.created_at }}
                                · {{ wh.deliveries_count }} {{ t('webhooks.colDeliveries').toLowerCase() }}
                            </CardDescription>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="statusVariant(wh.is_active)">
                                {{ wh.is_active ? t('webhooks.active') : t('webhooks.inactive') }}
                            </Badge>

                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="`/webhooks/${wh.id}/deliveries`">
                                    {{ t('webhooks.viewDeliveries') }}
                                </Link>
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive size-8"
                                @click="deleteWebhook(wh.id)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pt-0">
                    <div class="flex flex-wrap gap-1">
                        <code
                            v-for="event in wh.events"
                            :key="event"
                            class="bg-muted rounded px-2 py-0.5 text-xs font-mono"
                        >
                            {{ event }}
                        </code>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
