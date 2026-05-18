<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { AlertTriangle, Download, KeyRound, User } from 'lucide-vue-next'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { PageProps } from '@/types'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
const page = usePage<PageProps>()

defineProps<{ mustVerifyEmail?: boolean }>()

// ── Personal information ──────────────────────────────────────
const profileForm = useForm({
    name: page.props.auth.user?.name ?? '',
    email: page.props.auth.user?.email ?? '',
})

function saveProfile() {
    profileForm.put('/user/profile-information', {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
    })
}

// ── Password ──────────────────────────────────────────────────
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function savePassword() {
    passwordForm.put('/user/password', {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}

// ── Delete account ────────────────────────────────────────────
const showDeleteDialog = ref(false)
const deleteForm = useForm({ password: '' })

function deleteAccount() {
    deleteForm.delete('/profile', {
        preserveScroll: true,
        onError: () => deleteForm.reset('password'),
        onFinish: () => { showDeleteDialog.value = false },
    })
}
</script>

<template>
    <Head :title="t('profile.index.headTitle')" />

    <div class="mx-auto max-w-2xl space-y-6">
        <!-- Header -->
        <div>
            <h1 class="bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-2xl font-bold text-transparent">
                {{ t('profile.index.headTitle') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('profile.index.personalInfo.subtitle') }}</p>
        </div>

        <!-- Personal information -->
        <section class="relative overflow-hidden rounded-xl border border-border bg-card p-6 space-y-4 hover:border-primary/30 transition-colors duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <User class="size-4 text-primary" />
                </div>
                <div>
                    <h2 class="font-semibold text-base">{{ t('profile.index.personalInfo.title') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.index.personalInfo.subtitle') }}</p>
                </div>
            </div>
            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <form
                class="space-y-4"
                @submit.prevent="saveProfile"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="name"
                    >{{ t('profile.index.personalInfo.name') }}</label>
                    <Input
                        id="name"
                        v-model="profileForm.name"
                        autocomplete="name"
                        :class="{ 'border-destructive': profileForm.errors.name }"
                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                        type="text"
                    />
                    <p
                        v-if="profileForm.errors.name"
                        class="text-xs text-destructive"
                    >{{ profileForm.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="email"
                    >{{ t('profile.index.personalInfo.email') }}</label>
                    <Input
                        id="email"
                        v-model="profileForm.email"
                        autocomplete="email"
                        :class="{ 'border-destructive': profileForm.errors.email }"
                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50 transition-colors duration-150"
                        type="email"
                    />
                    <p
                        v-if="profileForm.errors.email"
                        class="text-xs text-destructive"
                    >{{ profileForm.errors.email }}</p>
                    <p
                        v-if="mustVerifyEmail"
                        class="text-xs text-warning-600"
                    >{{ t('profile.index.personalInfo.emailPending') }}</p>
                    <p class="text-xs text-muted-foreground">{{ t('profile.index.personalInfo.emailNote') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        :disabled="profileForm.processing"
                        type="submit"
                    >
                        {{ t('profile.index.personalInfo.save') }}
                    </Button>
                    <span
                        v-if="profileForm.recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >{{ t('profile.index.personalInfo.saved') }}</span>
                </div>
            </form>
        </section>

        <!-- Change password -->
        <section class="relative overflow-hidden rounded-xl border border-border bg-card p-6 space-y-4 hover:border-cyan-400/30 transition-colors duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-cyan-400/10 ring-1 ring-cyan-400/20">
                    <KeyRound class="size-4 text-cyan-400" />
                </div>
                <div>
                    <h2 class="font-semibold text-base">{{ t('profile.index.password.title') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.index.password.subtitle') }}</p>
                </div>
            </div>
            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <form
                class="space-y-4"
                @submit.prevent="savePassword"
            >
                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="current_password"
                    >{{ t('profile.index.password.current') }}</label>
                    <Input
                        id="current_password"
                        v-model="passwordForm.current_password"
                        autocomplete="current-password"
                        :class="{ 'border-destructive': passwordForm.errors.current_password }"
                        type="password"
                    />
                    <p
                        v-if="passwordForm.errors.current_password"
                        class="text-xs text-destructive"
                    >{{ passwordForm.errors.current_password }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="new_password"
                    >{{ t('profile.index.password.new') }}</label>
                    <Input
                        id="new_password"
                        v-model="passwordForm.password"
                        autocomplete="new-password"
                        :class="{ 'border-destructive': passwordForm.errors.password }"
                        type="password"
                    />
                    <p
                        v-if="passwordForm.errors.password"
                        class="text-xs text-destructive"
                    >{{ passwordForm.errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-sm font-medium"
                        for="password_confirmation"
                    >{{ t('profile.index.password.confirm') }}</label>
                    <Input
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        autocomplete="new-password"
                        type="password"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        :disabled="passwordForm.processing"
                        type="submit"
                    >
                        {{ t('profile.index.password.save') }}
                    </Button>
                    <span
                        v-if="passwordForm.recentlySuccessful"
                        class="text-sm text-muted-foreground"
                    >{{ t('profile.index.password.saved') }}</span>
                </div>
            </form>
        </section>

        <!-- GDPR export -->
        <section class="relative overflow-hidden rounded-xl border border-border bg-card p-6 space-y-4 hover:border-gold-500/30 transition-colors duration-200">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold-500/40 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-gold-500/10 ring-1 ring-gold-500/20">
                    <Download class="size-4 text-gold-500" />
                </div>
                <div>
                    <h2 class="font-semibold text-base">{{ t('profile.index.gdpr.title') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.index.gdpr.subtitle') }}</p>
                </div>
            </div>
            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

            <Button
                as="a"
                href="/profile/export-data"
                variant="outline"
                class="transition-colors duration-150 hover:border-gold-500/40 hover:text-gold-500"
            >
                {{ t('profile.index.gdpr.download') }}
            </Button>
        </section>

        <!-- Danger zone -->
        <section class="relative overflow-hidden rounded-xl border border-destructive/30 bg-card p-6 space-y-4">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-destructive/50 to-transparent" />
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-full bg-destructive/10 ring-1 ring-destructive/20">
                    <AlertTriangle class="size-4 text-destructive" />
                </div>
                <div>
                    <h2 class="font-semibold text-base text-destructive">{{ t('profile.index.danger.title') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ t('profile.index.danger.subtitle') }}</p>
                </div>
            </div>
            <!-- Section divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-destructive/20 to-transparent" />

            <Button
                variant="destructive"
                @click="showDeleteDialog = true"
            >
                {{ t('profile.index.danger.delete') }}
            </Button>
        </section>
    </div>

    <!-- Delete account dialog -->
    <Dialog v-model:open="showDeleteDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('profile.index.danger.dialogTitle') }}</DialogTitle>
                <DialogDescription>{{ t('profile.index.danger.dialogDesc') }}</DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <label
                    class="text-sm font-medium"
                    for="delete_password"
                >{{ t('profile.index.danger.passwordLabel') }}</label>
                <Input
                    id="delete_password"
                    v-model="deleteForm.password"
                    :class="{ 'border-destructive': deleteForm.errors.password }"
                    type="password"
                    @keyup.enter="deleteAccount"
                />
                <p
                    v-if="deleteForm.errors.password"
                    class="text-xs text-destructive"
                >{{ deleteForm.errors.password }}</p>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showDeleteDialog = false"
                >
                    {{ t('profile.index.danger.cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    :disabled="deleteForm.processing || !deleteForm.password"
                    @click="deleteAccount"
                >
                    {{ t('profile.index.danger.confirmButton') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
