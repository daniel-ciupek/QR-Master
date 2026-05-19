<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Download, FileText, Info } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

    <div class="space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button
                variant="ghost"
                size="icon"
                class="shrink-0 self-start hover:bg-muted/60 hover:text-primary transition-colors duration-150"
                @click="goBack"
            >
                <ArrowLeft class="size-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                    {{ t('workspace.dpa.title') }}
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ t('workspace.dpa.subtitle') }}</p>
            </div>
        </div>

        <div class="max-w-2xl space-y-6">
            <!-- Info banner -->
            <div class="flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                    <Info class="size-4 text-primary" />
                </div>
                <p class="text-sm text-primary/80 pt-0.5">
                    {{ t('workspace.dpa.info') }}
                </p>
            </div>

            <!-- Controller data card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <div class="flex size-7 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                            <FileText class="size-3.5 text-primary" />
                        </div>
                        {{ t('workspace.dpa.controller_title') }}
                    </CardTitle>
                    <CardDescription>{{ t('workspace.dpa.controller_desc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="company-name">{{ t('workspace.dpa.company_name') }} *</Label>
                        <Input
                            id="company-name"
                            v-model="form.company_name"
                            :placeholder="t('workspace.dpa.company_name_placeholder')"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.company_name" class="text-sm text-destructive">{{ form.errors.company_name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="company-address">{{ t('workspace.dpa.address') }} *</Label>
                        <Input
                            id="company-address"
                            v-model="form.company_address"
                            :placeholder="t('workspace.dpa.address_placeholder')"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.company_address" class="text-sm text-destructive">{{ form.errors.company_address }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="postal">{{ t('workspace.dpa.postal') }} *</Label>
                            <Input
                                id="postal"
                                v-model="form.company_postal"
                                placeholder="00-001"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="form.errors.company_postal" class="text-sm text-destructive">{{ form.errors.company_postal }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="city">{{ t('workspace.dpa.city') }} *</Label>
                            <Input
                                id="city"
                                v-model="form.company_city"
                                :placeholder="t('workspace.dpa.city_placeholder')"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="form.errors.company_city" class="text-sm text-destructive">{{ form.errors.company_city }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="country">{{ t('workspace.dpa.country') }} *</Label>
                            <Input
                                id="country"
                                v-model="form.company_country"
                                :placeholder="t('workspace.dpa.country_placeholder')"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                            <p v-if="form.errors.company_country" class="text-sm text-destructive">{{ form.errors.company_country }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="vat">{{ t('workspace.dpa.vat') }}</Label>
                            <Input
                                id="vat"
                                v-model="form.company_vat"
                                placeholder="PL1234567890"
                                class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                            />
                        </div>
                    </div>
                </CardContent>
            </div>

            <!-- Representative card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
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
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.representative_email" class="text-sm text-destructive">{{ form.errors.representative_email }}</p>
                    </div>
                </CardContent>
            </div>

            <!-- Agreement date card -->
            <div class="relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 hover:border-primary/30">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
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
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                        <p v-if="form.errors.agreement_date" class="text-sm text-destructive">{{ form.errors.agreement_date }}</p>
                    </div>
                </CardContent>
            </div>

            <!-- Generate button -->
            <div class="flex justify-end">
                <Button
                    class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                    @click="generate"
                >
                    <Download class="mr-2 size-4" />
                    {{ t('workspace.dpa.generate') }}
                </Button>
            </div>
        </div>
    </div>
</template>
