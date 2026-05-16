<script setup lang="ts">
import { CheckCircle, Clock, Globe, RefreshCw, Trash2, XCircle } from 'lucide-vue-next'
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
    if (status === 'verified') return 'text-green-600 dark:text-green-400'
    if (status === 'failed') return 'text-destructive'
    return 'text-amber-600 dark:text-amber-400'
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">{{ t('domains.title') }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('domains.subtitle') }}</p>
        </div>

        <!-- Add domain form -->
        <div class="rounded-lg border p-5 space-y-3">
            <h2 class="font-semibold">{{ t('domains.addDomain') }}</h2>
            <form
                class="flex gap-2"
                @submit.prevent="submit"
            >
                <Input
                    v-model="form.domain"
                    type="text"
                    :placeholder="t('domains.domainPlaceholder')"
                    class="max-w-sm"
                    :class="{ 'border-destructive': form.errors.domain }"
                />
                <Button
                    type="submit"
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
        <div class="rounded-lg border bg-muted/30 p-5 space-y-3 text-sm">
            <h2 class="font-semibold flex items-center gap-2">
                <Globe class="size-4" />
                {{ t('domains.dnsInstructions') }}
            </h2>
            <p class="text-muted-foreground">{{ t('domains.dnsStep1') }}</p>
            <div class="font-mono bg-background rounded border px-3 py-2 text-xs">
                <div>Type: <span class="text-primary">CNAME</span></div>
                <div>Host: <span class="text-primary">@</span> (or your subdomain)</div>
                <div>Value: <span class="text-primary">{{ props.cnameTarget }}</span></div>
            </div>
            <p class="text-muted-foreground">{{ t('domains.dnsStep2') }}</p>
            <div class="font-mono bg-background rounded border px-3 py-2 text-xs">
                <div>Type: <span class="text-primary">TXT</span></div>
                <div>Host: <span class="text-primary">_qrmaster-verify.yourdomain.com</span></div>
                <div>Value: <span class="text-primary">qrmaster-verification=YOUR_TOKEN</span></div>
            </div>
        </div>

        <!-- Domains list -->
        <div
            v-if="props.domains.length > 0"
            class="rounded-lg border divide-y"
        >
            <div
                v-for="domain in props.domains"
                :key="domain.id"
                class="flex items-center gap-3 p-4"
            >
                <!-- Status icon -->
                <component
                    :is="statusIcon(domain.status)"
                    class="size-5 shrink-0"
                    :class="statusColor(domain.status)"
                />

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium truncate">{{ domain.domain }}</span>
                        <Badge
                            :variant="domain.status === 'verified' ? 'default' : 'secondary'"
                            class="text-xs"
                        >
                            {{ t(`domains.status.${domain.status}`) }}
                        </Badge>
                    </div>

                    <!-- Verification token (only for pending) -->
                    <div
                        v-if="domain.status !== 'verified'"
                        class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span>Token:</span>
                        <code class="font-mono">qrmaster-verification={{ domain.verification_token }}</code>
                        <button
                            class="underline hover:no-underline"
                            @click="copyText(`qrmaster-verification=${domain.verification_token}`, domain.id)"
                        >
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
                        class="gap-1.5"
                        @click="verifyDomain(domain.id)"
                    >
                        <RefreshCw class="size-3.5" />
                        {{ t('domains.verify') }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 text-muted-foreground hover:text-destructive"
                        @click="deleteDomain(domain.id)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground text-sm"
        >
            {{ t('domains.empty') }}
        </div>
    </div>
</template>
