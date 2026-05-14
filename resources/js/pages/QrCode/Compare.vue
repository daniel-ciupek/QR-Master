<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { usePreferredDark } from '@vueuse/core'
import { ArrowLeft, TrendingUp } from 'lucide-vue-next'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import VueApexCharts from 'vue3-apexcharts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Stats {
    total: number
    unique: number
    today: number
    this_week: number
    this_month: number
}

interface CountryRow { country: string; count: number }
interface DeviceRow { type: string; count: number }
interface DailyRow { date: string; count: number }

interface CompareItem {
    id: number
    title: string
    short_hash: string
    type: string
    destination_url: string | null
    is_active: boolean
    created_at: string
    stats: Stats
    topCountries: CountryRow[]
    deviceBreakdown: DeviceRow[]
    dailyScans: DailyRow[]
}

const props = defineProps<{ items: CompareItem[] }>()

const { t } = useI18n()
const isDark = usePreferredDark()
const themeMode = computed((): 'dark' | 'light' => (isDark.value ? 'dark' : 'light'))

// Fill last 30 days for each item
function filledDaily(dailyScans: DailyRow[]): { x: number; y: number }[] {
    const map = new Map(dailyScans.map((d) => [d.date, d.count]))
    const result: { x: number; y: number }[] = []
    for (let i = 29; i >= 0; i--) {
        const d = new Date()
        d.setDate(d.getDate() - i)
        const key = d.toISOString().slice(0, 10)
        result.push({ x: d.getTime(), y: map.get(key) ?? 0 })
    }
    return result
}

// Palette for multi-series (one colour per QR code)
const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']

// Build a single overlay chart with one series per QR code
const overlayOptions = computed(() => ({
    chart: {
        type: 'line' as const,
        height: 220,
        toolbar: { show: false },
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 400 },
    },
    theme: { mode: themeMode.value },
    stroke: { curve: 'smooth' as const, width: 2 },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { format: 'dd MMM', style: { fontSize: '10px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '10px' } }, min: 0 },
    grid: { borderColor: isDark.value ? '#333' : '#e5e7eb', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM yyyy' } },
    legend: { show: true, position: 'top' as const, fontSize: '11px' },
    colors: COLORS.slice(0, props.items.length),
    series: props.items.map((item, i) => ({
        name: item.title,
        data: filledDaily(item.dailyScans),
        color: COLORS[i],
    })),
}))

// Per-item sparkline options (small inline chart inside each card)
function sparklineOptions(item: CompareItem, colorIndex: number) {
    return {
        chart: {
            type: 'area' as const,
            height: 60,
            sparkline: { enabled: true },
            background: 'transparent',
        },
        theme: { mode: themeMode.value },
        stroke: { curve: 'smooth' as const, width: 1.5 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0, stops: [0, 100] },
        },
        colors: [COLORS[colorIndex]],
        tooltip: { x: { format: 'dd MMM' } },
        series: [{ name: 'Scans', data: filledDaily(item.dailyScans) }],
    }
}

// Max total for percentage bar in stat comparisons
const maxTotal = computed(() => Math.max(...props.items.map((i) => i.stats.total), 1))
const maxUnique = computed(() => Math.max(...props.items.map((i) => i.stats.unique), 1))
const maxMonth = computed(() => Math.max(...props.items.map((i) => i.stats.this_month), 1))

function pct(value: number, max: number): number {
    return max > 0 ? Math.round((value / max) * 100) : 0
}

// Winner highlight helpers
function isWinner(item: CompareItem, key: keyof Stats): boolean {
    const max = Math.max(...props.items.map((i) => i.stats[key]))
    return max > 0 && item.stats[key] === max
}
</script>

<template>
    <Head :title="t('qr.compare.title')" />

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" as-child>
                <Link href="/qr">
                    <ArrowLeft class="size-4" />
                </Link>
            </Button>
            <div class="flex-1">
                <h1 class="text-xl font-semibold">{{ t('qr.compare.title') }}</h1>
                <p class="text-muted-foreground text-sm">
                    {{ t('qr.compare.subtitle', { count: items.length }) }}
                </p>
            </div>
        </div>

        <!-- Overlay timeline chart -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <TrendingUp class="size-4" />
                    {{ t('qr.compare.timelineTitle') }}
                </CardTitle>
            </CardHeader>
            <CardContent class="pt-0">
                <VueApexCharts
                    type="line"
                    height="220"
                    :options="overlayOptions"
                    :series="overlayOptions.series"
                />
            </CardContent>
        </Card>

        <!-- Per-code cards grid -->
        <div
            class="grid gap-4"
            :class="{
                'grid-cols-1 md:grid-cols-2': items.length <= 2,
                'grid-cols-1 md:grid-cols-3': items.length === 3,
                'grid-cols-1 md:grid-cols-2 xl:grid-cols-4': items.length === 4,
                'grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5': items.length === 5,
            }"
        >
            <Card
                v-for="(item, idx) in items"
                :key="item.id"
                class="flex flex-col"
            >
                <!-- Card header with colour strip -->
                <div class="h-1 rounded-t-xl" :style="{ backgroundColor: COLORS[idx] }" />
                <CardHeader class="pb-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <CardTitle class="truncate text-base">{{ item.title }}</CardTitle>
                            <p class="text-muted-foreground mt-0.5 truncate text-xs">{{ item.destination_url ?? '—' }}</p>
                        </div>
                        <Badge :variant="item.is_active ? 'default' : 'secondary'" class="shrink-0 text-[10px]">
                            {{ item.is_active ? t('qr.compare.active') : t('qr.compare.paused') }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <Badge variant="outline" class="text-[10px]">{{ item.type }}</Badge>
                        <code class="bg-muted rounded px-1.5 py-0.5 font-mono text-[10px]">{{ item.short_hash }}</code>
                        <span class="text-muted-foreground ml-auto text-[10px]">{{ item.created_at }}</span>
                    </div>
                </CardHeader>

                <CardContent class="flex flex-col gap-4 pt-0">

                    <!-- Sparkline -->
                    <VueApexCharts
                        type="area"
                        height="60"
                        :options="sparklineOptions(item, idx)"
                        :series="sparklineOptions(item, idx).series"
                    />

                    <!-- Stats with relative bars -->
                    <div class="space-y-2">
                        <!-- Total -->
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.totalScans') }}</span>
                                <span
                                    class="font-semibold"
                                    :class="isWinner(item, 'total') ? 'text-emerald-500' : ''"
                                >
                                    {{ item.stats.total.toLocaleString() }}
                                    <span v-if="isWinner(item, 'total')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${pct(item.stats.total, maxTotal)}%`, backgroundColor: COLORS[idx] }"
                                />
                            </div>
                        </div>

                        <!-- Unique -->
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.uniqueVisitors') }}</span>
                                <span
                                    class="font-semibold"
                                    :class="isWinner(item, 'unique') ? 'text-emerald-500' : ''"
                                >
                                    {{ item.stats.unique.toLocaleString() }}
                                    <span v-if="isWinner(item, 'unique')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${pct(item.stats.unique, maxUnique)}%`, backgroundColor: COLORS[idx] }"
                                />
                            </div>
                        </div>

                        <!-- This month -->
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.thisMonth') }}</span>
                                <span
                                    class="font-semibold"
                                    :class="isWinner(item, 'this_month') ? 'text-emerald-500' : ''"
                                >
                                    {{ item.stats.this_month.toLocaleString() }}
                                    <span v-if="isWinner(item, 'this_month')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted h-1.5 w-full overflow-hidden rounded-full">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${pct(item.stats.this_month, maxMonth)}%`, backgroundColor: COLORS[idx] }"
                                />
                            </div>
                        </div>

                        <!-- Today / This week inline -->
                        <div class="flex gap-4 pt-1 text-xs">
                            <div>
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.today') }}</span>
                                <span
                                    class="font-semibold"
                                    :class="isWinner(item, 'today') ? 'text-emerald-500' : ''"
                                >{{ item.stats.today }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.thisWeek') }}</span>
                                <span
                                    class="font-semibold"
                                    :class="isWinner(item, 'this_week') ? 'text-emerald-500' : ''"
                                >{{ item.stats.this_week }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top countries -->
                    <div v-if="item.topCountries.length > 0">
                        <p class="text-muted-foreground mb-1.5 text-[10px] font-semibold uppercase tracking-wider">{{ t('qr.compare.topCountries') }}</p>
                        <div class="space-y-1">
                            <div
                                v-for="row in item.topCountries"
                                :key="row.country"
                                class="flex items-center justify-between text-xs"
                            >
                                <span>{{ row.country }}</span>
                                <span class="text-muted-foreground font-medium">{{ row.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Device breakdown -->
                    <div v-if="item.deviceBreakdown.length > 0">
                        <p class="text-muted-foreground mb-1.5 text-[10px] font-semibold uppercase tracking-wider">{{ t('qr.compare.devices') }}</p>
                        <div class="space-y-1">
                            <div
                                v-for="row in item.deviceBreakdown"
                                :key="row.type"
                                class="flex items-center gap-2 text-xs"
                            >
                                <span class="text-muted-foreground w-16 capitalize">{{ row.type }}</span>
                                <div class="bg-muted h-1.5 flex-1 overflow-hidden rounded-full">
                                    <div
                                        class="h-full rounded-full opacity-70"
                                        :style="{
                                            width: `${pct(row.count, item.stats.total)}%`,
                                            backgroundColor: COLORS[idx],
                                        }"
                                    />
                                </div>
                                <span class="w-6 text-right font-medium">{{ row.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Link to full analytics -->
                    <div class="mt-auto pt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full text-xs"
                            as-child
                        >
                            <Link :href="`/qr/${item.id}/analytics`">{{ t('qr.compare.viewFullAnalytics') }}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
