<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Activity, ArrowLeft, CheckCircle2, Clock, ServerCrash, Webhook } from 'lucide-vue-next'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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

function statusIcon(status: string) {
    if (status === 'success') return CheckCircle2
    if (status === 'failed') return ServerCrash
    return Clock
}

function statusColorClass(status: string): string {
    if (status === 'success') return 'text-emerald-400'
    if (status === 'failed') return 'text-destructive'
    return 'text-gold-500'
}

function statusRingClass(status: string): string {
    if (status === 'success') return 'bg-emerald-400/10 ring-1 ring-emerald-400/20'
    if (status === 'failed') return 'bg-destructive/10 ring-1 ring-destructive/20'
    return 'bg-gold-500/10 ring-1 ring-gold-500/20'
}

function statusTopBorder(status: string): string {
    if (status === 'success') return 'via-emerald-400/50'
    if (status === 'failed') return 'via-destructive/50'
    return 'via-gold-500/50'
}

function httpStatusClass(code: number | null): string {
    if (!code) return 'text-muted-foreground'
    if (code >= 200 && code < 300) return 'text-emerald-400'
    if (code >= 400) return 'text-destructive'
    return 'text-gold-500'
}

const formatDate = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>

<template>
    <Head :title="t('webhooks.deliveriesTitle')" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="sm"
                class="gap-1.5 hover:text-primary transition-colors duration-150 shrink-0"
                as-child
            >
                <Link href="/webhooks">
                    <ArrowLeft class="size-4" />
                    <span class="hidden sm:inline">{{ t('common.back') }}</span>
                </Link>
            </Button>

            <!-- Divider dot -->
            <div class="size-1 rounded-full bg-border shrink-0" />

            <div class="flex items-center gap-3 min-w-0">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <Activity class="size-5 text-primary" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl font-bold bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('webhooks.deliveriesTitle') }}
                    </h1>
                    <p class="text-muted-foreground truncate text-xs font-mono">{{ webhook.url }}</p>
                </div>
            </div>
        </div>

        <!-- Section divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

        <!-- Empty state -->
        <div v-if="deliveries.data.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-14 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-muted ring-1 ring-border mb-4">
                <Webhook class="size-8 text-muted-foreground" />
            </div>
            <h3 class="text-base font-semibold mb-1">{{ t('webhooks.noDeliveries') }}</h3>
            <p class="text-sm text-muted-foreground max-w-xs">No webhook deliveries have been attempted yet for this endpoint.</p>
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="d in deliveries.data"
                :key="d.id"
                class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-border/80 transition-colors duration-100"
            >
                <!-- Per-delivery status top-border -->
                <div
                    class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                    :class="statusTopBorder(d.status)"
                />

                <div class="p-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <!-- Status icon in circle -->
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full"
                                :class="statusRingClass(d.status)"
                            >
                                <component
                                    :is="statusIcon(d.status)"
                                    class="size-4"
                                    :class="statusColorClass(d.status)"
                                />
                            </div>

                            <div>
                                <!-- Status badge + event chip -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <Badge
                                        class="text-xs"
                                        :class="{
                                            'bg-emerald-400/10 text-emerald-400 border-emerald-400/20': d.status === 'success',
                                            'bg-destructive/10 text-destructive border-destructive/20': d.status === 'failed',
                                            'bg-gold-500/10 text-gold-500 border-gold-500/20': d.status === 'pending',
                                        }"
                                        variant="outline"
                                    >
                                        {{ d.status === 'success' ? t('webhooks.statusSuccess') : d.status === 'failed' ? t('webhooks.statusFailed') : t('webhooks.statusPending') }}
                                    </Badge>
                                    <code class="rounded-full bg-cyan-400/10 border border-cyan-400/20 px-2.5 py-0.5 text-xs font-mono text-cyan-400">
                                        {{ d.event }}
                                    </code>
                                </div>
                            </div>
                        </div>

                        <!-- Meta info -->
                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span>{{ t('webhooks.colAttempts') }}: <span class="text-foreground font-medium">{{ d.attempts }}</span></span>
                            <span
                                v-if="d.response_status"
                                class="font-mono font-bold"
                                :class="httpStatusClass(d.response_status)"
                            >
                                HTTP {{ d.response_status }}
                            </span>
                            <span class="hidden sm:block">{{ formatDate(d.last_attempted_at ?? d.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Response body -->
                    <div v-if="d.response_body" class="mt-3">
                        <pre class="bg-muted/50 border border-border max-h-24 overflow-auto rounded-lg p-3 text-xs whitespace-pre-wrap font-mono text-muted-foreground">{{ d.response_body }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="deliveries.last_page > 1" class="flex items-center justify-center gap-3">
            <Button
                v-if="deliveries.prev_page_url"
                variant="outline"
                size="sm"
                class="gap-1.5 hover:border-primary/40 transition-colors duration-150"
                as-child
            >
                <Link :href="deliveries.prev_page_url">
                    <ArrowLeft class="size-3.5" />
                    {{ t('common.previous') }}
                </Link>
            </Button>
            <span class="text-muted-foreground text-sm font-medium">
                {{ deliveries.current_page }} / {{ deliveries.last_page }}
            </span>
            <Button
                v-if="deliveries.next_page_url"
                variant="outline"
                size="sm"
                class="gap-1.5 hover:border-primary/40 transition-colors duration-150"
                as-child
            >
                <Link :href="deliveries.next_page_url">
                    {{ t('common.next') }}
                </Link>
            </Button>
        </div>
    </div>
</template>
