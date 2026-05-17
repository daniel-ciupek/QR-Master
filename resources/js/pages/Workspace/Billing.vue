<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, Check, CreditCard, ExternalLink, Zap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

const plans = [
    { key: 'pro', name: 'Pro', price: '49 PLN', features: ['100 QR codes', '50k scans/mo', 'Analytics', 'A/B testing'] },
    { key: 'business', name: 'Business', price: '199 PLN', features: ['1000 QR codes', '1M scans/mo', 'Smart redirect', 'API access', 'Webhooks'] },
    { key: 'enterprise', name: 'Enterprise', price: t('workspace.billing.contact'), features: ['Unlimited QR codes', 'Unlimited scans', 'SSO', 'SCIM', 'SLA'] },
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

    <div class="mx-auto max-w-4xl space-y-8 p-6">
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                :aria-label="t('common.back')"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('workspace.billing.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ team.name }}
                </p>
            </div>
        </div>

        <!-- Current Plan -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CreditCard class="size-5" />
                    {{ t('workspace.billing.currentPlan') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.billing.currentPlanDesc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Badge :variant="isFree ? 'secondary' : 'default'">
                            {{ planName }}
                        </Badge>
                        <span
                            v-if="onTrial && trialEndsAt"
                            class="text-sm text-amber-600 dark:text-amber-400"
                        >
                            {{ t('workspace.billing.trialEnds', { date: trialEndsAt }) }}
                        </span>
                        <span
                            v-if="cancelledAt"
                            class="text-sm text-red-500"
                        >
                            {{ t('workspace.billing.cancelsOn', { date: cancelledAt }) }}
                        </span>
                    </div>
                    <Button
                        v-if="subscribed"
                        variant="outline"
                        size="sm"
                        @click="openPortal"
                    >
                        <ExternalLink class="mr-2 size-4" />
                        {{ t('workspace.billing.manageSubscription') }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Plans -->
        <div>
            <h2 class="mb-4 text-lg font-semibold">
                {{ t('workspace.billing.choosePlan') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <Card
                    v-for="p in plans"
                    :key="p.key"
                    :class="plan === p.key ? 'ring-2 ring-primary' : ''"
                >
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">
                                {{ p.name }}
                            </CardTitle>
                            <Check
                                v-if="plan === p.key"
                                class="size-4 text-primary"
                            />
                        </div>
                        <CardDescription>
                            <span class="text-lg font-bold text-foreground">{{ p.price }}</span>
                            <span v-if="p.key !== 'enterprise'" class="text-muted-foreground">/{{ t('workspace.billing.month') }}</span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <ul class="space-y-1 text-sm">
                            <li
                                v-for="feature in p.features"
                                :key="feature"
                                class="flex items-center gap-2"
                            >
                                <Zap class="size-3 text-primary" />
                                {{ feature }}
                            </li>
                        </ul>
                        <Button
                            v-if="p.key !== 'enterprise'"
                            class="mt-4 w-full"
                            :variant="plan === p.key ? 'outline' : 'default'"
                            :disabled="plan === p.key && subscribed"
                            @click="subscribePlan(p.key)"
                        >
                            {{ plan === p.key && subscribed ? t('workspace.billing.currentPlan') : t('workspace.billing.selectPlan') }}
                        </Button>
                        <Button
                            v-else
                            class="mt-4 w-full"
                            variant="outline"
                            as="a"
                            href="mailto:enterprise@qr-master.app"
                        >
                            {{ t('workspace.billing.contact') }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
