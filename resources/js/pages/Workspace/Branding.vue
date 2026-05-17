<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Trash2, Upload } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('workspace.branding.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Logo -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.branding.logo') }}</CardTitle>
                <CardDescription>{{ t('workspace.branding.logo_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="flex size-20 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            alt="Logo preview"
                            class="size-full object-contain p-1"
                        >
                        <span
                            v-else
                            class="text-xs text-muted-foreground"
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
                            @click="fileInput?.click()"
                        >
                            <Upload class="mr-2 size-4" />
                            {{ t('workspace.branding.upload_logo') }}
                        </Button>
                        <Button
                            v-if="logoPreview"
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive"
                            @click="removeLogo"
                        >
                            <Trash2 class="mr-2 size-4" />
                            {{ t('workspace.branding.remove_logo') }}
                        </Button>
                    </div>
                </div>
                <p
                    v-if="form.errors.logo"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.logo }}
                </p>
            </CardContent>
        </Card>

        <!-- Brand name -->
        <Card>
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
                    />
                    <p
                        v-if="form.errors.brand_name"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.brand_name }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Colors -->
        <Card>
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
                            class="size-10 cursor-pointer rounded border border-input"
                            :value="colorPreview"
                        >
                        <Input
                            id="primary-color"
                            v-model="form.primary_color"
                            placeholder="#6366f1"
                            maxlength="7"
                            class="font-mono uppercase"
                        />
                        <div
                            class="size-10 shrink-0 rounded-md border"
                            :style="{ backgroundColor: colorPreview }"
                        />
                    </div>
                    <p
                        v-if="form.errors.primary_color"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.primary_color }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Powered by -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.branding.powered_by') }}</CardTitle>
                <CardDescription>{{ t('workspace.branding.powered_by_desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <label class="flex cursor-pointer items-center gap-3">
                    <input
                        v-model="form.powered_by_hidden"
                        type="checkbox"
                        class="size-4 rounded border-input accent-primary"
                    >
                    <span class="text-sm">{{ t('workspace.branding.hide_powered_by') }}</span>
                </label>
            </CardContent>
        </Card>

        <!-- Save -->
        <div class="flex justify-end">
            <Button
                :disabled="form.processing"
                @click="save"
            >
                {{ form.processing ? t('common.saving') : t('common.save') }}
            </Button>
        </div>
    </div>
</template>
