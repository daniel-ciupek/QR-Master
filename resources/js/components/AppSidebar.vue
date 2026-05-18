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
    { titleKey: 'nav.dashboard', href: '/dashboard', icon: LayoutDashboard, iconActive: 'text-violet-400', iconInactive: 'text-violet-400/45' },
    { titleKey: 'nav.qrCodes',   href: '/qr',        icon: QrCode,          iconActive: 'text-cyan-400',   iconInactive: 'text-cyan-400/45' },
    { titleKey: 'nav.analytics', href: '/analytics', icon: BarChart2,        iconActive: 'text-gold-500',   iconInactive: 'text-gold-500/45' },
]

const navAccount = [
    { titleKey: 'nav.account',  href: '/profile',           icon: User,     iconActive: 'text-primary',     iconInactive: 'text-primary/45' },
    { titleKey: 'nav.security', href: '/profile/security',  icon: Shield,   iconActive: 'text-red-400',     iconInactive: 'text-red-400/45' },
    { titleKey: 'nav.sessions', href: '/profile/sessions',  icon: Monitor,  iconActive: 'text-emerald-400', iconInactive: 'text-emerald-400/45' },
    { titleKey: 'nav.passkeys', href: '/profile/passkeys',  icon: KeyRound, iconActive: 'text-sky-400',     iconInactive: 'text-sky-400/45' },
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
                        class="data-[state=open]:bg-sidebar-accent hover:bg-sidebar-accent/50 transition-colors duration-150"
                    >
                        <Link href="/dashboard">
                            <!-- Logo z pulsing glow -->
                            <div class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-violet-500 via-primary to-cyan-500 text-primary-foreground animate-pulse-glow">
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
                                <!-- Gradient tekst nazwy -->
                                <span class="truncate font-bold bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">
                                    {{ branding?.brand_name || t('app.name') }}
                                </span>
                                <span class="truncate text-xs text-muted-foreground/70">{{ t('app.tagline') }}</span>
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
                            <!-- Gradient active background -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute inset-0 rounded-lg bg-gradient-to-r from-primary/15 via-primary/8 to-transparent pointer-events-none"
                            />
                            <!-- Gradient left accent bar -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-6 rounded-full bg-gradient-to-b from-violet-400 via-primary to-cyan-400 shadow-[0_0_8px_oklch(0.66_0.25_285/0.7)]"
                            />
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                                class="transition-all duration-150 hover:bg-sidebar-accent/60"
                                :class="isActive(item.href) ? 'font-medium' : ''"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4 transition-colors duration-150"
                                        :class="isActive(item.href) ? item.iconActive : item.iconInactive"
                                    />
                                    <span :class="isActive(item.href) ? 'text-foreground' : 'text-muted-foreground'">
                                        {{ t(item.titleKey) }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Section divider -->
            <div class="mx-3 h-px bg-gradient-to-r from-transparent via-primary/20 to-transparent" />

            <SidebarGroup>
                <SidebarGroupLabel class="text-xs uppercase tracking-wider text-muted-foreground/60 font-semibold">{{ t('nav.profile') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navAccount"
                            :key="item.href"
                            class="relative"
                        >
                            <!-- Gradient active background -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute inset-0 rounded-lg bg-gradient-to-r from-primary/15 via-primary/8 to-transparent pointer-events-none"
                            />
                            <!-- Gradient left accent bar -->
                            <div
                                v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-6 rounded-full bg-gradient-to-b from-violet-400 via-primary to-cyan-400 shadow-[0_0_8px_oklch(0.66_0.25_285/0.7)]"
                            />
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="t(item.titleKey)"
                                class="transition-all duration-150 hover:bg-sidebar-accent/60"
                                :class="isActive(item.href) ? 'font-medium' : ''"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4 transition-colors duration-150"
                                        :class="isActive(item.href) ? item.iconActive : item.iconInactive"
                                    />
                                    <span :class="isActive(item.href) ? 'text-foreground' : 'text-muted-foreground'">
                                        {{ t(item.titleKey) }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="relative border-t border-transparent">
            <!-- Gradient top separator -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent transition-colors duration-150 group"
                            >
                                <!-- Avatar z gradient ring na hover -->
                                <div class="relative size-8 shrink-0">
                                    <div class="absolute -inset-0.5 rounded-lg bg-gradient-to-br from-violet-500 via-primary to-cyan-400 opacity-0 group-hover:opacity-60 transition-opacity duration-300" />
                                    <Avatar class="relative size-8 rounded-lg ring-1 ring-primary/30">
                                        <AvatarFallback class="rounded-lg bg-gradient-to-br from-primary/30 to-violet-500/20 text-xs font-semibold text-primary">{{ initials }}</AvatarFallback>
                                    </Avatar>
                                </div>
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
