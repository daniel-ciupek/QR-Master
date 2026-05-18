<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Activity, ArrowLeft, Download, Globe, Monitor, Smartphone, Tablet, Sparkles, Shield, BarChart3, Clock } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import VueApexCharts from 'vue3-apexcharts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface QrCodeProp {
    id: number
    title: string
    short_hash: string
    type: string
    destination_url: string | null
    is_active: boolean
    created_at: string
}

interface Stats {
    total: number
    unique: number
    today: number
    this_week: number
    this_month: number
}

interface CountRow {
    country: string
    count: number
}

interface DeviceRow {
    type: string
    count: number
}

interface BrowserRow {
    browser: string
    count: number
}

interface DailyRow {
    date: string
    count: number
}

interface ScanRow {
    scanned_at: string
    country: string | null
    city: string | null
    device_type: string | null
    os: string | null
    browser: string | null
    referrer: string | null
    language: string | null
}

interface HourlyRow {
    day_of_week: number
    hour: number
    count: number
}

const props = defineProps<{
    qrCode: QrCodeProp
    stats: Stats
    topCountries: CountRow[]
    deviceBreakdown: DeviceRow[]
    topBrowsers: BrowserRow[]
    dailyScans: DailyRow[]
    recentScans: ScanRow[]
    hourlyHeatmap: HourlyRow[]
}>()

const { t } = useI18n()

// Fill missing days in last 30 days
const filledDailyScans = computed(() => {
    const map = new Map(props.dailyScans.map((d) => [d.date, d.count]))
    const result: { x: number; y: number }[] = []
    for (let i = 29; i >= 0; i--) {
        const d = new Date()
        d.setDate(d.getDate() - i)
        const key = d.toISOString().slice(0, 10)
        result.push({ x: d.getTime(), y: map.get(key) ?? 0 })
    }
    return result
})

const timelineOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 180,
        toolbar: { show: false },
        background: 'transparent',
        sparkline: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 400 },
    },
    theme: { mode: 'dark' as const },
    colors: ['#8b5cf6'],
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.02, stops: [0, 100] },
    },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { format: 'dd MMM', style: { fontSize: '11px', colors: '#71717a' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#71717a' } }, min: 0 },
    grid: { borderColor: '#27272a', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM yyyy' }, theme: 'dark' },
    series: [{ name: 'Scans', data: filledDailyScans.value }],
}))

const treemapOptions = computed(() => ({
    chart: {
        type: 'treemap' as const,
        height: 220,
        toolbar: { show: false },
        background: 'transparent',
    },
    theme: { mode: 'dark' as const },
    colors: ['#8b5cf6', '#22d3ee', '#f59e0b', '#10b981', '#f43f5e', '#3b82f6'],
    dataLabels: { enabled: true, style: { fontSize: '12px' } },
    plotOptions: { treemap: { distributed: true, enableShades: false } },
    series: [
        {
            data: props.topCountries.length
                ? props.topCountries.map((r) => ({ x: r.country, y: r.count }))
                : [{ x: 'No data', y: 1 }],
        },
    ],
    tooltip: { y: { formatter: (v: number) => `${v} scans` }, theme: 'dark' },
}))

const deviceDonutOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        height: 200,
        background: 'transparent',
    },
    theme: { mode: 'dark' as const },
    colors: ['#8b5cf6', '#22d3ee', '#f59e0b'],
    labels: props.deviceBreakdown.map((r) => r.type),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '12px', labels: { colors: '#a1a1aa' } },
    series: props.deviceBreakdown.map((r) => r.count),
    plotOptions: { pie: { donut: { size: '55%' } } },
}))

const browserDonutOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        height: 200,
        background: 'transparent',
    },
    theme: { mode: 'dark' as const },
    colors: ['#8b5cf6', '#22d3ee', '#f59e0b', '#10b981', '#f43f5e'],
    labels: props.topBrowsers.map((r) => r.browser),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '12px', labels: { colors: '#a1a1aa' } },
    series: props.topBrowsers.map((r) => r.count),
    plotOptions: { pie: { donut: { size: '55%' } } },
}))

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const HOURS = Array.from({ length: 24 }, (_, h) => `${String(h).padStart(2, '0')}:00`)

// Transform flat rows into ApexCharts heatmap series (7 days × 24 hours)
const heatmapSeries = computed(() => {
    const grid: Record<number, Record<number, number>> = {}
    for (const row of props.hourlyHeatmap) {
        if (!grid[row.day_of_week]) grid[row.day_of_week] = {}
        ;(grid[row.day_of_week] as Record<number, number>)[row.hour] = row.count
    }
    return DAY_NAMES.map((name, day) => ({
        name,
        data: HOURS.map((hour, h) => ({ x: hour, y: grid[day]?.[h] ?? 0 })),
    }))
})

const heatmapOptions = computed(() => ({
    chart: {
        type: 'heatmap' as const,
        height: 220,
        toolbar: { show: false },
        background: 'transparent',
        animations: { enabled: false },
    },
    theme: { mode: 'dark' as const },
    dataLabels: { enabled: false },
    stroke: { width: 1 },
    plotOptions: {
        heatmap: {
            shadeIntensity: 0.6,
            radius: 2,
            colorScale: {
                ranges: [
                    { from: 0, to: 0, color: '#18181b', name: 'None' },
                    { from: 1, to: 5, color: '#4c1d95', name: 'Low' },
                    { from: 6, to: 20, color: '#7c3aed', name: 'Medium' },
                    { from: 21, to: 9999, color: '#a78bfa', name: 'High' },
                ],
            },
        },
    },
    xaxis: { labels: { style: { fontSize: '10px', colors: '#71717a' }, rotate: -45 } },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#71717a' } } },
    tooltip: { y: { formatter: (v: number) => `${v} scans` }, theme: 'dark' },
}))

const deviceIcon = (type: string | null) => {
    if (type === 'mobile') return Smartphone
    if (type === 'tablet') return Tablet
    return Monitor
}

const formatDateTime = (iso: string) =>
    new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const publicUrl = computed(() => `/q/${props.qrCode.short_hash}`)

const aiInsight = ref<string | null>(null)
const loadingInsight = ref(false)

interface AnomalyResult {
    is_anomalous: boolean
    confidence: 'low' | 'medium' | 'high'
    reasons: string[]
    recommendation: string
}

const anomalyResult = ref<AnomalyResult | null>(null)
const loadingAnomaly = ref(false)

async function detectAnomalies() {
    loadingAnomaly.value = true
    anomalyResult.value = null
    try {
        const res = await fetch(`/qr/${props.qrCode.id}/ai/detect-anomalies`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                Accept: 'application/json',
            },
        })
        if (res.ok) {
            anomalyResult.value = await res.json() as AnomalyResult
        }
    } finally {
        loadingAnomaly.value = false
    }
}

async function generateInsight() {
    loadingInsight.value = true
    aiInsight.value = null
    try {
        const res = await fetch(`/qr/${props.qrCode.id}/ai/insights`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                Accept: 'application/json',
            },
        })
        if (res.ok) {
            const data = await res.json() as { insight: string }
            aiInsight.value = data.insight
        }
    } finally {
        loadingInsight.value = false
    }
}

// Real-time counter via Reverb
const liveTotal = ref(props.stats.total)

onMounted(() => {
    window.Echo.private(`qr-analytics.${props.qrCode.id}`).listen('.scan.recorded', () => {
        liveTotal.value++
    })
})

onUnmounted(() => {
    window.Echo.leave(`qr-analytics.${props.qrCode.id}`)
})
</script>

<template>
    <Head :title="`${qrCode.title} — Analytics`" />

    <div class="mx-auto w-full max-w-6xl space-y-4 md:space-y-6 px-4 py-4 md:py-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <Button variant="ghost" size="icon" as-child class="shrink-0 mt-0.5 hover:bg-muted/60 transition-colors duration-150">
                    <Link href="/qr">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold sm:text-2xl bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">
                            {{ qrCode.title }}
                        </h1>
                        <Badge
                            :class="qrCode.is_active
                                ? 'bg-green-500/10 text-green-400 border-green-500/30'
                                : 'bg-muted text-muted-foreground border-border'"
                            class="border text-xs"
                        >
                            {{ qrCode.is_active ? t('qr.analytics.active') : t('qr.analytics.paused') }}
                        </Badge>
                    </div>
                    <p class="text-muted-foreground text-sm mt-0.5 font-mono">{{ publicUrl }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <Button variant="outline" size="sm" as-child class="gap-1.5 hover:border-primary/50 transition-colors duration-150">
                    <a :href="`/qr/${qrCode.id}/analytics/export-pdf`" target="_blank" rel="noopener noreferrer">
                        <Download class="size-3.5" />
                        <span class="hidden sm:inline">{{ t('qr.analytics.exportPdf') }}</span>
                    </a>
                </Button>
                <Button variant="outline" size="sm" as-child class="hover:border-primary/50 transition-colors duration-150">
                    <Link :href="`/qr/${qrCode.id}/edit`">{{ t('qr.analytics.edit') }}</Link>
                </Button>
            </div>
        </div>

        <!-- Metric cards -->
        <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            <!-- Total Scans — violet accent -->
            <div class="relative rounded-xl border border-border bg-card p-4 md:p-5 overflow-hidden hover:border-primary/40 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/70 to-transparent" />
                <div class="flex items-start justify-between gap-2 mb-3">
                    <p class="text-xs font-medium text-muted-foreground">{{ t('qr.analytics.totalScans') }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex size-2">
                            <span class="bg-primary absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" />
                            <span class="bg-primary relative inline-flex size-2 rounded-full" />
                        </span>
                    </div>
                </div>
                <p class="text-2xl font-bold md:text-3xl">{{ liveTotal.toLocaleString() }}</p>
            </div>

            <!-- Unique — cyan accent -->
            <div class="relative rounded-xl border border-border bg-card p-4 md:p-5 overflow-hidden hover:border-cyan-400/40 hover:shadow-[0_0_20px_oklch(0.72_0.15_200/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/70 to-transparent" />
                <p class="text-xs font-medium text-muted-foreground mb-3">{{ t('qr.analytics.unique') }}</p>
                <p class="text-2xl font-bold md:text-3xl">{{ stats.unique.toLocaleString() }}</p>
                <p class="text-muted-foreground mt-1 text-xs">{{ t('qr.analytics.distinctIps') }}</p>
            </div>

            <!-- Today — violet accent -->
            <div class="relative rounded-xl border border-border bg-card p-4 md:p-5 overflow-hidden hover:border-primary/40 hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
                <p class="text-xs font-medium text-muted-foreground mb-3">{{ t('qr.analytics.today') }}</p>
                <p class="text-2xl font-bold md:text-3xl">{{ stats.today.toLocaleString() }}</p>
            </div>

            <!-- This Month — gold accent -->
            <div class="relative rounded-xl border border-border bg-card p-4 md:p-5 overflow-hidden hover:border-gold-500/40 hover:shadow-[0_0_20px_oklch(0.74_0.16_85/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/70 to-transparent" />
                <p class="text-xs font-medium text-muted-foreground mb-3">{{ t('qr.analytics.thisMonth') }}</p>
                <p class="text-2xl font-bold md:text-3xl">{{ stats.this_month.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Timeline chart -->
        <div class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
            <div class="p-4 md:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex size-7 items-center justify-center rounded-md bg-primary/10">
                        <BarChart3 class="size-4 text-primary" />
                    </div>
                    <p class="text-sm font-semibold">{{ t('qr.analytics.timelineTitle') }}</p>
                </div>
                <VueApexCharts
                    type="area"
                    height="180"
                    :options="timelineOptions"
                    :series="timelineOptions.series"
                />
            </div>
        </div>

        <!-- Country treemap + device donut -->
        <div class="grid gap-3 md:gap-4 md:grid-cols-2">
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <div class="p-4 md:p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex size-7 items-center justify-center rounded-md bg-cyan-400/10">
                            <Globe class="size-4 text-cyan-400" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('qr.analytics.countryDistribution') }}</p>
                    </div>
                    <VueApexCharts
                        type="treemap"
                        height="220"
                        :options="treemapOptions"
                        :series="treemapOptions.series"
                    />
                </div>
            </div>

            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
                <div class="p-4 md:p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex size-7 items-center justify-center rounded-md bg-primary/10">
                            <Smartphone class="size-4 text-primary" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('qr.analytics.deviceTypes') }}</p>
                    </div>
                    <template v-if="deviceBreakdown.length">
                        <VueApexCharts
                            type="donut"
                            height="200"
                            :options="deviceDonutOptions"
                            :series="deviceDonutOptions.series"
                        />
                    </template>
                    <div v-else class="flex h-48 items-center justify-center">
                        <p class="text-muted-foreground text-sm">{{ t('qr.analytics.noDeviceData') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Browser donut + This week -->
        <div class="grid gap-3 md:gap-4 md:grid-cols-2">
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <div class="p-4 md:p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex size-7 items-center justify-center rounded-md bg-cyan-400/10">
                            <Monitor class="size-4 text-cyan-400" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('qr.analytics.topBrowsers') }}</p>
                    </div>
                    <template v-if="topBrowsers.length">
                        <VueApexCharts
                            type="donut"
                            height="200"
                            :options="browserDonutOptions"
                            :series="browserDonutOptions.series"
                        />
                    </template>
                    <div v-else class="flex h-48 items-center justify-center">
                        <p class="text-muted-foreground text-sm">{{ t('qr.analytics.noBrowserData') }}</p>
                    </div>
                </div>
            </div>

            <!-- This week stat card — gold accent -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-gold-500/40 hover:shadow-[0_0_20px_oklch(0.74_0.16_85/0.1)] transition-all duration-200">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/70 to-transparent" />
                <div class="p-4 md:p-5 flex flex-col justify-between h-full">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex size-7 items-center justify-center rounded-md bg-gold-500/10">
                            <Clock class="size-4 text-gold-500" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('qr.analytics.thisWeek') }}</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold md:text-5xl">{{ stats.this_week.toLocaleString() }}</p>
                        <p class="text-muted-foreground mt-2 text-sm">{{ t('qr.analytics.scansLastWeek') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly heatmap -->
        <div class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/50 to-transparent" />
            <div class="p-4 md:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex size-7 items-center justify-center rounded-md bg-primary/10">
                        <Clock class="size-4 text-primary" />
                    </div>
                    <p class="text-sm font-semibold">{{ t('qr.analytics.heatmapTitle') }}</p>
                </div>
                <VueApexCharts
                    type="heatmap"
                    height="220"
                    :options="heatmapOptions"
                    :series="heatmapSeries"
                />
            </div>
        </div>

        <!-- AI Performance Insights -->
        <div class="relative rounded-xl border border-primary/20 bg-primary/5 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <div class="p-4 md:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-primary/10">
                            <Sparkles class="size-4 text-primary" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('ai.performanceInsights') }}</p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="loadingInsight"
                        class="hover:border-primary/50 transition-colors duration-150 shrink-0"
                        @click="generateInsight"
                    >
                        <span v-if="loadingInsight" class="animate-spin mr-1.5 size-3.5 inline-block">⏳</span>
                        {{ loadingInsight ? t('ai.generating') : t('ai.generateInsight') }}
                    </Button>
                </div>
                <p v-if="aiInsight" class="text-sm leading-relaxed">{{ aiInsight }}</p>
                <p v-else class="text-sm text-muted-foreground">{{ t('ai.insightHint') }}</p>
            </div>
        </div>

        <!-- AI Anomaly Detection -->
        <div
            :class="anomalyResult?.is_anomalous
                ? 'border-destructive/40 bg-destructive/5'
                : 'border-border bg-card'"
            class="relative rounded-xl overflow-hidden"
        >
            <div
                :class="anomalyResult?.is_anomalous
                    ? 'bg-gradient-to-r from-transparent via-destructive/50 to-transparent'
                    : 'bg-gradient-to-r from-transparent via-border/60 to-transparent'"
                class="absolute inset-x-0 top-0 h-px"
            />
            <div class="p-4 md:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-md bg-muted">
                            <Shield class="size-4 text-muted-foreground" />
                        </div>
                        <p class="text-sm font-semibold">{{ t('ai.anomalyDetection') }}</p>
                        <span
                            v-if="anomalyResult"
                            :class="anomalyResult.is_anomalous
                                ? 'bg-destructive/10 text-destructive border-destructive/30'
                                : 'bg-green-500/10 text-green-400 border-green-500/30'"
                            class="rounded-full border px-2 py-0.5 text-xs font-medium"
                        >
                            {{ anomalyResult.is_anomalous ? t('ai.anomalyDetected') : t('ai.noAnomaly') }}
                        </span>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="loadingAnomaly"
                        class="hover:border-primary/50 transition-colors duration-150 shrink-0"
                        @click="detectAnomalies"
                    >
                        <span v-if="loadingAnomaly" class="animate-spin mr-1.5 size-3.5 inline-block">⏳</span>
                        {{ loadingAnomaly ? t('ai.analyzing') : t('ai.runAnalysis') }}
                    </Button>
                </div>
                <template v-if="anomalyResult">
                    <p class="text-sm text-muted-foreground mb-2">
                        {{ t('ai.confidence') }}: <strong class="text-foreground">{{ anomalyResult.confidence }}</strong>
                    </p>
                    <ul v-if="anomalyResult.reasons.length" class="mb-2 space-y-1">
                        <li
                            v-for="r in anomalyResult.reasons"
                            :key="r"
                            class="flex items-start gap-1.5 text-sm"
                        >
                            <span class="mt-0.5 text-destructive shrink-0">⚠</span>
                            {{ r }}
                        </li>
                    </ul>
                    <p class="text-sm font-medium">{{ t('ai.recommendation') }}: {{ anomalyResult.recommendation }}</p>
                </template>
                <p v-else class="text-sm text-muted-foreground">{{ t('ai.anomalyHint') }}</p>
            </div>
        </div>

        <!-- Recent scans -->
        <div class="relative rounded-xl border border-border bg-card overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
            <div class="p-4 md:p-5 pb-0">
                <div class="flex items-center gap-2 mb-1">
                    <div class="flex size-7 items-center justify-center rounded-md bg-cyan-400/10">
                        <Activity class="size-4 text-cyan-400" />
                    </div>
                    <p class="text-sm font-semibold">{{ t('qr.analytics.recentScans') }}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <template v-if="recentScans.length">
                    <div
                        v-for="(scan, i) in recentScans"
                        :key="i"
                        class="flex items-center gap-4 px-4 md:px-5 py-3 text-sm transition-colors duration-100 hover:bg-muted/30 cursor-default"
                        :class="{ 'border-t border-border/60': i > 0 }"
                    >
                        <div class="flex size-8 items-center justify-center rounded-md bg-muted shrink-0">
                            <component
                                :is="deviceIcon(scan.device_type)"
                                class="text-muted-foreground size-4"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-sm">
                                {{ scan.city ?? scan.country ?? t('qr.analytics.unknownLocation') }}
                                <span
                                    v-if="scan.country && scan.city"
                                    class="text-muted-foreground font-normal"
                                >, {{ scan.country }}</span>
                            </p>
                            <p class="text-muted-foreground truncate text-xs">
                                {{ scan.browser ?? t('qr.analytics.unknownBrowser') }} · {{ scan.os ?? t('qr.analytics.unknownOs') }}
                            </p>
                        </div>
                        <span class="text-muted-foreground shrink-0 text-xs tabular-nums">
                            {{ formatDateTime(scan.scanned_at) }}
                        </span>
                    </div>
                </template>
                <div v-else class="flex flex-col items-center justify-center py-12 text-center px-6">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-muted ring-1 ring-border mb-3">
                        <Activity class="size-6 text-muted-foreground" />
                    </div>
                    <p class="text-muted-foreground text-sm">{{ t('qr.analytics.noScansRecorded') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
