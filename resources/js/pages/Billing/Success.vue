<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { CheckCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const { t } = useI18n()

defineProps<{
    plan: string
    planName: string
}>()
</script>

<template>
    <Head :title="t('billing.success.pageTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md space-y-6 text-center">
            <CheckCircle class="mx-auto h-16 w-16 text-green-500" />

            <div class="space-y-2">
                <h1 class="text-2xl font-bold">{{ t('billing.success.title') }}</h1>
                <p class="text-muted-foreground">
                    {{ t('billing.success.subtitle', { plan: planName }) }}
                </p>
            </div>

            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                <p class="text-sm text-green-800 dark:text-green-300">
                    {{ t('billing.success.hint') }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Button @click="router.visit('/dashboard')">
                    {{ t('billing.success.toDashboard') }}
                </Button>
                <Button variant="outline" @click="router.visit('/pricing')">
                    {{ t('billing.success.viewPricing') }}
                </Button>
            </div>
        </div>
    </div>
</template>
