<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Copy, Key, Plus, Trash2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface TokenProp {
    id: number
    name: string
    abilities: string[]
    last_used_at: string | null
    expires_at: string | null
    created_at: string | null
}

interface AbilityOption {
    value: string
    label: string
}

const props = defineProps<{
    tokens: TokenProp[]
    availableAbilities: AbilityOption[]
    newTokenValue: string | null
}>()

const showCreateForm = ref(false)
const copied = ref(false)

const form = useForm({
    name: '',
    abilities: [] as string[],
    expires_at: '',
})

function submitCreate(): void {
    form.post('/api-tokens', {
        onSuccess: () => { form.reset(); showCreateForm.value = false },
    })
}

function toggleAbility(value: string): void {
    const idx = form.abilities.indexOf(value)
    if (idx >= 0) {
        form.abilities.splice(idx, 1)
    } else {
        form.abilities.push(value)
    }
}

async function copyToken(value: string): Promise<void> {
    await navigator.clipboard.writeText(value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

function revokeToken(id: number): void {
    if (!confirm(t('apiTokens.revokeConfirm'))) return
    useForm({}).delete(`/api-tokens/${id}`, { preserveScroll: true })
}
</script>

<template>
    <Head :title="t('apiTokens.pageTitle')" />

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent sm:text-3xl">
                    {{ t('apiTokens.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ t('apiTokens.subtitle') }}</p>
            </div>
            <Button
                v-if="!showCreateForm"
                class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200 self-start sm:self-auto"
                @click="showCreateForm = true"
            >
                <Plus class="mr-2 size-4" />
                {{ t('apiTokens.newToken') }}
            </Button>
        </div>

        <!-- New token value (shown once) -->
        <div
            v-if="props.newTokenValue"
            class="relative overflow-hidden rounded-xl border border-green-500/30 bg-green-500/5 p-4 space-y-2"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-green-500/50 to-transparent" />
            <p class="text-sm font-semibold text-green-500">
                {{ t('apiTokens.tokenCreated') }}
            </p>
            <div class="flex items-center gap-2 rounded-lg border border-green-500/20 bg-muted px-3 py-2">
                <code class="flex-1 break-all font-mono text-xs">{{ props.newTokenValue }}</code>
                <Button
                    variant="ghost"
                    size="sm"
                    class="transition-colors hover:text-green-500"
                    @click="copyToken(props.newTokenValue!)"
                >
                    <Copy class="size-4" :class="copied ? 'text-green-500' : ''" />
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">{{ t('apiTokens.tokenOnce') }}</p>
        </div>

        <!-- Create form -->
        <div v-if="showCreateForm" class="relative overflow-hidden rounded-xl border border-primary/30 bg-card p-5 space-y-4">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <p class="text-sm font-semibold">{{ t('apiTokens.newToken') }}</p>

            <Input
                v-model="form.name"
                :placeholder="t('apiTokens.tokenName')"
                class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
            />

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">{{ t('apiTokens.selectAbilities') }}</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="ability in props.availableAbilities"
                        :key="ability.value"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs transition-all duration-150"
                        :class="form.abilities.includes(ability.value)
                            ? 'border-primary bg-primary text-primary-foreground shadow-[0_0_8px_oklch(0.66_0.25_285/0.3)]'
                            : 'border-border bg-card text-muted-foreground hover:border-primary/50 hover:text-foreground'"
                        @click="toggleAbility(ability.value)"
                    >
                        {{ ability.label }}
                    </button>
                </div>
                <p v-if="form.errors.abilities" class="text-xs text-destructive">{{ form.errors.abilities }}</p>
            </div>

            <div class="space-y-1">
                <label class="text-xs text-muted-foreground">{{ t('apiTokens.expiresAt') }}</label>
                <Input
                    v-model="form.expires_at"
                    type="date"
                    class="w-48 focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                />
            </div>

            <div class="flex gap-2">
                <Button
                    size="sm"
                    :disabled="!form.name || form.abilities.length === 0 || form.processing"
                    @click="submitCreate"
                >
                    {{ t('apiTokens.create') }}
                </Button>
                <Button size="sm" variant="ghost" @click="showCreateForm = false; form.reset()">
                    {{ t('common.cancel') }}
                </Button>
            </div>
        </div>

        <!-- Token list -->
        <div class="relative overflow-hidden rounded-xl border border-border bg-card">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
            <div v-if="props.tokens.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-muted ring-1 ring-border">
                    <Key class="size-6 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">{{ t('apiTokens.noTokens') }}</p>
            </div>
            <template v-else>
                <!-- Mobile: card per token -->
                <div class="sm:hidden divide-y divide-border/60">
                    <div
                        v-for="token in props.tokens"
                        :key="token.id"
                        class="p-4 space-y-2"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-sm">{{ token.name }}</p>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive transition-colors hover:bg-destructive/10 shrink-0"
                                @click="revokeToken(token.id)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="ability in token.abilities"
                                :key="ability"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ ability }}
                            </Badge>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                            <span>{{ t('apiTokens.colExpires') }}: {{ token.expires_at ?? '—' }}</span>
                            <span>{{ t('apiTokens.colLastUsed') }}: {{ token.last_used_at ?? t('apiTokens.never') }}</span>
                        </div>
                    </div>
                </div>
                <!-- Desktop: table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/30">
                                <th class="p-3 text-left font-medium text-muted-foreground">{{ t('apiTokens.colName') }}</th>
                                <th class="p-3 text-left font-medium text-muted-foreground">{{ t('apiTokens.colAbilities') }}</th>
                                <th class="p-3 text-right font-medium text-muted-foreground">{{ t('apiTokens.colExpires') }}</th>
                                <th class="p-3 text-right font-medium text-muted-foreground">{{ t('apiTokens.colLastUsed') }}</th>
                                <th class="p-3" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="token in props.tokens"
                                :key="token.id"
                                class="border-b border-border/60 last:border-0 transition-colors duration-100 hover:bg-muted/30"
                            >
                                <td class="p-3 font-medium">{{ token.name }}</td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="ability in token.abilities"
                                            :key="ability"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ ability }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="p-3 text-right text-xs text-muted-foreground">
                                    {{ token.expires_at ?? '—' }}
                                </td>
                                <td class="p-3 text-right text-xs text-muted-foreground">
                                    {{ token.last_used_at ?? t('apiTokens.never') }}
                                </td>
                                <td class="p-3 text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive transition-colors hover:bg-destructive/10"
                                        @click="revokeToken(token.id)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
</template>
