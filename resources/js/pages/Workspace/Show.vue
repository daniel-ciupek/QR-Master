<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import {
    ArrowLeft, ChevronDown, ClipboardList, CreditCard, Crown, Eye, FileText, Globe,
    KeyRound, LogOut, Mail, Paintbrush, Pencil, Shield, ShieldCheck, Trash2,
    UserMinus, UserPlus, Users, X,
} from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface Member {
    id: number
    name: string
    email: string
    role: string
    joinedAt: string
}

interface PendingInvitation {
    id: number
    email: string
    role: string
    expiresAt: string
}

interface TeamDetail {
    id: number
    name: string
    slug: string
    owner: { id: number; name: string } | null
    members: Member[]
    pendingInvitations: PendingInvitation[]
    isOwner: boolean
    myRole: string
    myId: number
}

const props = defineProps<{ team: TeamDetail }>()

const editingName = ref(false)

const canManageMembers = props.team.myRole === 'owner' || props.team.myRole === 'admin'

const nameForm = useForm({ name: props.team.name })

const inviteForm = useForm({
    email: '',
    role: 'editor',
})

const roles = ['admin', 'editor', 'viewer'] as const

function saveName(): void {
    nameForm.patch(route('workspaces.update', { team: props.team.id }), {
        onSuccess: () => { editingName.value = false },
    })
}

function sendInvite(): void {
    inviteForm.post(route('workspaces.invitations.store', { team: props.team.id }), {
        onSuccess: () => { inviteForm.reset() },
        preserveScroll: true,
    })
}

function revokeInvitation(invitationId: number): void {
    router.delete(
        route('workspaces.invitations.destroy', { team: props.team.id, invitation: invitationId }),
        { preserveScroll: true },
    )
}

function changeRole(memberId: number, newRole: string): void {
    router.patch(
        route('workspaces.members.role', { team: props.team.id, user: memberId }),
        { role: newRole },
        { preserveScroll: true },
    )
}

function removeMember(memberId: number): void {
    if (!confirm(t('workspace.confirm_remove_member'))) return
    router.delete(
        route('workspaces.members.destroy', { team: props.team.id, user: memberId }),
        { preserveScroll: true },
    )
}

function leaveWorkspace(): void {
    if (!confirm(t('workspace.confirm_leave'))) return
    router.delete(route('workspaces.members.leave', { team: props.team.id }))
}

function deleteWorkspace(): void {
    if (!confirm(t('workspace.confirm_delete'))) return
    router.delete(route('workspaces.destroy', { team: props.team.id }))
}

function goToBilling(): void {
    router.visit(route('workspaces.billing.show', { team: props.team.slug }))
}

function goToBranding(): void {
    router.visit(route('workspaces.branding.show', { team: props.team.slug }))
}

function goToDpa(): void {
    router.visit(route('workspaces.dpa.show', { team: props.team.slug }))
}

function goToCompliance(): void {
    router.visit(route('workspaces.compliance.show', { team: props.team.slug }))
}

function goToAudit(): void {
    router.visit(route('workspaces.audit.show', { team: props.team.slug }))
}

function goToSso(): void {
    router.visit(route('workspaces.sso.show', { team: props.team.slug }))
}

function goToScim(): void {
    router.visit(route('workspaces.scim.show', { team: props.team.slug }))
}

function goToIpAllowlist(): void {
    router.visit(route('workspaces.ip-allowlist.show', { team: props.team.slug }))
}

function goToDataResidency(): void {
    router.visit(route('workspaces.data-residency.show', { team: props.team.slug }))
}

function goBack(): void {
    router.visit(route('workspaces.index'))
}

function roleIcon(role: string): typeof Crown {
    if (role === 'owner') return Crown
    if (role === 'admin') return Shield
    if (role === 'viewer') return Eye
    return Pencil
}

function roleBadgeVariant(role: string): 'default' | 'secondary' | 'outline' {
    if (role === 'owner') return 'default'
    if (role === 'admin') return 'secondary'
    return 'outline'
}

function canChangeRole(member: Member): boolean {
    if (!canManageMembers) return false
    if (member.role === 'owner' && props.team.myRole !== 'owner') return false
    return true
}

function canRemoveMember(member: Member): boolean {
    if (member.id === props.team.myId) return false
    if (member.role === 'owner') return false
    return canManageMembers
}

interface SettingsSection {
    key: string
    icon: typeof Paintbrush
    titleKey: string
    descKey: string
    action: () => void
    accentClass: string
    iconBgClass: string
    iconTextClass: string
    topBorderClass: string
}

const settingsSections: SettingsSection[] = [
    {
        key: 'branding',
        icon: Paintbrush,
        titleKey: 'workspace.branding.title',
        descKey: 'workspace.branding.desc',
        action: goToBranding,
        accentClass: 'hover:border-violet-400/40',
        iconBgClass: 'bg-primary/10',
        iconTextClass: 'text-primary',
        topBorderClass: 'via-primary/60',
    },
    {
        key: 'data-residency',
        icon: Globe,
        titleKey: 'workspace.data_residency.title',
        descKey: 'workspace.data_residency.desc',
        action: goToDataResidency,
        accentClass: 'hover:border-cyan-400/40',
        iconBgClass: 'bg-cyan-400/10',
        iconTextClass: 'text-cyan-400',
        topBorderClass: 'via-cyan-400/50',
    },
    {
        key: 'ip-allowlist',
        icon: Shield,
        titleKey: 'workspace.ip_allowlist.title',
        descKey: 'workspace.ip_allowlist.desc',
        action: goToIpAllowlist,
        accentClass: 'hover:border-cyan-400/40',
        iconBgClass: 'bg-cyan-400/10',
        iconTextClass: 'text-cyan-400',
        topBorderClass: 'via-cyan-400/50',
    },
    {
        key: 'scim',
        icon: Users,
        titleKey: 'workspace.scim.title',
        descKey: 'workspace.scim.desc',
        action: goToScim,
        accentClass: 'hover:border-primary/40',
        iconBgClass: 'bg-primary/10',
        iconTextClass: 'text-primary',
        topBorderClass: 'via-primary/60',
    },
    {
        key: 'sso',
        icon: KeyRound,
        titleKey: 'workspace.sso.title',
        descKey: 'workspace.sso.desc',
        action: goToSso,
        accentClass: 'hover:border-gold-500/40',
        iconBgClass: 'bg-gold-500/10',
        iconTextClass: 'text-gold-500',
        topBorderClass: 'via-gold-500/60',
    },
]

const adminSections: SettingsSection[] = [
    {
        key: 'audit',
        icon: ClipboardList,
        titleKey: 'workspace.audit.title',
        descKey: 'workspace.audit.desc',
        action: goToAudit,
        accentClass: 'hover:border-cyan-400/40',
        iconBgClass: 'bg-cyan-400/10',
        iconTextClass: 'text-cyan-400',
        topBorderClass: 'via-cyan-400/50',
    },
    {
        key: 'compliance',
        icon: ShieldCheck,
        titleKey: 'workspace.compliance.title',
        descKey: 'workspace.compliance.desc',
        action: goToCompliance,
        accentClass: 'hover:border-cyan-400/40',
        iconBgClass: 'bg-cyan-400/10',
        iconTextClass: 'text-cyan-400',
        topBorderClass: 'via-cyan-400/50',
    },
    {
        key: 'dpa',
        icon: FileText,
        titleKey: 'workspace.dpa.title',
        descKey: 'workspace.dpa.desc',
        action: goToDpa,
        accentClass: 'hover:border-primary/40',
        iconBgClass: 'bg-primary/10',
        iconTextClass: 'text-primary',
        topBorderClass: 'via-primary/60',
    },
]
</script>

<template>
    <Head :title="team.name" />

    <div class="space-y-6 p-4 md:p-6">
        <!-- Page header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    class="shrink-0 hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                    @click="goBack"
                >
                    <ArrowLeft class="size-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ team.name }}
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ t('workspace.settings') }}</p>
                </div>
            </div>
            <Crown v-if="team.isOwner" class="size-5 text-gold-500 shrink-0 hidden sm:block" />
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- General settings -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle>{{ t('workspace.general') }}</CardTitle>
                    <CardDescription>{{ t('workspace.general_description') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="workspace-name">{{ t('workspace.name') }}</Label>
                        <div v-if="!editingName" class="flex items-center gap-2">
                            <span class="text-sm">{{ team.name }}</span>
                            <Button
                                v-if="team.isOwner || team.myRole === 'admin'"
                                variant="ghost"
                                size="sm"
                                class="hover:text-primary transition-colors duration-150"
                                @click="editingName = true"
                            >
                                <Pencil class="size-3.5" />
                            </Button>
                        </div>
                        <form v-else class="flex items-center gap-2" @submit.prevent="saveName">
                            <Input
                                id="workspace-name"
                                v-model="nameForm.name"
                                autofocus
                                class="max-w-xs focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                maxlength="60"
                            />
                            <Button type="submit" size="sm" :disabled="nameForm.processing">
                                {{ t('common.save') }}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="hover:text-primary transition-colors duration-150"
                                @click="editingName = false"
                            >
                                {{ t('common.cancel') }}
                            </Button>
                        </form>
                        <p v-if="nameForm.errors.name" class="text-sm text-destructive">
                            {{ nameForm.errors.name }}
                        </p>
                    </div>
                </CardContent>
            </div>

            <!-- Invite member (Owner/Admin only) -->
            <div v-if="canManageMembers" class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                            <UserPlus class="size-3.5 text-cyan-400" />
                        </div>
                        {{ t('workspace.invite_member') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.invite_description') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="sendInvite">
                        <div class="flex-1 space-y-1.5">
                            <Label for="invite-email">{{ t('workspace.invite_email') }}</Label>
                            <Input
                                id="invite-email"
                                v-model="inviteForm.email"
                                type="email"
                                :placeholder="t('workspace.invite_email_placeholder')"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="inviteForm.errors.email" class="text-xs text-destructive">
                                {{ inviteForm.errors.email }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="invite-role">{{ t('workspace.member_role') }}</Label>
                            <select
                                id="invite-role"
                                v-model="inviteForm.role"
                                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50"
                            >
                                <option v-for="r in roles" :key="r" :value="r">
                                    {{ t(`workspace.role_${r}`) }}
                                </option>
                            </select>
                        </div>
                        <Button
                            type="submit"
                            :disabled="inviteForm.processing"
                            class="shrink-0 shadow-[0_0_16px_oklch(0.66_0.25_285/0.2)] hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)] transition-shadow duration-200"
                        >
                            <Mail class="mr-2 size-4" />
                            {{ t('workspace.send_invite') }}
                        </Button>
                    </form>
                </CardContent>
            </div>

            <!-- Pending invitations -->
            <div v-if="canManageMembers && team.pendingInvitations.length > 0" class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="text-sm font-medium">{{ t('workspace.pending_invitations') }}</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableBody>
                                <TableRow
                                    v-for="inv in team.pendingInvitations"
                                    :key="inv.id"
                                    class="hover:bg-muted/30 transition-colors duration-100"
                                >
                                    <TableCell class="text-sm">{{ inv.email }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="gap-1 text-xs">
                                            <component :is="roleIcon(inv.role)" class="size-3" />
                                            {{ t(`workspace.role_${inv.role}`) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-xs text-muted-foreground">
                                        {{ t('workspace.invite_expires') }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive transition-colors duration-150"
                                            :title="t('workspace.revoke_invite')"
                                            @click="revokeInvitation(inv.id)"
                                        >
                                            <X class="size-3.5" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </div>

            <!-- Members list -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <Users class="size-3.5 text-primary" />
                        </div>
                        {{ t('workspace.members') }}
                        <Badge variant="secondary" class="ml-1 bg-primary/10 text-primary border-primary/20">
                            {{ team.members.length }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{ t('workspace.member_name') }}</TableHead>
                                    <TableHead>{{ t('workspace.member_role') }}</TableHead>
                                    <TableHead class="w-12" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="member in team.members"
                                    :key="member.id"
                                    class="hover:bg-muted/30 transition-colors duration-100"
                                >
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ member.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu v-if="canChangeRole(member)">
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="outline" size="sm" class="gap-1.5 hover:border-primary/40 transition-colors duration-150">
                                                    <component :is="roleIcon(member.role)" class="size-3.5" />
                                                    {{ t(`workspace.role_${member.role}`) }}
                                                    <ChevronDown class="size-3 opacity-60" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="start">
                                                <DropdownMenuLabel>{{ t('workspace.change_role') }}</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    v-for="r in ['owner', 'admin', 'editor', 'viewer']"
                                                    :key="r"
                                                    :class="member.role === r ? 'bg-accent' : ''"
                                                    @click="changeRole(member.id, r)"
                                                >
                                                    <component :is="roleIcon(r)" class="mr-2 size-4" />
                                                    <div>
                                                        <p>{{ t(`workspace.role_${r}`) }}</p>
                                                        <p class="text-xs text-muted-foreground">
                                                            {{ t(`workspace.role_${r}_desc`) }}
                                                        </p>
                                                    </div>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <Badge v-else :variant="roleBadgeVariant(member.role)" class="gap-1">
                                            <component :is="roleIcon(member.role)" class="size-3" />
                                            {{ t(`workspace.role_${member.role}`) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            v-if="canRemoveMember(member)"
                                            variant="ghost"
                                            size="icon"
                                            class="size-8 text-muted-foreground hover:text-destructive transition-colors duration-150"
                                            :title="t('workspace.remove_member')"
                                            @click="removeMember(member.id)"
                                        >
                                            <UserMinus class="size-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </div>

            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <!-- Owner-only settings grid -->
            <div v-if="team.isOwner">
                <h2 class="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">
                    {{ t('workspace.settings') }}
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="section in settingsSections"
                        :key="section.key"
                        class="relative rounded-xl border border-border bg-card p-4 overflow-hidden cursor-pointer transition-all duration-200"
                        :class="section.accentClass"
                        @click="section.action()"
                    >
                        <div :class="`absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent ${section.topBorderClass} to-transparent`" />
                        <div class="flex items-start gap-3">
                            <div :class="`flex size-9 shrink-0 items-center justify-center rounded-lg ring-1 ${section.iconBgClass}`" style="ring-color: currentColor">
                                <component :is="section.icon" :class="`size-4 ${section.iconTextClass}`" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold">{{ t(section.titleKey) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ t(section.descKey) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin sections (audit, compliance, dpa) -->
            <div v-if="team.isOwner || team.myRole === 'admin'">
                <h2 class="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">
                    {{ t('workspace.compliance.title') }}
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="section in adminSections"
                        :key="section.key"
                        class="relative rounded-xl border border-border bg-card p-4 overflow-hidden cursor-pointer transition-all duration-200"
                        :class="section.accentClass"
                        @click="section.action()"
                    >
                        <div :class="`absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent ${section.topBorderClass} to-transparent`" />
                        <div class="flex items-start gap-3">
                            <div :class="`flex size-9 shrink-0 items-center justify-center rounded-lg ${section.iconBgClass}`">
                                <component :is="section.icon" :class="`size-4 ${section.iconTextClass}`" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold">{{ t(section.titleKey) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ t(section.descKey) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing (owner only) -->
            <div v-if="team.isOwner" class="relative rounded-xl border border-border bg-card p-4 overflow-hidden cursor-pointer transition-all duration-200 hover:border-gold-500/40" @click="goToBilling">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/60 to-transparent" />
                <div class="flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gold-500/10">
                        <CreditCard class="size-4 text-gold-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold">{{ t('workspace.billing.billing_nav') }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ t('workspace.billing.currentPlanDesc') }}</p>
                    </div>
                </div>
            </div>

            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <!-- Leave workspace -->
            <div v-if="!team.isOwner" class="relative rounded-xl border border-border bg-card overflow-hidden">
                <CardHeader>
                    <CardTitle>{{ t('workspace.leave_workspace') }}</CardTitle>
                    <CardDescription>{{ t('workspace.leave_description') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        variant="outline"
                        class="hover:border-destructive/50 hover:text-destructive transition-colors duration-150"
                        @click="leaveWorkspace"
                    >
                        <LogOut class="mr-2 size-4" />
                        {{ t('workspace.leave_workspace') }}
                    </Button>
                </CardContent>
            </div>

            <!-- Danger zone -->
            <div v-if="team.isOwner" class="relative rounded-xl border border-destructive/40 bg-card overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/50 to-transparent" />
                <CardHeader>
                    <CardTitle class="text-destructive">{{ t('workspace.danger_zone') }}</CardTitle>
                    <CardDescription>{{ t('workspace.danger_zone_description') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="destructive" @click="deleteWorkspace">
                        <Trash2 class="mr-2 size-4" />
                        {{ t('workspace.delete_workspace') }}
                    </Button>
                </CardContent>
            </div>
        </div>
    </div>
</template>
