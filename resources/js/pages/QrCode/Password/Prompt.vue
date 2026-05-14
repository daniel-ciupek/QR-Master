<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { t } = useI18n()

const props = defineProps<{
    hash: string
    title: string
}>()

const form = useForm({ password: '' })

function submit() {
    form.post(`/q/${props.hash}/unlock`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('qrPassword.pageTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-1 text-center">
                <p class="text-2xl font-bold tracking-tight">🔒</p>
                <h1 class="text-lg font-semibold">{{ t('qrPassword.title') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('qrPassword.subtitle', { name: props.title }) }}
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <label class="text-sm font-medium" for="password">
                        {{ t('qrPassword.passwordLabel') }}
                    </label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        :placeholder="t('qrPassword.passwordPlaceholder')"
                        :class="{ 'border-destructive': form.errors.password }"
                        autocomplete="current-password"
                        autofocus
                    />
                    <p v-if="form.errors.password" class="text-xs text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing || !form.password"
                >
                    {{ form.processing ? t('qrPassword.verifying') : t('qrPassword.submit') }}
                </Button>
            </form>
        </div>
    </div>
</template>
