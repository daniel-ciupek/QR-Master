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

    <div class="space-y-4 md:space-y-6">
        <!-- Header — mesh gradient card -->
        <div class="relative overflow-hidden rounded-2xl border border-border/50 bg-card p-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Animated mesh gradient (3 radiale) -->
            <div
                class="pointer-events-none absolute inset-0"
                style="background:
                    radial-gradient(ellipse at 15% 50%, oklch(0.66 0.25 285 / 0.13) 0%, transparent 55%),
                    radial-gradient(ellipse at 85% 20%, oklch(0.72 0.15 200 / 0.10) 0%, transparent 50%),
                    radial-gradient(ellipse at 55% 90%, oklch(0.78 0.15 85 / 0.07) 0%, transparent 45%);"
            />
            <!-- Gradient top-border violet→cyan -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/80 via-50% to-cyan-400/40 to-transparent" />
            <!-- Dot grid overlay -->
            <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />

            <div class="relative">
                <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent sm:text-3xl">
                    {{ t('dashboard.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ t('dashboard.subtitle') }}</p>
            </div>
            <Button
                as-child
                class="relative self-start sm:self-auto shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_28px_oklch(0.66_0.25_285/0.6)] hover:-translate-y-0.5 transition-all duration-300"
            >
                <Link :href="r('qr.create')">
                    <Plus class="mr-2 size-4" />
                    {{ t('dashboard.quickActions.createQr') }}
                </Link>
            </Button>
        </div>

        <!-- Empty state -->
        <div
            v-if="stats.totalQr === 0"
            class="relative flex flex-col items-center justify-center overflow-hidden rounded-xl border border-dashed py-20 text-center"
        >
            <!-- Dot grid background -->
            <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
            <div class="relative flex size-16 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 mb-4">
                <QrCode class="size-8 text-primary" />
            </div>
            <h2 class="mb-1 text-lg font-semibold">{{ t('dashboard.empty.title') }}</h2>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">{{ t('dashboard.empty.description') }}</p>
            <Button
                as-child
                class="relative shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
            >
                <Link :href="r('qr.create')">
                    <Plus class="mr-2 size-4" />
                    {{ t('dashboard.empty.cta') }}
                </Link>
            </Button>
        </div>

        <template v-else>
            <!-- Stat cards -->
            <div class="grid gap-3 md:gap-4 grid-cols-2 lg:grid-cols-4">
                <!-- Total QR -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.totalQr') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ stats.totalQr }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <QrCode class="size-5 text-primary" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ stats.activeQr }} {{ t('dashboard.recentQr.active').toLowerCase() }}
                    </p>
                </div>

                <!-- Scans this month -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-cyan-400/40 hover:shadow-[0_0_24px_oklch(0.72_0.15_200/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.scansMonth') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ formatNumber(stats.scansThisMonth) }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <BarChart2 class="size-5 text-cyan-400" />
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
                </div>

                <!-- Active QR -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-green-500/40 hover:shadow-[0_0_24px_oklch(0.65_0.19_142/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-green-500/50 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.activeQr') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ stats.activeQr }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-green-500/10 ring-1 ring-green-500/20">
                            <Zap class="size-5 text-green-500" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ stats.totalQr - stats.activeQr }} {{ t('dashboard.recentQr.inactive').toLowerCase() }}
                    </p>
                </div>

                <!-- Expiring -->
                <div
                    class="relative overflow-hidden rounded-xl border bg-card p-4 md:p-5 transition-all duration-200"
                    :class="stats.expiringQr > 0
                        ? 'border-amber-500/30 hover:border-amber-500/50 hover:shadow-[0_0_24px_oklch(0.76_0.17_70/0.12)]'
                        : 'border-border hover:border-border/80'"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                        :class="stats.expiringQr > 0 ? 'via-amber-500/50' : 'via-border/60'"
                    />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.expiringQr') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ stats.expiringQr }}</p>
                        </div>
                        <div
                            class="flex size-10 items-center justify-center rounded-full ring-1"
                            :class="stats.expiringQr > 0 ? 'bg-amber-500/10 ring-amber-500/20' : 'bg-muted ring-border'"
                        >
                            <AlertTriangle
                                :class="stats.expiringQr > 0 ? 'text-amber-500' : 'text-muted-foreground'"
                                class="size-5"
                            />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('dashboard.stats.in7days') }}</p>
                </div>
            </div>

            <!-- Chart + Top QR -->
            <div class="grid gap-3 md:gap-4 lg:grid-cols-3">
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
            <div class="grid gap-3 md:gap-4 lg:grid-cols-3">
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
                        <div class="divide-y divide-border/60">
                            <div
                                v-for="qr in recentQrCodes"
                                :key="qr.id"
                                class="flex items-center gap-3 py-3 transition-colors duration-100 hover:bg-muted/30 -mx-2 px-2 rounded-lg"
                            >
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 ring-1 ring-primary/20">
                                    <QrCode class="size-4 text-primary" />
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
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1"
                                    :class="planUsage.plan === 'free'
                                        ? 'bg-muted text-muted-foreground ring-border'
                                        : planUsage.plan === 'pro'
                                            ? 'bg-primary/10 text-primary ring-primary/20'
                                            : 'bg-gold-500/10 text-gold-500 ring-gold-500/20'"
                                >
                                    {{ planUsage.planName }}
                                </span>
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
                                class="w-full border-gold-500/30 text-gold-500 hover:bg-gold-500/10 hover:border-gold-500/50 transition-colors duration-150"
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
