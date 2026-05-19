<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle2, Globe, Info } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
                    {{ t('workspace.data_residency.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Info banner -->
            <div class="flex items-start gap-3 rounded-xl border border-cyan-400/20 bg-cyan-400/5 p-4">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                    <Info class="size-4 text-cyan-400" />
                </div>
                <p class="text-sm text-cyan-400/90 pt-0.5">
                    {{ t('workspace.data_residency.info') }}
                </p>
            </div>

            <!-- Current status -->
            <div
                v-if="confirmedAt"
                class="flex items-center gap-3 rounded-xl border border-cyan-400/20 bg-cyan-400/5 px-4 py-3"
            >
                <CheckCircle2 class="size-4 shrink-0 text-cyan-400" />
                <span class="text-sm text-cyan-400/90">
                    {{ t('workspace.data_residency.confirmed_on', { region: currentRegion.toUpperCase(), date: confirmedAt.slice(0, 10) }) }}
                </span>
            </div>

            <!-- Region picker card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <Globe class="size-4 text-cyan-400" />
                        </div>
                        {{ t('workspace.data_residency.choose_region') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.data_residency.choose_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-3 grid-cols-1 sm:grid-cols-2">
                        <label
                            v-for="(info, key) in regions"
                            :key="key"
                            class="flex cursor-pointer flex-col gap-2 rounded-xl border p-4 transition-all duration-150"
                            :class="form.region === key
                                ? 'border-primary/50 bg-primary/5 shadow-[0_0_16px_oklch(0.66_0.25_285/0.08)]'
                                : 'border-border hover:border-primary/30 hover:bg-muted/20'"
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
                                    class="rounded-full bg-cyan-400/10 px-2 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/20"
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
                                    class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                                >{{ cert }}</span>
                            </div>
                        </label>
                    </div>

                    <!-- Divider -->
                    <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs text-muted-foreground">
                            {{ t('workspace.data_residency.declaration_note') }}
                        </div>
                        <Button
                            :disabled="form.processing"
                            class="shrink-0 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                            @click="save"
                        >
                            {{ form.processing ? t('common.saving') : t('workspace.data_residency.confirm') }}
                        </Button>
                    </div>
                </CardContent>
            </div>

            <!-- Legal note -->
            <div class="rounded-xl border border-border bg-muted/20 p-4 text-xs text-muted-foreground">
                {{ t('workspace.data_residency.legal_note') }}
            </div>
        </div>
    </div>
</template>
