<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { ArrowLeft, Check, Copy, Plus, Server, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface ScimToken {
    id: string
    name: string
    created_at: string
}

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    tokens: ScimToken[]
    newToken: string | null
    scimBaseUrl: string
    maxTokens: number
}>()

const copied = ref(false)
const confirmRevokeId = ref<string | null>(null)

const generateForm = useForm({ name: '' })

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
    generateForm.post(route('workspaces.scim.generate', { team: props.team.slug }), {
        onSuccess: () => { generateForm.reset() },
    })
}

function revoke(tokenId: string): void {
    router.delete(route('workspaces.scim.revoke', { team: props.team.slug }), {
        data: { token_id: tokenId },
        onSuccess: () => { confirmRevokeId.value = null },
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
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label="Copy token"
                    @click="copyToken"
                >
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
                <CardDescription>{{ t('workspace.scim.token_desc', { max: maxTokens }) }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Token list -->
                <div v-if="tokens.length > 0" class="divide-y rounded-md border">
                    <div
                        v-for="token in tokens"
                        :key="token.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ token.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ t('workspace.scim.token_table_created') }}: {{ new Date(token.created_at).toLocaleDateString() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="confirmRevokeId !== token.id"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmRevokeId = token.id"
                            >
                                <Trash2 class="mr-1 size-3.5" />
                                {{ t('workspace.scim.token_revoke') }}
                            </Button>
                            <div v-else class="flex gap-2">
                                <Button variant="destructive" size="sm" @click="revoke(token.id)">
                                    {{ t('workspace.scim.confirm_revoke') }}
                                </Button>
                                <Button variant="ghost" size="sm" @click="confirmRevokeId = null">
                                    {{ t('common.cancel') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-else class="text-sm text-muted-foreground">{{ t('workspace.scim.token_none') }}</p>

                <!-- Generate new token -->
                <form
                    v-if="tokens.length < maxTokens"
                    class="flex items-end gap-3"
                    @submit.prevent="generate"
                >
                    <div class="flex-1">
                        <Label for="scim-token-name">{{ t('workspace.scim.token_name') }}</Label>
                        <Input
                            id="scim-token-name"
                            v-model="generateForm.name"
                            :placeholder="t('workspace.scim.token_name_placeholder')"
                            maxlength="40"
                            class="mt-1"
                        />
                        <p v-if="generateForm.errors.name" class="mt-1 text-xs text-destructive">
                            {{ generateForm.errors.name }}
                        </p>
                    </div>
                    <Button type="submit" :disabled="generateForm.processing">
                        <Plus class="mr-2 size-4" />
                        {{ t('workspace.scim.token_generate') }}
                    </Button>
                </form>

                <p v-else class="text-sm text-muted-foreground">
                    {{ t('workspace.scim.max_tokens_reached') }}
                </p>
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
                        :aria-label="`Copy ${ep.label} URL`"
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
                    <p class="mb-1 font-semibold text-foreground">{{ t('workspace.scim.okta_title') }}</p>
                    <ol class="list-decimal space-y-1 pl-4">
                        <li>{{ t('workspace.scim.okta_step1') }}</li>
                        <li>{{ t('workspace.scim.okta_step2') }}</li>
                        <li>{{ t('workspace.scim.okta_step3') }}</li>
                    </ol>
                </div>
                <div>
                    <p class="mb-1 font-semibold text-foreground">{{ t('workspace.scim.azure_title') }}</p>
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
