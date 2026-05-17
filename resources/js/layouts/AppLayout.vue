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
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger class="-ml-1" />
                <Separator
                    orientation="vertical"
                    class="mr-2 h-4"
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
                            class="size-8"
                        >
                            <Globe class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            v-for="lang in ['pl', 'en'] as const"
                            :key="lang"
                            :class="{ 'font-semibold': locale === lang }"
                            @click="setLocale(lang)"
                        >
                            {{ t(`lang.${lang}`) }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

            </header>

            <!-- Page content -->
            <main class="flex flex-1 flex-col gap-4 p-6">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>

    <ChatWidget />
    <Toaster />
</template>
