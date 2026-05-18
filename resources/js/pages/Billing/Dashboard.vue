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

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent sm:text-3xl">
                    {{ t('billing.dashboard.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ t('billing.dashboard.subtitle') }}</p>
            </div>
            <Button
                variant="outline"
                as="a"
                href="/pricing"
                class="transition-colors duration-150 hover:border-primary/40 hover:text-primary self-start sm:self-auto"
            >
                {{ t('billing.dashboard.viewPlans') }}
            </Button>
        </div>

        <!-- Current plan card -->
        <div class="relative overflow-hidden rounded-2xl border bg-card p-6 transition-all duration-200"
            :class="isFree ? 'border-border' : 'border-primary/30 shadow-[0_0_32px_oklch(0.66_0.25_285/0.08)]'"
        >
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                :class="isFree ? 'via-border/60' : 'via-gold-500/60'"
            />
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full"
                            :class="isFree ? 'bg-muted' : 'bg-gold-500/10 ring-1 ring-gold-500/20'"
                        >
                            <Zap class="size-4" :class="isFree ? 'text-muted-foreground' : 'text-gold-500'" />
                        </div>
                        <span class="text-lg font-bold">{{ planName }}</span>
                        <span
                            v-if="onTrial"
                            class="inline-flex items-center rounded-full bg-cyan-400/10 px-2 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/20"
                        >Trial</span>
                        <Badge v-if="cancelledAt" variant="destructive" class="text-xs">{{ t('billing.dashboard.cancelled') }}</Badge>
                        <span
                            v-if="subscribed && !cancelledAt"
                            class="inline-flex items-center rounded-full bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-500 ring-1 ring-green-500/20"
                        >{{ t('billing.dashboard.active') }}</span>
                    </div>
                    <p v-if="onTrial && trialEndsAt" class="text-sm text-muted-foreground pl-10">
                        {{ t('billing.dashboard.trialEnds', { date: trialEndsAt }) }}
                    </p>
                    <p v-if="renewsAtDate && !cancelledAt" class="text-sm text-muted-foreground pl-10">
                        {{ t('billing.dashboard.renewsAt', { date: renewsAtDate }) }}
                    </p>
                    <p v-if="cancelledAt" class="text-sm text-muted-foreground pl-10">
                        {{ t('billing.dashboard.accessUntil', { date: cancelledAt }) }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <Button
                        v-if="subscribed || onTrial"
                        variant="outline"
                        size="sm"
                        class="transition-colors duration-150 hover:border-primary/40 hover:text-primary"
                        @click="router.visit('/billing/portal')"
                    >
                        <CreditCard class="mr-2 size-4" />
                        {{ t('billing.dashboard.manageSubscription') }}
                    </Button>
                    <Button
                        v-if="isFree"
                        size="sm"
                        class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="router.visit('/pricing')"
                    >
                        <Zap class="mr-2 size-4" />
                        {{ t('billing.dashboard.upgrade') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Usage limits -->
        <div class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 space-y-5">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <h2 class="text-sm font-semibold">{{ t('billing.dashboard.usageTitle') }}</h2>

            <!-- Dynamic QR codes -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">{{ t('billing.dashboard.dynamicQr') }}</span>
                    <div class="flex items-center gap-2">
                        <AlertTriangle
                            v-if="usageWarning(limits.dynamicQr)"
                            class="size-4 text-amber-500"
                        />
                        <span :class="usageWarning(limits.dynamicQr) ? 'text-amber-500' : 'text-muted-foreground'">
                            {{ limitLabel(limits.dynamicQr) }}
                        </span>
                    </div>
                </div>
                <div v-if="limits.dynamicQr.max !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="usageWarning(limits.dynamicQr) ? 'bg-amber-500' : 'bg-primary'"
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
                <div v-if="limits.scansPerMonth.max !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                    <div
                        class="h-full rounded-full bg-cyan-400 transition-all duration-300"
                        :style="{ width: `${usagePercent(limits.scansPerMonth)}%` }"
                    />
                </div>
                <div v-else class="text-xs text-muted-foreground">{{ t('billing.dashboard.unlimited') }}</div>
            </div>
        </div>

        <!-- Features -->
        <div class="relative overflow-hidden rounded-2xl border border-border bg-card p-6">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
            <h2 class="mb-4 text-sm font-semibold">{{ t('billing.dashboard.featuresTitle') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="[key, enabled] in Object.entries(features)"
                    :key="key"
                    class="flex items-center gap-3 rounded-lg border p-3 transition-colors duration-150"
                    :class="enabled
                        ? 'border-green-500/20 bg-green-500/5'
                        : 'border-border opacity-50 hover:opacity-70'"
                >
                    <component
                        :is="enabled ? Check : Minus"
                        class="size-4 shrink-0"
                        :class="enabled ? 'text-green-500' : 'text-muted-foreground'"
                    />
                    <span class="text-sm">{{ t(`billing.dashboard.featureNames.${key}`) }}</span>
                    <Button
                        v-if="!enabled"
                        variant="ghost"
                        size="sm"
                        class="ml-auto h-6 px-2 text-xs text-primary transition-colors hover:bg-primary/10"
                        @click="router.visit('/pricing')"
                    >
                        <ExternalLink class="size-3" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Upsell banner for Free users -->
        <div
            v-if="isFree"
            class="relative overflow-hidden rounded-2xl border border-primary/30 bg-primary/5 p-6 text-center space-y-3"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-primary/20 ring-1 ring-primary/30">
                <Zap class="size-6 text-primary" />
            </div>
            <h2 class="font-semibold">{{ t('billing.dashboard.upsellTitle') }}</h2>
            <p class="text-sm text-muted-foreground">{{ t('billing.dashboard.upsellSubtitle') }}</p>
            <Button
                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                @click="router.visit('/billing/subscribe/pro')"
            >
                {{ t('billing.dashboard.upsellCta') }}
            </Button>
        </div>
    </div>
</template>
