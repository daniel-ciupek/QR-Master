<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowLeft, CheckCircle2, Plus, Shield, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    allowlist: string[]
    currentIp: string
    currentIpAllowed: boolean
}>()

const addForm = useForm({ entry: '' })
const removeForm = useForm({ entry: '' })
const confirmClear = ref(false)

function addIp(): void {
    addForm.post(route('workspaces.ip-allowlist.store', { team: props.team.slug }), {
        preserveScroll: true,
        onSuccess: () => { addForm.reset() },
    })
}

function addCurrentIp(): void {
    addForm.entry = props.currentIp
    addIp()
}

function removeEntry(entry: string): void {
    removeForm.entry = entry
    removeForm.delete(route('workspaces.ip-allowlist.destroy', { team: props.team.slug }), {
        preserveScroll: true,
    })
}

function clearAll(): void {
    router.delete(route('workspaces.ip-allowlist.clear', { team: props.team.slug }), {
        preserveScroll: true,
        onSuccess: () => { confirmClear.value = false },
    })
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.ip_allowlist.title')}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                :aria-label="t('common.back')"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">{{ t('workspace.ip_allowlist.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <!-- Status banner -->
        <div
            v-if="!currentIpAllowed"
            class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30"
        >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-red-600" />
            <div>
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">{{ t('workspace.ip_allowlist.current_ip_blocked') }}</p>
                <p class="text-sm text-red-700 dark:text-red-400">{{ t('workspace.ip_allowlist.current_ip_blocked_hint', { ip: currentIp }) }}</p>
                <Button size="sm" class="mt-2" @click="addCurrentIp">
                    {{ t('workspace.ip_allowlist.add_my_ip') }}
                </Button>
            </div>
        </div>

        <!-- Current IP info -->
        <div class="flex items-center gap-3 rounded-lg border bg-muted/30 px-4 py-3">
            <CheckCircle2
                v-if="currentIpAllowed"
                class="size-4 shrink-0 text-green-500"
            />
            <AlertTriangle
                v-else
                class="size-4 shrink-0 text-amber-500"
            />
            <div class="flex-1">
                <span class="text-sm font-medium">{{ t('workspace.ip_allowlist.your_ip') }}</span>
                <code class="ml-2 rounded bg-muted px-2 py-0.5 text-xs font-mono">{{ currentIp }}</code>
            </div>
            <Button
                v-if="currentIpAllowed && allowlist.length > 0"
                size="sm"
                variant="ghost"
                class="shrink-0 text-xs"
                @click="addCurrentIp"
            >
                <Plus class="mr-1 size-3" />
                {{ t('workspace.ip_allowlist.add_my_ip') }}
            </Button>
        </div>

        <!-- Add IP/CIDR -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Shield class="size-4" />
                    {{ t('workspace.ip_allowlist.manage_title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.ip_allowlist.manage_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Add form -->
                <div class="space-y-2">
                    <Label for="ip-entry">{{ t('workspace.ip_allowlist.add_label') }}</Label>
                    <div class="flex gap-2">
                        <Input
                            id="ip-entry"
                            v-model="addForm.entry"
                            placeholder="192.168.1.0/24"
                            class="font-mono"
                            @keydown.enter="addIp"
                        />
                        <Button :disabled="!addForm.entry.trim() || addForm.processing" @click="addIp">
                            <Plus class="mr-2 size-4" />
                            {{ t('workspace.ip_allowlist.add') }}
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ t('workspace.ip_allowlist.format_hint') }}</p>
                    <p v-if="addForm.errors.entry" class="text-sm text-destructive">{{ addForm.errors.entry }}</p>
                </div>

                <!-- Status when empty -->
                <div
                    v-if="allowlist.length === 0"
                    class="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400"
                >
                    <CheckCircle2 class="size-4 shrink-0" />
                    {{ t('workspace.ip_allowlist.all_allowed') }}
                </div>

                <!-- List -->
                <div v-else class="space-y-2">
                    <div
                        v-for="entry in allowlist"
                        :key="entry"
                        class="flex items-center justify-between gap-2 rounded-md border bg-background px-3 py-2"
                    >
                        <code class="flex-1 font-mono text-sm">{{ entry }}</code>
                        <span
                            v-if="entry === currentIp || currentIp.startsWith(entry.split('/')[0] ?? '')"
                            class="text-xs text-green-600"
                        >{{ t('workspace.ip_allowlist.yours') }}</span>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-7 shrink-0 text-muted-foreground hover:text-destructive"
                            :aria-label="`Remove ${entry}`"
                            @click="removeEntry(entry)"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Clear all -->
        <Card v-if="allowlist.length > 0" class="border-destructive/50">
            <CardHeader>
                <CardTitle class="text-destructive">{{ t('workspace.ip_allowlist.clear_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.ip_allowlist.clear_desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button
                    v-if="!confirmClear"
                    variant="destructive"
                    size="sm"
                    @click="confirmClear = true"
                >
                    {{ t('workspace.ip_allowlist.clear_all') }}
                </Button>
                <div v-else class="flex gap-2">
                    <Button variant="destructive" size="sm" @click="clearAll">{{ t('workspace.ip_allowlist.confirm_clear') }}</Button>
                    <Button variant="ghost" size="sm" @click="confirmClear = false">{{ t('common.cancel') }}</Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
