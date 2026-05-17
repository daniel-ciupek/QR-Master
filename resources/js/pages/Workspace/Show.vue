<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ChevronDown, ClipboardList, CreditCard, Crown, Eye, FileText, KeyRound, LogOut, Mail, Paintbrush, Pencil, Shield, ShieldCheck, Trash2, UserMinus, UserPlus, Users, X } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
</script>

<template>
    <Head :title="team.name" />

    <div class="max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ team.name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('workspace.settings') }}</p>
        </div>

        <!-- General settings -->
        <Card>
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
                            @click="editingName = true"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <form v-else class="flex items-center gap-2" @submit.prevent="saveName">
                        <Input
                            id="workspace-name"
                            v-model="nameForm.name"
                            autofocus
                            class="max-w-xs"
                            maxlength="60"
                        />
                        <Button type="submit" size="sm" :disabled="nameForm.processing">
                            {{ t('common.save') }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
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
        </Card>

        <!-- Invite member (Owner/Admin only) -->
        <Card v-if="canManageMembers">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <UserPlus class="h-4 w-4" />
                    {{ t('workspace.invite_member') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.invite_description') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <form class="flex items-end gap-2" @submit.prevent="sendInvite">
                    <div class="flex-1 space-y-1.5">
                        <Label for="invite-email">{{ t('workspace.invite_email') }}</Label>
                        <Input
                            id="invite-email"
                            v-model="inviteForm.email"
                            type="email"
                            :placeholder="t('workspace.invite_email_placeholder')"
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
                            class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50"
                        >
                            <option v-for="r in roles" :key="r" :value="r">
                                {{ t(`workspace.role_${r}`) }}
                            </option>
                        </select>
                    </div>
                    <Button type="submit" :disabled="inviteForm.processing">
                        <Mail class="mr-2 h-4 w-4" />
                        {{ t('workspace.send_invite') }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <!-- Pending invitations -->
        <Card v-if="canManageMembers && team.pendingInvitations.length > 0">
            <CardHeader>
                <CardTitle class="text-sm font-medium">{{ t('workspace.pending_invitations') }}</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableBody>
                        <TableRow v-for="inv in team.pendingInvitations" :key="inv.id">
                            <TableCell class="text-sm">{{ inv.email }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" class="gap-1 text-xs">
                                    <component :is="roleIcon(inv.role)" class="h-3 w-3" />
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
                                    class="h-7 w-7 text-muted-foreground hover:text-destructive"
                                    :title="t('workspace.revoke_invite')"
                                    @click="revokeInvitation(inv.id)"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Members list -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="h-4 w-4" />
                    {{ t('workspace.members') }}
                    <Badge variant="secondary" class="ml-1">{{ team.members.length }}</Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('workspace.member_name') }}</TableHead>
                            <TableHead>{{ t('workspace.member_role') }}</TableHead>
                            <TableHead class="w-12" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="member in team.members" :key="member.id">
                            <TableCell>
                                <div>
                                    <p class="font-medium">{{ member.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                                </div>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu v-if="canChangeRole(member)">
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm" class="gap-1.5">
                                            <component :is="roleIcon(member.role)" class="h-3.5 w-3.5" />
                                            {{ t(`workspace.role_${member.role}`) }}
                                            <ChevronDown class="h-3 w-3 opacity-60" />
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
                                            <component :is="roleIcon(r)" class="mr-2 h-4 w-4" />
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
                                    <component :is="roleIcon(member.role)" class="h-3 w-3" />
                                    {{ t(`workspace.role_${member.role}`) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Button
                                    v-if="canRemoveMember(member)"
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                    :title="t('workspace.remove_member')"
                                    @click="removeMember(member.id)"
                                >
                                    <UserMinus class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Branding / White-label -->
        <Card v-if="team.isOwner || team.myRole === 'admin'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Paintbrush class="size-4" />
                    {{ t('workspace.branding.title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.branding.desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToBranding">
                    <Paintbrush class="mr-2 h-4 w-4" />
                    {{ t('workspace.branding.manage') }}
                </Button>
            </CardContent>
        </Card>

        <!-- SSO -->
        <Card v-if="team.isOwner">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <KeyRound class="size-4" />
                    {{ t('workspace.sso.title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.sso.desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToSso">
                    <KeyRound class="mr-2 h-4 w-4" />
                    {{ t('workspace.sso.manage') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Audit Report -->
        <Card v-if="team.isOwner || team.myRole === 'admin'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ClipboardList class="size-4" />
                    {{ t('workspace.audit.title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.audit.desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToAudit">
                    <ClipboardList class="mr-2 h-4 w-4" />
                    {{ t('workspace.audit.manage') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Compliance Dashboard -->
        <Card v-if="team.isOwner || team.myRole === 'admin'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShieldCheck class="size-4" />
                    {{ t('workspace.compliance.title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.compliance.desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToCompliance">
                    <ShieldCheck class="mr-2 h-4 w-4" />
                    {{ t('workspace.compliance.manage') }}
                </Button>
            </CardContent>
        </Card>

        <!-- DPA -->
        <Card v-if="team.isOwner || team.myRole === 'admin'">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <FileText class="size-4" />
                    {{ t('workspace.dpa.title') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.dpa.desc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToDpa">
                    <FileText class="mr-2 h-4 w-4" />
                    {{ t('workspace.dpa.manage') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Billing -->
        <Card v-if="team.isOwner">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CreditCard class="size-4" />
                    {{ t('workspace.billing.billing_nav') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.billing.currentPlanDesc') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="goToBilling">
                    <CreditCard class="mr-2 h-4 w-4" />
                    {{ t('workspace.billing.manageSubscription') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Leave workspace -->
        <Card v-if="!team.isOwner">
            <CardHeader>
                <CardTitle>{{ t('workspace.leave_workspace') }}</CardTitle>
                <CardDescription>{{ t('workspace.leave_description') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="outline" @click="leaveWorkspace">
                    <LogOut class="mr-2 h-4 w-4" />
                    {{ t('workspace.leave_workspace') }}
                </Button>
            </CardContent>
        </Card>

        <!-- Danger zone -->
        <Card v-if="team.isOwner" class="border-destructive/50">
            <CardHeader>
                <CardTitle class="text-destructive">{{ t('workspace.danger_zone') }}</CardTitle>
                <CardDescription>{{ t('workspace.danger_zone_description') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="destructive" @click="deleteWorkspace">
                    <Trash2 class="mr-2 h-4 w-4" />
                    {{ t('workspace.delete_workspace') }}
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
