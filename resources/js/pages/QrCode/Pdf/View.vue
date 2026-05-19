<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { QrCode, FileText, AlertCircle } from 'lucide-vue-next'

const props = defineProps<{
    pdfUrl: string | null
    title: string
}>()
</script>

<template>
    <Head :title="props.title" />

    <div class="flex min-h-screen flex-col bg-background">
        <!-- Header bar -->
        <header class="relative flex items-center gap-3 border-b border-border bg-card px-4 py-3 shrink-0">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />

            <!-- Logo -->
            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 ring-1 ring-primary/20 shrink-0">
                <QrCode class="size-4 text-primary" />
            </div>

            <!-- Title -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <FileText class="size-4 text-muted-foreground shrink-0" />
                    <h1 class="truncate text-sm font-semibold text-foreground">{{ props.title }}</h1>
                </div>
            </div>

            <!-- Branding -->
            <span class="hidden sm:block text-xs text-muted-foreground shrink-0">QR Master</span>
        </header>

        <!-- Content -->
        <main class="flex flex-1 flex-col">
            <!-- PDF iframe -->
            <div v-if="props.pdfUrl" class="flex flex-1 flex-col">
                <iframe
                    :src="props.pdfUrl"
                    class="flex-1 border-0 w-full"
                    style="height: calc(100vh - 57px)"
                    :title="props.title"
                    allowfullscreen
                />
            </div>

            <!-- Error / empty state -->
            <div v-else class="relative flex flex-1 flex-col items-center justify-center p-8">
                <!-- Dot grid background -->
                <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.03)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />

                <div class="relative flex flex-col items-center text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-destructive/10 ring-1 ring-destructive/20 mb-4">
                        <AlertCircle class="size-8 text-destructive" />
                    </div>
                    <h3 class="text-base font-semibold mb-1">Plik niedostępny</h3>
                    <p class="text-sm text-muted-foreground max-w-xs">
                        Brak dostępnego pliku PDF. Skontaktuj się z właścicielem kodu QR.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
