<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface Delivery {
    id: number
    event: string
    status: 'pending' | 'success' | 'failed'
    attempts: number
    response_status: number | null
    response_body: string | null
    delivered_at: string | null
    last_attempted_at: string | null
    created_at: string
}

defineProps<{
    webhook: { id: number; url: string }
    deliveries: {
        data: Delivery[]
        current_page: number
        last_page: number
        next_page_url: string | null
        prev_page_url: string | null
    }
}>()

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    if (status === 'success') return 'default'
    if (status === 'failed') return 'destructive'
    return 'secondary'
}

const statusLabel = (status: string) => {
    if (status === 'success') return t('webhooks.statusSuccess')
    if (status === 'failed') return t('webhooks.statusFailed')
    return t('webhooks.statusPending')
}

const formatDate = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>

<template>
    <Head :title="t('webhooks.deliveriesTitle')" />

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Button
                variant="ghost"
                size="sm"
                as-child
            >
                <Link href="/webhooks">{{ t('common.back') }}</Link>
            </Button>
            <div>
                <h1 class="text-xl font-bold">{{ t('webhooks.deliveriesTitle') }}</h1>
                <p class="text-muted-foreground truncate text-xs font-mono">{{ webhook.url }}</p>
            </div>
        </div>

        <!-- Deliveries -->
        <Card v-if="deliveries.data.length === 0">
            <CardContent class="text-muted-foreground py-12 text-center text-sm">
                {{ t('webhooks.noDeliveries') }}
            </CardContent>
        </Card>

        <div v-else class="space-y-2">
            <Card v-for="d in deliveries.data" :key="d.id">
                <CardHeader class="pb-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <Badge :variant="statusVariant(d.status)">{{ statusLabel(d.status) }}</Badge>
                            <code class="bg-muted rounded px-1.5 py-0.5 text-xs font-mono">{{ d.event }}</code>
                        </div>
                        <div class="text-muted-foreground flex items-center gap-4 text-xs">
                            <span>{{ t('webhooks.colAttempts') }}: {{ d.attempts }}</span>
                            <span v-if="d.response_status" class="font-medium">HTTP {{ d.response_status }}</span>
                            <span>{{ formatDate(d.last_attempted_at ?? d.created_at) }}</span>
                        </div>
                    </div>
                </CardHeader>
                <CardContent v-if="d.response_body" class="pt-0">
                    <pre class="bg-muted max-h-24 overflow-auto rounded p-2 text-xs whitespace-pre-wrap">{{ d.response_body }}</pre>
                </CardContent>
            </Card>
        </div>

        <!-- Pagination -->
        <div v-if="deliveries.last_page > 1" class="flex justify-center gap-2">
            <Button
                v-if="deliveries.prev_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="deliveries.prev_page_url">{{ t('common.previous') }}</Link>
            </Button>
            <span class="text-muted-foreground self-center text-sm">
                {{ deliveries.current_page }} / {{ deliveries.last_page }}
            </span>
            <Button
                v-if="deliveries.next_page_url"
                variant="outline"
                size="sm"
                as-child
            >
                <Link :href="deliveries.next_page_url">{{ t('common.next') }}</Link>
            </Button>
        </div>
    </div>
</template>
