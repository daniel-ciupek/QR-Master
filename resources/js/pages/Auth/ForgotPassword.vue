<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

defineProps<{ status?: string }>()

const form = useForm({ email: '' })

function submit() {
    form.post('/forgot-password')
}
</script>

<template>
    <Head title="Reset hasła" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">Nie pamiętasz hasła?</h1>
                <p class="text-sm text-muted-foreground">
                    Podaj email — wyślemy link do resetu hasła.
                </p>
            </div>

            <div
                v-if="status"
                class="rounded-md bg-success-50 p-3 text-sm text-success-700"
            >
                {{ status }}
            </div>

            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="email"
                    >Email</label>
                    <Input
                        id="email"
                        v-model="form.email"
                        autofocus
                        :class="{ 'border-destructive': form.errors.email }"
                        type="email"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-xs text-destructive"
                    >{{ form.errors.email }}</p>
                </div>

                <Button
                    class="w-full"
                    :disabled="form.processing"
                    type="submit"
                >
                    Wyślij link resetujący
                </Button>
            </form>

            <p class="text-center text-sm">
                <a
                    class="text-muted-foreground underline-offset-4 hover:underline"
                    href="/login"
                >Wróć do logowania</a>
            </p>
        </div>
    </div>
</template>
