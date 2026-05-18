<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, Copy, DollarSign, TrendingUp, Users } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

defineProps<{
    referralCode: string | null
    referralUrl: string
    referralCount: number
    totalPendingPln: number
    totalPaidPln: number
    commissions: Array<{
        id: number
        referred_user_id: number
        amount_gross: number
        commission_amount: number
        currency: string
        status: string
        created_at: string
        referred_user: { name: string; email: string } | null
    }>
}>()

const copied = ref(false)

async function copyLink(url: string): Promise<void> {
    await navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

function formatMoney(grosze: number, currency: string): string {
    return (grosze / 100).toFixed(2) + ' ' + currency.toUpperCase()
}
</script>

<template>
    <Head :title="t('affiliate.pageTitle')" />

    <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
        <!-- Page header -->
        <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20">
                <TrendingUp class="size-5 text-gold-500" />
            </div>
            <div>
                <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-gold-400 via-gold-500 to-yellow-400 bg-clip-text text-transparent">
                    {{ t('affiliate.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ t('affiliate.subtitle') }}</p>
            </div>
        </div>

        <!-- Section divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

        <!-- Stats row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Referrals stat -->
            <div class="relative rounded-xl border border-border bg-card p-5 text-center overflow-hidden hover:border-primary/30 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20 mx-auto mb-3">
                    <Users class="size-5 text-primary" />
                </div>
                <p class="text-2xl font-bold sm:text-3xl">{{ referralCount }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ t('affiliate.referrals') }}</p>
            </div>

            <!-- Pending stat -->
            <div class="relative rounded-xl border border-border bg-card p-5 text-center overflow-hidden hover:border-gold-500/30 hover:shadow-[0_0_20px_oklch(0.78_0.15_85/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
                <div class="flex size-10 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20 mx-auto mb-3">
                    <DollarSign class="size-5 text-gold-500" />
                </div>
                <p class="text-2xl font-bold text-gold-500 sm:text-3xl">
                    {{ totalPendingPln.toFixed(2) }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">{{ t('affiliate.pending') }}</p>
            </div>

            <!-- Paid stat -->
            <div class="relative rounded-xl border border-border bg-card p-5 text-center overflow-hidden hover:border-emerald-400/30 hover:shadow-[0_0_20px_oklch(0.72_0.15_160/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-400/60 to-transparent" />
                <div class="flex size-10 items-center justify-center rounded-full bg-emerald-400/10 ring-1 ring-emerald-400/20 mx-auto mb-3">
                    <TrendingUp class="size-5 text-emerald-400" />
                </div>
                <p class="text-2xl font-bold text-emerald-400 sm:text-3xl">
                    {{ totalPaidPln.toFixed(2) }}
                </p>
                <p class="text-xs text-muted-foreground mt-1">{{ t('affiliate.paid') }}</p>
            </div>
        </div>

        <!-- Referral link -->
        <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden hover:border-primary/30 transition-colors duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent" />
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">{{ t('affiliate.yourLink') }}</p>
                <Badge
                    v-if="referralCode"
                    class="text-xs bg-gold-500/10 text-gold-500 border-gold-500/20"
                    variant="outline"
                >
                    {{ referralCode }}
                </Badge>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/30 px-3 py-2.5">
                <span class="flex-1 truncate font-mono text-xs text-muted-foreground">{{ referralUrl }}</span>
                <Button
                    variant="ghost"
                    size="sm"
                    class="shrink-0 gap-1.5 hover:text-primary transition-colors duration-150"
                    @click="copyLink(referralUrl)"
                >
                    <component
                        :is="copied ? Check : Copy"
                        class="size-4"
                        :class="copied ? 'text-emerald-400' : ''"
                    />
                    <span class="hidden sm:inline text-xs">{{ copied ? t('domains.copied') : t('domains.copy') }}</span>
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">{{ t('affiliate.commission') }}</p>
        </div>

        <!-- Commission history -->
        <div class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <div class="border-b border-border px-5 py-4">
                <p class="text-sm font-semibold">{{ t('affiliate.history') }}</p>
            </div>

            <!-- Empty state -->
            <div v-if="commissions.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-muted ring-1 ring-border mb-3">
                    <DollarSign class="size-6 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">{{ t('affiliate.noCommissions') }}</p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/20">
                            <th class="p-3 text-left font-medium text-muted-foreground text-xs uppercase tracking-wide">{{ t('affiliate.colUser') }}</th>
                            <th class="p-3 text-right font-medium text-muted-foreground text-xs uppercase tracking-wide">{{ t('affiliate.colAmount') }}</th>
                            <th class="p-3 text-right font-medium text-muted-foreground text-xs uppercase tracking-wide">{{ t('affiliate.colCommission') }}</th>
                            <th class="p-3 text-center font-medium text-muted-foreground text-xs uppercase tracking-wide">{{ t('affiliate.colStatus') }}</th>
                            <th class="p-3 text-right font-medium text-muted-foreground text-xs uppercase tracking-wide">{{ t('affiliate.colDate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="c in commissions"
                            :key="c.id"
                            class="border-b border-border last:border-0 hover:bg-muted/30 transition-colors duration-100"
                        >
                            <td class="p-3">
                                <p class="font-medium">{{ c.referred_user?.name ?? '—' }}</p>
                                <p class="text-xs text-muted-foreground">{{ c.referred_user?.email ?? '' }}</p>
                            </td>
                            <td class="p-3 text-right font-mono text-xs">{{ formatMoney(c.amount_gross, c.currency) }}</td>
                            <td class="p-3 text-right font-mono text-xs font-semibold text-emerald-400">
                                +{{ formatMoney(c.commission_amount, c.currency) }}
                            </td>
                            <td class="p-3 text-center">
                                <Badge
                                    class="text-xs"
                                    :class="{
                                        'bg-emerald-400/10 text-emerald-400 border-emerald-400/20': c.status === 'paid',
                                        'bg-gold-500/10 text-gold-500 border-gold-500/20': c.status !== 'paid',
                                    }"
                                    variant="outline"
                                >
                                    {{ t(`affiliate.status.${c.status}`) }}
                                </Badge>
                            </td>
                            <td class="p-3 text-right text-xs text-muted-foreground">
                                {{ new Date(c.created_at).toLocaleDateString() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
