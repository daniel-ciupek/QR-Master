<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()
const props = defineProps<{ token: string; email: string }>()

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head :title="t('auth.resetPassword.headTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">{{ t('auth.resetPassword.title') }}</h1>
            </div>

            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="email"
                    >{{ t('auth.emailLabel') }}</label>
                    <Input
                        id="email"
                        v-model="form.email"
                        autocomplete="email"
                        :class="{ 'border-destructive': form.errors.email }"
                        readonly
                        type="email"
                    />
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="password"
                    >{{ t('auth.newPasswordLabel') }}</label>
                    <Input
                        id="password"
                        v-model="form.password"
                        autocomplete="new-password"
                        autofocus
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
                    >{{ t('auth.passwordConfirmLabel') }}</label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        type="password"
                    />
                </div>

                <Button
                    class="w-full"
                    :disabled="form.processing"
                    type="submit"
                >
                    {{ t('auth.resetPassword.submit') }}
                </Button>
            </form>
        </div>
    </div>
</template>
