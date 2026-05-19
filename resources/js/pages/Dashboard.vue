<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
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
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

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
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
        dropShadow: {
            enabled: true,
            blur: 10,
            color: '#7c3aed',
            opacity: 0.18,
        },
    },
    theme: { mode: 'dark' as const },
    stroke: { curve: 'smooth' as const, width: 2.5 },
    colors: ['oklch(0.66 0.25 285)'],
    fill: {
        type: 'gradient',
        gradient: {
            type: 'vertical',
            colorStops: [
                { offset: 0,   color: 'oklch(0.72 0.15 200)', opacity: 0.50 },
                { offset: 50,  color: 'oklch(0.66 0.25 285)', opacity: 0.22 },
                { offset: 100, color: 'oklch(0.66 0.25 285)', opacity: 0.00 },
            ],
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { style: { fontSize: '11px', colors: '#94a3b8' }, datetimeUTC: false },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        min: 0,
        labels: {
            style: { fontSize: '11px', colors: '#94a3b8' },
            formatter: (v: number) => Math.floor(v).toString(),
        },
    },
    grid: {
        strokeDashArray: 4,
        borderColor: 'oklch(0.28 0.028 272 / 0.6)',
        xaxis: { lines: { show: false } },
    },
    tooltip: { x: { format: 'dd MMM' }, theme: 'dark' },
    markers: {
        size: 0,
        hover: { size: 5 },
        strokeColors: 'oklch(0.66 0.25 285)',
        strokeWidth: 2,
        fillColor: 'oklch(0.17 0.025 272)',
    },
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

interface QrTypeConfig { color: string; bg: string; ring: string; border: string }
const QR_TYPE_COLORS: Record<string, QrTypeConfig> = {
    url:      { color: 'text-violet-400',  bg: 'bg-violet-400/10',  ring: 'ring-violet-400/20',  border: 'hover:border-l-violet-400' },
    vcard:    { color: 'text-cyan-400',    bg: 'bg-cyan-400/10',    ring: 'ring-cyan-400/20',    border: 'hover:border-l-cyan-400' },
    wifi:     { color: 'text-sky-400',     bg: 'bg-sky-400/10',     ring: 'ring-sky-400/20',     border: 'hover:border-l-sky-400' },
    geo:      { color: 'text-emerald-400', bg: 'bg-emerald-400/10', ring: 'ring-emerald-400/20', border: 'hover:border-l-emerald-400' },
    email:    { color: 'text-gold-500',    bg: 'bg-gold-500/10',    ring: 'ring-gold-500/20',    border: 'hover:border-l-gold-500' },
    phone:    { color: 'text-green-400',   bg: 'bg-green-400/10',   ring: 'ring-green-400/20',   border: 'hover:border-l-green-400' },
    sms:      { color: 'text-teal-400',    bg: 'bg-teal-400/10',    ring: 'ring-teal-400/20',    border: 'hover:border-l-teal-400' },
    pdf:      { color: 'text-red-400',     bg: 'bg-red-400/10',     ring: 'ring-red-400/20',     border: 'hover:border-l-red-400' },
    app:      { color: 'text-indigo-400',  bg: 'bg-indigo-400/10',  ring: 'ring-indigo-400/20',  border: 'hover:border-l-indigo-400' },
    calendar: { color: 'text-rose-400',    bg: 'bg-rose-400/10',    ring: 'ring-rose-400/20',    border: 'hover:border-l-rose-400' },
    crypto:   { color: 'text-amber-400',   bg: 'bg-amber-400/10',   ring: 'ring-amber-400/20',   border: 'hover:border-l-amber-400' },
    review:   { color: 'text-orange-400',  bg: 'bg-orange-400/10',  ring: 'ring-orange-400/20',  border: 'hover:border-l-orange-400' },
    text:     { color: 'text-slate-400',   bg: 'bg-slate-400/10',   ring: 'ring-slate-400/20',   border: 'hover:border-l-slate-400' },
    bio_link: { color: 'text-pink-400',    bg: 'bg-pink-400/10',    ring: 'ring-pink-400/20',    border: 'hover:border-l-pink-400' },
}
function getQrTypeConfig(type: string): QrTypeConfig {
    return QR_TYPE_COLORS[type] ?? { color: 'text-primary', bg: 'bg-primary/10', ring: 'ring-primary/20', border: 'hover:border-l-primary' }
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
                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5
                            hover:border-primary/50 hover:shadow-[0_0_32px_oklch(0.66_0.25_285/0.18)]
                            hover:-translate-y-0.5 transition-all duration-300 ease-out">
                    <!-- Static top-border -->
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <!-- Shimmer sweep on hover -->
                    <div class="absolute inset-x-0 top-0 h-px overflow-hidden">
                        <div class="animate-shimmer h-full w-1/3 bg-gradient-to-r from-transparent via-white/50 to-transparent opacity-0 group-hover:opacity-100" />
                    </div>
                    <!-- Gradient fill on hover -->
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary/8 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    <div class="relative flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.totalQr') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">{{ stats.totalQr }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20
                                    group-hover:bg-primary/20 group-hover:ring-primary/40 group-hover:shadow-[0_0_12px_oklch(0.66_0.25_285/0.35)]
                                    transition-all duration-300">
                            <QrCode class="size-5 text-primary" />
                        </div>
                    </div>
                    <p class="relative mt-2 text-xs text-muted-foreground">
                        {{ stats.activeQr }} {{ t('dashboard.recentQr.active').toLowerCase() }}
                    </p>
                </div>

                <!-- Scans this month -->
                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5
                            hover:border-cyan-400/50 hover:shadow-[0_0_32px_oklch(0.72_0.15_200/0.18)]
                            hover:-translate-y-0.5 transition-all duration-300 ease-out">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent" />
                    <div class="absolute inset-x-0 top-0 h-px overflow-hidden">
                        <div class="animate-shimmer h-full w-1/3 bg-gradient-to-r from-transparent via-cyan-300/50 to-transparent opacity-0 group-hover:opacity-100" />
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-400/8 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    <div class="relative flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.scansMonth') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">{{ formatNumber(stats.scansThisMonth) }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20
                                    group-hover:bg-cyan-400/20 group-hover:ring-cyan-400/40 group-hover:shadow-[0_0_12px_oklch(0.72_0.15_200/0.35)]
                                    transition-all duration-300">
                            <BarChart2 class="size-5 text-cyan-400" />
                        </div>
                    </div>
                    <div class="relative mt-2 flex items-center gap-1 text-xs">
                        <template v-if="stats.scansLastMonth > 0 || stats.scansThisMonth > 0">
                            <TrendingUp v-if="stats.scansTrend >= 0" class="size-3.5 text-green-500" />
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
                <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5
                            hover:border-green-500/50 hover:shadow-[0_0_32px_oklch(0.65_0.19_142/0.18)]
                            hover:-translate-y-0.5 transition-all duration-300 ease-out">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-green-500/50 to-transparent" />
                    <div class="absolute inset-x-0 top-0 h-px overflow-hidden">
                        <div class="animate-shimmer h-full w-1/3 bg-gradient-to-r from-transparent via-green-400/50 to-transparent opacity-0 group-hover:opacity-100" />
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-green-500/8 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                    <div class="relative flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.activeQr') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">{{ stats.activeQr }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-green-500/10 ring-1 ring-green-500/20
                                    group-hover:bg-green-500/20 group-hover:ring-green-500/40 group-hover:shadow-[0_0_12px_oklch(0.65_0.19_142/0.35)]
                                    transition-all duration-300">
                            <Zap class="size-5 text-green-500" />
                        </div>
                    </div>
                    <p class="relative mt-2 text-xs text-muted-foreground">
                        {{ stats.totalQr - stats.activeQr }} {{ t('dashboard.recentQr.inactive').toLowerCase() }}
                    </p>
                </div>

                <!-- Expiring -->
                <div
                    class="group relative overflow-hidden rounded-xl border bg-card p-4 md:p-5
                           hover:-translate-y-0.5 transition-all duration-300 ease-out"
                    :class="stats.expiringQr > 0
                        ? 'border-amber-500/30 hover:border-amber-500/55 hover:shadow-[0_0_32px_oklch(0.76_0.17_70/0.18)]'
                        : 'border-border hover:border-border/80 hover:shadow-sm'"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                        :class="stats.expiringQr > 0 ? 'via-amber-500/50' : 'via-border/60'"
                    />
                    <div class="absolute inset-x-0 top-0 h-px overflow-hidden">
                        <div
                            class="animate-shimmer h-full w-1/3 bg-gradient-to-r from-transparent to-transparent opacity-0 group-hover:opacity-100"
                            :class="stats.expiringQr > 0 ? 'via-amber-400/50' : 'via-white/20'"
                        />
                    </div>
                    <div
                        class="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        :class="stats.expiringQr > 0
                            ? 'bg-gradient-to-br from-amber-500/8 via-transparent to-transparent'
                            : 'bg-gradient-to-br from-muted/30 via-transparent to-transparent'"
                    />
                    <div class="relative flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('dashboard.stats.expiringQr') }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">{{ stats.expiringQr }}</p>
                        </div>
                        <div
                            class="flex size-10 items-center justify-center rounded-full ring-1 transition-all duration-300"
                            :class="stats.expiringQr > 0
                                ? 'bg-amber-500/10 ring-amber-500/20 group-hover:bg-amber-500/20 group-hover:ring-amber-500/40 group-hover:shadow-[0_0_12px_oklch(0.76_0.17_70/0.35)]'
                                : 'bg-muted ring-border'"
                        >
                            <AlertTriangle
                                class="size-5"
                                :class="stats.expiringQr > 0 ? 'text-amber-500' : 'text-muted-foreground'"
                            />
                        </div>
                    </div>
                    <p class="relative mt-2 text-xs text-muted-foreground">{{ t('dashboard.stats.in7days') }}</p>
                </div>
            </div>

            <!-- Chart + Top QR -->
            <div class="grid gap-3 md:gap-4 lg:grid-cols-3">
                <!-- Scan chart -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card lg:col-span-2 hover:border-primary/30 transition-colors duration-300">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 via-50% to-cyan-400/40 to-transparent" />
                    <div class="p-4 pb-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold">{{ t('dashboard.chart.title') }}</h3>
                            <span class="text-xs text-muted-foreground">{{ t('dashboard.chart.subtitle') }}</span>
                        </div>
                    </div>
                    <div class="px-2 pb-3">
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
                    </div>
                </div>

                <!-- Top QR codes -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card hover:border-cyan-400/30 transition-colors duration-300">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                    <div class="p-4 pb-2">
                        <h3 class="text-base font-semibold">{{ t('dashboard.topQr.title') }}</h3>
                    </div>
                    <div class="space-y-1 px-4 pb-4">
                        <div
                            v-for="(qr, idx) in topQrCodes"
                            :key="qr.id"
                            class="group flex items-center gap-3 rounded-lg p-1.5 -mx-1.5 hover:bg-muted/30 transition-colors duration-150"
                        >
                            <span
                                class="flex size-5 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="{
                                    'bg-gold-500/20 text-gold-500 ring-1 ring-gold-500/30': idx === 0,
                                    'bg-muted/60 text-muted-foreground ring-1 ring-border': idx === 1,
                                    'bg-amber-700/20 text-amber-600 ring-1 ring-amber-700/30': idx === 2,
                                    'bg-muted/30 text-muted-foreground/70': idx > 2,
                                }"
                            >{{ idx + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="r('qr.analytics', qr.id)"
                                    class="block truncate text-sm font-medium hover:text-primary transition-colors duration-150"
                                >{{ qr.title }}</Link>
                                <p class="text-xs text-muted-foreground tabular-nums">
                                    {{ formatNumber(qr.scan_count) }} {{ t('dashboard.topQr.scans') }}
                                </p>
                            </div>
                        </div>
                        <div v-if="topQrCodes.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                            {{ t('dashboard.recentQr.noQr') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent QR + Plan Usage + Quick Actions -->
            <div class="grid gap-3 md:gap-4 lg:grid-cols-3">
                <!-- Recent QR codes -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card lg:col-span-2">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <div class="flex items-center justify-between p-4 pb-2">
                        <h3 class="text-base font-semibold">{{ t('dashboard.recentQr.title') }}</h3>
                        <Button variant="ghost" size="sm" as-child class="hover:text-primary transition-colors duration-150">
                            <Link :href="r('qr.index')">{{ t('dashboard.recentQr.viewAll') }}</Link>
                        </Button>
                    </div>
                    <div class="divide-y divide-border/40 px-4 pb-4">
                        <div
                            v-for="qr in recentQrCodes"
                            :key="qr.id"
                            class="group flex items-center gap-3 py-2.5 border-l-2 border-l-transparent
                                   hover:bg-gradient-to-r hover:from-muted/40 hover:to-transparent
                                   -mx-4 px-4 transition-all duration-200"
                            :class="getQrTypeConfig(qr.type).border"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg ring-1 group-hover:scale-110 transition-transform duration-200"
                                :class="[getQrTypeConfig(qr.type).bg, getQrTypeConfig(qr.type).ring]"
                            >
                                <QrCode class="size-4" :class="getQrTypeConfig(qr.type).color" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <Link
                                    :href="r('qr.edit', qr.id)"
                                    class="block truncate text-sm font-medium hover:text-primary transition-colors duration-150"
                                >{{ qr.title }}</Link>
                                <p class="text-xs font-medium uppercase tracking-wider" :class="getQrTypeConfig(qr.type).color">
                                    {{ qr.type }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-muted-foreground tabular-nums">
                                    {{ formatNumber(qr.scan_count) }} {{ t('dashboard.recentQr.scans') }}
                                </span>
                                <Badge :variant="qrBadgeVariant(qr)" class="text-xs">
                                    {{ qrBadgeLabel(qr) }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column -->
                <div class="space-y-4">
                    <!-- Plan Usage -->
                    <div class="relative overflow-hidden rounded-xl border border-border bg-card">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent" />
                        <div class="flex items-center justify-between p-4 pb-2">
                            <h3 class="text-base font-semibold">{{ t('dashboard.planUsage.title') }}</h3>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1"
                                :class="planUsage.plan === 'free'
                                    ? 'bg-muted text-muted-foreground ring-border'
                                    : planUsage.plan === 'pro'
                                        ? 'bg-primary/10 text-primary ring-primary/20'
                                        : 'bg-gold-500/10 text-gold-500 ring-gold-500/20'"
                            >{{ planUsage.planName }}</span>
                        </div>
                        <div class="space-y-4 px-4 pb-4">
                            <!-- QR codes usage -->
                            <div>
                                <div class="mb-1.5 flex justify-between text-xs text-muted-foreground">
                                    <span>{{ t('dashboard.planUsage.qrCodes') }}</span>
                                    <span class="tabular-nums">
                                        {{ planUsage.qrUsed }}
                                        {{ planUsage.qrMax !== null ? `${t('dashboard.planUsage.of')} ${planUsage.qrMax}` : t('dashboard.planUsage.unlimited') }}
                                    </span>
                                </div>
                                <div v-if="planUsage.qrMax !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-primary via-violet-400 to-cyan-400 transition-all duration-700"
                                        :style="{ width: `${usagePercent(planUsage.qrUsed, planUsage.qrMax)}%` }"
                                    />
                                </div>
                            </div>
                            <!-- Scans usage -->
                            <div>
                                <div class="mb-1.5 flex justify-between text-xs text-muted-foreground">
                                    <span>{{ t('dashboard.planUsage.scansMonth') }}</span>
                                    <span class="tabular-nums">
                                        {{ formatNumber(planUsage.scansUsed) }}
                                        {{ planUsage.scansMax !== null ? `${t('dashboard.planUsage.of')} ${formatNumber(planUsage.scansMax)}` : t('dashboard.planUsage.unlimited') }}
                                    </span>
                                </div>
                                <div v-if="planUsage.scansMax !== null" class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-cyan-400 via-primary to-violet-400 transition-all duration-700"
                                        :style="{ width: `${usagePercent(planUsage.scansUsed, planUsage.scansMax)}%` }"
                                    />
                                </div>
                            </div>
                            <!-- Upgrade button -->
                            <Button
                                v-if="planUsage.plan === 'free' || planUsage.plan === 'pro'"
                                variant="outline"
                                size="sm"
                                class="w-full border-gold-500/30 text-gold-500
                                       hover:border-gold-500/60 hover:shadow-[0_0_16px_oklch(0.78_0.15_85/0.2)]
                                       transition-all duration-200"
                                as-child
                            >
                                <Link :href="r('pricing')">
                                    <span class="mr-1.5 text-gold-500">✦</span>
                                    {{ t('dashboard.planUsage.upgrade') }}
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="relative overflow-hidden rounded-xl border border-border bg-card">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
                        <div class="p-4 pb-2">
                            <h3 class="text-base font-semibold">{{ t('dashboard.quickActions.title') }}</h3>
                        </div>
                        <div class="space-y-2 px-4 pb-4">
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full justify-start hover:border-primary/40 hover:text-primary transition-all duration-150"
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
                                class="w-full justify-start hover:border-cyan-400/40 hover:text-cyan-400 transition-all duration-150"
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
                                class="w-full justify-start hover:border-gold-500/40 hover:text-gold-500 transition-all duration-150"
                                as-child
                            >
                                <Link :href="r('analytics')">
                                    <FileDown class="mr-2 size-4" />
                                    {{ t('dashboard.quickActions.viewAnalytics') }}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
