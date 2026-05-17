<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Download, FileText } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const props = defineProps<{
    team: { id: number; name: string; slug: string }
    prefill: {
        company_name: string
        company_address: string
        company_city: string
        company_postal: string
        company_country: string
        company_vat: string
        representative_name: string
        representative_email: string
        agreement_date: string
    }
}>()

const form = useForm({ ...props.prefill })

function generate(): void {
    const url = route('workspaces.dpa.generate', { team: props.team.slug })
    const params = new URLSearchParams(form.data() as Record<string, string>)

    // POST to get PDF — open in new tab via form submit
    const el = document.createElement('form')
    el.method = 'POST'
    el.action = url
    el.target = '_blank'

    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
    const csrfInput = document.createElement('input')
    csrfInput.type = 'hidden'
    csrfInput.name = '_token'
    csrfInput.value = csrf
    el.appendChild(csrfInput)

    for (const [key, value] of params.entries()) {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = key
        input.value = value
        el.appendChild(input)
    }

    document.body.appendChild(el)
    el.submit()
    document.body.removeChild(el)
}

function goBack(): void {
    router.visit(route('workspaces.show', { team: props.team.slug }))
}
</script>

<template>
    <Head :title="`${team.name} — ${t('workspace.dpa.title')}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('workspace.dpa.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('workspace.dpa.subtitle') }}
                </p>
            </div>
        </div>

        <!-- Info -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
            <FileText class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="text-sm text-blue-800 dark:text-blue-300">
                {{ t('workspace.dpa.info') }}
            </p>
        </div>

        <!-- Controller data -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.dpa.controller_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.dpa.controller_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="company-name">{{ t('workspace.dpa.company_name') }} *</Label>
                    <Input
                        id="company-name"
                        v-model="form.company_name"
                        :placeholder="t('workspace.dpa.company_name_placeholder')"
                    />
                    <p v-if="form.errors.company_name" class="text-sm text-destructive">{{ form.errors.company_name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="company-address">{{ t('workspace.dpa.address') }} *</Label>
                    <Input
                        id="company-address"
                        v-model="form.company_address"
                        :placeholder="t('workspace.dpa.address_placeholder')"
                    />
                    <p v-if="form.errors.company_address" class="text-sm text-destructive">{{ form.errors.company_address }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="postal">{{ t('workspace.dpa.postal') }} *</Label>
                        <Input id="postal" v-model="form.company_postal" placeholder="00-001" />
                        <p v-if="form.errors.company_postal" class="text-sm text-destructive">{{ form.errors.company_postal }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="city">{{ t('workspace.dpa.city') }} *</Label>
                        <Input id="city" v-model="form.company_city" :placeholder="t('workspace.dpa.city_placeholder')" />
                        <p v-if="form.errors.company_city" class="text-sm text-destructive">{{ form.errors.company_city }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="country">{{ t('workspace.dpa.country') }} *</Label>
                        <Input id="country" v-model="form.company_country" :placeholder="t('workspace.dpa.country_placeholder')" />
                        <p v-if="form.errors.company_country" class="text-sm text-destructive">{{ form.errors.company_country }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="vat">{{ t('workspace.dpa.vat') }}</Label>
                        <Input id="vat" v-model="form.company_vat" placeholder="PL1234567890" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Representative -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.dpa.representative_title') }}</CardTitle>
                <CardDescription>{{ t('workspace.dpa.representative_desc') }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="rep-name">{{ t('workspace.dpa.representative_name') }} *</Label>
                    <Input
                        id="rep-name"
                        v-model="form.representative_name"
                        :placeholder="t('workspace.dpa.representative_name_placeholder')"
                    />
                    <p v-if="form.errors.representative_name" class="text-sm text-destructive">{{ form.errors.representative_name }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="rep-email">{{ t('workspace.dpa.representative_email') }} *</Label>
                    <Input
                        id="rep-email"
                        v-model="form.representative_email"
                        type="email"
                        :placeholder="t('workspace.dpa.representative_email_placeholder')"
                    />
                    <p v-if="form.errors.representative_email" class="text-sm text-destructive">{{ form.errors.representative_email }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Agreement date -->
        <Card>
            <CardHeader>
                <CardTitle>{{ t('workspace.dpa.agreement_date') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <Label for="agreement-date">{{ t('workspace.dpa.agreement_date') }} *</Label>
                    <Input
                        id="agreement-date"
                        v-model="form.agreement_date"
                        type="date"
                    />
                    <p v-if="form.errors.agreement_date" class="text-sm text-destructive">{{ form.errors.agreement_date }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Generate button -->
        <div class="flex justify-end">
            <Button @click="generate">
                <Download class="mr-2 size-4" />
                {{ t('workspace.dpa.generate') }}
            </Button>
        </div>
    </div>
</template>
