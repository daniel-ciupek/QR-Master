<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { AlertTriangle, Check, CreditCard, ExternalLink, Minus, Zap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface Limit {
    used: number | null
    max: number | null
}

const props = defineProps<{
    plan: string
    planName: string
    onTrial: boolean
    trialEndsAt: string | null
    subscribed: boolean
    cancelledAt: string | null
    renewsAt: number | null
    limits: {
        dynamicQr: Limit
        scansPerMonth: Limit
    }
    features: {
        analytics: boolean
        abTest: boolean
        smartRedirect: boolean
        api: boolean
    }
}>()

const renewsAtDate = computed(() =>
    props.renewsAt ? new Date(props.renewsAt * 1000).toLocaleDateString() : null
)

const isFree = computed(() => props.plan === 'free')

function usagePercent(limit: Limit): number {
    if (limit.max === null || limit.used === null) return 0
    return Math.min(100, Math.round((limit.used / limit.max) * 100))
}

function usageWarning(limit: Limit): boolean {
    return usagePercent(limit) >= 80
}

function limitLabel(limit: Limit): string {
    if (limit.max === null) return t('billing.dashboard.unlimited')
    if (limit.used === null) return `— / ${limit.max.toLocaleString()}`
    return `${limit.used.toLocaleString()} / ${limit.max.toLocaleString()}`
}
</script>

<template>
    <Head :title="t('billing.dashboard.pageTitle')" />

    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ t('billing.dashboard.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('billing.dashboard.subtitle') }}</p>
            </div>
            <Button variant="outline" as="a" href="/pricing">
                {{ t('billing.dashboard.viewPlans') }}
            </Button>
        </div>

        <!-- Current plan card -->
        <div class="rounded-2xl border border-border bg-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Zap class="h-5 w-5 text-primary" />
                        <span class="text-lg font-bold">{{ planName }}</span>
                        <Badge v-if="onTrial" variant="secondary">Trial</Badge>
                        <Badge v-if="cancelledAt" variant="destructive">{{ t('billing.dashboard.cancelled') }}</Badge>
                        <Badge v-if="subscribed && !cancelledAt" class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            {{ t('billing.dashboard.active') }}
                        </Badge>
                    </div>
                    <p v-if="onTrial && trialEndsAt" class="text-sm text-muted-foreground">
                        {{ t('billing.dashboard.trialEnds', { date: trialEndsAt }) }}
                    </p>
                    <p v-if="renewsAtDate && !cancelledAt" class="text-sm text-muted-foreground">
                        {{ t('billing.dashboard.renewsAt', { date: renewsAtDate }) }}
                    </p>
                    <p v-if="cancelledAt" class="text-sm text-muted-foreground">
                        {{ t('billing.dashboard.accessUntil', { date: cancelledAt }) }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Button
                        v-if="subscribed || onTrial"
                        variant="outline"
                        size="sm"
                        @click="router.visit('/billing/portal')"
                    >
                        <CreditCard class="mr-2 h-4 w-4" />
                        {{ t('billing.dashboard.manageSubscription') }}
                    </Button>
                    <Button
                        v-if="isFree"
                        size="sm"
                        @click="router.visit('/pricing')"
                    >
                        <Zap class="mr-2 h-4 w-4" />
                        {{ t('billing.dashboard.upgrade') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Usage limits -->
        <div class="rounded-2xl border border-border bg-card p-6 space-y-5">
            <h2 class="text-sm font-semibold">{{ t('billing.dashboard.usageTitle') }}</h2>

            <!-- Dynamic QR codes -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">{{ t('billing.dashboard.dynamicQr') }}</span>
                    <div class="flex items-center gap-2">
                        <AlertTriangle
                            v-if="usageWarning(limits.dynamicQr)"
                            class="h-4 w-4 text-yellow-500"
                        />
                        <span :class="usageWarning(limits.dynamicQr) ? 'text-yellow-600 dark:text-yellow-400' : 'text-muted-foreground'">
                            {{ limitLabel(limits.dynamicQr) }}
                        </span>
                    </div>
                </div>
                <div v-if="limits.dynamicQr.max !== null" class="h-2 w-full rounded-full bg-muted">
                    <div
                        class="h-2 rounded-full transition-all"
                        :class="usageWarning(limits.dynamicQr) ? 'bg-yellow-500' : 'bg-primary'"
                        :style="{ width: `${usagePercent(limits.dynamicQr)}%` }"
                    />
                </div>
                <div v-else class="text-xs text-muted-foreground">{{ t('billing.dashboard.unlimited') }}</div>
            </div>

            <!-- Scans per month -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">{{ t('billing.dashboard.scansPerMonth') }}</span>
                    <span class="text-muted-foreground">{{ limitLabel(limits.scansPerMonth) }}</span>
                </div>
                <div v-if="limits.scansPerMonth.max !== null" class="h-2 w-full rounded-full bg-muted">
                    <div class="h-2 w-0 rounded-full bg-primary" />
                </div>
                <div v-else class="text-xs text-muted-foreground">{{ t('billing.dashboard.unlimited') }}</div>
            </div>
        </div>

        <!-- Features -->
        <div class="rounded-2xl border border-border bg-card p-6">
            <h2 class="mb-4 text-sm font-semibold">{{ t('billing.dashboard.featuresTitle') }}</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div
                    v-for="[key, enabled] in Object.entries(features)"
                    :key="key"
                    class="flex items-center gap-3 rounded-lg border border-border p-3"
                    :class="enabled ? '' : 'opacity-50'"
                >
                    <component
                        :is="enabled ? Check : Minus"
                        class="h-4 w-4 shrink-0"
                        :class="enabled ? 'text-green-500' : 'text-muted-foreground'"
                    />
                    <span class="text-sm">{{ t(`billing.dashboard.featureNames.${key}`) }}</span>
                    <Button
                        v-if="!enabled"
                        variant="ghost"
                        size="sm"
                        class="ml-auto h-6 px-2 text-xs text-primary"
                        @click="router.visit('/pricing')"
                    >
                        <ExternalLink class="h-3 w-3" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Upsell banner for Free users -->
        <div
            v-if="isFree"
            class="rounded-2xl border border-primary/30 bg-primary/5 p-6 text-center space-y-3"
        >
            <Zap class="mx-auto h-8 w-8 text-primary" />
            <h2 class="font-semibold">{{ t('billing.dashboard.upsellTitle') }}</h2>
            <p class="text-sm text-muted-foreground">{{ t('billing.dashboard.upsellSubtitle') }}</p>
            <Button @click="router.visit('/billing/subscribe/pro')">
                {{ t('billing.dashboard.upsellCta') }}
            </Button>
        </div>
    </div>
</template>
