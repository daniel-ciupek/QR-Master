<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowRight, Building2, Crown, Plus, Settings, Users } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface TeamItem {
    id: number
    name: string
    slug: string
    role: string
    members_count: number
    is_current: boolean
    is_owner: boolean
}

/* eslint-disable-next-line vue/prop-name-casing */
defineProps<{
    teams: TeamItem[]
    current_team_id: number | null
}>()

function goToCreate(): void {
    router.visit(route('workspaces.create'))
}

function goToSettings(teamId: number): void {
    router.visit(route('workspaces.show', { team: teamId }))
}

function switchToTeam(teamId: number): void {
    router.post(route('workspace.switch', { team: teamId }))
}

function switchToPersonal(): void {
    router.post(route('workspace.switch'))
}

function roleLabel(role: string): string {
    const labels: Record<string, string> = {
        owner: t('workspace.role_owner'),
        admin: t('workspace.role_admin'),
        editor: t('workspace.role_editor'),
        viewer: t('workspace.role_viewer'),
    }
    return labels[role] ?? role
}

function roleBadgeVariant(role: string): 'default' | 'secondary' | 'outline' {
    if (role === 'owner') return 'default'
    if (role === 'admin') return 'secondary'
    return 'outline'
}
</script>

<template>
    <Head :title="t('workspace.workspaces')" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ t('workspace.workspaces') }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ t('workspace.workspaces_description') }}</p>
            </div>
            <Button @click="goToCreate">
                <Plus class="mr-2 h-4 w-4" />
                {{ t('workspace.new_workspace') }}
            </Button>
        </div>

        <!-- Personal workspace card -->
        <Card :class="current_team_id === null ? 'ring-2 ring-primary' : ''">
            <CardHeader class="flex flex-row items-center gap-4 pb-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-muted">
                    <Building2 class="h-5 w-5 text-muted-foreground" />
                </div>
                <div class="flex-1">
                    <CardTitle class="text-base">{{ t('workspace.personal') }}</CardTitle>
                    <CardDescription>{{ t('workspace.personal_description') }}</CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <Badge v-if="current_team_id === null" variant="default">
                        {{ t('workspace.active') }}
                    </Badge>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        @click="switchToPersonal"
                    >
                        {{ t('workspace.switch') }}
                        <ArrowRight class="ml-1 h-3 w-3" />
                    </Button>
                </div>
            </CardHeader>
        </Card>

        <!-- Team workspace cards -->
        <Card
            v-for="team in teams"
            :key="team.id"
            :class="team.is_current ? 'ring-2 ring-primary' : ''"
        >
            <CardHeader class="flex flex-row items-center gap-4 pb-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                    <Users class="h-5 w-5 text-primary" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <CardTitle class="truncate text-base">{{ team.name }}</CardTitle>
                        <Crown v-if="team.is_owner" class="h-3.5 w-3.5 shrink-0 text-yellow-500" />
                    </div>
                    <CardDescription class="mt-0.5 flex items-center gap-2">
                        <Badge :variant="roleBadgeVariant(team.role)" class="h-5 text-xs">
                            {{ roleLabel(team.role) }}
                        </Badge>
                        <span>·</span>
                        <span>{{ t('workspace.members_count', { count: team.members_count }) }}</span>
                    </CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <Badge v-if="team.is_current" variant="default">
                        {{ t('workspace.active') }}
                    </Badge>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        @click="switchToTeam(team.id)"
                    >
                        {{ t('workspace.switch') }}
                        <ArrowRight class="ml-1 h-3 w-3" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        @click="goToSettings(team.id)"
                    >
                        <Settings class="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
        </Card>

        <!-- Empty state -->
        <div v-if="teams.length === 0" class="py-12 text-center">
            <Users class="mx-auto h-12 w-12 text-muted-foreground/50" />
            <h3 class="mt-4 text-sm font-medium">{{ t('workspace.no_teams') }}</h3>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('workspace.no_teams_description') }}</p>
            <Button class="mt-4" @click="goToCreate">
                <Plus class="mr-2 h-4 w-4" />
                {{ t('workspace.new_workspace') }}
            </Button>
        </div>
    </div>
</template>
