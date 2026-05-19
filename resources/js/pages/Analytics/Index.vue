<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { usePreferredDark } from '@vueuse/core'
import { BarChart2, QrCode, TrendingDown, TrendingUp } from 'lucide-vue-next'
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

interface TimelinePoint { date: string; count: number }
interface QrRow { id: number; title: string; type: string; short_hash: string; is_active: boolean; total_scans: number; period_scans: number; percent: number }
interface DeviceRow { type: string; count: number }
interface CountryRow { country: string; count: number }
interface BrowserRow { browser: string; count: number }

const props = defineProps<{
    range: number
    stats: { totalScans: number; uniqueScans: number; avgPerDay: number; trend: number }
    scanTimeline: TimelinePoint[]
    topQrCodes: QrRow[]
    deviceBreakdown: DeviceRow[]
    countryBreakdown: CountryRow[]
    browserBreakdown: BrowserRow[]
}>()

function changeRange(days: number): void {
    router.get(route('analytics'), { range: days }, { preserveScroll: true })
}

const chartSeries = computed(() => [{
    name: t('analytics.chart.scans'),
    data: props.scanTimeline.map(p => ({ x: new Date(p.date).getTime(), y: p.count })),
}])

const chartOptions = computed(() => ({
    chart: { type: 'area' as const, height: 240, toolbar: { show: false }, background: 'transparent', animations: { enabled: true } },
    theme: { mode: themeMode.value },
    stroke: { curve: 'smooth' as const, width: 2 },
    colors: ['#6366f1'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false },
    xaxis: { type: 'datetime' as const, labels: { style: { fontSize: '11px' }, datetimeUTC: false }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { min: 0, labels: { style: { fontSize: '11px' }, formatter: (v: number) => Math.floor(v).toString() } },
    grid: { strokeDashArray: 4, borderColor: isDark.value ? '#1e293b' : '#e2e8f0' },
    tooltip: { x: { format: 'dd MMM' }, theme: themeMode.value },
}))

const deviceOptions = computed(() => ({
    chart: { type: 'donut' as const, height: 200, background: 'transparent' },
    theme: { mode: themeMode.value },
    labels: props.deviceBreakdown.map(r => r.type || 'unknown'),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '11px' },
    plotOptions: { pie: { donut: { size: '55%' } } },
}))

const deviceSeries = computed(() => props.deviceBreakdown.map(r => r.count))

const browserOptions = computed(() => ({
    chart: { type: 'donut' as const, height: 200, background: 'transparent' },
    theme: { mode: themeMode.value },
    labels: props.browserBreakdown.map(r => r.browser || 'unknown'),
    dataLabels: { enabled: true },
    legend: { position: 'bottom' as const, fontSize: '11px' },
    plotOptions: { pie: { donut: { size: '55%' } } },
}))

const browserSeries = computed(() => props.browserBreakdown.map(r => r.count))

function formatNum(n: number): string {
    return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : n.toString()
}

function r(name: string, params?: Record<string, string | number | boolean> | string | number): string {
    return route(name, params)
}
</script>

<template>
    <Head :title="t('analytics.title')" />

    <div class="space-y-4 md:space-y-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div>
                <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent sm:text-3xl">
                    {{ t('analytics.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ t('analytics.subtitle') }}</p>
            </div>
            <!-- Range picker -->
            <div class="flex gap-1 rounded-lg border border-border/60 bg-card p-1 self-start sm:self-auto">
                <button
                    v-for="d in [7, 30, 90]"
                    :key="d"
                    class="rounded-md px-3 py-1 text-sm transition-all duration-150"
                    :class="range === d
                        ? 'bg-primary text-primary-foreground shadow-[0_0_8px_oklch(0.66_0.25_285/0.3)]'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    @click="changeRange(d)"
                >
                    {{ d }}d
                </button>
            </div>
        </div>

        <!-- No data empty state -->
        <div
            v-if="stats.totalScans === 0"
            class="relative flex flex-col items-center justify-center overflow-hidden rounded-xl border border-dashed py-20 text-center"
        >
            <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
            <div class="relative flex size-16 items-center justify-center rounded-2xl bg-cyan-400/10 ring-1 ring-cyan-400/20 mb-4">
                <BarChart2 class="size-8 text-cyan-400" />
            </div>
            <h2 class="mb-1 text-lg font-semibold">{{ t('analytics.noData.title') }}</h2>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">{{ t('analytics.noData.description') }}</p>
            <Button variant="outline" as-child class="transition-colors duration-150 hover:border-primary/50 hover:text-primary">
                <a :href="r('qr.index')">{{ t('analytics.noData.cta') }}</a>
            </Button>
        </div>

        <template v-else>
            <!-- Stat cards -->
            <div class="grid gap-3 md:gap-4 grid-cols-1 sm:grid-cols-3">
                <!-- Total scans -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('analytics.stats.totalScans') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ formatNum(stats.totalScans) }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <BarChart2 class="size-5 text-primary" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-1 text-xs">
                        <TrendingUp v-if="stats.trend >= 0" class="size-3.5 text-green-500" />
                        <TrendingDown v-else class="size-3.5 text-red-500" />
                        <span :class="stats.trend >= 0 ? 'text-green-500' : 'text-red-500'">
                            {{ stats.trend >= 0 ? '+' : '' }}{{ stats.trend }}%
                        </span>
                        <span class="text-muted-foreground">{{ t('analytics.stats.trend') }}</span>
                    </div>
                </div>

                <!-- Unique scans -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-cyan-400/40 hover:shadow-[0_0_24px_oklch(0.72_0.15_200/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('analytics.stats.uniqueScans') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ formatNum(stats.uniqueScans) }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <QrCode class="size-5 text-cyan-400" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ stats.totalScans > 0 ? Math.round(stats.uniqueScans / stats.totalScans * 100) : 0 }}% unique
                    </p>
                </div>

                <!-- Avg per day -->
                <div class="relative overflow-hidden rounded-xl border border-border bg-card p-4 md:p-5 hover:border-gold-500/40 hover:shadow-[0_0_24px_oklch(0.78_0.15_85/0.12)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ t('analytics.stats.avgPerDay') }}</p>
                            <p class="mt-1 text-2xl font-bold sm:text-3xl">{{ stats.avgPerDay }}</p>
                        </div>
                        <div class="flex size-10 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20">
                            <TrendingUp class="size-5 text-gold-500" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ t('analytics.range.label') }}: {{ range }}d</p>
                </div>
            </div>

            <!-- Timeline chart -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">{{ t('analytics.chart.title') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <VueApexCharts
                        type="area"
                        height="240"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </CardContent>
            </Card>

            <!-- Top QR codes + device/browser breakdowns -->
            <div class="grid gap-3 md:gap-4 lg:grid-cols-2">
                <!-- Top QR codes table -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('analytics.topQr.title') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topQrCodes.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            {{ t('analytics.topQr.noData') }}
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(qr, idx) in topQrCodes"
                                :key="qr.id"
                                class="flex items-center gap-3"
                            >
                                <span class="w-5 text-right text-sm font-bold text-muted-foreground">{{ idx + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <a
                                        :href="r('qr.analytics', qr.id)"
                                        class="block truncate text-sm font-medium hover:text-primary"
                                    >{{ qr.title }}</a>
                                    <div class="mt-0.5 h-1 w-full overflow-hidden rounded-full bg-secondary">
                                        <div
                                            class="h-full rounded-full bg-primary/60"
                                            :style="{ width: `${qr.percent}%` }"
                                        />
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold">{{ formatNum(qr.period_scans) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ qr.percent }}%</p>
                                </div>
                                <Badge :variant="qr.is_active ? 'default' : 'secondary'" class="shrink-0 text-xs">
                                    <QrCode class="size-3" />
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Country breakdown -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('analytics.country.title') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="countryBreakdown.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            {{ t('analytics.topQr.noData') }}
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="row in countryBreakdown"
                                :key="row.country"
                                class="flex items-center gap-3"
                            >
                                <span class="w-8 text-sm font-medium">{{ row.country }}</span>
                                <div class="flex-1">
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                        <div
                                            class="h-full rounded-full bg-violet-500/70"
                                            :style="{ width: `${stats.totalScans > 0 ? Math.round(row.count / stats.totalScans * 100) : 0}%` }"
                                        />
                                    </div>
                                </div>
                                <span class="w-10 text-right text-xs text-muted-foreground">{{ formatNum(row.count) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Device + Browser donuts -->
            <div class="grid gap-3 md:gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('analytics.device.title') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <VueApexCharts
                            v-if="deviceBreakdown.length > 0"
                            type="donut"
                            height="200"
                            :options="deviceOptions"
                            :series="deviceSeries"
                        />
                        <div v-else class="flex h-[200px] items-center justify-center text-sm text-muted-foreground">
                            {{ t('analytics.topQr.noData') }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ t('analytics.browser.title') }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <VueApexCharts
                            v-if="browserBreakdown.length > 0"
                            type="donut"
                            height="200"
                            :options="browserOptions"
                            :series="browserSeries"
                        />
                        <div v-else class="flex h-[200px] items-center justify-center text-sm text-muted-foreground">
                            {{ t('analytics.topQr.noData') }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>
    </div>
</template>
