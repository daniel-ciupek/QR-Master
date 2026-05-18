<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, TrendingUp, BarChart2, Globe, Smartphone } from 'lucide-vue-next'
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

// Palette — design system tokens mapped to chart hex values
const COLORS = ['#8b5cf6', '#22d3ee', '#f59e0b', '#ef4444', '#10b981']

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

// Build a single overlay chart with one series per QR code
const overlayOptions = computed(() => ({
    chart: {
        type: 'line' as const,
        height: 220,
        toolbar: { show: false },
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 400 },
    },
    theme: { mode: 'dark' as const },
    stroke: { curve: 'smooth' as const, width: 2 },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime' as const,
        labels: { format: 'dd MMM', style: { fontSize: '10px', colors: '#71717a' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { fontSize: '10px', colors: '#71717a' } }, min: 0 },
    grid: { borderColor: 'oklch(0.28 0.028 272)', strokeDashArray: 4 },
    tooltip: { x: { format: 'dd MMM yyyy' }, theme: 'dark' },
    legend: { show: true, position: 'top' as const, fontSize: '11px', labels: { colors: '#a1a1aa' } },
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
        theme: { mode: 'dark' as const },
        stroke: { curve: 'smooth' as const, width: 1.5 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0, stops: [0, 100] },
        },
        colors: [COLORS[colorIndex]],
        tooltip: { x: { format: 'dd MMM' }, theme: 'dark' },
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

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 md:px-6">

        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    as-child
                    class="hover:bg-primary/10 hover:text-primary transition-colors duration-150"
                >
                    <Link href="/qr">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('qr.compare.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ t('qr.compare.subtitle', { count: items.length }) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Overlay timeline chart -->
        <Card class="relative overflow-hidden hover:border-primary/30 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)] transition-all duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 ring-1 ring-primary/20">
                        <TrendingUp class="size-4 text-primary" />
                    </div>
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
                class="relative flex flex-col overflow-hidden hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)] transition-all duration-200"
                :class="idx === 0 ? 'hover:border-primary/40' : idx === 1 ? 'hover:border-cyan-400/40' : 'hover:border-gold-500/40'"
            >
                <!-- Gradient top-border per card colour -->
                <div
                    class="absolute inset-x-0 top-0 h-px"
                    :style="{
                        background: `linear-gradient(to right, transparent, ${COLORS[idx]}, transparent)`,
                    }"
                />
                <CardHeader class="pb-2 pt-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <CardTitle class="truncate text-base">{{ item.title }}</CardTitle>
                            <p class="text-muted-foreground mt-0.5 truncate text-xs">{{ item.destination_url ?? '—' }}</p>
                        </div>
                        <Badge
                            :variant="item.is_active ? 'default' : 'secondary'"
                            class="shrink-0 text-[10px]"
                            :class="item.is_active ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25' : ''"
                        >
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
                    <div class="space-y-2.5">
                        <!-- Total -->
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">{{ t('qr.compare.stats.totalScans') }}</span>
                                <span
                                    class="font-semibold tabular-nums"
                                    :class="isWinner(item, 'total') ? 'text-emerald-400' : 'text-foreground'"
                                >
                                    {{ item.stats.total.toLocaleString() }}
                                    <span v-if="isWinner(item, 'total')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted/60 h-1.5 w-full overflow-hidden rounded-full">
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
                                    class="font-semibold tabular-nums"
                                    :class="isWinner(item, 'unique') ? 'text-emerald-400' : 'text-foreground'"
                                >
                                    {{ item.stats.unique.toLocaleString() }}
                                    <span v-if="isWinner(item, 'unique')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted/60 h-1.5 w-full overflow-hidden rounded-full">
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
                                    class="font-semibold tabular-nums"
                                    :class="isWinner(item, 'this_month') ? 'text-emerald-400' : 'text-foreground'"
                                >
                                    {{ item.stats.this_month.toLocaleString() }}
                                    <span v-if="isWinner(item, 'this_month')" class="ml-0.5 text-[9px]">▲</span>
                                </span>
                            </div>
                            <div class="bg-muted/60 h-1.5 w-full overflow-hidden rounded-full">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${pct(item.stats.this_month, maxMonth)}%`, backgroundColor: COLORS[idx] }"
                                />
                            </div>
                        </div>

                        <!-- Today / This week inline -->
                        <div class="flex gap-4 pt-1 text-xs border-t border-border/50">
                            <div class="pt-2">
                                <span class="text-muted-foreground block">{{ t('qr.compare.stats.today') }}</span>
                                <span
                                    class="font-semibold tabular-nums"
                                    :class="isWinner(item, 'today') ? 'text-emerald-400' : 'text-foreground'"
                                >{{ item.stats.today }}</span>
                            </div>
                            <div class="pt-2">
                                <span class="text-muted-foreground block">{{ t('qr.compare.stats.thisWeek') }}</span>
                                <span
                                    class="font-semibold tabular-nums"
                                    :class="isWinner(item, 'this_week') ? 'text-emerald-400' : 'text-foreground'"
                                >{{ item.stats.this_week }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top countries -->
                    <div v-if="item.topCountries.length > 0">
                        <div class="flex items-center gap-1.5 mb-2">
                            <Globe class="size-3 text-muted-foreground" />
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ t('qr.compare.topCountries') }}</p>
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="row in item.topCountries"
                                :key="row.country"
                                class="flex items-center justify-between text-xs hover:bg-muted/30 rounded px-1 py-0.5 transition-colors duration-100"
                            >
                                <span class="text-foreground">{{ row.country }}</span>
                                <span class="text-muted-foreground font-medium tabular-nums">{{ row.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Device breakdown -->
                    <div v-if="item.deviceBreakdown.length > 0">
                        <div class="flex items-center gap-1.5 mb-2">
                            <Smartphone class="size-3 text-muted-foreground" />
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ t('qr.compare.devices') }}</p>
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="row in item.deviceBreakdown"
                                :key="row.type"
                                class="flex items-center gap-2 text-xs"
                            >
                                <span class="text-muted-foreground w-16 capitalize">{{ row.type }}</span>
                                <div class="bg-muted/60 h-1.5 flex-1 overflow-hidden rounded-full">
                                    <div
                                        class="h-full rounded-full opacity-70 transition-all duration-500"
                                        :style="{
                                            width: `${pct(row.count, item.stats.total)}%`,
                                            backgroundColor: COLORS[idx],
                                        }"
                                    />
                                </div>
                                <span class="w-6 text-right font-medium tabular-nums text-foreground">{{ row.count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Link to full analytics -->
                    <div class="mt-auto pt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full text-xs hover:border-primary/40 hover:text-primary transition-colors duration-150"
                            as-child
                        >
                            <Link :href="`/qr/${item.id}/analytics`">
                                <BarChart2 class="size-3.5 mr-1.5" />
                                {{ t('qr.compare.viewFullAnalytics') }}
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
