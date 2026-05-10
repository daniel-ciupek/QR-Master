<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    BarChart2,
    ChevronsUpDown,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Monitor,
    QrCode,
    Shield,
    User,
} from 'lucide-vue-next'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar'
import type { PageProps } from '@/types'

const page = usePage<PageProps>()
const user = page.props.auth.user

const initials = user
    ? user.name.split(' ').map((n: string) => n[0]).join('').slice(0, 2).toUpperCase()
    : '??'

const navMain = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Kody QR', href: '/qr-codes', icon: QrCode, disabled: true },
    { title: 'Analityka', href: '/analytics', icon: BarChart2, disabled: true },
]

const navAccount = [
    { title: 'Profil', href: '/profile/security', icon: Shield },
    { title: 'Sesje', href: '/profile/sessions', icon: Monitor },
    { title: 'Klucze dostępu', href: '/profile/passkeys', icon: KeyRound },
]

function isActive(href: string) {
    return page.url.startsWith(href)
}
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent"
                    >
                        <Link href="/dashboard">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <QrCode class="size-4" />
                            </div>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">QR-Master</span>
                                <span class="truncate text-xs text-muted-foreground">SaaS Platform</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Nawigacja</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navMain"
                            :key="item.href"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="item.title"
                                :class="{ 'pointer-events-none opacity-50': item.disabled }"
                            >
                                <Link :href="item.disabled ? '#' : item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                    />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>Konto</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navAccount"
                            :key="item.href"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="item.title"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                    />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent"
                            >
                                <Avatar class="size-8 rounded-lg">
                                    <AvatarFallback class="rounded-lg text-xs">{{ initials }}</AvatarFallback>
                                </Avatar>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ user?.name }}</span>
                                    <span class="truncate text-xs text-muted-foreground">{{ user?.email }}</span>
                                </div>
                                <ChevronsUpDown class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            side="top"
                            align="end"
                            class="w-56"
                        >
                            <DropdownMenuItem as-child>
                                <Link href="/profile/security">
                                    <User class="mr-2 size-4" />
                                    Profil
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive focus:text-destructive"
                                @click="router.post('/logout')"
                            >
                                <LogOut class="mr-2 size-4" />
                                Wyloguj się
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
