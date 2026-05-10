<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

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
    <Head title="Aktywne sesje" />

    <div class="mx-auto max-w-2xl space-y-6 px-4 py-8">
        <div>
            <h1 class="text-2xl font-bold">Aktywne sesje</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Urządzenia zalogowane na Twoje konto. Zakończ sesje, których nie rozpoznajesz.
            </p>
        </div>

        <div class="divide-y rounded-lg border">
            <div
                v-for="session in sessions"
                :key="session.id"
                class="flex items-center justify-between px-4 py-4"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-sm">{{ session.browser }} — {{ session.os }}</span>
                        <Badge
                            v-if="session.is_current"
                            variant="default"
                        >Bieżąca</Badge>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ session.ip_address ?? 'Nieznany IP' }} · {{ session.last_active_at }}
                    </div>
                </div>

                <Button
                    v-if="!session.is_current"
                    size="sm"
                    variant="destructive"
                    @click="revoke(session.id)"
                >
                    Zakończ
                </Button>
            </div>

            <div
                v-if="sessions.length === 0"
                class="px-4 py-8 text-center text-sm text-muted-foreground"
            >
                Brak aktywnych sesji.
            </div>
        </div>

        <div class="flex justify-end">
            <Button
                variant="outline"
                :disabled="sessions.filter(s => !s.is_current).length === 0"
                @click="revokeOthers"
            >
                Zakończ wszystkie inne sesje
            </Button>
        </div>
    </div>
</template>
