<script setup lang="ts">
import { Globe } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import AppSidebar from '@/components/AppSidebar.vue'
import ChatWidget from '@/components/ChatWidget.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Toaster } from '@/components/ui/sonner'
import { useLocale } from '@/composables/useLocale'

defineProps<{ title?: string }>()

const { t } = useI18n()
const { locale, setLocale } = useLocale()

</script>

<template>
    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- Topbar -->
            <header class="relative flex h-14 shrink-0 items-center gap-2 border-b border-border/40 bg-background/85 px-4 backdrop-blur-md">
                <!-- Stronger gradient bottom border -->
                <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-primary/40 via-50% to-cyan-400/20 to-transparent" />
                <!-- Ambient top glow -->
                <div class="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-primary/[0.04] to-transparent pointer-events-none" />

                <SidebarTrigger class="-ml-1 transition-all duration-150 hover:text-primary hover:scale-110" />
                <Separator
                    orientation="vertical"
                    class="mr-2 h-4 opacity-50"
                />

                <div class="flex-1" />

                <!-- Notification bell -->
                <NotificationBell />

                <!-- Language switcher -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8 transition-all duration-150 hover:text-primary hover:bg-primary/10"
                        >
                            <Globe class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            v-for="lang in ['pl', 'en'] as const"
                            :key="lang"
                            :class="{ 'font-semibold text-primary': locale === lang }"
                            @click="setLocale(lang)"
                        >
                            {{ t(`lang.${lang}`) }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

            </header>

            <!-- Page content -->
            <main class="flex flex-1 flex-col gap-4 overflow-x-hidden p-4 md:p-6">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>

    <ChatWidget />
    <Toaster />
</template>
