<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { BarChart2, KeyRound, LayoutDashboard, LogOut, Monitor, Shield } from 'lucide-vue-next'
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
        <CommandInput placeholder="Szukaj lub wpisz komendę…" />
        <CommandList>
            <CommandEmpty>Brak wyników.</CommandEmpty>

            <CommandGroup heading="Nawigacja">
                <CommandItem
                    value="dashboard"
                    @select="navigate('/dashboard')"
                >
                    <LayoutDashboard class="mr-2 size-4" />
                    Dashboard
                </CommandItem>
                <CommandItem
                    value="profil bezpieczenstwo"
                    @select="navigate('/profile/security')"
                >
                    <Shield class="mr-2 size-4" />
                    Profil — Bezpieczeństwo
                </CommandItem>
                <CommandItem
                    value="sesje"
                    @select="navigate('/profile/sessions')"
                >
                    <Monitor class="mr-2 size-4" />
                    Aktywne sesje
                </CommandItem>
                <CommandItem
                    value="klucze dostepu passkeys"
                    @select="navigate('/profile/passkeys')"
                >
                    <KeyRound class="mr-2 size-4" />
                    Klucze dostępu
                </CommandItem>
                <CommandItem
                    value="analityka"
                    disabled
                >
                    <BarChart2 class="mr-2 size-4" />
                    Analityka
                    <span class="ml-auto text-xs text-muted-foreground">wkrótce</span>
                </CommandItem>
            </CommandGroup>

            <CommandSeparator />

            <CommandGroup heading="Konto">
                <CommandItem
                    value="wyloguj logout"
                    class="text-destructive data-[selected]:text-destructive"
                    @select="logout"
                >
                    <LogOut class="mr-2 size-4" />
                    Wyloguj się
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
