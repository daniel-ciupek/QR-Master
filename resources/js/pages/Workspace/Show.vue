<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Crown, Shield, Eye, Pencil, Trash2, Users } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
    owner: { id: number; name: string }
    members: Member[]
    is_owner: boolean
    my_role: string
}

const props = defineProps<{ team: TeamDetail }>()

const editingName = ref(false)

const nameForm = useForm({
    name: props.team.name,
})

function saveName() {
    nameForm.patch(route('workspaces.update', { team: props.team.id }), {
        onSuccess: () => { editingName.value = false },
    })
}

function roleIcon(role: string) {
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
</script>

<template>
    <Head :title="team.name" />

    <div class="space-y-6 max-w-2xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ team.name }}</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ t('workspace.settings') }}</p>
            </div>
        </div>

        <!-- Name settings -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.general') }}</CardTitle>
                <CardDescription>{{ t('workspace.general_description') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label>{{ t('workspace.name') }}</Label>
                    <div v-if="!editingName" class="flex items-center gap-2">
                        <span class="text-sm">{{ team.name }}</span>
                        <Button
                            v-if="team.is_owner || team.my_role === 'admin'"
                            variant="ghost"
                            size="sm"
                            @click="editingName = true"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <form v-else class="flex items-center gap-2" @submit.prevent="saveName">
                        <Input
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
                    <p v-if="nameForm.errors.name" class="text-sm text-destructive">{{ nameForm.errors.name }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Members list -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-4 w-4" />
                        {{ t('workspace.members') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.members_description') }}</CardDescription>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('workspace.member_name') }}</TableHead>
                            <TableHead>{{ t('workspace.member_email') }}</TableHead>
                            <TableHead>{{ t('workspace.member_role') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="member in team.members" :key="member.id">
                            <TableCell class="font-medium">{{ member.name }}</TableCell>
                            <TableCell class="text-muted-foreground">{{ member.email }}</TableCell>
                            <TableCell>
                                <Badge :variant="roleBadgeVariant(member.role)" class="gap-1">
                                    <component :is="roleIcon(member.role)" class="h-3 w-3" />
                                    {{ t(`workspace.role_${member.role}`) }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Danger zone -->
        <Card v-if="team.is_owner" class="border-destructive/50">
            <CardHeader>
                <CardTitle class="text-destructive">{{ t('workspace.danger_zone') }}</CardTitle>
                <CardDescription>{{ t('workspace.danger_zone_description') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <Button
                    variant="destructive"
                    @click="$inertia.delete(route('workspaces.destroy', { team: team.id }))"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    {{ t('workspace.delete_workspace') }}
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
