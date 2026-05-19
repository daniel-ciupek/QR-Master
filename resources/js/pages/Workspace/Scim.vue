<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { ArrowLeft, Check, Copy, Plus, Server, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
                variant="ghost"
                size="icon"
                class="shrink-0 self-start hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.scim.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Info banner -->
            <div class="flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <Server class="size-4 text-primary" />
                </div>
                <p class="text-sm text-primary/80 pt-0.5">
                    {{ t('workspace.scim.info') }}
                </p>
            </div>

            <!-- New token alert -->
            <div
                v-if="newToken"
                class="rounded-xl border border-gold-500/30 bg-gold-500/5 p-4"
            >
                <p class="mb-2 text-sm font-semibold text-gold-500">
                    {{ t('workspace.scim.token_generated') }}
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 overflow-x-auto rounded-lg bg-muted px-3 py-2 font-mono text-xs">{{ newToken }}</code>
                    <Button
                        size="icon"
                        variant="ghost"
                        class="size-8 shrink-0 hover:text-primary transition-colors duration-150"
                        aria-label="Copy token"
                        @click="copyToken"
                    >
                        <Check v-if="copied" class="size-4 text-cyan-400" />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
                <p class="mt-2 text-xs text-gold-500/80">{{ t('workspace.scim.token_once_warning') }}</p>
            </div>

            <!-- Token management card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.scim.token_title') }}</CardTitle>
                    <CardDescription>{{ t('workspace.scim.token_desc', { max: maxTokens }) }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Token list -->
                    <div v-if="tokens.length > 0" class="divide-y divide-border rounded-xl border border-border overflow-hidden">
                        <div
                            v-for="token in tokens"
                            :key="token.id"
                            class="flex items-center justify-between px-4 py-3 transition-colors duration-100 hover:bg-muted/30"
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
                                    class="text-destructive hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                                    @click="confirmRevokeId = token.id"
                                >
                                    <Trash2 class="mr-1 size-3.5" />
                                    {{ t('workspace.scim.token_revoke') }}
                                </Button>
                                <div v-else class="flex gap-2">
                                    <Button variant="destructive" size="sm" @click="revoke(token.id)">
                                        {{ t('workspace.scim.confirm_revoke') }}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="hover:text-primary transition-colors duration-150"
                                        @click="confirmRevokeId = null"
                                    >
                                        {{ t('common.cancel') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">{{ t('workspace.scim.token_none') }}</p>

                    <!-- Divider -->
                    <div v-if="tokens.length < maxTokens" class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                    <!-- Generate form -->
                    <form
                        v-if="tokens.length < maxTokens"
                        class="flex flex-col gap-3 sm:flex-row sm:items-end"
                        @submit.prevent="generate"
                    >
                        <div class="flex-1">
                            <Label for="scim-token-name">{{ t('workspace.scim.token_name') }}</Label>
                            <Input
                                id="scim-token-name"
                                v-model="generateForm.name"
                                :placeholder="t('workspace.scim.token_name_placeholder')"
                                maxlength="40"
                                class="mt-1 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="generateForm.errors.name" class="mt-1 text-xs text-destructive">
                                {{ generateForm.errors.name }}
                            </p>
                        </div>
                        <Button
                            type="submit"
                            :disabled="generateForm.processing"
                            class="shrink-0 shadow-[0_0_12px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                        >
                            <Plus class="mr-2 size-4" />
                            {{ t('workspace.scim.token_generate') }}
                        </Button>
                    </form>

                    <p v-else class="text-sm text-muted-foreground">
                        {{ t('workspace.scim.max_tokens_reached') }}
                    </p>
                </CardContent>
            </div>

            <!-- Endpoints card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.scim.endpoints_title') }}</CardTitle>
                    <CardDescription>{{ t('workspace.scim.endpoints_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div
                        v-for="ep in endpoints"
                        :key="ep.path"
                        class="flex items-center justify-between gap-2 rounded-xl border border-border bg-muted/20 px-3 py-2 transition-colors duration-100 hover:bg-muted/40"
                    >
                        <div>
                            <p class="text-xs font-medium text-muted-foreground">{{ ep.label }}</p>
                            <code class="text-xs font-mono">{{ scimBaseUrl }}{{ ep.path }}</code>
                        </div>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-7 shrink-0 hover:text-primary transition-colors duration-150"
                            :aria-label="`Copy ${ep.label} URL`"
                            @click="copyUrl(scimBaseUrl + ep.path)"
                        >
                            <Copy class="size-3.5" />
                        </Button>
                    </div>
                </CardContent>
            </div>

            <!-- Setup guide card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.scim.guide_title') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 text-sm text-muted-foreground">
                    <div>
                        <p class="mb-2 font-semibold text-foreground">{{ t('workspace.scim.okta_title') }}</p>
                        <ol class="list-decimal space-y-1 pl-4">
                            <li>{{ t('workspace.scim.okta_step1') }}</li>
                            <li>{{ t('workspace.scim.okta_step2') }}</li>
                            <li>{{ t('workspace.scim.okta_step3') }}</li>
                        </ol>
                    </div>
                    <!-- Divider -->
                    <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
                    <div>
                        <p class="mb-2 font-semibold text-foreground">{{ t('workspace.scim.azure_title') }}</p>
                        <ol class="list-decimal space-y-1 pl-4">
                            <li>{{ t('workspace.scim.azure_step1') }}</li>
                            <li>{{ t('workspace.scim.azure_step2') }}</li>
                            <li>{{ t('workspace.scim.azure_step3') }}</li>
                        </ol>
                    </div>
                </CardContent>
            </div>
        </div>
    </div>
</template>
