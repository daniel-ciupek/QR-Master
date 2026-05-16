<script setup lang="ts">
import { Bell, Check, Trash2 } from 'lucide-vue-next'
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '@/types'

interface AppNotification {
    id: string
    type: string
    data: Record<string, unknown>
    read_at: string | null
    created_at: string
}

const { t } = useI18n()
const page = usePage<PageProps>()

const notifications = ref<AppNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)

async function fetchNotifications(): Promise<void> {
    loading.value = true
    try {
        const res = await fetch(route('notifications.index'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        const data = await res.json()
        notifications.value = data.notifications ?? []
        unreadCount.value = data.unread_count ?? 0
    } finally {
        loading.value = false
    }
}

async function markAsRead(id: string): Promise<void> {
    await fetch(route('notifications.read', { id }), {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    const n = notifications.value.find(n => n.id === id)
    if (n) n.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
}

async function markAllAsRead(): Promise<void> {
    await fetch(route('notifications.read-all'), {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    notifications.value.forEach(n => { n.read_at = n.read_at ?? new Date().toISOString() })
    unreadCount.value = 0
}

async function remove(id: string): Promise<void> {
    await fetch(route('notifications.destroy', { id }), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) {
        const item = notifications.value[idx]
        if (item && !item.read_at) unreadCount.value = Math.max(0, unreadCount.value - 1)
        notifications.value.splice(idx, 1)
    }
}

function notificationLabel(n: AppNotification): string {
    const type = n.data.type as string | undefined
    if (type === 'scan_anomaly') return t('notifications.scanAnomaly', { title: n.data.qr_code_title as string })
    if (type === 'plan_limit_reached') return t('notifications.planLimit', { type: n.data.limit_type as string })
    return t('notifications.system')
}

function formatTime(iso: string): string {
    const d = new Date(iso)
    const diff = Math.floor((Date.now() - d.getTime()) / 1000)
    if (diff < 60) return t('notifications.justNow')
    if (diff < 3600) return t('notifications.minutesAgo', { n: Math.floor(diff / 60) })
    if (diff < 86400) return t('notifications.hoursAgo', { n: Math.floor(diff / 3600) })
    return d.toLocaleDateString()
}

onMounted(() => {
    fetchNotifications()

    const userId = (page.props.auth.user as { id: number } | null)?.id
    if (userId && window.Echo) {
        window.Echo.private(`App.Models.User.${userId}`)
            .notification(() => {
                fetchNotifications()
            })
    }
})

onUnmounted(() => {
    const userId = (page.props.auth.user as { id: number } | null)?.id
    if (userId && window.Echo) {
        window.Echo.leave(`App.Models.User.${userId}`)
    }
})
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-8"
                :aria-label="t('notifications.title')"
            >
                <Bell class="size-4" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-destructive-foreground"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="w-80"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-3 py-2">
                <span class="text-sm font-semibold">{{ t('notifications.title') }}</span>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 text-xs"
                    @click="markAllAsRead"
                >
                    <Check class="mr-1 size-3" />
                    {{ t('notifications.markAllRead') }}
                </Button>
            </div>

            <DropdownMenuSeparator />

            <!-- List -->
            <div class="max-h-80 overflow-y-auto">
                <div
                    v-if="loading"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('notifications.loading') }}
                </div>

                <div
                    v-else-if="notifications.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('notifications.empty') }}
                </div>

                <div
                    v-for="n in notifications"
                    :key="n.id"
                    class="group flex items-start gap-2 px-3 py-2.5 transition-colors hover:bg-muted/50"
                    :class="{ 'bg-muted/30': !n.read_at }"
                >
                    <!-- Unread dot -->
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="n.read_at ? 'bg-transparent' : 'bg-primary'"
                    />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm">{{ notificationLabel(n) }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatTime(n.created_at) }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex shrink-0 gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                        <Button
                            v-if="!n.read_at"
                            variant="ghost"
                            size="icon"
                            class="size-6"
                            :title="t('notifications.markRead')"
                            @click.stop="markAsRead(n.id)"
                        >
                            <Check class="size-3" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-6"
                            :title="t('notifications.delete')"
                            @click.stop="remove(n.id)"
                        >
                            <Trash2 class="size-3" />
                        </Button>
                    </div>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
