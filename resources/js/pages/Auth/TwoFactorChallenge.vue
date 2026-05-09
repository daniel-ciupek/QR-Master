<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const useRecovery = ref(false)

const form = useForm({
    code: '',
    recovery_code: '',
})

function submit() {
    form.post('/two-factor-challenge', {
        onFinish: () => form.reset('code', 'recovery_code'),
    })
}
</script>

<template>
    <Head title="Weryfikacja dwuetapowa" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">Weryfikacja 2FA</h1>
                <p class="text-sm text-muted-foreground">
                    {{ useRecovery ? 'Wprowadź kod odzyskiwania.' : 'Wprowadź kod z aplikacji uwierzytelniającej.' }}
                </p>
            </div>

            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div
                    v-if="!useRecovery"
                    class="space-y-2"
                >
                    <label
                        class="text-sm font-medium"
                        for="code"
                    >Kod jednorazowy</label>
                    <Input
                        id="code"
                        v-model="form.code"
                        autocomplete="one-time-code"
                        autofocus
                        inputmode="numeric"
                        type="text"
                    />
                    <p
                        v-if="form.errors.code"
                        class="text-xs text-destructive"
                    >{{ form.errors.code }}</p>
                </div>

                <div
                    v-else
                    class="space-y-2"
                >
                    <label
                        class="text-sm font-medium"
                        for="recovery_code"
                    >Kod odzyskiwania</label>
                    <Input
                        id="recovery_code"
                        v-model="form.recovery_code"
                        autocomplete="one-time-code"
                        autofocus
                        type="text"
                    />
                    <p
                        v-if="form.errors.recovery_code"
                        class="text-xs text-destructive"
                    >{{ form.errors.recovery_code }}</p>
                </div>

                <Button
                    class="w-full"
                    :disabled="form.processing"
                    type="submit"
                >
                    Zaloguj się
                </Button>
            </form>

            <p class="text-center text-sm">
                <button
                    class="text-muted-foreground underline-offset-4 hover:underline"
                    type="button"
                    @click="useRecovery = !useRecovery"
                >
                    {{ useRecovery ? 'Użyj kodu z aplikacji' : 'Użyj kodu odzyskiwania' }}
                </button>
            </p>
        </div>
    </div>
</template>
