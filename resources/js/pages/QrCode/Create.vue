<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { ErrorCorrectionLevel } from 'qr-code-styling'
import { computed, ref } from 'vue'
import type { CornerDotStyle, CornerSquareStyle, DotStyle, FrameType } from '@/types/qr-visual'
import { useI18n } from 'vue-i18n'
import ColorPicker from '@/components/qr/ColorPicker.vue'
import CornerStylePicker from '@/components/qr/CornerStylePicker.vue'
import DotStylePicker from '@/components/qr/DotStylePicker.vue'
import GradientPicker from '@/components/qr/GradientPicker.vue'
import type { GradientConfig } from '@/components/qr/GradientPicker.vue'
import FramePicker from '@/components/qr/FramePicker.vue'
import TemplatePicker from '@/components/qr/TemplatePicker.vue'
import type { QrTemplate } from '@/components/qr/TemplatePicker.vue'
import LogoControls from '@/components/qr/LogoControls.vue'
import LogoUpload from '@/components/qr/LogoUpload.vue'
import QrFrame from '@/components/qr/QrFrame.vue'
import ExportModal from '@/components/qr/ExportModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useContrastChecker } from '@/composables/useContrastChecker'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

// QR data capacity: conservative limit for good scannability at ECC M
const MAX_CHARS = 900

const ALLOWED_URL_SCHEMES = ['https://', 'http://']

type TabId = 'url' | 'text' | 'email' | 'phone' | 'sms'
type EccLevel = ErrorCorrectionLevel

const ECC_LEVELS: EccLevel[] = ['L', 'M', 'Q', 'H']

const activeTab = ref<TabId>('url')
const eccLevel = ref<EccLevel>('M')

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

const urlError = computed<string | null>(() => {
    const v = url.value.trim()
    if (!v) return null
    return ALLOWED_URL_SCHEMES.some((s) => v.startsWith(s)) ? null : t('qr.validation.urlScheme')
})

const charCount = computed(() => qrData.value.length)
const isTooLong = computed(() => charCount.value > MAX_CHARS)
const showCharCounter = computed(() => charCount.value > MAX_CHARS * 0.7)

const hasError = computed(() => urlError.value !== null || isTooLong.value)
const canPreview = computed(() => qrData.value.length > 0 && !hasError.value)

const dotStyle = ref<DotStyle>('square')
const cornerSquare = ref<CornerSquareStyle>('square')
const cornerDot = ref<CornerDotStyle>('square')
const dotColor = ref('#000000')
const backgroundColor = ref('#ffffff')
const frameType = ref<FrameType>('none')
const frameText = ref('')
const frameColor = ref('#000000')

function applyTemplate(tpl: QrTemplate) {
    dotStyle.value = tpl.dotStyle
    dotColor.value = tpl.dotColor
    backgroundColor.value = tpl.bgColor
    cornerSquare.value = tpl.cornerSquare
    cornerDot.value = tpl.cornerDot
    gradient.value = { ...tpl.gradient }
    frameType.value = tpl.frameType
    frameText.value = tpl.frameText
    frameColor.value = tpl.frameColor
}
const logoDataUrl = ref<string | null>(null)
const logoSize = ref(0.3)
const logoMargin = ref(5)

function onLogoUpload(file: File) {
    const reader = new FileReader()
    reader.onload = (e) => { logoDataUrl.value = e.target?.result as string }
    reader.readAsDataURL(file)
}

function onLogoRemove() {
    logoDataUrl.value = null
}

const gradient = ref<GradientConfig>({
    enabled: false,
    type: 'linear',
    colorStart: '#000000',
    colorEnd: '#444444',
    rotation: 0,
})

const contrast = useContrastChecker(dotColor, backgroundColor)

// Export modal
const exportOpen = ref(false)
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
            <div class="lg:col-span-3 space-y-5">
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
                            :aria-invalid="urlError !== null"
                            autocomplete="off"
                        />
                        <p v-if="urlError" class="text-xs text-destructive">{{ urlError }}</p>
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

                <!-- Char counter -->
                <p
                    v-if="showCharCounter"
                    :class="isTooLong ? 'text-destructive' : 'text-muted-foreground'"
                    class="text-xs"
                >
                    {{
                        isTooLong
                            ? t('qr.validation.tooLong', { count: charCount, max: MAX_CHARS })
                            : t('qr.validation.nearLimit', { count: charCount, max: MAX_CHARS })
                    }}
                </p>

                <!-- Templates -->
                <TemplatePicker @apply="applyTemplate" />

                <!-- Dot style picker -->
                <DotStylePicker v-model="dotStyle" />

                <!-- Corner style picker -->
                <CornerStylePicker
                    :corner-square="cornerSquare"
                    :corner-dot="cornerDot"
                    @update:corner-square="cornerSquare = $event"
                    @update:corner-dot="cornerDot = $event"
                />

                <!-- Color pickers -->
                <div class="space-y-2">
                    <p class="text-sm font-medium">{{ t('qr.colors.label') }}</p>
                    <div class="flex flex-wrap gap-4">
                        <ColorPicker v-model="dotColor" :label="t('qr.colors.dotColor')" />
                        <ColorPicker v-model="backgroundColor" :label="t('qr.colors.bgColor')" />
                    </div>
                </div>

                <!-- Gradient picker -->
                <GradientPicker v-model="gradient" />

                <!-- Frame picker -->
                <FramePicker
                    v-model:frame-type="frameType"
                    v-model:frame-text="frameText"
                    v-model:frame-color="frameColor"
                />

                <!-- Logo upload (client-side only on Create) -->
                <LogoUpload
                    :current-logo-url="logoDataUrl"
                    @upload="onLogoUpload"
                    @remove="onLogoRemove"
                />

                <!-- Logo size/margin controls (shown only when logo present) -->
                <LogoControls
                    v-if="logoDataUrl"
                    v-model:logo-size="logoSize"
                    v-model:logo-margin="logoMargin"
                />

                <!-- ECC selector -->
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium shrink-0">{{ t('qr.ecc.label') }}</label>
                    <div class="flex gap-1">
                        <button
                            v-for="level in ECC_LEVELS"
                            :key="level"
                            type="button"
                            :class="[
                                'px-3 py-1 rounded-md text-xs font-mono font-semibold border transition-colors',
                                eccLevel === level
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'border-border text-muted-foreground hover:border-ring hover:text-foreground',
                            ]"
                            @click="eccLevel = level"
                        >
                            {{ t(`qr.ecc.${level}`) }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-2 flex flex-col items-center gap-3">
                <p class="text-sm font-medium self-start">{{ t('qr.preview.title') }}</p>
                <div class="rounded-xl border border-border bg-card p-4 flex items-center justify-center w-full min-h-[300px]">
                    <template v-if="canPreview">
                        <QrFrame
                            :data="qrData"
                            :size="260"
                            :error-correction-level="eccLevel"
                            :dot-type="dotStyle"
                            :dot-color="dotColor"
                            :background-color="backgroundColor"
                            :corners-square-type="cornerSquare"
                            :corners-dot-type="cornerDot"
                            :gradient-enabled="gradient.enabled"
                            :gradient-type="gradient.type"
                            :gradient-color-start="gradient.colorStart"
                            :gradient-color-end="gradient.colorEnd"
                            :gradient-rotation="gradient.rotation"
                            :image="logoDataUrl ?? undefined"
                            :image-size="logoSize"
                            :logo-margin="logoMargin"
                            :frame-type="frameType"
                            :frame-text="frameText"
                            :frame-color="frameColor"
                        />
                    </template>
                    <p v-else class="text-sm text-muted-foreground text-center px-6">
                        {{ t('qr.preview.empty') }}
                    </p>
                </div>

                <!-- Contrast warning -->
                <div
                    v-if="contrast && contrast.level !== 'good'"
                    :class="[
                        'flex items-start gap-2 rounded-md px-3 py-2 text-xs',
                        contrast.level === 'warn'
                            ? 'bg-yellow-50 text-yellow-800 border border-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-300 dark:border-yellow-800'
                            : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-950/30 dark:text-red-300 dark:border-red-800',
                    ]"
                >
                    <span class="mt-0.5 shrink-0">{{ contrast.level === 'warn' ? '⚠️' : '🚫' }}</span>
                    <span>{{ t(`qr.contrast.${contrast.level}`, { ratio: contrast.ratio }) }}</span>
                </div>

                <!-- Export trigger -->
                <Button
                    v-if="canPreview"
                    class="w-full"
                    @click="exportOpen = true"
                >
                    {{ t('qr.export.trigger') }}
                </Button>
            </div>
        </div>
    </div>

    <ExportModal
        v-model:open="exportOpen"
        :data="qrData"
        :ecc-level="eccLevel"
        :dot-color="dotColor"
        :background-color="backgroundColor"
    />
</template>
