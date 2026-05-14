<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

const props = defineProps<{
    pdfUrl: string | null
    title: string
}>()
</script>

<template>
    <Head :title="props.title" />

    <div class="flex min-h-screen flex-col bg-background">
        <header class="border-b px-4 py-3">
            <h1 class="text-base font-semibold text-foreground">{{ props.title }}</h1>
        </header>

        <main class="flex flex-1 flex-col">
            <div v-if="props.pdfUrl" class="flex flex-1 flex-col">
                <iframe
                    :src="props.pdfUrl"
                    class="flex-1 border-0"
                    style="height: calc(100vh - 57px)"
                    :title="props.title"
                    allowfullscreen
                />
            </div>

            <div v-else class="flex flex-1 items-center justify-center p-8">
                <p class="text-sm text-muted-foreground">Brak dostępnego pliku PDF.</p>
            </div>
        </main>
    </div>
</template>
