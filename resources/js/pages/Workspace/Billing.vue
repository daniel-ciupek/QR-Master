<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Check, CreditCard, ExternalLink, Star, Zap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    plan: string
    planName: string
    onTrial: boolean
    trialEndsAt: string | null
    subscribed: boolean
    cancelledAt: string | null
}>()

const isFree = computed(() => props.plan === 'free')

interface PlanItem {
    key: string
    name: string
    price: string
    features: string[]
    accent: string
    topBorder: string
    badgeClass: string
    featured: boolean
}

const plans: PlanItem[] = [
    {
        key: 'pro',
        name: 'Pro',
        price: '49 PLN',
        features: ['100 QR codes', '50k scans/mo', 'Analytics', 'A/B testing'],
        accent: 'hover:border-primary/50 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.12)]',
        topBorder: 'via-primary/60',
        badgeClass: 'bg-primary/10 text-primary border-primary/30',
        featured: false,
    },
    {
        key: 'business',
        name: 'Business',
        price: '199 PLN',
        features: ['1000 QR codes', '1M scans/mo', 'Smart redirect', 'API access', 'Webhooks'],
        accent: 'hover:border-cyan-400/50 hover:shadow-[0_0_24px_oklch(0.72_0.15_200/0.12)]',
        topBorder: 'via-cyan-400/60',
        badgeClass: 'bg-cyan-400/10 text-cyan-400 border-cyan-400/30',
        featured: true,
    },
    {
        key: 'enterprise',
        name: 'Enterprise',
        price: t('workspace.billing.contact'),
        features: ['Unlimited QR codes', 'Unlimited scans', 'SSO', 'SCIM', 'SLA'],
        accent: 'hover:border-gold-500/50 hover:shadow-[0_0_24px_oklch(0.78_0.15_85/0.12)]',
        topBorder: 'via-gold-500/60',
        badgeClass: 'bg-gold-500/10 text-gold-500 border-gold-500/30',
        featured: false,
    },
]

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}

function subscribePlan(planKey: string): void {
    router.visit(route('workspaces.billing.subscribe', { team: props.team.slug, plan: planKey }))
}

function openPortal(): void {
    router.visit(route('workspaces.billing.portal', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.billing.title')}`" />

    <div class="space-y-8 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
                variant="ghost"
                size="icon"
                class="shrink-0 self-start hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                :aria-label="t('common.back')"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.billing.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Current Plan card -->
        <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-gold-500/30">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20">
                        <CreditCard class="size-4 text-gold-500" />
                    </div>
                    {{ t('workspace.billing.currentPlan') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.billing.currentPlanDesc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge
                            :class="isFree ? 'bg-muted text-muted-foreground border-border' : 'bg-primary/10 text-primary border-primary/30'"
                            class="border font-semibold"
                        >
                            {{ planName }}
                        </Badge>
                        <span
                            v-if="onTrial && trialEndsAt"
                            class="text-sm text-gold-500"
                        >
                            {{ t('workspace.billing.trialEnds', { date: trialEndsAt }) }}
                        </span>
                        <span
                            v-if="cancelledAt"
                            class="text-sm text-destructive"
                        >
                            {{ t('workspace.billing.cancelsOn', { date: cancelledAt }) }}
                        </span>
                    </div>
                    <Button
                        v-if="subscribed"
                        variant="outline"
                        size="sm"
                        class="shrink-0 hover:border-primary/40 transition-colors duration-150"
                        @click="openPortal"
                    >
                        <ExternalLink class="mr-2 size-4" />
                        {{ t('workspace.billing.manageSubscription') }}
                    </Button>
                </div>
            </CardContent>
        </div>

        <!-- Plans grid -->
        <div>
            <h2 class="mb-4 text-lg font-semibold">
                {{ t('workspace.billing.choosePlan') }}
            </h2>
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-3">
                <div
                    v-for="p in plans"
                    :key="p.key"
                    class="relative rounded-xl border bg-card overflow-hidden transition-all duration-200"
                    :class="[
                        p.accent,
                        plan === p.key ? 'border-primary/50 shadow-[0_0_20px_oklch(0.66_0.25_285/0.12)]' : 'border-border',
                        p.featured ? 'ring-1 ring-cyan-400/20' : '',
                    ]"
                >
                    <div :class="`absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent ${p.topBorder} to-transparent`" />

                    <!-- Featured badge -->
                    <div v-if="p.featured" class="absolute right-3 top-3">
                        <span class="inline-flex items-center gap-1 rounded-full bg-cyan-400/10 px-2.5 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/20">
                            <Star class="size-3 fill-cyan-400" />
                            Popular
                        </span>
                    </div>

                    <!-- Enterprise badge -->
                    <div v-if="p.key === 'enterprise'" class="absolute right-3 top-3">
                        <span class="inline-flex items-center gap-1 rounded-full bg-gold-500/10 px-2.5 py-0.5 text-xs font-medium text-gold-500 ring-1 ring-gold-500/20">
                            <Star class="size-3 fill-gold-500" />
                            Enterprise
                        </span>
                    </div>

                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">{{ p.name }}</CardTitle>
                            <Check v-if="plan === p.key" class="size-4 text-primary" />
                        </div>
                        <div class="mt-2">
                            <span class="text-2xl font-bold text-foreground">{{ p.price }}</span>
                            <span v-if="p.key !== 'enterprise'" class="ml-1 text-sm text-muted-foreground">/{{ t('workspace.billing.month') }}</span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="feature in p.features"
                                :key="feature"
                                class="flex items-center gap-2"
                            >
                                <Zap :class="`size-3 shrink-0 ${p.key === 'enterprise' ? 'text-gold-500' : p.key === 'business' ? 'text-cyan-400' : 'text-primary'}`" />
                                {{ feature }}
                            </li>
                        </ul>

                        <!-- Divider -->
                        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                        <Button
                            v-if="p.key !== 'enterprise'"
                            class="w-full transition-shadow duration-200"
                            :class="plan !== p.key ? 'shadow-[0_0_16px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.4)]' : ''"
                            :variant="plan === p.key ? 'outline' : 'default'"
                            :disabled="plan === p.key && subscribed"
                            @click="subscribePlan(p.key)"
                        >
                            {{ plan === p.key && subscribed ? t('workspace.billing.currentPlan') : t('workspace.billing.selectPlan') }}
                        </Button>
                        <Button
                            v-else
                            class="w-full hover:border-gold-500/40 transition-colors duration-150"
                            variant="outline"
                            as="a"
                            href="mailto:enterprise@qr-master.app"
                        >
                            {{ t('workspace.billing.contact') }}
                        </Button>
                    </CardContent>
                </div>
            </div>
        </div>
    </div>
</template>
