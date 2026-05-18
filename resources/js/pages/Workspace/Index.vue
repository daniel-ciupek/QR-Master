<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowRight, Building2, Crown, Plus, Settings, Users } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CardDescription, CardTitle } from '@/components/ui/card'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

interface TeamItem {
    id: number
    name: string
    slug: string
    role: string
    membersCount: number
    isCurrent: boolean
    isOwner: boolean
}

defineProps<{
    teams: TeamItem[]
    currentTeamId: number | null
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

    <div class="space-y-6 p-4 md:p-6">
        <!-- Page header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.workspaces') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ t('workspace.workspaces_description') }}</p>
            </div>
            <Button
                class="shrink-0 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                @click="goToCreate"
            >
                <Plus class="mr-2 size-4" />
                <span>{{ t('workspace.new_workspace') }}</span>
            </Button>
        </div>

        <!-- Personal workspace card -->
        <div
            class="relative rounded-xl border bg-card p-4 overflow-hidden transition-all duration-200 hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)]"
            :class="currentTeamId === null ? 'border-primary/50 shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)]' : 'border-border'"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted ring-1 ring-border">
                    <Building2 class="size-5 text-muted-foreground" />
                </div>
                <div class="flex-1 min-w-0">
                    <CardTitle class="text-base">{{ t('workspace.personal') }}</CardTitle>
                    <CardDescription class="mt-0.5">{{ t('workspace.personal_description') }}</CardDescription>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Badge v-if="currentTeamId === null" variant="default" class="bg-primary/20 text-primary border-primary/30">
                        {{ t('workspace.active') }}
                    </Badge>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        class="hover:border-primary/40 transition-colors duration-150"
                        @click="switchToPersonal"
                    >
                        {{ t('workspace.switch') }}
                        <ArrowRight class="ml-1 size-3" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Team workspace cards -->
        <div
            v-for="team in teams"
            :key="team.id"
            class="relative rounded-xl border bg-card p-4 overflow-hidden transition-all duration-200 hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)]"
            :class="team.isCurrent ? 'border-primary/50 shadow-[0_0_20px_oklch(0.66_0.25_285/0.1)]' : 'border-border'"
        >
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <Users class="size-5 text-primary" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-base font-semibold">{{ team.name }}</span>
                        <Crown v-if="team.isOwner" class="size-3.5 shrink-0 text-gold-500" />
                    </div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <Badge :variant="roleBadgeVariant(team.role)" class="h-5 text-xs">
                            {{ roleLabel(team.role) }}
                        </Badge>
                        <span class="text-muted-foreground/50">·</span>
                        <span class="text-xs">{{ t('workspace.members_count', { count: team.membersCount }) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Badge v-if="team.isCurrent" variant="default" class="bg-primary/20 text-primary border-primary/30">
                        {{ t('workspace.active') }}
                    </Badge>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        class="hover:border-primary/40 transition-colors duration-150"
                        @click="switchToTeam(team.id)"
                    >
                        {{ t('workspace.switch') }}
                        <ArrowRight class="ml-1 size-3" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                        @click="goToSettings(team.id)"
                    >
                        <Settings class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="teams.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 mb-4">
                <Users class="size-8 text-primary" />
            </div>
            <h3 class="text-lg font-semibold mb-1">{{ t('workspace.no_teams') }}</h3>
            <p class="text-sm text-muted-foreground max-w-sm">{{ t('workspace.no_teams_description') }}</p>
            <Button
                class="mt-6 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                @click="goToCreate"
            >
                <Plus class="mr-2 size-4" />
                {{ t('workspace.new_workspace') }}
            </Button>
        </div>
    </div>
</template>
