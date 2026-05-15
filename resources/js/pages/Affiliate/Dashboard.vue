<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Check, Copy, Users } from 'lucide-vue-next'
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

    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div>
            <h1 class="text-xl font-bold">{{ t('affiliate.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('affiliate.subtitle') }}</p>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-border bg-card p-4 text-center">
                <Users class="mx-auto mb-1 h-6 w-6 text-muted-foreground" />
                <p class="text-2xl font-bold">{{ referralCount }}</p>
                <p class="text-xs text-muted-foreground">{{ t('affiliate.referrals') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4 text-center">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ totalPendingPln.toFixed(2) }} PLN
                </p>
                <p class="text-xs text-muted-foreground">{{ t('affiliate.pending') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-card p-4 text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ totalPaidPln.toFixed(2) }} PLN
                </p>
                <p class="text-xs text-muted-foreground">{{ t('affiliate.paid') }}</p>
            </div>
        </div>

        <!-- Referral link -->
        <div class="rounded-xl border border-border bg-card p-5 space-y-3">
            <p class="text-sm font-semibold">{{ t('affiliate.yourLink') }}</p>
            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/30 px-3 py-2">
                <span class="flex-1 truncate font-mono text-xs text-muted-foreground">{{ referralUrl }}</span>
                <Button variant="ghost" size="sm" @click="copyLink(referralUrl)">
                    <component :is="copied ? Check : Copy" class="h-4 w-4" :class="copied ? 'text-green-500' : ''" />
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">{{ t('affiliate.commission') }}</p>
        </div>

        <!-- Commission history -->
        <div class="rounded-xl border border-border bg-card">
            <div class="border-b border-border px-5 py-3">
                <p class="text-sm font-semibold">{{ t('affiliate.history') }}</p>
            </div>
            <div v-if="commissions.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                {{ t('affiliate.noCommissions') }}
            </div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/30">
                        <th class="p-3 text-left font-medium text-muted-foreground">{{ t('affiliate.colUser') }}</th>
                        <th class="p-3 text-right font-medium text-muted-foreground">{{ t('affiliate.colAmount') }}</th>
                        <th class="p-3 text-right font-medium text-muted-foreground">{{ t('affiliate.colCommission') }}</th>
                        <th class="p-3 text-center font-medium text-muted-foreground">{{ t('affiliate.colStatus') }}</th>
                        <th class="p-3 text-right font-medium text-muted-foreground">{{ t('affiliate.colDate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="c in commissions"
                        :key="c.id"
                        class="border-b border-border last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-3">
                            <p class="font-medium">{{ c.referred_user?.name ?? '—' }}</p>
                            <p class="text-xs text-muted-foreground">{{ c.referred_user?.email ?? '' }}</p>
                        </td>
                        <td class="p-3 text-right font-mono text-xs">{{ formatMoney(c.amount_gross, c.currency) }}</td>
                        <td class="p-3 text-right font-mono text-xs font-semibold text-green-600 dark:text-green-400">
                            +{{ formatMoney(c.commission_amount, c.currency) }}
                        </td>
                        <td class="p-3 text-center">
                            <Badge :variant="c.status === 'paid' ? 'default' : 'secondary'">
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
</template>
