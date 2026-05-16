<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ChevronDown, Crown, Eye, LogOut, Pencil, Shield, Trash2, UserMinus, Users } from 'lucide-vue-next'
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
    joined_at: string
}

interface TeamDetail {
    id: number
    name: string
    slug: string
    owner: { id: number; name: string } | null
    members: Member[]
    isOwner: boolean
    myRole: string
    myId: number
}

const props = defineProps<{ team: TeamDetail }>()

const editingName = ref(false)

const nameForm = useForm({ name: props.team.name })

const canManageMembers = props.team.myRole === 'owner' || props.team.myRole === 'admin'

const roles = ['owner', 'admin', 'editor', 'viewer'] as const

function saveName(): void {
    nameForm.patch(route('workspaces.update', { team: props.team.id }), {
        onSuccess: () => { editingName.value = false },
    })
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
    // Only owner can change another owner's role
    if (member.role === 'owner' && props.team.myRole !== 'owner') return false
    return true
}

function canRemoveMember(member: Member): boolean {
    if (member.id === props.team.myId) return false // use Leave instead
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

        <!-- Members list with role management -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="h-4 w-4" />
                    {{ t('workspace.members') }}
                </CardTitle>
                <CardDescription>{{ t('workspace.members_description') }}</CardDescription>
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
                                <!-- Role dropdown (only for authorized users) -->
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
                                            v-for="r in roles"
                                            :key="r"
                                            :class="{ 'bg-accent': member.role === r }"
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
                                <!-- Read-only badge for non-authorized or self -->
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

        <!-- Leave workspace (non-owners) -->
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

        <!-- Danger zone (owner only) -->
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
