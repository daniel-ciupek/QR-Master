<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'

interface PresenceUser {
    id: number
    name: string
    initials: string
}

const props = defineProps<{ qrCodeId: number }>()
const { t } = useI18n()

const users = ref<PresenceUser[]>([])

const COLORS = [
    'bg-violet-500',
    'bg-blue-500',
    'bg-green-500',
    'bg-amber-500',
    'bg-rose-500',
    'bg-cyan-500',
]

function colorFor(id: number): string {
    return COLORS[id % COLORS.length] ?? COLORS[0]!
}

onMounted(() => {
    if (!window.Echo) return

    window.Echo.join(`qr-edit.${props.qrCodeId}`)
        .here((members: PresenceUser[]) => {
            users.value = members
        })
        .joining((member: PresenceUser) => {
            if (!users.value.find(u => u.id === member.id)) {
                users.value.push(member)
            }
        })
        .leaving((member: PresenceUser) => {
            users.value = users.value.filter(u => u.id !== member.id)
        })
})

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`qr-edit.${props.qrCodeId}`)
    }
})
</script>

<template>
    <div
        v-if="users.length > 0"
        class="flex items-center gap-1"
    >
        <span class="mr-1 text-xs text-muted-foreground">{{ t('collab.editing') }}</span>

        <TooltipProvider>
            <div class="flex -space-x-2">
                <Tooltip
                    v-for="user in users.slice(0, 5)"
                    :key="user.id"
                >
                    <TooltipTrigger as-child>
                        <div
                            class="flex size-7 items-center justify-center rounded-full border-2 border-background text-xs font-semibold text-white ring-1 ring-white/20"
                            :class="colorFor(user.id)"
                        >
                            {{ user.initials }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>{{ user.name }}</TooltipContent>
                </Tooltip>

                <div
                    v-if="users.length > 5"
                    class="flex size-7 items-center justify-center rounded-full border-2 border-background bg-muted text-xs font-semibold text-muted-foreground"
                >
                    +{{ users.length - 5 }}
                </div>
            </div>
        </TooltipProvider>
    </div>
</template>
