<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
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

    <div class="mx-auto max-w-2xl space-y-8">
        <!-- Personal information -->
        <section class="rounded-lg border p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-lg">{{ t('profile.index.personalInfo.title') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.index.personalInfo.subtitle') }}</p>
            </div>

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
        <section class="rounded-lg border p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-lg">{{ t('profile.index.password.title') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.index.password.subtitle') }}</p>
            </div>

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
        <section class="rounded-lg border p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-lg">{{ t('profile.index.gdpr.title') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.index.gdpr.subtitle') }}</p>
            </div>

            <Button
                as="a"
                href="/profile/export-data"
                variant="outline"
            >
                {{ t('profile.index.gdpr.download') }}
            </Button>
        </section>

        <!-- Danger zone -->
        <section class="rounded-lg border border-destructive/40 p-6 space-y-4">
            <div>
                <h2 class="font-semibold text-lg text-destructive">{{ t('profile.index.danger.title') }}</h2>
                <p class="text-sm text-muted-foreground mt-1">{{ t('profile.index.danger.subtitle') }}</p>
            </div>

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
