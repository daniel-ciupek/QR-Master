<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft, Check, Copy, RefreshCw, Server, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    hasToken: boolean
    newToken: string | null
    scimBaseUrl: string
}>()

const copied = ref(false)
const confirmRevoke = ref(false)

function copyToken(): void {
    if (props.newToken) {
        navigator.clipboard.writeText(props.newToken).then(() => {
            copied.value = true
            setTimeout(() => { copied.value = false }, 2000)
        })
    }
}

function copyUrl(url: string): void {
    navigator.clipboard.writeText(url)
}

function generate(): void {
    router.post(route('workspaces.scim.generate', { team: props.team.slug }))
}

function revoke(): void {
    router.delete(route('workspaces.scim.revoke', { team: props.team.slug }), {
        onSuccess: () => { confirmRevoke.value = false },
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}

const endpoints = [
    { label: 'Base URL', path: '' },
    { label: 'Users', path: '/Users' },
    { label: 'ServiceProviderConfig', path: '/ServiceProviderConfig' },
    { label: 'Schemas', path: '/Schemas' },
]
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.scim.title')}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" @click="goBack">
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">{{ t('workspace.scim.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Info -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
            <Server class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="text-sm text-blue-800 dark:text-blue-300">
                {{ t('workspace.scim.info') }}
            </p>
        </div>

        <!-- New token alert -->
        <div
            v-if="newToken"
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30"
        >
            <p class="mb-2 text-sm font-semibold text-amber-800 dark:text-amber-300">
                {{ t('workspace.scim.token_generated') }}
            </p>
            <div class="flex items-center gap-2">
                <code class="flex-1 overflow-x-auto rounded bg-white/50 px-3 py-2 font-mono text-xs dark:bg-black/20">{{ newToken }}</code>
                <Button size="icon" variant="ghost" @click="copyToken">
                    <Check v-if="copied" class="size-4 text-green-600" />
                    <Copy v-else class="size-4" />
                </Button>
            </div>
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">{{ t('workspace.scim.token_once_warning') }}</p>
        </div>

        <!-- Token management -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.scim.token_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.scim.token_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center gap-3">
                    <div
                        class="size-2.5 rounded-full"
                        :class="hasToken ? 'bg-green-500' : 'bg-muted'"
                    />
                    <span class="text-sm">{{ hasToken ? t('workspace.scim.token_active') : t('workspace.scim.token_none') }}</span>
                </div>

                <div class="flex gap-3">
                    <Button @click="generate">
                        <RefreshCw class="mr-2 size-4" />
                        {{ hasToken ? t('workspace.scim.token_regenerate') : t('workspace.scim.token_generate') }}
                    </Button>
                    <div v-if="hasToken">
                        <Button
                            v-if="!confirmRevoke"
                            variant="outline"
                            size="default"
                            @click="confirmRevoke = true"
                        >
                            <Trash2 class="mr-2 size-4" />
                            {{ t('workspace.scim.token_revoke') }}
                        </Button>
                        <div v-else class="flex gap-2">
                            <Button variant="destructive" size="sm" @click="revoke">{{ t('workspace.scim.confirm_revoke') }}</Button>
                            <Button variant="ghost" size="sm" @click="confirmRevoke = false">{{ t('common.cancel') }}</Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Endpoints -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.scim.endpoints_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.scim.endpoints_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
                <div
                    v-for="ep in endpoints"
                    :key="ep.path"
                    class="flex items-center justify-between gap-2 rounded-md border px-3 py-2"
                >
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">{{ ep.label }}</p>
                        <code class="text-xs">{{ scimBaseUrl }}{{ ep.path }}</code>
                    </div>
                    <Button
                        size="icon"
                        variant="ghost"
                        class="size-7 shrink-0"
                        @click="copyUrl(scimBaseUrl + ep.path)"
                    >
                        <Copy class="size-3.5" />
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Setup guide -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.scim.guide_title') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm text-muted-foreground">
                <div>
                    <p class="mb-1 font-semibold text-foreground">Okta</p>
                    <ol class="list-decimal space-y-1 pl-4">
                        <li>{{ t('workspace.scim.okta_step1') }}</li>
                        <li>{{ t('workspace.scim.okta_step2') }}</li>
                        <li>{{ t('workspace.scim.okta_step3') }}</li>
                    </ol>
                </div>
                <div>
                    <p class="mb-1 font-semibold text-foreground">Microsoft Azure AD</p>
                    <ol class="list-decimal space-y-1 pl-4">
                        <li>{{ t('workspace.scim.azure_step1') }}</li>
                        <li>{{ t('workspace.scim.azure_step2') }}</li>
                        <li>{{ t('workspace.scim.azure_step3') }}</li>
                    </ol>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
