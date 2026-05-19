<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Building2 } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="p-4 md:p-6">
        <div class="max-w-lg">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    class="shrink-0 hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                    @click="goBack"
                >
                    <ArrowLeft class="size-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('workspace.new_workspace') }}
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ t('workspace.create_description') }}</p>
                </div>
            </div>

            <!-- Icon hero -->
            <div class="mb-6 flex justify-center">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                    <Building2 class="size-8 text-primary" />
                </div>
            </div>

            <!-- Card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)]">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
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
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                            >
                                {{ form.processing ? t('workspace.creating') : t('workspace.create') }}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                class="hover:text-primary transition-colors duration-150"
                                @click="goBack"
                            >
                                {{ t('common.cancel') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </div>
        </div>
    </div>
</template>
