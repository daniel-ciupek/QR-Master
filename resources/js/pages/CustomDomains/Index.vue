<script setup lang="ts">
import { CheckCircle, Clock, Copy, Globe, RefreshCw, Trash2, XCircle } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface Domain {
    id: number
    domain: string
    status: 'pending' | 'verified' | 'failed'
    verification_token: string
    verified_at: string | null
    ssl_status: string
}

const props = defineProps<{
    domains: Domain[]
    cnameTarget: string
}>()

const { t } = useI18n()
const form = useForm({ domain: '' })
const copiedId = ref<number | null>(null)

function submit(): void {
    form.post(route('domains.store'), { preserveScroll: true, onSuccess: () => { form.reset() } })
}

function verifyDomain(id: number): void {
    useForm({}).post(route('domains.verify', { customDomain: id }), { preserveScroll: true })
}

function deleteDomain(id: number): void {
    if (!confirm(t('domains.confirmDelete'))) return
    useForm({}).delete(route('domains.destroy', { customDomain: id }), { preserveScroll: true })
}

async function copyText(text: string, id: number): Promise<void> {
    await navigator.clipboard.writeText(text)
    copiedId.value = id
    setTimeout(() => { copiedId.value = null }, 2000)
}

function statusIcon(status: string): typeof CheckCircle {
    if (status === 'verified') return CheckCircle
    if (status === 'failed') return XCircle
    return Clock
}

function statusColor(status: string): string {
    if (status === 'verified') return 'text-emerald-400'
    if (status === 'failed') return 'text-destructive'
    return 'text-gold-500'
}

function statusBorderColor(status: string): string {
    if (status === 'verified') return 'via-emerald-400/50'
    if (status === 'failed') return 'via-destructive/50'
    return 'via-gold-500/50'
}
</script>

<template>
    <div class="space-y-6 p-4 md:p-6">
        <!-- Page header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                    <Globe class="size-5 text-cyan-400" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('domains.title') }}
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ t('domains.subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- Section divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

        <!-- Add domain form -->
        <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <h2 class="font-semibold flex items-center gap-2">
                <Globe class="size-4 text-primary" />
                {{ t('domains.addDomain') }}
            </h2>
            <form
                class="flex flex-col gap-3 sm:flex-row"
                @submit.prevent="submit"
            >
                <Input
                    v-model="form.domain"
                    type="text"
                    :placeholder="t('domains.domainPlaceholder')"
                    class="sm:max-w-sm focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                    :class="{ 'border-destructive focus-visible:ring-destructive/50': form.errors.domain }"
                />
                <Button
                    type="submit"
                    class="shrink-0 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                    :disabled="form.processing || !form.domain.trim()"
                >
                    {{ t('domains.add') }}
                </Button>
            </form>
            <p
                v-if="form.errors.domain"
                class="text-xs text-destructive"
            >
                {{ form.errors.domain }}
            </p>
        </div>

        <!-- DNS instructions -->
        <div class="relative rounded-xl border border-border bg-card/50 p-5 space-y-4 text-sm overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
            <h2 class="font-semibold flex items-center gap-2">
                <div class="flex size-6 items-center justify-center rounded-full bg-cyan-400/10">
                    <Globe class="size-3.5 text-cyan-400" />
                </div>
                {{ t('domains.dnsInstructions') }}
            </h2>
            <p class="text-muted-foreground">{{ t('domains.dnsStep1') }}</p>
            <div class="rounded-lg border border-border bg-background px-4 py-3 text-xs font-mono space-y-1">
                <div>Type: <span class="text-cyan-400 font-semibold">CNAME</span></div>
                <div>Host: <span class="text-primary font-semibold">@</span> (or your subdomain)</div>
                <div>Value: <span class="text-primary font-semibold">{{ props.cnameTarget }}</span></div>
            </div>
            <p class="text-muted-foreground">{{ t('domains.dnsStep2') }}</p>
            <div class="rounded-lg border border-border bg-background px-4 py-3 text-xs font-mono space-y-1">
                <div>Type: <span class="text-gold-500 font-semibold">TXT</span></div>
                <div>Host: <span class="text-primary font-semibold">_qrmaster-verify.yourdomain.com</span></div>
                <div>Value: <span class="text-primary font-semibold">qrmaster-verification=YOUR_TOKEN</span></div>
            </div>
        </div>

        <!-- Domains list -->
        <div
            v-if="props.domains.length > 0"
            class="rounded-xl border border-border bg-card overflow-hidden divide-y divide-border"
        >
            <div
                v-for="domain in props.domains"
                :key="domain.id"
                class="relative flex items-center gap-3 p-4 hover:bg-muted/30 transition-colors duration-100"
            >
                <!-- Status top-border per domain -->
                <div
                    class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent"
                    :class="statusBorderColor(domain.status)"
                />

                <!-- Status icon in colored circle -->
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-emerald-400/10 ring-1 ring-emerald-400/20': domain.status === 'verified',
                        'bg-destructive/10 ring-1 ring-destructive/20': domain.status === 'failed',
                        'bg-gold-500/10 ring-1 ring-gold-500/20': domain.status === 'pending',
                    }"
                >
                    <component
                        :is="statusIcon(domain.status)"
                        class="size-4 shrink-0"
                        :class="statusColor(domain.status)"
                    />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium truncate">{{ domain.domain }}</span>
                        <Badge
                            class="text-xs shrink-0"
                            :class="{
                                'bg-emerald-400/10 text-emerald-400 border-emerald-400/20': domain.status === 'verified',
                                'bg-destructive/10 text-destructive border-destructive/20': domain.status === 'failed',
                                'bg-gold-500/10 text-gold-500 border-gold-500/20': domain.status === 'pending',
                            }"
                            variant="outline"
                        >
                            {{ t(`domains.status.${domain.status}`) }}
                        </Badge>
                    </div>

                    <!-- Verification token (only for pending) -->
                    <div
                        v-if="domain.status !== 'verified'"
                        class="mt-1 flex items-center gap-2 text-xs text-muted-foreground flex-wrap"
                    >
                        <span>Token:</span>
                        <code class="font-mono text-foreground/70">qrmaster-verification={{ domain.verification_token }}</code>
                        <button
                            class="flex items-center gap-1 text-primary hover:text-primary/80 transition-colors duration-150"
                            @click="copyText(`qrmaster-verification=${domain.verification_token}`, domain.id)"
                        >
                            <Copy class="size-3" />
                            {{ copiedId === domain.id ? t('domains.copied') : t('domains.copy') }}
                        </button>
                    </div>

                    <div
                        v-else
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        {{ t('domains.verifiedOn', { date: domain.verified_at }) }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 shrink-0">
                    <Button
                        v-if="domain.status !== 'verified'"
                        variant="outline"
                        size="sm"
                        class="gap-1.5 hover:border-primary/40 transition-colors duration-150"
                        @click="verifyDomain(domain.id)"
                    >
                        <RefreshCw class="size-3.5" />
                        <span class="hidden sm:inline">{{ t('domains.verify') }}</span>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                        @click="deleteDomain(domain.id)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-12 text-center"
        >
            <div class="flex size-14 items-center justify-center rounded-2xl bg-cyan-400/10 ring-1 ring-cyan-400/20 mb-3">
                <Globe class="size-7 text-cyan-400" />
            </div>
            <p class="text-sm font-medium mb-1">{{ t('domains.empty') }}</p>
            <p class="text-xs text-muted-foreground max-w-xs">Add your first custom domain to start using it with your QR codes</p>
        </div>
    </div>
</template>
