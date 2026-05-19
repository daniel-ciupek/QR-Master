<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowLeft, CheckCircle2, Clock, Database, FileText, Globe, Save, Server, Shield, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
        dataRegion: string
        dataResidencyConfirmedAt: string | null
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
    {
        key: 'region',
        label: t('workspace.compliance.status_region'),
        ok: props.status.dataResidencyConfirmedAt !== null,
        detail: props.status.dataResidencyConfirmedAt
            ? t('workspace.compliance.status_region_set', { region: props.status.dataRegion.toUpperCase(), date: props.status.dataResidencyConfirmedAt.slice(0, 10) })
            : t('workspace.compliance.status_region_missing'),
        action: () => router.visit(route('workspaces.data-residency.show', { team: props.team.slug })),
        actionLabel: t('workspace.compliance.set_region'),
    },
])
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.compliance.title')}`" />

    <div class="space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
                variant="ghost"
                size="icon"
                class="shrink-0 self-start hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.compliance.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-4xl space-y-6">
            <!-- Stats row -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- Total scans -->
                <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden transition-all duration-200 hover:border-primary/30">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10">
                            <Database class="size-3.5 text-primary" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold">{{ stats.totalScans.toLocaleString() }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ t('workspace.compliance.total_scans') }}</p>
                </div>

                <!-- Anonymized % -->
                <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden transition-all duration-200 hover:border-cyan-400/30">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex size-7 items-center justify-center rounded-full bg-cyan-400/10">
                            <Shield class="size-3.5 text-cyan-400" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-cyan-400">{{ anonymizationPercent }}%</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ t('workspace.compliance.anonymized') }}</p>
                </div>

                <!-- Due soon -->
                <div
                    class="relative rounded-xl border bg-card p-4 overflow-hidden transition-all duration-200"
                    :class="stats.dueSoon > 0 ? 'border-gold-500/40 hover:border-gold-500/60' : 'border-border hover:border-primary/30'"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent"
                        :class="stats.dueSoon > 0 ? 'via-gold-500/60' : 'via-primary/40'"
                    />
                    <div class="flex items-center gap-2 mb-1">
                        <div
                            class="flex size-7 items-center justify-center rounded-full"
                            :class="stats.dueSoon > 0 ? 'bg-gold-500/10' : 'bg-muted'"
                        >
                            <Clock class="size-3.5" :class="stats.dueSoon > 0 ? 'text-gold-500' : 'text-muted-foreground'" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold" :class="stats.dueSoon > 0 ? 'text-gold-500' : ''">
                        {{ stats.dueSoon.toLocaleString() }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ t('workspace.compliance.due_soon') }}</p>
                </div>

                <!-- Members -->
                <div class="relative rounded-xl border border-border bg-card p-4 overflow-hidden transition-all duration-200 hover:border-primary/30">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                    <div class="flex items-center gap-2 mb-1">
                        <div class="flex size-7 items-center justify-center rounded-full bg-muted">
                            <Users class="size-3.5 text-muted-foreground" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold">{{ stats.members }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ t('workspace.compliance.members') }}</p>
                </div>
            </div>

            <!-- GDPR Status checklist -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <Shield class="size-4 text-cyan-400" />
                        </div>
                        {{ t('workspace.compliance.gdpr_status') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.compliance.gdpr_status_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="item in statusItems"
                        :key="item.key"
                        class="flex items-start justify-between gap-4 rounded-lg border p-3 transition-colors duration-150"
                        :class="item.ok
                            ? 'border-cyan-400/20 bg-cyan-400/5'
                            : 'border-gold-500/20 bg-gold-500/5'"
                    >
                        <div class="flex items-start gap-3">
                            <CheckCircle2
                                v-if="item.ok"
                                class="mt-0.5 size-4 shrink-0 text-cyan-400"
                            />
                            <AlertTriangle
                                v-else
                                class="mt-0.5 size-4 shrink-0 text-gold-500"
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
                            class="shrink-0 hover:border-primary/40 transition-colors duration-150"
                            @click="item.action()"
                        >
                            {{ item.actionLabel }}
                        </Button>
                    </div>

                    <!-- Audit form -->
                    <div class="mt-2">
                        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent mb-3" />
                        <div v-if="!showAuditForm" class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium">{{ t('workspace.compliance.mark_audit') }}</p>
                                <p v-if="status.auditNotes" class="text-xs text-muted-foreground">
                                    {{ status.auditNotes }}
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                class="hover:border-primary/40 transition-colors duration-150"
                                @click="showAuditForm = true"
                            >
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
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary/50 transition-shadow duration-150"
                            />
                            <div class="flex gap-2">
                                <Button size="sm" @click="saveAudit">{{ t('workspace.compliance.save_audit') }}</Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="hover:text-primary transition-colors duration-150"
                                    @click="showAuditForm = false"
                                >{{ t('common.cancel') }}</Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </div>

            <!-- Data categories table -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <FileText class="size-4 text-primary" />
                        </div>
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
                                    class="border-b last:border-0 hover:bg-muted/30 transition-colors duration-100"
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
            </div>

            <!-- Sub-processors -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-muted ring-1 ring-border">
                            <Server class="size-4 text-muted-foreground" />
                        </div>
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
                                    class="border-b last:border-0 hover:bg-muted/30 transition-colors duration-100"
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
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="p.transfer === 'No transfer'
                                                ? 'bg-cyan-400/10 text-cyan-400'
                                                : 'bg-primary/10 text-primary'"
                                        >
                                            {{ p.transfer }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </div>
        </div>
    </div>
</template>
