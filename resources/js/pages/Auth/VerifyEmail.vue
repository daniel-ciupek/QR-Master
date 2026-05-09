<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

defineProps<{ status?: string }>()

const form = useForm({})

function submit() {
    form.post('/email/verification-notification')
}
</script>

<template>
    <Head title="Weryfikacja email" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6 text-center">
            <h1 class="text-2xl font-bold">Potwierdź email</h1>
            <p class="text-sm text-muted-foreground">
                Wysłaliśmy link weryfikacyjny na Twój adres email.
                Sprawdź skrzynkę i kliknij w link.
            </p>

            <div
                v-if="status === 'verification-link-sent'"
                class="rounded-md bg-success-50 p-3 text-sm text-success-700"
            >
                Nowy link weryfikacyjny został wysłany.
            </div>

            <form @submit.prevent="submit">
                <Button
                    :disabled="form.processing"
                    type="submit"
                    variant="outline"
                >
                    Wyślij ponownie
                </Button>
            </form>

            <button
                class="block w-full text-sm text-muted-foreground underline-offset-4 hover:underline"
                type="button"
                @click="router.post('/logout')"
            >
                Wyloguj się
            </button>
        </div>
    </div>
</template>
