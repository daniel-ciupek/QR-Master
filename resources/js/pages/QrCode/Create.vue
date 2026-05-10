<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import LivePreview from '@/components/qr/LivePreview.vue'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

type TabId = 'url' | 'text' | 'email' | 'phone' | 'sms'

const activeTab = ref<TabId>('url')

const url = ref('')
const text = ref('')
const emailAddress = ref('')
const emailSubject = ref('')
const emailBody = ref('')
const phone = ref('')
const smsNumber = ref('')
const smsMessage = ref('')

const qrData = computed<string>(() => {
    switch (activeTab.value) {
        case 'url':
            return url.value.trim()
        case 'text':
            return text.value.trim()
        case 'email': {
            const addr = emailAddress.value.trim()
            if (!addr) return ''
            const params = new URLSearchParams()
            if (emailSubject.value.trim()) params.set('subject', emailSubject.value.trim())
            if (emailBody.value.trim()) params.set('body', emailBody.value.trim())
            const qs = params.toString()
            return `mailto:${addr}${qs ? `?${qs}` : ''}`
        }
        case 'phone':
            return phone.value.trim() ? `tel:${phone.value.trim()}` : ''
        case 'sms': {
            const num = smsNumber.value.trim()
            if (!num) return ''
            return smsMessage.value.trim() ? `smsto:${num}:${smsMessage.value.trim()}` : `smsto:${num}`
        }
        default:
            return ''
    }
})
</script>

<template>
    <Head :title="t('qr.create.headTitle')" />

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold">{{ t('qr.create.title') }}</h1>
            <p class="text-sm text-muted-foreground mt-1">{{ t('qr.create.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
            <!-- Form -->
            <div class="lg:col-span-3">
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="w-full">
                        <TabsTrigger value="url" class="flex-1">{{ t('qr.tabs.url') }}</TabsTrigger>
                        <TabsTrigger value="text" class="flex-1">{{ t('qr.tabs.text') }}</TabsTrigger>
                        <TabsTrigger value="email" class="flex-1">{{ t('qr.tabs.email') }}</TabsTrigger>
                        <TabsTrigger value="phone" class="flex-1">{{ t('qr.tabs.phone') }}</TabsTrigger>
                        <TabsTrigger value="sms" class="flex-1">{{ t('qr.tabs.sms') }}</TabsTrigger>
                    </TabsList>

                    <!-- URL -->
                    <TabsContent value="url" class="mt-4 space-y-2">
                        <label class="text-sm font-medium">{{ t('qr.fields.url.label') }}</label>
                        <Input
                            v-model="url"
                            type="url"
                            :placeholder="t('qr.fields.url.placeholder')"
                            autocomplete="off"
                        />
                    </TabsContent>

                    <!-- Text -->
                    <TabsContent value="text" class="mt-4 space-y-2">
                        <label class="text-sm font-medium">{{ t('qr.fields.text.label') }}</label>
                        <textarea
                            v-model="text"
                            rows="5"
                            :placeholder="t('qr.fields.text.placeholder')"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30 resize-none transition-[color,box-shadow]"
                        />
                    </TabsContent>

                    <!-- Email -->
                    <TabsContent value="email" class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.email.address') }}</label>
                            <Input
                                v-model="emailAddress"
                                type="email"
                                :placeholder="t('qr.fields.email.addressPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.email.subject') }}</label>
                            <Input
                                v-model="emailSubject"
                                :placeholder="t('qr.fields.email.subjectPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.email.body') }}</label>
                            <textarea
                                v-model="emailBody"
                                rows="4"
                                :placeholder="t('qr.fields.email.bodyPlaceholder')"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30 resize-none transition-[color,box-shadow]"
                            />
                        </div>
                    </TabsContent>

                    <!-- Phone -->
                    <TabsContent value="phone" class="mt-4 space-y-2">
                        <label class="text-sm font-medium">{{ t('qr.fields.phone.label') }}</label>
                        <Input
                            v-model="phone"
                            type="tel"
                            :placeholder="t('qr.fields.phone.placeholder')"
                            autocomplete="off"
                        />
                    </TabsContent>

                    <!-- SMS -->
                    <TabsContent value="sms" class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.sms.number') }}</label>
                            <Input
                                v-model="smsNumber"
                                type="tel"
                                :placeholder="t('qr.fields.sms.numberPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.sms.message') }}</label>
                            <textarea
                                v-model="smsMessage"
                                rows="4"
                                :placeholder="t('qr.fields.sms.messagePlaceholder')"
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30 resize-none transition-[color,box-shadow]"
                            />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-2 flex flex-col items-center gap-3">
                <p class="text-sm font-medium self-start">{{ t('qr.preview.title') }}</p>
                <div
                    class="rounded-xl border border-border bg-card p-4 flex items-center justify-center w-full min-h-[300px]"
                >
                    <template v-if="qrData">
                        <LivePreview :data="qrData" :size="260" />
                    </template>
                    <p v-else class="text-sm text-muted-foreground text-center px-6">
                        {{ t('qr.preview.empty') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
