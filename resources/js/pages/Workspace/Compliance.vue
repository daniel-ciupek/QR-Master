<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowLeft, CheckCircle2, Clock, Database, FileText, Globe, Save, Server, Shield, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface Processor {
    name: string
    country: string
    purpose: string
    transfer: string
}

interface DataCategory {
    category: string
    data: string
    purpose: string
    retention: string
    legal_basis: string
}

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    stats: {
        qrCodes: number
        members: number
        totalScans: number
        geoAnonymized: number
        geoRetained: number
        dueSoon: number
    }
    status: {
        dpaGenerated: boolean
        dpaDate: string | null
        retentionActive: boolean
        retentionDays: number
        exportAvailable: boolean
        lastAuditAt: string | null
        auditNotes: string | null
    }
    processors: Processor[]
    dataCategories: DataCategory[]
}>()

const showAuditForm = ref(false)
const auditForm = useForm({ notes: props.status.auditNotes ?? '' })

function saveAudit(): void {
    auditForm.post(route('workspaces.compliance.audit', { team: props.team.slug }), {
        preserveScroll: true,
        onSuccess: () => { showAuditForm.value = false },
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}

function goToDpa(): void {
    router.visit(route('workspaces.dpa.show', { team: props.team.slug }))
}

const anonymizationPercent = computed(() => {
    if (props.stats.totalScans === 0) return 100
    return Math.round((props.stats.geoAnonymized / props.stats.totalScans) * 100)
})

const statusItems = computed(() => [
    {
        key: 'dpa',
        label: t('workspace.compliance.status_dpa'),
        ok: props.status.dpaGenerated,
        detail: props.status.dpaDate ? t('workspace.compliance.status_dpa_date', { date: props.status.dpaDate }) : t('workspace.compliance.status_dpa_missing'),
        action: !props.status.dpaGenerated ? goToDpa : undefined,
        actionLabel: t('workspace.compliance.generate_dpa'),
    },
    {
        key: 'retention',
        label: t('workspace.compliance.status_retention'),
        ok: props.status.retentionActive,
        detail: t('workspace.compliance.status_retention_detail', { days: props.status.retentionDays }),
    },
    {
        key: 'export',
        label: t('workspace.compliance.status_export'),
        ok: props.status.exportAvailable,
        detail: t('workspace.compliance.status_export_detail'),
    },
    {
        key: 'audit',
        label: t('workspace.compliance.status_audit'),
        ok: props.status.lastAuditAt !== null,
        detail: props.status.lastAuditAt
            ? t('workspace.compliance.status_audit_date', { date: props.status.lastAuditAt })
            : t('workspace.compliance.status_audit_missing'),
    },
])
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.compliance.title')}`" />

    <div class="mx-auto max-w-4xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" @click="goBack">
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('workspace.compliance.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2">
                        <Database class="size-4 text-muted-foreground" />
                        <span class="text-2xl font-bold">{{ stats.totalScans.toLocaleString() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ t('workspace.compliance.total_scans') }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2">
                        <Shield class="size-4 text-green-500" />
                        <span class="text-2xl font-bold text-green-600">{{ anonymizationPercent }}%</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ t('workspace.compliance.anonymized') }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2">
                        <Clock :class="stats.dueSoon > 0 ? 'text-amber-500' : 'text-muted-foreground'" class="size-4" />
                        <span :class="stats.dueSoon > 0 ? 'text-amber-600' : ''" class="text-2xl font-bold">{{ stats.dueSoon.toLocaleString() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ t('workspace.compliance.due_soon') }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-2">
                        <Users class="size-4 text-muted-foreground" />
                        <span class="text-2xl font-bold">{{ stats.members }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ t('workspace.compliance.members') }}</p>
                </CardContent>
            </Card>
        </div>

        <!-- GDPR Status checklist -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Shield class="size-5" />
                    {{ t('workspace.compliance.gdpr_status') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.compliance.gdpr_status_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-for="item in statusItems"
                    :key="item.key"
                    class="flex items-start justify-between gap-4 rounded-lg border p-3"
                    :class="item.ok ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30'"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2
                            v-if="item.ok"
                            class="mt-0.5 size-4 shrink-0 text-green-600 dark:text-green-400"
                        />
                        <AlertTriangle
                            v-else
                            class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div>
                            <p class="text-sm font-medium">{{ item.label }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.detail }}</p>
                        </div>
                    </div>
                    <Button
                        v-if="item.action"
                        size="sm"
                        variant="outline"
                        class="shrink-0"
                        @click="item.action()"
                    >
                        {{ item.actionLabel }}
                    </Button>
                </div>

                <!-- Audit form -->
                <div class="mt-2 border-t pt-3">
                    <div v-if="!showAuditForm" class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium">{{ t('workspace.compliance.mark_audit') }}</p>
                            <p
                                v-if="status.auditNotes"
                                class="text-xs text-muted-foreground"
                            >
                                {{ status.auditNotes }}
                            </p>
                        </div>
                        <Button size="sm" variant="outline" @click="showAuditForm = true">
                            <Save class="mr-2 size-3.5" />
                            {{ t('workspace.compliance.record_audit') }}
                        </Button>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-sm font-medium">{{ t('workspace.compliance.audit_notes_label') }}</p>
                        <textarea
                            v-model="auditForm.notes"
                            rows="3"
                            :placeholder="t('workspace.compliance.audit_notes_placeholder')"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <div class="flex gap-2">
                            <Button size="sm" @click="saveAudit">{{ t('workspace.compliance.save_audit') }}</Button>
                            <Button size="sm" variant="ghost" @click="showAuditForm = false">{{ t('common.cancel') }}</Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Data categories table -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-5" />
                    {{ t('workspace.compliance.data_categories') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.compliance.data_categories_desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_category') }}</th>
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_data') }}</th>
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_retention') }}</th>
                                <th class="pb-2 font-medium">{{ t('workspace.compliance.col_basis') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="cat in dataCategories"
                                :key="cat.category"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4 font-medium">{{ cat.category }}</td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground">{{ cat.data }}</td>
                                <td class="py-2 pr-4 text-xs">{{ cat.retention }}</td>
                                <td class="py-2 text-xs text-muted-foreground">{{ cat.legal_basis }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Sub-processors -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Server class="size-5" />
                    {{ t('workspace.compliance.sub_processors') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.compliance.sub_processors_desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs text-muted-foreground">
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_processor') }}</th>
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_country') }}</th>
                                <th class="pb-2 pr-4 font-medium">{{ t('workspace.compliance.col_purpose') }}</th>
                                <th class="pb-2 font-medium">{{ t('workspace.compliance.col_transfer') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="p in processors"
                                :key="p.name"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4 font-medium">{{ p.name }}</td>
                                <td class="py-2 pr-4">
                                    <span class="flex items-center gap-1">
                                        <Globe class="size-3 text-muted-foreground" />
                                        {{ p.country }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-xs text-muted-foreground">{{ p.purpose }}</td>
                                <td class="py-2">
                                    <span
                                        class="rounded px-1.5 py-0.5 text-xs font-medium"
                                        :class="p.transfer === 'No transfer' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'"
                                    >
                                        {{ p.transfer }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
