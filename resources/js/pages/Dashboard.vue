<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-sm text-muted-foreground">Witaj w QR-Master — Etap 1 w budowie.</p>
        </div>
    </div>
</template>
