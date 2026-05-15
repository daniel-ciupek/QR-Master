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

    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">{{ t('apiTokens.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('apiTokens.subtitle') }}</p>
            </div>
            <Button v-if="!showCreateForm" @click="showCreateForm = true">
                <Plus class="mr-2 h-4 w-4" />
                {{ t('apiTokens.newToken') }}
            </Button>
        </div>

        <!-- New token value (shown once) -->
        <div
            v-if="props.newTokenValue"
            class="rounded-xl border border-green-300 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30 space-y-2"
        >
            <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                {{ t('apiTokens.tokenCreated') }}
            </p>
            <div class="flex items-center gap-2 rounded border border-green-200 bg-white px-3 py-2 dark:bg-green-950/50">
                <code class="flex-1 break-all font-mono text-xs">{{ props.newTokenValue }}</code>
                <Button variant="ghost" size="sm" @click="copyToken(props.newTokenValue!)">
                    <Copy class="h-4 w-4" :class="copied ? 'text-green-500' : ''" />
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">{{ t('apiTokens.tokenOnce') }}</p>
        </div>

        <!-- Create form -->
        <div v-if="showCreateForm" class="rounded-xl border border-primary/30 bg-card p-5 space-y-4">
            <p class="text-sm font-semibold">{{ t('apiTokens.newToken') }}</p>

            <Input v-model="form.name" :placeholder="t('apiTokens.tokenName')" />

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">{{ t('apiTokens.selectAbilities') }}</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="ability in props.availableAbilities"
                        :key="ability.value"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs transition-colors"
                        :class="form.abilities.includes(ability.value)
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-card text-muted-foreground hover:border-primary'"
                        @click="toggleAbility(ability.value)"
                    >
                        {{ ability.label }}
                    </button>
                </div>
                <p v-if="form.errors.abilities" class="text-xs text-destructive">{{ form.errors.abilities }}</p>
            </div>

            <div class="space-y-1">
                <label class="text-xs text-muted-foreground">{{ t('apiTokens.expiresAt') }}</label>
                <Input v-model="form.expires_at" type="date" class="w-48" />
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
        <div class="rounded-xl border border-border bg-card">
            <div v-if="props.tokens.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                <Key class="mx-auto mb-3 h-8 w-8 opacity-30" />
                {{ t('apiTokens.noTokens') }}
            </div>
            <table v-else class="w-full text-sm">
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
                        class="border-b border-border last:border-0 hover:bg-muted/20"
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
                                class="text-destructive"
                                @click="revokeToken(token.id)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
