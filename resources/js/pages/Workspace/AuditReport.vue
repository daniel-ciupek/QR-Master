<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft, Download, Filter } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
    if (action.startsWith('member.')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
    if (action.startsWith('branding.')) return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
    if (action.startsWith('dpa.')) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    if (action.startsWith('team.')) return 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400'
    return 'bg-muted text-muted-foreground'
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString()
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.audit.title')}`" />

    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="size-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ t('workspace.audit.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ team.name }} · {{ logs.total.toLocaleString() }} {{ t('workspace.audit.events') }}
                    </p>
                </div>
            </div>
            <Button variant="outline" @click="exportCsv">
                <Download class="mr-2 size-4" />
                {{ t('workspace.audit.export_csv') }}
            </Button>
        </div>

        <!-- Filters -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Filter class="size-4" />
                    {{ t('workspace.audit.filters') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="space-y-1">
                        <Label>{{ t('workspace.audit.filter_action') }}</Label>
                        <select
                            v-model="localFilters.action"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">{{ t('workspace.audit.all_actions') }}</option>
                            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label>{{ t('workspace.audit.filter_user') }}</Label>
                        <select
                            v-model="localFilters.user_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">{{ t('workspace.audit.all_users') }}</option>
                            <option v-for="m in members" :key="m.id" :value="String(m.id)">{{ m.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label>{{ t('workspace.audit.filter_from') }}</Label>
                        <Input v-model="localFilters.from" type="date" />
                    </div>
                    <div class="space-y-1">
                        <Label>{{ t('workspace.audit.filter_to') }}</Label>
                        <Input v-model="localFilters.to" type="date" />
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button size="sm" @click="applyFilters">
                        {{ t('workspace.audit.apply') }}
                    </Button>
                    <Button size="sm" variant="ghost" @click="clearFilters">
                        {{ t('workspace.audit.clear') }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Log table -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.audit.log_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.audit.log_desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="logs.data.length === 0" class="py-12 text-center text-muted-foreground">
                    {{ t('workspace.audit.no_events') }}
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
                                class="border-b last:border-0 hover:bg-muted/30"
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
                                        class="rounded px-2 py-0.5 text-xs font-medium"
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
                <div v-if="logs.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        {{ t('workspace.audit.page_info', { current: logs.current_page, total: logs.last_page }) }}
                    </span>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in logs.links"
                            :key="link.label"
                            size="sm"
                            :variant="link.active ? 'default' : 'ghost'"
                            :disabled="!link.url"
                            class="min-w-8"
                            @click="goToPage(link.url)"
                        >
                            <!-- eslint-disable-next-line vue/no-v-html -->
                            <span v-html="link.label" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
