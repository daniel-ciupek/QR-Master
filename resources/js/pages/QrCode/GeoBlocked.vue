<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps<{
    title: string
    allowedCountries: string[]
}>()
</script>

<template>
    <Head :title="t('geoBlocked.pageTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-4 text-center">
            <p class="text-4xl">🌍</p>
            <h1 class="text-lg font-semibold">{{ t('geoBlocked.title') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ t('geoBlocked.subtitle', { name: title }) }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('geoBlocked.hint') }}
            </p>
        </div>
    </div>
</template>
