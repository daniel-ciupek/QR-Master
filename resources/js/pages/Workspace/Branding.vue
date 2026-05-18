<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Paintbrush, Trash2, Upload } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    branding: {
        brand_name: string | null
        primary_color: string | null
        powered_by_hidden: boolean
        logo_url: string | null
    }
}>()

const form = useForm({
    brand_name: props.branding.brand_name ?? '',
    primary_color: props.branding.primary_color ?? '#6366f1',
    powered_by_hidden: props.branding.powered_by_hidden,
    logo: null as File | null,
    remove_logo: false,
})

const logoPreview = ref<string | null>(props.branding.logo_url)
const fileInput = ref<HTMLInputElement | null>(null)

const colorPreview = computed(() =>
    form.primary_color.match(/^#[0-9a-fA-F]{6}$/) ? form.primary_color : '#6366f1'
)

function onLogoChange(e: Event): void {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    form.logo = file
    form.remove_logo = false
    const reader = new FileReader()
    reader.onload = (ev) => { logoPreview.value = ev.target?.result as string }
    reader.readAsDataURL(file)
}

function removeLogo(): void {
    form.logo = null
    form.remove_logo = true
    logoPreview.value = null
    if (fileInput.value) fileInput.value.value = ''
}

function save(): void {
    form.post(route('workspaces.branding.update', { team: props.team.slug }), {
        forceFormData: true,
        preserveScroll: true,
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.branding.title')}`" />

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
                    {{ t('workspace.branding.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Logo card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <Paintbrush class="size-3.5 text-primary" />
                        </div>
                        {{ t('workspace.branding.logo') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.branding.logo_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted/30">
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                alt="Logo preview"
                                class="size-full object-contain p-1"
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground text-center px-2"
                            >{{ t('workspace.branding.no_logo') }}</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                class="hidden"
                                @change="onLogoChange"
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                class="hover:border-primary/40 transition-colors duration-150"
                                @click="fileInput?.click()"
                            >
                                <Upload class="mr-2 size-4" />
                                {{ t('workspace.branding.upload_logo') }}
                            </Button>
                            <Button
                                v-if="logoPreview"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                                @click="removeLogo"
                            >
                                <Trash2 class="mr-2 size-4" />
                                {{ t('workspace.branding.remove_logo') }}
                            </Button>
                        </div>
                    </div>
                    <p v-if="form.errors.logo" class="text-sm text-destructive">
                        {{ form.errors.logo }}
                    </p>
                </CardContent>
            </div>

            <!-- Brand name card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.branding.brand_name') }}</CardTitle>
                    <CardDescription>{{ t('workspace.branding.brand_name_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="brand-name">{{ t('workspace.branding.brand_name') }}</Label>
                        <Input
                            id="brand-name"
                            v-model="form.brand_name"
                            :placeholder="t('workspace.branding.brand_name_placeholder')"
                            maxlength="60"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.brand_name" class="text-sm text-destructive">
                            {{ form.errors.brand_name }}
                        </p>
                    </div>
                </CardContent>
            </div>

            <!-- Colors card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.branding.colors') }}</CardTitle>
                    <CardDescription>{{ t('workspace.branding.colors_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="primary-color">{{ t('workspace.branding.primary_color') }}</Label>
                        <div class="flex items-center gap-3">
                            <input
                                id="primary-color-picker"
                                v-model="form.primary_color"
                                type="color"
                                class="size-10 cursor-pointer rounded-lg border border-input bg-transparent"
                                :value="colorPreview"
                            >
                            <Input
                                id="primary-color"
                                v-model="form.primary_color"
                                placeholder="#6366f1"
                                maxlength="7"
                                class="font-mono uppercase focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <div
                                class="size-10 shrink-0 rounded-lg border border-border transition-all duration-200"
                                :style="{ backgroundColor: colorPreview }"
                            />
                        </div>
                        <p v-if="form.errors.primary_color" class="text-sm text-destructive">
                            {{ form.errors.primary_color }}
                        </p>
                    </div>
                </CardContent>
            </div>

            <!-- Powered by card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.branding.powered_by') }}</CardTitle>
                    <CardDescription>{{ t('workspace.branding.powered_by_desc') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-border p-3 transition-colors duration-150 hover:bg-muted/30">
                        <input
                            v-model="form.powered_by_hidden"
                            type="checkbox"
                            class="size-4 rounded border-input accent-primary"
                        >
                        <span class="text-sm">{{ t('workspace.branding.hide_powered_by') }}</span>
                    </label>
                </CardContent>
            </div>

            <!-- Save button -->
            <div class="flex justify-end">
                <Button
                    :disabled="form.processing"
                    class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    @click="save"
                >
                    {{ form.processing ? t('common.saving') : t('common.save') }}
                </Button>
            </div>
        </div>
    </div>
</template>
