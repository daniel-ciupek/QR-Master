<script setup lang="ts">
import { Globe, Monitor, Moon, Search, Sun } from 'lucide-vue-next'
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppSidebar from '@/components/AppSidebar.vue'
import CommandPalette from '@/components/CommandPalette.vue'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Toaster } from '@/components/ui/sonner'
import { useColorMode } from '@/composables/useColorMode'
import { useLocale } from '@/composables/useLocale'

defineProps<{ title?: string }>()

const { t } = useI18n()
const { mode, setMode } = useColorMode()
const { locale, setLocale } = useLocale()

const commandOpen = ref(false)

function onKeydown(e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        commandOpen.value = !commandOpen.value
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
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

                <!-- Dark mode toggle -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                        >
                            <Sun
                                v-if="mode === 'light'"
                                class="size-4"
                            />
                            <Moon
                                v-else-if="mode === 'dark'"
                                class="size-4"
                            />
                            <Monitor
                                v-else
                                class="size-4"
                            />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="setMode('light')">
                            <Sun class="mr-2 size-4" />
                            {{ t('theme.light') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="setMode('dark')">
                            <Moon class="mr-2 size-4" />
                            {{ t('theme.dark') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="setMode('system')">
                            <Monitor class="mr-2 size-4" />
                            {{ t('theme.system') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

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
                            :class="{ 'font-semibold': locale === 'pl' }"
                            @click="setLocale('pl')"
                        >
                            {{ t('lang.pl') }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            :class="{ 'font-semibold': locale === 'en' }"
                            @click="setLocale('en')"
                        >
                            {{ t('lang.en') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Cmd+K trigger -->
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 gap-2 text-muted-foreground"
                    @click="commandOpen = true"
                >
                    <Search class="size-3.5" />
                    <span class="hidden text-xs sm:inline">{{ t('ui.search') }}</span>
                    <kbd class="hidden rounded border bg-muted px-1.5 py-0.5 text-[10px] font-mono sm:inline">
                        ⌘K
                    </kbd>
                </Button>
            </header>

            <!-- Page content -->
            <main class="flex flex-1 flex-col gap-4 p-6">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>

    <CommandPalette v-model:open="commandOpen" />
    <Toaster />
</template>
