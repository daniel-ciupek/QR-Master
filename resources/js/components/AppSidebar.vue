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
const branding = page.props.branding

const initials = user
    ? user.name.split(' ').map((n: string) => n[0]).join('').slice(0, 2).toUpperCase()
    : '??'

const navMain = [
    { titleKey: 'nav.dashboard', href: '/dashboard', icon: LayoutDashboard },
    { titleKey: 'nav.qrCodes', href: '/qr', icon: QrCode },
    { titleKey: 'nav.analytics', href: '/analytics', icon: BarChart2 },
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
        <SidebarHeader class="border-b border-border/40">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent"
                    >
                        <Link href="/dashboard">
                            <div class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-violet-500 to-primary text-primary-foreground shadow-[0_0_12px_oklch(0.66_0.25_285/0.4)]">
                                <img
                                    v-if="branding?.logo_url"
                                    :src="branding.logo_url"
                                    alt="logo"
                                    class="size-full object-contain"
                                >
                                <QrCode
                                    v-else
                                    class="size-4"
                                />
                            </div>
                            <div class="grid flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-semibold">{{ branding?.brand_name || t('app.name') }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ t('app.tagline') }}</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel class="text-xs uppercase tracking-wider text-muted-foreground/60 font-semibold">{{ t('nav.dashboard') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navMain"
                            :key="item.href"
                            class="relative"
                        >
                            <!-- Left accent bar for active item -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full bg-primary"
                            />
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                                class="transition-colors duration-150"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        :class="isActive(item.href) ? 'text-primary' : 'text-muted-foreground'"
                                        class="size-4 transition-colors duration-150"
                                    />
                                    <span>{{ t(item.titleKey) }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Section divider -->
            <div class="mx-3 h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <SidebarGroup>
                <SidebarGroupLabel class="text-xs uppercase tracking-wider text-muted-foreground/60 font-semibold">{{ t('nav.profile') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navAccount"
                            :key="item.href"
                            class="relative"
                        >
                            <!-- Left accent bar for active item -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full bg-primary"
                            />
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                                class="transition-colors duration-150"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        :class="isActive(item.href) ? 'text-primary' : 'text-muted-foreground'"
                                        class="size-4 transition-colors duration-150"
                                    />
                                    <span>{{ t(item.titleKey) }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-border/40">
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent transition-colors duration-150"
                            >
                                <Avatar class="size-8 rounded-lg ring-1 ring-primary/30">
                                    <AvatarFallback class="rounded-lg bg-primary/20 text-xs font-semibold text-primary">{{ initials }}</AvatarFallback>
                                </Avatar>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ user?.name }}</span>
                                    <span class="truncate text-xs text-muted-foreground">{{ user?.email }}</span>
                                </div>
                                <ChevronsUpDown class="ml-auto size-4 text-muted-foreground" />
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
