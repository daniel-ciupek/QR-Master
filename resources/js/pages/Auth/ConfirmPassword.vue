<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

const form = useForm({ password: '' })

function submit() {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head :title="t('auth.confirmPassword.headTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">{{ t('auth.confirmPassword.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('auth.confirmPassword.description') }}</p>
            </div>

            <form
                class="space-y-4"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="password"
                    >{{ t('auth.passwordLabel') }}</label>
                    <Input
                        id="password"
                        v-model="form.password"
                        autocomplete="current-password"
                        autofocus
                        :class="{ 'border-destructive': form.errors.password }"
                        type="password"
                    />
                    <p
                        v-if="form.errors.password"
                        class="text-xs text-destructive"
                    >{{ form.errors.password }}</p>
                </div>

                <Button
                    class="w-full"
                    :disabled="form.processing"
                    type="submit"
                >
                    {{ t('auth.confirmPassword.submit') }}
                </Button>
            </form>
        </div>
    </div>
</template>
