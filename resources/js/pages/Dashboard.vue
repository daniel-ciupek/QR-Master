<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { usePreferredDark } from '@vueuse/core'
import {
    AlertTriangle,
    BarChart2,
    FileDown,
    Plus,
    QrCode,
    TrendingDown,
    TrendingUp,
    Upload,
    Zap,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import VueApexCharts from 'vue3-apexcharts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
const isDark = usePreferredDark()
const themeMode = computed((): 'dark' | 'light' => (isDark.value ? 'dark' : 'light'))

interface QrItem {
    id: number
    title: string
    type: string
    short_hash: string
    scan_count: number
    is_active: boolean
    created_at: string
    expires_at: string | null
}

interface TimelinePoint {
    date: string
    count: number
}

interface PlanUsage {
    plan: string
    planName: string
    qrUsed: number
    qrMax: number | null
    scansUsed: number
    scansMax: number | null
}

const props = defineProps<{
    stats: {
        totalQr: number
        activeQr: number
        expiringQr: number
        scansThisMonth: number
        scansLastMonth: number
        scansTrend: number
    }
    planUsage: PlanUsage
    scanTimeline: TimelinePoint[]
    recentQrCodes: QrItem[]
    topQrCodes: QrItem[]
}>()

const chartSeries = computed(() => [{
    name: t('dashboard.chart.scans'),
    data: props.scanTimeline.map(p => ({ x: new Date(p.date).getTime(), y: p.count })),
}])

const chartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 200,
        toolbar: { show: false },
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 400 },
    },
    theme: { mode: themeMode.value },
    stroke: { curve: 'smooth' as const, width: 2 },
    colors: ['#6366f1'],
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 100] },
    },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { style: { fontSize: '11px' }, datetimeUTC: false },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        min: 0,
        labels: { style: { fontSize: '11px' }, formatter: (v: number) => Math.floor(v).toString() },
    },
    grid: { strokeDashArray: 4, borderColor: isDark.value ? '#1e293b' : '#e2e8f0' },
    tooltip: { x: { format: 'dd MMM' }, theme: themeMode.value },
}))

function qrBadgeLabel(qr: QrItem): string {
    if (qr.expires_at && new Date(qr.expires_at) < new Date()) return t('dashboard.recentQr.expired')
    return qr.is_active ? t('dashboard.recentQr.active') : t('dashboard.recentQr.inactive')
}

function qrBadgeVariant(qr: QrItem): 'default' | 'secondary' | 'destructive' {
    if (qr.expires_at && new Date(qr.expires_at) < new Date()) return 'destructive'
    return qr.is_active ? 'default' : 'secondary'
}

function usagePercent(used: number, max: number | null): number {
    if (max === null) return 0
    return Math.min(100, Math.round((used / max) * 100))
}

function formatNumber(n: number): string {
    return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : n.toString()
}

// Ziggy route() is injected via @routes blade directive — wrap for template use
function r(name: string, params?: Record<string, string | number | boolean> | string | number): string {
    return route(name, params)
}
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ t('dashboard.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('dashboard.subtitle') }}</p>
            </div>
            <Button as-child>
                <Link :href="r('qr.create')">
                    <Plus class="mr-2 size-4" />
                    {{ t('dashboard.quickActions.createQr') }}
                </Link>
            </Button>
        </div>

        <!-- Empty state -->
        <div
            v-if="stats.totalQr === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed py-20 text-center"
        >
            <QrCode class="mb-4 size-12 text-muted-foreground/40" />
            <h2 class="mb-1 text-lg font-semibold">{{ t('dashboard.empty.title') }}</h2>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">{{ t('dashboard.empty.description') }}</p>
            <Button as-child>
                <Link :href="r('qr.create')">
                    <Plus class="mr-2 size-4" />
                    {{ t('dashboard.empty.cta') }}
                </Link>
            </Button>
        </div>

        <template v-else>
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total QR -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.totalQr') }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ stats.totalQr }}</p>
                            </div>
                            <div class="rounded-lg bg-primary/10 p-2">
                                <QrCode class="size-5 text-primary" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ stats.activeQr }} {{ t('dashboard.recentQr.active').toLowerCase() }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Scans this month -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.scansMonth') }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ formatNumber(stats.scansThisMonth) }}</p>
                            </div>
                            <div class="rounded-lg bg-violet-500/10 p-2">
                                <BarChart2 class="size-5 text-violet-500" />
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-1 text-xs">
                            <template v-if="stats.scansLastMonth > 0 || stats.scansThisMonth > 0">
                                <TrendingUp
                                    v-if="stats.scansTrend >= 0"
                                    class="size-3.5 text-green-500"
                                />
                                <TrendingDown v-else class="size-3.5 text-red-500" />
                                <span :class="stats.scansTrend >= 0 ? 'text-green-500' : 'text-red-500'">
                                    {{ stats.scansTrend >= 0 ? '+' : '' }}{{ stats.scansTrend }}%
                                </span>
                                <span class="text-muted-foreground">{{ t('dashboard.stats.vsLastMonth') }}</span>
                            </template>
                            <span v-else class="text-muted-foreground">{{ t('dashboard.stats.noChange') }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active QR -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.activeQr') }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ stats.activeQr }}</p>
                            </div>
                            <div class="rounded-lg bg-green-500/10 p-2">
                                <Zap class="size-5 text-green-500" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ stats.totalQr - stats.activeQr }} {{ t('dashboard.recentQr.inactive').toLowerCase() }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Expiring -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.expiringQr') }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ stats.expiringQr }}</p>
                            </div>
                            <div :class="stats.expiringQr > 0 ? 'bg-amber-500/10' : 'bg-muted'" class="rounded-lg p-2">
                                <AlertTriangle :class="stats.expiringQr > 0 ? 'text-amber-500' : 'text-muted-foreground'" class="size-5" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">{{ t('dashboard.stats.in7days') }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Chart + Top QR -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Scan chart -->
                <Card class="lg:col-span-2">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">{{ t('dashboard.chart.title') }}</CardTitle>
                            <span class="text-xs text-muted-foreground">{{ t('dashboard.chart.subtitle') }}</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <VueApexCharts
                            v-if="scanTimeline.some(p => p.count > 0)"
                            type="area"
                            height="200"
                            :options="chartOptions"
                            :series="chartSeries"
                        />
                        <div
                            v-else
                            class="flex h-[200px] items-center justify-center text-sm text-muted-foreground"
                        >
                            {{ t('dashboard.chart.noData') }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Top QR codes -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('dashboard.topQr.title') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="(qr, idx) in topQrCodes"
                            :key="qr.id"
                            class="flex items-center gap-3"
                        >
                            <span class="w-4 text-center text-sm font-bold text-muted-foreground">{{ idx + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="r('qr.analytics', qr.id)"
                                    class="block truncate text-sm font-medium hover:text-primary"
                                >
                                    {{ qr.title }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatNumber(qr.scan_count) }} {{ t('dashboard.topQr.scans') }}
                                </p>
                            </div>
                        </div>
                        <div v-if="topQrCodes.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                            {{ t('dashboard.recentQr.noQr') }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent QR + Plan Usage + Quick Actions -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Recent QR codes -->
                <Card class="lg:col-span-2">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">{{ t('dashboard.recentQr.title') }}</CardTitle>
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="r('qr.index')">{{ t('dashboard.recentQr.viewAll') }}</Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="divide-y">
                            <div
                                v-for="qr in recentQrCodes"
                                :key="qr.id"
                                class="flex items-center gap-3 py-3"
                            >
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted">
                                    <QrCode class="size-4 text-muted-foreground" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="r('qr.edit', qr.id)"
                                        class="block truncate text-sm font-medium hover:text-primary"
                                    >
                                        {{ qr.title }}
                                    </Link>
                                    <p class="text-xs text-muted-foreground uppercase">{{ qr.type }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs text-muted-foreground">
                                        {{ formatNumber(qr.scan_count) }} {{ t('dashboard.recentQr.scans') }}
                                    </span>
                                    <Badge :variant="qrBadgeVariant(qr)" class="text-xs">
                                        {{ qrBadgeLabel(qr) }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Right column -->
                <div class="space-y-4">
                    <!-- Plan Usage -->
                    <Card>
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-base">{{ t('dashboard.planUsage.title') }}</CardTitle>
                                <Badge variant="secondary" class="text-xs capitalize">
                                    {{ planUsage.planName }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- QR codes usage -->
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-muted-foreground">
                                    <span>{{ t('dashboard.planUsage.qrCodes') }}</span>
                                    <span>
                                        {{ planUsage.qrUsed }}
                                        {{ planUsage.qrMax !== null ? `${t('dashboard.planUsage.of')} ${planUsage.qrMax}` : t('dashboard.planUsage.unlimited') }}
                                    </span>
                                </div>
                                <div v-if="planUsage.qrMax !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${usagePercent(planUsage.qrUsed, planUsage.qrMax)}%` }"
                                    />
                                </div>
                            </div>
                            <!-- Scans usage -->
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-muted-foreground">
                                    <span>{{ t('dashboard.planUsage.scansMonth') }}</span>
                                    <span>
                                        {{ formatNumber(planUsage.scansUsed) }}
                                        {{ planUsage.scansMax !== null ? `${t('dashboard.planUsage.of')} ${formatNumber(planUsage.scansMax)}` : t('dashboard.planUsage.unlimited') }}
                                    </span>
                                </div>
                                <div v-if="planUsage.scansMax !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${usagePercent(planUsage.scansUsed, planUsage.scansMax)}%` }"
                                    />
                                </div>
                            </div>
                            <Button
                                v-if="planUsage.plan === 'free' || planUsage.plan === 'pro'"
                                variant="outline"
                                size="sm"
                                class="w-full"
                                as-child
                            >
                                <Link :href="r('pricing')">{{ t('dashboard.planUsage.upgrade') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base">{{ t('dashboard.quickActions.title') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full justify-start"
                                as-child
                            >
                                <Link :href="r('qr.create')">
                                    <Plus class="mr-2 size-4" />
                                    {{ t('dashboard.quickActions.createQr') }}
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full justify-start"
                                as-child
                            >
                                <Link :href="r('qr.import')">
                                    <Upload class="mr-2 size-4" />
                                    {{ t('dashboard.quickActions.importCsv') }}
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full justify-start"
                                as-child
                            >
                                <Link :href="r('analytics')">
                                    <FileDown class="mr-2 size-4" />
                                    {{ t('dashboard.quickActions.viewAnalytics') }}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </template>
    </div>
</template>
