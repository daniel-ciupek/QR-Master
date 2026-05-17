<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle2, Globe } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface RegionInfo {
    name: string
    flag: string
    description: string
    location: string
    provider: string
    gdpr: boolean
    certifications: string[]
}

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    currentRegion: string
    confirmedAt: string | null
    regions: Record<string, RegionInfo>
}>()

const form = useForm({ region: props.currentRegion })

function save(): void {
    form.put(route('workspaces.data-residency.update', { team: props.team.slug }), {
        preserveScroll: true,
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.data_residency.title')}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" @click="goBack">
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('workspace.data_residency.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Info -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
            <Globe class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="text-sm text-blue-800 dark:text-blue-300">
                {{ t('workspace.data_residency.info') }}
            </p>
        </div>

        <!-- Current status -->
        <div
            v-if="confirmedAt"
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-950/30"
        >
            <CheckCircle2 class="size-4 shrink-0 text-green-600" />
            <span class="text-sm text-green-800 dark:text-green-300">
                {{ t('workspace.data_residency.confirmed_on', { region: currentRegion.toUpperCase(), date: confirmedAt.slice(0, 10) }) }}
            </span>
        </div>

        <!-- Region picker -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.data_residency.choose_region') }}</CardTitle>
                <CardDescription>{{ t('workspace.data_residency.choose_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label
                        v-for="(info, key) in regions"
                        :key="key"
                        class="flex cursor-pointer flex-col gap-2 rounded-lg border p-4 transition-colors"
                        :class="form.region === key
                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                            : 'border-input hover:bg-muted/30'"
                    >
                        <input
                            v-model="form.region"
                            type="radio"
                            :value="key"
                            class="sr-only"
                        >
                        <div class="flex items-center justify-between">
                            <span class="text-xl">{{ info.flag }}</span>
                            <span
                                v-if="info.gdpr"
                                class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400"
                            >GDPR</span>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">{{ info.name }}</p>
                            <p class="text-xs text-muted-foreground mt-0.5">{{ info.location }}</p>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ info.description }}</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <span
                                v-for="cert in info.certifications"
                                :key="cert"
                                class="rounded bg-muted px-1.5 py-0.5 text-xs"
                            >{{ cert }}</span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-between border-t pt-4">
                    <div class="text-xs text-muted-foreground">
                        {{ t('workspace.data_residency.declaration_note') }}
                    </div>
                    <Button :disabled="form.processing" @click="save">
                        {{ form.processing ? t('common.saving') : t('workspace.data_residency.confirm') }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Legal note -->
        <div class="rounded-lg border bg-muted/20 p-4 text-xs text-muted-foreground">
            {{ t('workspace.data_residency.legal_note') }}
        </div>
    </div>
</template>
