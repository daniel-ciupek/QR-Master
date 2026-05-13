<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { usePreferredDark } from '@vueuse/core'
import { Activity, ArrowLeft, Monitor, Smartphone, Tablet } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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

const isDark = usePreferredDark()
const themeMode = computed((): 'dark' | 'light' => (isDark.value ? 'dark' : 'light'))

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
        height: 160,
        toolbar: { show: false },
        background: 'transparent',
        sparkline: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 400 },
    },
    theme: { mode: themeMode.value },
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] },
    },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { format: 'dd MMM', style: { fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '11px' } }, min: 0 },
    grid: { borderColor: isDark.value ? '#333' : '#e5e7eb', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM yyyy' } },
    series: [{ name: 'Scans', data: filledDailyScans.value }],
}))

const treemapOptions = computed(() => ({
    chart: {
        type: 'treemap' as const,
        height: 220,
        toolbar: { show: false },
        background: 'transparent',
    },
    theme: { mode: themeMode.value },
    dataLabels: { enabled: true, style: { fontSize: '12px' } },
    plotOptions: { treemap: { distributed: true, enableShades: false } },
    series: [
        {
            data: props.topCountries.length
                ? props.topCountries.map((r) => ({ x: r.country, y: r.count }))
                : [{ x: 'No data', y: 1 }],
        },
    ],
    tooltip: { y: { formatter: (v: number) => `${v} scans` } },
}))

const deviceDonutOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        height: 200,
        background: 'transparent',
    },
    theme: { mode: themeMode.value },
    labels: props.deviceBreakdown.map((r) => r.type),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '12px' },
    series: props.deviceBreakdown.map((r) => r.count),
    plotOptions: { pie: { donut: { size: '55%' } } },
}))

const browserDonutOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        height: 200,
        background: 'transparent',
    },
    theme: { mode: themeMode.value },
    labels: props.topBrowsers.map((r) => r.browser),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '12px' },
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
    theme: { mode: themeMode.value },
    dataLabels: { enabled: false },
    stroke: { width: 1 },
    plotOptions: {
        heatmap: {
            shadeIntensity: 0.6,
            radius: 2,
            colorScale: {
                ranges: [
                    { from: 0, to: 0, color: isDark.value ? '#1f2937' : '#f3f4f6', name: 'None' },
                    { from: 1, to: 5, color: '#93c5fd', name: 'Low' },
                    { from: 6, to: 20, color: '#3b82f6', name: 'Medium' },
                    { from: 21, to: 9999, color: '#1d4ed8', name: 'High' },
                ],
            },
        },
    },
    xaxis: { labels: { style: { fontSize: '10px' }, rotate: -45 } },
    yaxis: { labels: { style: { fontSize: '11px' } } },
    tooltip: { y: { formatter: (v: number) => `${v} scans` } },
}))

const deviceIcon = (type: string | null) => {
    if (type === 'mobile') return Smartphone
    if (type === 'tablet') return Tablet
    return Monitor
}

const formatDateTime = (iso: string) =>
    new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const publicUrl = computed(() => `/q/${props.qrCode.short_hash}`)

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

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" as-child>
                <Link href="/qr">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold">{{ qrCode.title }}</h1>
                    <Badge :variant="qrCode.is_active ? 'default' : 'secondary'">
                        {{ qrCode.is_active ? 'Active' : 'Paused' }}
                    </Badge>
                </div>
                <p class="text-muted-foreground text-sm">{{ publicUrl }}</p>
            </div>
            <Button variant="outline" size="sm" as-child>
                <Link :href="`/qr/${qrCode.id}/edit`">Edit</Link>
            </Button>
        </div>

        <!-- Metric cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-1.5">
                        Total Scans
                        <span class="relative flex size-2">
                            <span class="bg-primary absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" />
                            <span class="bg-primary relative inline-flex size-2 rounded-full" />
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ liveTotal.toLocaleString() }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle>Unique</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ stats.unique.toLocaleString() }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">distinct IPs</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle>Today</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ stats.today.toLocaleString() }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle>This Month</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ stats.this_month.toLocaleString() }}</p>
                </CardContent>
            </Card>
        </div>

        <!-- Timeline chart -->
        <Card>
            <CardHeader>
                <CardTitle>Scans — Last 30 Days</CardTitle>
            </CardHeader>
            <CardContent class="pt-0">
                <VueApexCharts
                    type="area"
                    height="160"
                    :options="timelineOptions"
                    :series="timelineOptions.series"
                />
            </CardContent>
        </Card>

        <!-- Country treemap + device donut -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Country Distribution</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <VueApexCharts
                        type="treemap"
                        height="220"
                        :options="treemapOptions"
                        :series="treemapOptions.series"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Device Types</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <template v-if="deviceBreakdown.length">
                        <VueApexCharts
                            type="donut"
                            height="200"
                            :options="deviceDonutOptions"
                            :series="deviceDonutOptions.series"
                        />
                    </template>
                    <div v-else class="text-muted-foreground flex h-48 items-center justify-center text-sm">
                        No device data yet
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Browser donut -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Top Browsers</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <template v-if="topBrowsers.length">
                        <VueApexCharts
                            type="donut"
                            height="200"
                            :options="browserDonutOptions"
                            :series="browserDonutOptions.series"
                        />
                    </template>
                    <div v-else class="text-muted-foreground flex h-48 items-center justify-center text-sm">
                        No browser data yet
                    </div>
                </CardContent>
            </Card>

            <!-- This week stat card -->
            <Card class="flex flex-col justify-between">
                <CardHeader>
                    <CardTitle>This Week</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-5xl font-bold">{{ stats.this_week.toLocaleString() }}</p>
                    <p class="text-muted-foreground mt-2 text-sm">scans in the last 7 days</p>
                </CardContent>
            </Card>
        </div>

        <!-- Hourly heatmap -->
        <Card>
            <CardHeader>
                <CardTitle>Scan Activity by Hour &amp; Day</CardTitle>
            </CardHeader>
            <CardContent class="pt-0">
                <VueApexCharts
                    type="heatmap"
                    height="220"
                    :options="heatmapOptions"
                    :series="heatmapSeries"
                />
            </CardContent>
        </Card>

        <!-- Recent scans -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Activity class="size-4" />
                    Recent Scans
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <template v-if="recentScans.length">
                    <div
                        v-for="(scan, i) in recentScans"
                        :key="i"
                        class="hover:bg-muted/50 flex items-center gap-4 px-6 py-3 text-sm transition-colors"
                        :class="{ 'border-t': i > 0 }"
                    >
                        <component
                            :is="deviceIcon(scan.device_type)"
                            class="text-muted-foreground size-4 shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ scan.city ?? scan.country ?? 'Unknown location' }}
                                <span
                                    v-if="scan.country && scan.city"
                                    class="text-muted-foreground font-normal"
                                >, {{ scan.country }}</span>
                            </p>
                            <p class="text-muted-foreground truncate text-xs">
                                {{ scan.browser ?? 'Unknown browser' }} · {{ scan.os ?? 'Unknown OS' }}
                            </p>
                        </div>
                        <span class="text-muted-foreground shrink-0 text-xs">
                            {{ formatDateTime(scan.scanned_at) }}
                        </span>
                    </div>
                </template>
                <div v-else class="text-muted-foreground px-6 py-8 text-center text-sm">
                    No scans recorded yet
                </div>
            </CardContent>
        </Card>
    </div>
</template>
