<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

defineProps<{
    canResetPassword?: boolean
    status?: string
}>()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head :title="t('auth.login.headTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">{{ t('app.name') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('auth.login.subtitle') }}</p>
            </div>

            <div
                v-if="status"
                class="rounded-md bg-success-50 p-3 text-sm text-success-700 dark:bg-success-700/20 dark:text-success-500"
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
                    >{{ t('auth.emailLabel') }}</label>
                    <Input
                        id="email"
                        v-model="form.email"
                        autocomplete="email"
                        autofocus
                        :class="{ 'border-destructive': form.errors.email }"
                        :placeholder="t('auth.emailPlaceholder')"
                        type="email"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label
                            class="text-sm font-medium"
                            for="password"
                        >{{ t('auth.passwordLabel') }}</label>
                        <a
                            v-if="canResetPassword"
                            class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                            href="/forgot-password"
                        >{{ t('auth.login.forgotPassword') }}</a>
                    </div>
                    <Input
                        id="password"
                        v-model="form.password"
                        autocomplete="current-password"
                        :class="{ 'border-destructive': form.errors.password }"
                        type="password"
                    />
                    <p
                        v-if="form.errors.password"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <Button
                    class="w-full"
                    :disabled="form.processing"
                    type="submit"
                >
                    {{ form.processing ? t('auth.login.submitting') : t('auth.login.submit') }}
                </Button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                {{ t('auth.login.noAccount') }}
                <a
                    class="font-medium underline-offset-4 hover:underline"
                    href="/register"
                >{{ t('auth.login.register') }}</a>
            </p>
        </div>
    </div>
</template>
