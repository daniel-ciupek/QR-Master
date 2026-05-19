<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { BarChart2, KeyRound, LayoutDashboard, LogOut, Monitor, Shield } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command'

const open = defineModel<boolean>('open', { default: false })
const { t } = useI18n()

function navigate(href: string) {
    open.value = false
    router.visit(href)
}

function logout() {
    open.value = false
    router.post('/logout')
}
</script>

<template>
    <CommandDialog v-model:open="open">
        <CommandInput :placeholder="t('ui.searchPlaceholder')" />
        <CommandList>
            <CommandEmpty>{{ t('ui.noResults') }}</CommandEmpty>

            <CommandGroup :heading="t('command.navigation')">
                <CommandItem
                    value="dashboard"
                    @select="navigate('/dashboard')"
                >
                    <LayoutDashboard class="mr-2 size-4" />
                    {{ t('nav.dashboard') }}
                </CommandItem>
                <CommandItem
                    value="profil bezpieczenstwo security"
                    @select="navigate('/profile/security')"
                >
                    <Shield class="mr-2 size-4" />
                    {{ t('command.securityProfile') }}
                </CommandItem>
                <CommandItem
                    value="sesje sessions"
                    @select="navigate('/profile/sessions')"
                >
                    <Monitor class="mr-2 size-4" />
                    {{ t('command.activeSessions') }}
                </CommandItem>
                <CommandItem
                    value="klucze dostepu passkeys"
                    @select="navigate('/profile/passkeys')"
                >
                    <KeyRound class="mr-2 size-4" />
                    {{ t('nav.passkeys') }}
                </CommandItem>
                <CommandItem
                    value="analityka analytics"
                    disabled
                >
                    <BarChart2 class="mr-2 size-4" />
                    {{ t('nav.analytics') }}
                    <span class="ml-auto text-xs text-muted-foreground">{{ t('nav.soon') }}</span>
                </CommandItem>
            </CommandGroup>

            <CommandSeparator />

            <CommandGroup :heading="t('command.account')">
                <CommandItem
                    value="wyloguj logout"
                    class="text-destructive data-[selected]:text-destructive"
                    @select="logout"
                >
                    <LogOut class="mr-2 size-4" />
                    {{ t('nav.logout') }}
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
