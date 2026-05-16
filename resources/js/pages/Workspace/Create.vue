<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const form = useForm({
    name: '',
})

function submit(): void {
    form.post(route('workspaces.store'))
}

function goBack(): void {
    router.visit(route('workspaces.index'))
}
</script>

<template>
    <Head :title="t('workspace.new_workspace')" />

    <div class="max-w-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ t('workspace.new_workspace') }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('workspace.create_description') }}</p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.details') }}</CardTitle>
                <CardDescription>{{ t('workspace.details_description') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="name">{{ t('workspace.name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :placeholder="t('workspace.name_placeholder')"
                            autofocus
                            maxlength="60"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? t('workspace.creating') : t('workspace.create') }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            @click="goBack"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
