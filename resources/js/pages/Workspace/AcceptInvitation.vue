<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { Building2, CheckCircle, Clock, QrCode, Shield } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

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

    <!-- Full-screen wrapper with premium dot-grid background -->
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-background p-4">
        <!-- Dot grid -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.96_0.008_280/0.04)_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none" />
        <!-- Radial glow -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_40%,oklch(0.66_0.25_285/0.07),transparent)] pointer-events-none" />

        <!-- Logo mark -->
        <div class="relative mb-8 flex flex-col items-center gap-3">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 shadow-[0_0_24px_oklch(0.66_0.25_285/0.2)]">
                <QrCode class="size-6 text-primary" />
            </div>
            <span class="text-sm font-semibold bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                QR Master
            </span>
        </div>

        <!-- Invitation card -->
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card shadow-[0_0_40px_oklch(0.66_0.25_285/0.1)]">
            <!-- Gradient top border -->
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />

            <CardHeader class="text-center pb-4">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20">
                    <Building2 class="size-7 text-primary" />
                </div>
                <CardTitle class="text-xl font-bold bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.accept_invitation_title') }}
                </CardTitle>
                <CardDescription class="mt-1">
                    {{ t('workspace.accept_invitation_intro', {
                        inviter: invitation.inviterName,
                        team: invitation.teamName,
                    }) }}
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <!-- Invitation details -->
                <div class="rounded-xl border border-border bg-muted/30 p-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.workspace') }}</span>
                        <span class="font-semibold">{{ invitation.teamName }}</span>
                    </div>
                    <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.member_role') }}</span>
                        <Badge variant="secondary" class="gap-1 bg-primary/10 text-primary border-primary/20">
                            <Shield class="size-3" />
                            {{ invitation.role }}
                        </Badge>
                    </div>
                    <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">{{ t('workspace.invite_email') }}</span>
                        <code class="rounded bg-muted px-2 py-0.5 font-mono text-xs">{{ invitation.email }}</code>
                    </div>
                </div>

                <!-- Warning — wrong email -->
                <div
                    v-if="isLoggedIn && !emailMatches"
                    class="flex items-start gap-3 rounded-xl border border-gold-500/20 bg-gold-500/5 p-3 text-sm"
                >
                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gold-500/10">
                        <Clock class="size-3.5 text-gold-500" />
                    </div>
                    <p class="text-gold-500/90 pt-0.5">{{ t('workspace.invite_wrong_email', { email: invitation.email }) }}</p>
                </div>
            </CardContent>

            <CardFooter class="flex flex-col gap-2 pt-2">
                <!-- Logged in with correct email -->
                <template v-if="isLoggedIn && emailMatches">
                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="accept"
                    >
                        <CheckCircle class="mr-2 size-4" />
                        {{ t('workspace.accept_invitation') }}
                    </Button>
                </template>

                <!-- Not logged in -->
                <template v-else-if="!isLoggedIn">
                    <p class="mb-1 text-center text-sm text-muted-foreground">
                        {{ t('workspace.invite_login_prompt') }}
                    </p>
                    <Button
                        class="w-full shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="goToLogin"
                    >
                        {{ t('workspace.invite_login') }}
                    </Button>
                    <Button
                        variant="outline"
                        class="w-full hover:border-primary/40 transition-colors duration-150"
                        @click="goToRegister"
                    >
                        {{ t('workspace.invite_register') }}
                    </Button>
                </template>

                <!-- Logged in with wrong email -->
                <template v-else>
                    <Button
                        variant="outline"
                        class="w-full hover:border-primary/40 transition-colors duration-150"
                        @click="goToLogin"
                    >
                        {{ t('workspace.invite_switch_account') }}
                    </Button>
                </template>
            </CardFooter>
        </div>

        <!-- Footer note -->
        <p class="relative mt-6 text-xs text-muted-foreground/60 text-center">
            QR Master &copy; {{ new Date().getFullYear() }}
        </p>
    </div>
</template>
