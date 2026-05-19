<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft, Download, Filter } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface AuditEntry {
    id: number
    action: string
    description: string
    subject_type: string | null
    subject_id: number | null
    created_at: string
    user: { id: number; name: string; email: string } | null
}

interface Paginator {
    data: AuditEntry[]
    current_page: number
    last_page: number
    total: number
    per_page: number
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    logs: Paginator
    members: { id: number; name: string; email: string }[]
    actions: string[]
    filters: { action?: string; user_id?: string; from?: string; to?: string }
}>()

const localFilters = ref({
    action: props.filters.action ?? '',
    user_id: props.filters.user_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
})

function applyFilters(): void {
    const params: Record<string, string> = {}
    if (localFilters.value.action) params.action = localFilters.value.action
    if (localFilters.value.user_id) params.user_id = localFilters.value.user_id
    if (localFilters.value.from) params.from = localFilters.value.from
    if (localFilters.value.to) params.to = localFilters.value.to

    router.get(route('workspaces.audit.show', { team: props.team.slug }), params, { preserveScroll: true })
}

function clearFilters(): void {
    localFilters.value = { action: '', user_id: '', from: '', to: '' }
    router.get(route('workspaces.audit.show', { team: props.team.slug }), {}, { preserveScroll: true })
}

function exportCsv(): void {
    const params = new URLSearchParams()
    if (localFilters.value.action) params.set('action', localFilters.value.action)
    if (localFilters.value.from) params.set('from', localFilters.value.from)
    if (localFilters.value.to) params.set('to', localFilters.value.to)

    window.location.href = route('workspaces.audit.export', { team: props.team.slug }) + (params.toString() ? '?' + params.toString() : '')
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}

function goToPage(url: string | null): void {
    if (url) router.visit(url, { preserveScroll: true })
}

function actionBadgeClass(action: string): string {
    if (action.startsWith('member.')) return 'bg-primary/10 text-primary'
    if (action.startsWith('branding.')) return 'bg-violet-400/10 text-violet-400'
    if (action.startsWith('dpa.')) return 'bg-cyan-400/10 text-cyan-400'
    if (action.startsWith('team.')) return 'bg-muted text-muted-foreground'
    return 'bg-muted text-muted-foreground'
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString()
}

function decodeHtml(html: string): string {
    const doc = new DOMParser().parseFromString(html, 'text/html')
    return doc.documentElement.textContent ?? html
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.audit.title')}`" />

    <div class="space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    class="shrink-0 hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                    @click="goBack"
                >
                    <ArrowLeft class="size-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('workspace.audit.title') }}
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ team.name }} <span class="text-muted-foreground/50">·</span>
                        <span class="text-primary/80">{{ logs.total.toLocaleString() }}</span>
                        {{ t('workspace.audit.events') }}
                    </p>
                </div>
            </div>
            <Button
                variant="outline"
                class="shrink-0 hover:border-primary/40 transition-colors duration-150"
                @click="exportCsv"
            >
                <Download class="mr-2 size-4" />
                <span class="hidden sm:inline">{{ t('workspace.audit.export_csv') }}</span>
                <span class="sm:hidden">CSV</span>
            </Button>
        </div>

        <div class="max-w-6xl space-y-6">
            <!-- Filters card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <Filter class="size-3.5 text-primary" />
                        </div>
                        {{ t('workspace.audit.filters') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="space-y-1">
                            <Label>{{ t('workspace.audit.filter_action') }}</Label>
                            <select
                                v-model="localFilters.action"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary/50 transition-shadow duration-150"
                            >
                                <option value="">{{ t('workspace.audit.all_actions') }}</option>
                                <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label>{{ t('workspace.audit.filter_user') }}</Label>
                            <select
                                v-model="localFilters.user_id"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary/50 transition-shadow duration-150"
                            >
                                <option value="">{{ t('workspace.audit.all_users') }}</option>
                                <option v-for="m in members" :key="m.id" :value="String(m.id)">{{ m.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label>{{ t('workspace.audit.filter_from') }}</Label>
                            <Input
                                v-model="localFilters.from"
                                type="date"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                        </div>
                        <div class="space-y-1">
                            <Label>{{ t('workspace.audit.filter_to') }}</Label>
                            <Input
                                v-model="localFilters.to"
                                type="date"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="my-4 h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            class="shadow-[0_0_12px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                            @click="applyFilters"
                        >
                            {{ t('workspace.audit.apply') }}
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            class="hover:text-primary transition-colors duration-150"
                            @click="clearFilters"
                        >
                            {{ t('workspace.audit.clear') }}
                        </Button>
                    </div>
                </CardContent>
            </div>

            <!-- Log table card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.audit.log_title') }}</CardTitle>
                    <CardDescription>{{ t('workspace.audit.log_desc') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Empty state -->
                    <div v-if="logs.data.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="flex size-14 items-center justify-center rounded-2xl bg-muted ring-1 ring-border mb-4">
                            <Filter class="size-7 text-muted-foreground" />
                        </div>
                        <p class="text-sm text-muted-foreground">{{ t('workspace.audit.no_events') }}</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">{{ t('workspace.audit.col_date') }}</th>
                                    <th class="pb-2 pr-4 font-medium">{{ t('workspace.audit.col_user') }}</th>
                                    <th class="pb-2 pr-4 font-medium">{{ t('workspace.audit.col_action') }}</th>
                                    <th class="pb-2 font-medium">{{ t('workspace.audit.col_description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="border-b last:border-0 hover:bg-muted/30 transition-colors duration-100"
                                >
                                    <td class="py-2 pr-4 font-mono text-xs text-muted-foreground whitespace-nowrap">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        <div class="font-medium">{{ log.user?.name ?? t('workspace.audit.system') }}</div>
                                        <div v-if="log.user" class="text-xs text-muted-foreground">{{ log.user.email }}</div>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="actionBadgeClass(log.action)"
                                        >
                                            {{ log.action }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-sm">{{ log.description }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="mt-4">
                        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent mb-4" />
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm">
                            <span class="text-muted-foreground">
                                {{ t('workspace.audit.page_info', { current: logs.current_page, total: logs.last_page }) }}
                            </span>
                            <div class="flex flex-wrap gap-1">
                                <Button
                                    v-for="link in logs.links"
                                    :key="link.label"
                                    size="sm"
                                    :variant="link.active ? 'default' : 'ghost'"
                                    :disabled="!link.url"
                                    class="min-w-8 transition-colors duration-150"
                                    @click="goToPage(link.url)"
                                >
                                    {{ decodeHtml(link.label) }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </div>
        </div>
    </div>
</template>
