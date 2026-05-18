<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Monitor } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface SessionData {
    id: number
    is_current: boolean
    browser: string
    os: string
    ip_address: string | null
    last_active_at: string
}

defineProps<{ sessions: SessionData[] }>()

function revoke(id: number) {
    router.delete(`/profile/sessions/${id}`)
}

function revokeOthers() {
    router.delete('/profile/sessions/others')
}
</script>

<template>
    <Head :title="t('profile.sessions.headTitle')" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent">
                {{ t('profile.sessions.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('profile.sessions.description') }}</p>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-border bg-card divide-y divide-border/60">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <div
                v-for="session in sessions"
                :key="session.id"
                class="flex items-center justify-between px-4 py-4 transition-colors duration-100 hover:bg-muted/30"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-8 items-center justify-center rounded-full ring-1"
                        :class="session.is_current
                            ? 'bg-primary/10 ring-primary/20'
                            : 'bg-muted ring-border'"
                    >
                        <Monitor
                            class="size-4"
                            :class="session.is_current ? 'text-primary' : 'text-muted-foreground'"
                        />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ session.browser }} — {{ session.os }}</span>
                            <span
                                v-if="session.is_current"
                                class="inline-flex items-center rounded-full bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-500 ring-1 ring-green-500/20"
                            >{{ t('profile.sessions.current') }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ session.ip_address ?? t('profile.sessions.unknownIp') }} · {{ session.last_active_at }}
                        </div>
                    </div>
                </div>

                <Button
                    v-if="!session.is_current"
                    size="sm"
                    variant="destructive"
                    @click="revoke(session.id)"
                >
                    {{ t('profile.sessions.revoke') }}
                </Button>
            </div>

            <div
                v-if="sessions.length === 0"
                class="px-4 py-12 text-center"
            >
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-muted ring-1 ring-border">
                    <Monitor class="size-6 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">{{ t('profile.sessions.empty') }}</p>
            </div>
        </div>

        <div class="flex justify-end">
            <Button
                variant="outline"
                :disabled="sessions.filter(s => !s.is_current).length === 0"
                class="transition-colors duration-150 hover:border-destructive/40 hover:text-destructive"
                @click="revokeOthers"
            >
                {{ t('profile.sessions.revokeOthers') }}
            </Button>
        </div>
    </div>
</template>
