<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Building2, CheckCircle, Clock, Shield } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const { t } = useI18n()

interface InvitationProps {
    token: string
    teamName: string
    inviterName: string
    role: string
    email: string
    expiresAt: string
}

const props = defineProps<{
    invitation: InvitationProps
    isLoggedIn: boolean
    userEmail: string | null
}>()

function accept(): void {
    router.post(route('workspaces.invitations.accept', { token: props.invitation.token }))
}

function goToLogin(): void {
    router.visit(route('login', { invitation: props.invitation.token }))
}

function goToRegister(): void {
    router.visit(route('register', { invitation: props.invitation.token }))
}

const emailMatches = props.userEmail?.toLowerCase() === props.invitation.email.toLowerCase()
</script>

<template>
    <Head :title="t('workspace.accept_invitation_title')" />

    <div class="flex min-h-screen items-center justify-center bg-background p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                    <Building2 class="h-7 w-7 text-primary" />
                </div>
                <CardTitle class="text-xl">{{ t('workspace.accept_invitation_title') }}</CardTitle>
                <CardDescription>
                    {{ t('workspace.accept_invitation_intro', {
                        inviter: invitation.inviterName,
                        team: invitation.teamName,
                    }) }}
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <!-- Invitation details -->
                <div class="rounded-lg border bg-muted/40 p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.workspace') }}</span>
                        <span class="font-medium">{{ invitation.teamName }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.member_role') }}</span>
                        <Badge variant="secondary" class="gap-1">
                            <Shield class="h-3 w-3" />
                            {{ invitation.role }}
                        </Badge>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.invite_email') }}</span>
                        <span class="font-mono text-xs">{{ invitation.email }}</span>
                    </div>
                </div>

                <!-- Warning if logged in with wrong email -->
                <div
                    v-if="isLoggedIn && !emailMatches"
                    class="flex items-start gap-2 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200"
                >
                    <Clock class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>{{ t('workspace.invite_wrong_email', { email: invitation.email }) }}</p>
                </div>
            </CardContent>

            <CardFooter class="flex flex-col gap-2">
                <!-- Logged in with correct email -->
                <template v-if="isLoggedIn && emailMatches">
                    <Button class="w-full" @click="accept">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        {{ t('workspace.accept_invitation') }}
                    </Button>
                </template>

                <!-- Not logged in -->
                <template v-else-if="!isLoggedIn">
                    <p class="mb-1 text-center text-sm text-muted-foreground">
                        {{ t('workspace.invite_login_prompt') }}
                    </p>
                    <Button class="w-full" @click="goToLogin">
                        {{ t('workspace.invite_login') }}
                    </Button>
                    <Button variant="outline" class="w-full" @click="goToRegister">
                        {{ t('workspace.invite_register') }}
                    </Button>
                </template>

                <!-- Logged in with wrong email -->
                <template v-else>
                    <Button variant="outline" class="w-full" @click="goToLogin">
                        {{ t('workspace.invite_switch_account') }}
                    </Button>
                </template>
            </CardFooter>
        </Card>
    </div>
</template>
