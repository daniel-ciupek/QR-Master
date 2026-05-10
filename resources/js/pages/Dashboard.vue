<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-bold">{{ t('dashboard.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('dashboard.subtitle') }}</p>
        </div>
    </div>
</template>
