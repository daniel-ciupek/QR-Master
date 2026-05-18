<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, Minus, Zap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion'

const { t } = useI18n()

const props = defineProps<{
    currentPlan: string
    onTrial: boolean
    trialEndsAt: string | null
}>()

const yearly = ref(false)
const yearlyDiscount = 0.2

interface Plan {
    id: string
    badge?: string
    priceMonthly: number
    features: string[]
    cta: string
    highlighted: boolean
}

const plans = computed<Plan[]>(() => [
    {
        id: 'free',
        priceMonthly: 0,
        features: ['freeQr', 'staticQr', 'basicColors', 'communitySupport'],
        cta: props.currentPlan === 'free' ? 'currentPlan' : 'getStarted',
        highlighted: false,
    },
    {
        id: 'pro',
        badge: 'popular',
        priceMonthly: 49,
        features: ['proQr', 'analytics', 'logo', 'gradients', 'vcardWifi', 'abTest', 'emailSupport'],
        cta: props.currentPlan === 'pro' ? 'currentPlan' : 'upgradeToPro',
        highlighted: true,
    },
    {
        id: 'business',
        priceMonthly: 199,
        features: ['businessQr', 'api', 'bulkOps', 'smartRedirect', 'realtime', 'webhooks', 'prioritySupport'],
        cta: props.currentPlan === 'business' ? 'currentPlan' : 'upgradeToBusiness',
        highlighted: false,
    },
    {
        id: 'enterprise',
        priceMonthly: 0,
        features: ['unlimited', 'customDomain', 'whiteLabel', 'dpa', 'sla', 'sso', 'dedicatedSupport'],
        cta: 'contactSales',
        highlighted: false,
    },
])

function displayPrice(plan: Plan): string {
    if (plan.id === 'enterprise') return t('pricing.custom')
    if (plan.priceMonthly === 0) return t('pricing.free')
    const price = yearly.value
        ? Math.round(plan.priceMonthly * (1 - yearlyDiscount))
        : plan.priceMonthly
    return `${price} PLN`
}

interface Feature {
    key: string
    free: boolean | string
    pro: boolean | string
    business: boolean | string
    enterprise: boolean | string
    [tier: string]: boolean | string
}

const comparisonFeatures: Feature[] = [
    { key: 'dynamicQr', free: '5', pro: '100', business: '1 000', enterprise: t('pricing.unlimited') },
    { key: 'scansPerMonth', free: '100', pro: '50 000', business: '1 000 000', enterprise: t('pricing.unlimited') },
    { key: 'analytics', free: false, pro: true, business: true, enterprise: true },
    { key: 'abTest', free: false, pro: true, business: true, enterprise: true },
    { key: 'smartRedirect', free: false, pro: false, business: true, enterprise: true },
    { key: 'api', free: false, pro: false, business: true, enterprise: true },
    { key: 'webhooks', free: false, pro: false, business: true, enterprise: true },
    { key: 'customDomain', free: false, pro: false, business: false, enterprise: true },
    { key: 'whiteLabel', free: false, pro: false, business: false, enterprise: true },
    { key: 'sso', free: false, pro: false, business: false, enterprise: true },
]

const faqs = ['whatIsDynamic', 'canChange', 'trialInfo', 'cancelAnytime', 'vatInfo', 'enterpriseCustom']

function handleCta(plan: Plan): void {
    if (plan.id === props.currentPlan) return
    if (plan.id === 'enterprise') {
        window.location.href = 'mailto:sales@qr-master.app'
        return
    }
    if (plan.id === 'free') {
        router.visit('/register')
        return
    }
    router.visit('/billing/subscribe/' + plan.id)
}
</script>

<template>
    <Head :title="t('pricing.pageTitle')" />

    <div class="relative min-h-screen bg-background overflow-hidden">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />
        <!-- Top radial glow -->
        <div class="absolute left-1/2 -top-32 -translate-x-1/2 size-[800px] rounded-full bg-primary/5 blur-3xl pointer-events-none" />

        <!-- Header -->
        <div class="relative mx-auto max-w-6xl px-4 py-16 text-center">
            <Badge v-if="props.onTrial" variant="secondary" class="mb-4 border-gold-500/30 bg-gold-500/10 text-gold-500">
                {{ t('pricing.trialBadge', { date: props.trialEndsAt }) }}
            </Badge>
            <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-4xl font-bold tracking-tight text-transparent sm:text-5xl">
                {{ t('pricing.title') }}
            </h1>
            <p class="mt-4 text-lg text-muted-foreground">
                {{ t('pricing.subtitle') }}
            </p>

            <!-- Billing toggle -->
            <div class="mt-8 flex items-center justify-center gap-3">
                <span class="text-sm" :class="!yearly ? 'font-semibold' : 'text-muted-foreground'">
                    {{ t('pricing.monthly') }}
                </span>
                <button
                    type="button"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="yearly ? 'bg-primary shadow-[0_0_12px_oklch(0.66_0.25_285/0.4)]' : 'bg-input'"
                    @click="yearly = !yearly"
                >
                    <span
                        class="inline-block h-4 w-4 transform rounded-full bg-background shadow-md transition-transform duration-200"
                        :class="yearly ? 'translate-x-6' : 'translate-x-1'"
                    />
                </button>
                <span class="text-sm" :class="yearly ? 'font-semibold' : 'text-muted-foreground'">
                    {{ t('pricing.yearly') }}
                    <Badge class="ml-1 bg-green-500/10 text-green-500 border-green-500/30 text-xs">-20%</Badge>
                </span>
            </div>
        </div>

        <!-- Plan cards -->
        <div class="relative mx-auto max-w-6xl px-4 pb-16">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="relative overflow-hidden rounded-2xl border p-6 transition-all duration-200"
                    :class="[
                        plan.highlighted
                            ? 'border-primary/60 bg-primary/5 shadow-[0_0_40px_oklch(0.66_0.25_285/0.15)] ring-1 ring-primary/30 hover:shadow-[0_0_60px_oklch(0.66_0.25_285/0.22)]'
                            : 'border-border bg-card hover:border-primary/30 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)]',
                        plan.id === props.currentPlan ? 'ring-1 ring-green-500/50' : '',
                    ]"
                >
                    <!-- Gradient top-border -->
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                        :class="plan.highlighted ? 'via-primary/80' : 'via-border/60'"
                    />

                    <Badge
                        v-if="plan.badge"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary text-primary-foreground shadow-[0_0_12px_oklch(0.66_0.25_285/0.4)]"
                    >
                        {{ t(`pricing.badge.${plan.badge}`) }}
                    </Badge>

                    <div class="mb-4 flex items-center gap-2">
                        <div v-if="plan.highlighted" class="flex size-7 items-center justify-center rounded-full bg-primary/20 ring-1 ring-primary/30">
                            <Zap class="size-4 text-primary" />
                        </div>
                        <h2 class="text-lg font-bold capitalize" :class="plan.highlighted ? 'text-primary' : ''">
                            {{ t(`pricing.plans.${plan.id}.name`) }}
                        </h2>
                    </div>

                    <div class="mb-1">
                        <span class="text-3xl font-bold">{{ displayPrice(plan) }}</span>
                        <span v-if="plan.priceMonthly > 0" class="text-sm text-muted-foreground">/{{ t('pricing.mo') }}</span>
                    </div>
                    <p class="mb-6 text-xs text-muted-foreground">{{ t(`pricing.plans.${plan.id}.desc`) }}</p>

                    <Button
                        class="w-full transition-shadow duration-200"
                        :class="plan.highlighted ? 'shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)]' : ''"
                        :variant="plan.highlighted ? 'default' : 'outline'"
                        :disabled="plan.id === props.currentPlan"
                        @click="handleCta(plan)"
                    >
                        {{ t(`pricing.cta.${plan.cta}`) }}
                    </Button>

                    <ul class="mt-6 space-y-2">
                        <li
                            v-for="feature in plan.features"
                            :key="feature"
                            class="flex items-start gap-2 text-sm"
                        >
                            <Check class="mt-0.5 size-4 shrink-0 text-green-500" />
                            <span>{{ t(`pricing.features.${feature}`) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Comparison table -->
        <div class="mx-auto max-w-6xl px-4 pb-16">
            <h2 class="mb-8 text-center text-2xl font-bold">{{ t('pricing.comparisonTitle') }}</h2>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="p-4 text-left font-semibold">{{ t('pricing.feature') }}</th>
                            <th class="p-4 text-center font-semibold">Free</th>
                            <th class="p-4 text-center font-semibold text-primary">Pro</th>
                            <th class="p-4 text-center font-semibold">Business</th>
                            <th class="p-4 text-center font-semibold">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="feature in comparisonFeatures"
                            :key="feature.key"
                            class="border-b border-border last:border-0 hover:bg-muted/20"
                        >
                            <td class="p-4 font-medium">{{ t(`pricing.comparison.${feature.key}`) }}</td>
                            <td
                                v-for="tier in ['free', 'pro', 'business', 'enterprise']"
                                :key="tier"
                                class="p-4 text-center"
                            >
                                <template v-if="typeof feature[tier] === 'boolean'">
                                    <Check v-if="feature[tier]" class="mx-auto h-4 w-4 text-green-500" />
                                    <Minus v-else class="mx-auto h-4 w-4 text-muted-foreground" />
                                </template>
                                <template v-else>
                                    <span class="text-xs font-medium">{{ feature[tier] }}</span>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FAQ -->
        <div class="mx-auto max-w-2xl px-4 pb-24">
            <h2 class="mb-8 text-center text-2xl font-bold">{{ t('pricing.faqTitle') }}</h2>
            <Accordion type="multiple">
                <AccordionItem
                    v-for="faq in faqs"
                    :key="faq"
                    :value="faq"
                >
                    <AccordionTrigger>{{ t(`pricing.faq.${faq}.q`) }}</AccordionTrigger>
                    <AccordionContent>
                        <p class="text-muted-foreground">{{ t(`pricing.faq.${faq}.a`) }}</p>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    </div>
</template>
