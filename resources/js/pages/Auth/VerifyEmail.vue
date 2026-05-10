<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'

const { t } = useI18n()

defineProps<{ status?: string }>()

const form = useForm({})

function submit() {
    form.post('/email/verification-notification')
}
</script>

<template>
    <Head :title="t('auth.verifyEmail.headTitle')" />

    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-sm space-y-6 text-center">
            <h1 class="text-2xl font-bold">{{ t('auth.verifyEmail.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('auth.verifyEmail.description') }}</p>

            <div
                v-if="status === 'verification-link-sent'"
                class="rounded-md bg-success-50 p-3 text-sm text-success-700"
            >
                {{ t('auth.verifyEmail.linkSent') }}
            </div>

            <form @submit.prevent="submit">
                <Button
                    :disabled="form.processing"
                    type="submit"
                    variant="outline"
                >
                    {{ t('auth.verifyEmail.resend') }}
                </Button>
            </form>

            <button
                class="block w-full text-sm text-muted-foreground underline-offset-4 hover:underline"
                type="button"
                @click="router.post('/logout')"
            >
                {{ t('auth.verifyEmail.logout') }}
            </button>
        </div>
    </div>
</template>
