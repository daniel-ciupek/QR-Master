<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

const siteKey = import.meta.env.VITE_CLOUDFLARE_TURNSTILE_SITE_KEY as string

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    turnstile_token: '',
})

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Rejestracja" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">QR-Master</h1>
                <p class="text-sm text-muted-foreground">Utwórz nowe konto</p>
            </div>

            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="name"
                    >Imię i nazwisko</label>
                    <Input
                        id="name"
                        v-model="form.name"
                        autocomplete="name"
                        autofocus
                        :class="{ 'border-destructive': form.errors.name }"
                        type="text"
                    />
                    <p
                        v-if="form.errors.name"
                        class="text-xs text-destructive"
                    >{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="email"
                    >Email</label>
                    <Input
                        id="email"
                        v-model="form.email"
                        autocomplete="email"
                        :class="{ 'border-destructive': form.errors.email }"
                        type="email"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-xs text-destructive"
                    >{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="password"
                    >Hasło</label>
                    <Input
                        id="password"
                        v-model="form.password"
                        autocomplete="new-password"
                        :class="{ 'border-destructive': form.errors.password }"
                        type="password"
                    />
                    <p
                        v-if="form.errors.password"
                        class="text-xs text-destructive"
                    >{{ form.errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="password_confirmation"
                    >Powtórz hasło</label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        type="password"
                    />
                </div>

                <TurnstileWidget
                    :site-key="siteKey"
                    @token="form.turnstile_token = $event"
                    @expire="form.turnstile_token = ''"
                />
                <p
                    v-if="form.errors.turnstile_token"
                    class="text-xs text-destructive"
                >{{ form.errors.turnstile_token }}</p>

                <Button
                    class="w-full"
                    :disabled="form.processing || !form.turnstile_token"
                    type="submit"
                >
                    {{ form.processing ? 'Rejestrowanie…' : 'Zarejestruj się' }}
                </Button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                Masz już konto?
                <a
                    class="font-medium underline-offset-4 hover:underline"
                    href="/login"
                >Zaloguj się</a>
            </p>
        </div>
    </div>
</template>
