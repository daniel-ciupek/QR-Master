<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, KeyRound, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface SsoConn {
    id: number
    provider: string
    email_domain: string
    has_client_id: boolean
    tenant_id: string | null
    okta_domain: string | null
    is_active: boolean
    auto_provision: boolean
}

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    providers: string[]
    connection: SsoConn | null
}>()

const form = useForm({
    provider: props.connection?.provider ?? 'google',
    email_domain: props.connection?.email_domain ?? '',
    client_id: '',
    client_secret: '',
    tenant_id: props.connection?.tenant_id ?? '',
    okta_domain: props.connection?.okta_domain ?? '',
    is_active: props.connection?.is_active ?? true,
    auto_provision: props.connection?.auto_provision ?? false,
})

const confirmDelete = ref(false)
const appOrigin = window.location.origin

const needsTenant = computed(() => form.provider === 'azure')
const needsOktaDomain = computed(() => form.provider === 'okta')

const providerLabels: Record<string, string> = {
    google: 'Google Workspace',
    azure: 'Microsoft 365 / Azure AD',
    okta: 'Okta',
}

function save(): void {
    form.put(route('workspaces.sso.update', { team: props.team.slug }), {
        preserveScroll: true,
    })
}

function remove(): void {
    router.delete(route('workspaces.sso.destroy', { team: props.team.slug }), {
        onSuccess: () => { confirmDelete.value = false },
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.sso.title')}`" />

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
                    {{ t('workspace.sso.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Status badge -->
            <div
                v-if="connection"
                class="flex items-center gap-3 rounded-xl border p-3 transition-colors duration-150"
                :class="connection.is_active
                    ? 'border-cyan-400/20 bg-cyan-400/5'
                    : 'border-gold-500/20 bg-gold-500/5'"
            >
                <div
                    class="flex size-8 items-center justify-center rounded-full"
                    :class="connection.is_active ? 'bg-cyan-400/10' : 'bg-gold-500/10'"
                >
                    <KeyRound class="size-4" :class="connection.is_active ? 'text-cyan-400' : 'text-gold-500'" />
                </div>
                <div>
                    <span class="text-sm font-medium">
                        {{ connection.is_active ? t('workspace.sso.status_active') : t('workspace.sso.status_inactive') }}
                    </span>
                    <span class="ml-2 text-sm text-muted-foreground">
                        — {{ providerLabels[connection.provider] }} · @{{ connection.email_domain }}
                    </span>
                </div>
            </div>
            <div
                v-else
                class="flex items-center gap-3 rounded-xl border border-border bg-muted/20 p-3 text-sm text-muted-foreground"
            >
                <div class="flex size-8 items-center justify-center rounded-full bg-muted">
                    <KeyRound class="size-4" />
                </div>
                {{ t('workspace.sso.no_connection') }}
            </div>

            <!-- Config form card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20">
                            <KeyRound class="size-4 text-gold-500" />
                        </div>
                        {{ t('workspace.sso.config_title') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.sso.config_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Provider selector -->
                    <div class="space-y-2">
                        <Label>{{ t('workspace.sso.provider') }}</Label>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="p in providers"
                                :key="p"
                                :for="`provider-${p}`"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition-all duration-150"
                                :class="form.provider === p
                                    ? 'border-primary/50 bg-primary/5 shadow-[0_0_12px_oklch(0.66_0.25_285/0.08)]'
                                    : 'border-input hover:border-primary/30 hover:bg-muted/20'"
                            >
                                <input
                                    :id="`provider-${p}`"
                                    v-model="form.provider"
                                    type="radio"
                                    :value="p"
                                    class="sr-only"
                                >
                                {{ providerLabels[p] }}
                            </label>
                        </div>
                    </div>

                    <!-- Email domain -->
                    <div class="space-y-2">
                        <Label for="email-domain">{{ t('workspace.sso.email_domain') }}</Label>
                        <Input
                            id="email-domain"
                            v-model="form.email_domain"
                            placeholder="acme.com"
                            class="font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p class="text-xs text-muted-foreground">{{ t('workspace.sso.email_domain_hint') }}</p>
                        <p v-if="form.errors.email_domain" class="text-sm text-destructive">{{ form.errors.email_domain }}</p>
                    </div>

                    <!-- Client ID -->
                    <div class="space-y-2">
                        <Label for="client-id">{{ t('workspace.sso.client_id') }}</Label>
                        <Input
                            id="client-id"
                            v-model="form.client_id"
                            :placeholder="connection?.has_client_id ? t('workspace.sso.client_id_placeholder_update') : t('workspace.sso.client_id_placeholder')"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.client_id" class="text-sm text-destructive">{{ form.errors.client_id }}</p>
                    </div>

                    <!-- Client Secret -->
                    <div class="space-y-2">
                        <Label for="client-secret">{{ t('workspace.sso.client_secret') }}</Label>
                        <Input
                            id="client-secret"
                            v-model="form.client_secret"
                            type="password"
                            :placeholder="connection ? t('workspace.sso.client_secret_placeholder_update') : t('workspace.sso.client_secret_placeholder')"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.client_secret" class="text-sm text-destructive">{{ form.errors.client_secret }}</p>
                    </div>

                    <!-- Azure Tenant ID -->
                    <div v-if="needsTenant" class="space-y-2">
                        <Label for="tenant-id">{{ t('workspace.sso.tenant_id') }}</Label>
                        <Input
                            id="tenant-id"
                            v-model="form.tenant_id"
                            placeholder="your-tenant-id or common"
                            class="font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.tenant_id" class="text-sm text-destructive">{{ form.errors.tenant_id }}</p>
                    </div>

                    <!-- Okta domain -->
                    <div v-if="needsOktaDomain" class="space-y-2">
                        <Label for="okta-domain">{{ t('workspace.sso.okta_domain') }}</Label>
                        <Input
                            id="okta-domain"
                            v-model="form.okta_domain"
                            placeholder="your-company.okta.com"
                            class="font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.okta_domain" class="text-sm text-destructive">{{ form.errors.okta_domain }}</p>
                    </div>

                    <!-- Divider -->
                    <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                    <!-- Toggles -->
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors duration-150 hover:bg-muted/20">
                            <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input accent-primary">
                            <div>
                                <span class="text-sm font-medium">{{ t('workspace.sso.is_active') }}</span>
                                <p class="text-xs text-muted-foreground">{{ t('workspace.sso.is_active_hint') }}</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors duration-150 hover:bg-muted/20">
                            <input v-model="form.auto_provision" type="checkbox" class="size-4 rounded border-input accent-primary">
                            <div>
                                <span class="text-sm font-medium">{{ t('workspace.sso.auto_provision') }}</span>
                                <p class="text-xs text-muted-foreground">{{ t('workspace.sso.auto_provision_hint') }}</p>
                            </div>
                        </label>
                    </div>

                    <Button
                        :disabled="form.processing"
                        class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="save"
                    >
                        {{ form.processing ? t('common.saving') : t('workspace.sso.save') }}
                    </Button>
                </CardContent>
            </div>

            <!-- Delete card -->
            <div v-if="connection" class="relative rounded-xl border border-destructive/40 bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="text-destructive">{{ t('workspace.sso.danger_title') }}</CardTitle>
                    <CardDescription>{{ t('workspace.sso.danger_desc') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        v-if="!confirmDelete"
                        variant="destructive"
                        size="sm"
                        @click="confirmDelete = true"
                    >
                        <Trash2 class="mr-2 size-4" />
                        {{ t('workspace.sso.delete') }}
                    </Button>
                    <div v-else class="flex gap-2">
                        <Button variant="destructive" size="sm" @click="remove">{{ t('workspace.sso.confirm_delete') }}</Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="hover:text-primary transition-colors duration-150"
                            @click="confirmDelete = false"
                        >{{ t('common.cancel') }}</Button>
                    </div>
                </CardContent>
            </div>

            <!-- Setup instructions card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.sso.instructions_title') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <p class="text-foreground font-medium">{{ t('workspace.sso.callback_url') }}:</p>
                    <code class="block rounded-xl bg-muted px-3 py-2 font-mono text-xs">{{ appOrigin }}/sso/{{ form.provider }}/callback</code>
                    <ul class="list-disc space-y-1 pl-4">
                        <li><strong class="text-foreground">Google:</strong> {{ t('workspace.sso.google_hint') }}</li>
                        <li><strong class="text-foreground">Microsoft:</strong> {{ t('workspace.sso.azure_hint') }}</li>
                        <li><strong class="text-foreground">Okta:</strong> {{ t('workspace.sso.okta_hint') }}</li>
                    </ul>
                </CardContent>
            </div>
        </div>
    </div>
</template>
