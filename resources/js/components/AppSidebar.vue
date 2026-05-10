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
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
const page = usePage<PageProps>()
const user = page.props.auth.user

const initials = user
    ? user.name.split(' ').map((n: string) => n[0]).join('').slice(0, 2).toUpperCase()
    : '??'

const navMain = [
    { titleKey: 'nav.dashboard', href: '/dashboard', icon: LayoutDashboard },
    { titleKey: 'nav.qrCodes', href: '/qr', icon: QrCode },
    { titleKey: 'nav.analytics', href: '/analytics', icon: BarChart2, disabled: true },
]

const navAccount = [
    { titleKey: 'nav.account', href: '/profile', icon: User },
    { titleKey: 'nav.security', href: '/profile/security', icon: Shield },
    { titleKey: 'nav.sessions', href: '/profile/sessions', icon: Monitor },
    { titleKey: 'nav.passkeys', href: '/profile/passkeys', icon: KeyRound },
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
                                <span class="truncate font-semibold">{{ t('app.name') }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ t('app.tagline') }}</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>{{ t('nav.dashboard') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navMain"
                            :key="item.href"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                                :class="{ 'pointer-events-none opacity-50': item.disabled }"
                            >
                                <Link :href="item.disabled ? '#' : item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                    />
                                    <span>{{ t(item.titleKey) }}</span>
                                    <span
                                        v-if="item.disabled"
                                        class="ml-auto text-[10px] text-muted-foreground"
                                    >{{ t('nav.soon') }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>{{ t('nav.profile') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navAccount"
                            :key="item.href"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4"
                                    />
                                    <span>{{ t(item.titleKey) }}</span>
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
                                <Link href="/profile">
                                    <User class="mr-2 size-4" />
                                    {{ t('nav.account') }}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive focus:text-destructive"
                                @click="router.post('/logout')"
                            >
                                <LogOut class="mr-2 size-4" />
                                {{ t('nav.logout') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
