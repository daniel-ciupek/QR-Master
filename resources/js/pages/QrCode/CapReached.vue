<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps<{
    title: string
    limit: number
}>()
</script>

<template>
    <Head :title="t('capReached.pageTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-4 text-center">
            <p class="text-4xl">🏁</p>
            <h1 class="text-lg font-semibold">{{ t('capReached.title') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ t('capReached.subtitle', { name: title, limit }) }}
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t('capReached.hint') }}
            </p>
        </div>
    </div>
</template>
