<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Activity, ArrowLeft, BarChart3, Globe, Monitor, Smartphone, Tablet, TrendingUp } from 'lucide-vue-next'
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface QrCode {
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

const props = defineProps<{
    qrCode: QrCode
    stats: Stats
    topCountries: CountRow[]
    deviceBreakdown: DeviceRow[]
    topBrowsers: BrowserRow[]
    dailyScans: DailyRow[]
    recentScans: ScanRow[]
}>()

const maxCountry = computed(() => Math.max(1, ...props.topCountries.map((r) => r.count)))
const maxDevice = computed(() => Math.max(1, ...props.deviceBreakdown.map((r) => r.count)))
const maxBrowser = computed(() => Math.max(1, ...props.topBrowsers.map((r) => r.count)))
const maxDaily = computed(() => Math.max(1, ...props.dailyScans.map((r) => r.count)))

const deviceIcon = (type: string | null) => {
    if (type === 'mobile') return Smartphone
    if (type === 'tablet') return Tablet
    return Monitor
}

const formatDateTime = (iso: string) =>
    new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const publicUrl = computed(() => `/q/${props.qrCode.short_hash}`)
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
                    <CardTitle>Total Scans</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold">{{ stats.total.toLocaleString() }}</p>
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

        <!-- Daily sparkline + top countries -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="size-4" />
                        Last 30 Days
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex h-28 items-end gap-0.5">
                        <template v-if="dailyScans.length">
                            <div
                                v-for="day in dailyScans"
                                :key="day.date"
                                :title="`${day.date}: ${day.count}`"
                                class="bg-primary/70 hover:bg-primary min-w-0 flex-1 rounded-t transition-colors"
                                :style="{ height: `${Math.max(4, (day.count / maxDaily) * 100)}%` }"
                            />
                        </template>
                        <div v-else class="text-muted-foreground flex w-full items-center justify-center text-sm">
                            No scans yet
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="size-4" />
                        Top Countries
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <template v-if="topCountries.length">
                        <div
                            v-for="row in topCountries"
                            :key="row.country"
                            class="space-y-1"
                        >
                            <div class="flex justify-between text-sm">
                                <span class="font-medium">{{ row.country }}</span>
                                <span class="text-muted-foreground">{{ row.count }}</span>
                            </div>
                            <div class="bg-muted h-1.5 w-full rounded-full">
                                <div
                                    class="bg-primary h-1.5 rounded-full"
                                    :style="{ width: `${(row.count / maxCountry) * 100}%` }"
                                />
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-muted-foreground text-sm">No geo data yet</p>
                </CardContent>
            </Card>
        </div>

        <!-- Device + browser breakdown -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Monitor class="size-4" />
                        Device Types
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <template v-if="deviceBreakdown.length">
                        <div
                            v-for="row in deviceBreakdown"
                            :key="row.type"
                            class="space-y-1"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-1.5 font-medium capitalize">
                                    <component :is="deviceIcon(row.type)" class="size-3.5" />
                                    {{ row.type }}
                                </span>
                                <span class="text-muted-foreground">{{ row.count }}</span>
                            </div>
                            <div class="bg-muted h-1.5 w-full rounded-full">
                                <div
                                    class="bg-primary h-1.5 rounded-full"
                                    :style="{ width: `${(row.count / maxDevice) * 100}%` }"
                                />
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-muted-foreground text-sm">No device data yet</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="size-4" />
                        Top Browsers
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <template v-if="topBrowsers.length">
                        <div
                            v-for="row in topBrowsers"
                            :key="row.browser"
                            class="space-y-1"
                        >
                            <div class="flex justify-between text-sm">
                                <span class="font-medium">{{ row.browser }}</span>
                                <span class="text-muted-foreground">{{ row.count }}</span>
                            </div>
                            <div class="bg-muted h-1.5 w-full rounded-full">
                                <div
                                    class="bg-primary h-1.5 rounded-full"
                                    :style="{ width: `${(row.count / maxBrowser) * 100}%` }"
                                />
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-muted-foreground text-sm">No browser data yet</p>
                </CardContent>
            </Card>
        </div>

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
