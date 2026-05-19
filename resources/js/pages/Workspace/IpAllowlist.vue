<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowLeft, CheckCircle2, Plus, Shield, Trash2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
                variant="ghost"
                size="icon"
                class="shrink-0 self-start hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                :aria-label="t('common.back')"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.ip_allowlist.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ team.name }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Status banner — blocked -->
            <div
                v-if="!currentIpAllowed"
                class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4"
            >
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-destructive/10">
                    <AlertTriangle class="size-4 text-destructive" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-destructive">{{ t('workspace.ip_allowlist.current_ip_blocked') }}</p>
                    <p class="text-sm text-destructive/80 mt-0.5">{{ t('workspace.ip_allowlist.current_ip_blocked_hint', { ip: currentIp }) }}</p>
                    <Button size="sm" class="mt-3 shadow-[0_0_12px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200" @click="addCurrentIp">
                        {{ t('workspace.ip_allowlist.add_my_ip') }}
                    </Button>
                </div>
            </div>

            <!-- Current IP status bar -->
            <div
                class="flex items-center gap-3 rounded-xl border bg-muted/20 px-4 py-3"
                :class="currentIpAllowed ? 'border-cyan-400/20' : 'border-gold-500/20'"
            >
                <CheckCircle2 v-if="currentIpAllowed" class="size-4 shrink-0 text-cyan-400" />
                <AlertTriangle v-else class="size-4 shrink-0 text-gold-500" />
                <div class="flex-1">
                    <span class="text-sm font-medium">{{ t('workspace.ip_allowlist.your_ip') }}</span>
                    <code class="ml-2 rounded bg-muted px-2 py-0.5 text-xs font-mono">{{ currentIp }}</code>
                </div>
                <Button
                    v-if="currentIpAllowed && allowlist.length > 0"
                    size="sm"
                    variant="ghost"
                    class="shrink-0 text-xs hover:text-primary transition-colors duration-150"
                    @click="addCurrentIp"
                >
                    <Plus class="mr-1 size-3" />
                    {{ t('workspace.ip_allowlist.add_my_ip') }}
                </Button>
            </div>

            <!-- Manage card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <Shield class="size-4 text-cyan-400" />
                        </div>
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
                                class="font-mono focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                @keydown.enter="addIp"
                            />
                            <Button
                                :disabled="!addForm.entry.trim() || addForm.processing"
                                class="shrink-0 shadow-[0_0_12px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                                @click="addIp"
                            >
                                <Plus class="mr-2 size-4" />
                                {{ t('workspace.ip_allowlist.add') }}
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ t('workspace.ip_allowlist.format_hint') }}</p>
                        <p v-if="addForm.errors.entry" class="text-sm text-destructive">{{ addForm.errors.entry }}</p>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="allowlist.length === 0"
                        class="flex items-center gap-2 rounded-xl border border-cyan-400/20 bg-cyan-400/5 px-3 py-3 text-sm text-cyan-400"
                    >
                        <CheckCircle2 class="size-4 shrink-0" />
                        {{ t('workspace.ip_allowlist.all_allowed') }}
                    </div>

                    <!-- IP list -->
                    <div v-else class="space-y-2">
                        <div
                            v-for="entry in allowlist"
                            :key="entry"
                            class="flex items-center justify-between gap-2 rounded-xl border border-border bg-muted/20 px-3 py-2 transition-colors duration-100 hover:bg-muted/40"
                        >
                            <code class="flex-1 font-mono text-sm">{{ entry }}</code>
                            <span
                                v-if="entry === currentIp || currentIp.startsWith(entry.split('/')[0] ?? '')"
                                class="text-xs text-cyan-400"
                            >{{ t('workspace.ip_allowlist.yours') }}</span>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="size-7 shrink-0 text-muted-foreground hover:text-destructive transition-colors duration-150"
                                :aria-label="`Remove ${entry}`"
                                @click="removeEntry(entry)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </div>

            <!-- Danger zone — clear all -->
            <div v-if="allowlist.length > 0" class="relative rounded-xl border border-destructive/40 bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/50 to-transparent" />
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
                        <Button
                            variant="ghost"
                            size="sm"
                            class="hover:text-primary transition-colors duration-150"
                            @click="confirmClear = false"
                        >{{ t('common.cancel') }}</Button>
                    </div>
                </CardContent>
            </div>
        </div>
    </div>
</template>
