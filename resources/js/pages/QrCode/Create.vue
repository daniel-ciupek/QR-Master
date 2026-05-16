<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
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
import SuggestPaletteButton from '@/components/qr/SuggestPaletteButton.vue'
import UserTemplatePicker from '@/components/qr/UserTemplatePicker.vue'
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

const props = defineProps<{
    userTemplates: Array<{ id: number; name: string; settings: Omit<QrTemplate, 'name'> }>
}>()

const { t } = useI18n()

// QR data capacity: conservative limit for good scannability at ECC M
const MAX_CHARS = 900

const ALLOWED_URL_SCHEMES = ['https://', 'http://']

type TabId = 'url' | 'text' | 'email' | 'phone' | 'sms' | 'vcard' | 'wifi' | 'geo' | 'pdf' | 'bio_link' | 'app' | 'calendar' | 'crypto' | 'review'
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

// Geo fields
const geoLat = ref('')
const geoLng = ref('')

// PDF fields
const pdfFile = ref<File | null>(null)

// App Store fields
const appIosUrl = ref('')
const appAndroidUrl = ref('')
const appFallbackUrl = ref('')

// Review fields
type ReviewPlatform = 'google' | 'trustpilot' | 'yelp' | 'facebook' | 'tripadvisor' | 'other'
const REVIEW_PLATFORMS: ReviewPlatform[] = ['google', 'trustpilot', 'yelp', 'facebook', 'tripadvisor', 'other']
const reviewPlatform = ref<ReviewPlatform>('google')
const reviewUrl = ref('')

// Crypto fields
type CryptoCoin = 'bitcoin' | 'ethereum' | 'litecoin' | 'dogecoin'
const CRYPTO_COINS: CryptoCoin[] = ['bitcoin', 'ethereum', 'litecoin', 'dogecoin']
const cryptoCoin = ref<CryptoCoin>('bitcoin')
const cryptoAddress = ref('')
const cryptoAmount = ref('')
const cryptoLabel = ref('')
const cryptoMessage = ref('')

// Calendar fields
const calendarTitle = ref('')
const calendarStart = ref('')
const calendarEnd = ref('')
const calendarDescription = ref('')
const calendarLocation = ref('')
const calendarAllDay = ref(false)

// WiFi fields
type WifiSecurity = 'wpa' | 'wpa2' | 'wpa3' | 'wep' | 'open'
const WIFI_SECURITY_TYPES: WifiSecurity[] = ['wpa', 'wpa2', 'wpa3', 'wep', 'open']
const wifiSsid = ref('')
const wifiSecurity = ref<WifiSecurity>('wpa2')
const wifiPassword = ref('')
const wifiHidden = ref(false)

// vCard fields
const vcardFirstName = ref('')
const vcardLastName = ref('')
const vcardCompany = ref('')
const vcardJobTitle = ref('')
const vcardPhone = ref('')
const vcardEmail = ref('')
const vcardWebsite = ref('')
const vcardAddress = ref('')

// Form meta
const qrTitle = ref('')
const isSaving = ref(false)

// Build vCard string client-side for live preview
function buildVCardPreview(): string {
    const fn = `${vcardFirstName.value.trim()} ${vcardLastName.value.trim()}`.trim()
    const lines = ['BEGIN:VCARD', 'VERSION:4.0']
    if (fn) { lines.push(`FN:${fn}`); lines.push(`N:${vcardLastName.value.trim()};${vcardFirstName.value.trim()};;;`) }
    if (vcardCompany.value.trim()) lines.push(`ORG:${vcardCompany.value.trim()}`)
    if (vcardJobTitle.value.trim()) lines.push(`TITLE:${vcardJobTitle.value.trim()}`)
    if (vcardPhone.value.trim()) lines.push(`TEL;TYPE=WORK:${vcardPhone.value.trim()}`)
    if (vcardEmail.value.trim()) lines.push(`EMAIL;TYPE=WORK:${vcardEmail.value.trim()}`)
    if (vcardWebsite.value.trim()) lines.push(`URL:${vcardWebsite.value.trim()}`)
    if (vcardAddress.value.trim()) lines.push(`ADR;TYPE=WORK:;;${vcardAddress.value.trim()};;;;`)
    lines.push('END:VCARD')
    return lines.join('\r\n')
}

const WIFI_SECURITY_MAP: Record<WifiSecurity, string> = {
    wpa: 'WPA', wpa2: 'WPA2', wpa3: 'WPA3', wep: 'WEP', open: 'nopass',
}

function escapeWifi(v: string): string {
    return v.replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/"/g, '\\"')
}

function buildWifiPreview(): string {
    const ssid = escapeWifi(wifiSsid.value.trim())
    if (!ssid) return ''
    const sec = WIFI_SECURITY_MAP[wifiSecurity.value]
    const pass = escapeWifi(wifiPassword.value)
    const hidden = wifiHidden.value ? 'true' : 'false'
    return `WIFI:T:${sec};S:${ssid};P:${pass};H:${hidden};;`
}

function escapeIcs(v: string): string {
    return v.replace(/\\/g, '\\\\').replace(/,/g, '\\,').replace(/;/g, '\\;').replace(/\n/g, '\\n')
}

function formatIcsDt(dt: string, allDay: boolean): string {
    // dt is "YYYY-MM-DDTHH:mm" (datetime-local) or "YYYY-MM-DD" (date)
    const datePart = dt.slice(0, 10).replace(/-/g, '')
    if (allDay) return datePart
    const timePart = (dt.slice(11, 16) || '00:00').replace(':', '') + '00'
    return `${datePart}T${timePart}`
}

function buildCalendarPreview(): string {
    const title = escapeIcs(calendarTitle.value.trim())
    if (!title || !calendarStart.value) return ''
    const uid = encodeURIComponent(title + calendarStart.value).slice(0, 32) + '@qr-master.app'
    const allDay = calendarAllDay.value
    const lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//QR-Master//EN',
        'BEGIN:VEVENT',
        `UID:${uid}`,
        `DTSTART${allDay ? ';VALUE=DATE:' : ':'}${formatIcsDt(calendarStart.value, allDay)}`,
    ]
    if (calendarEnd.value) {
        lines.push(`DTEND${allDay ? ';VALUE=DATE:' : ':'}${formatIcsDt(calendarEnd.value, allDay)}`)
    }
    lines.push(`SUMMARY:${title}`)
    const desc = escapeIcs(calendarDescription.value.trim())
    if (desc) lines.push(`DESCRIPTION:${desc}`)
    const loc = escapeIcs(calendarLocation.value.trim())
    if (loc) lines.push(`LOCATION:${loc}`)
    lines.push('END:VEVENT', 'END:VCALENDAR')
    return lines.join('\r\n')
}

function buildCryptoPreview(): string {
    const address = cryptoAddress.value.trim()
    if (!address) return ''
    const params: string[] = []
    const amount = cryptoAmount.value.trim()
    if (amount && parseFloat(amount) > 0) params.push(`amount=${encodeURIComponent(amount)}`)
    if (cryptoLabel.value.trim()) params.push(`label=${encodeURIComponent(cryptoLabel.value.trim())}`)
    if (cryptoMessage.value.trim()) params.push(`message=${encodeURIComponent(cryptoMessage.value.trim())}`)
    return `${cryptoCoin.value}:${address}${params.length ? '?' + params.join('&') : ''}`
}

function saveQrCode() {
    if (!qrTitle.value.trim()) return
    isSaving.value = true
    router.post('/qr', {
        title: qrTitle.value.trim(),
        type: activeTab.value,
        is_active: true,
        // URL
        destination_url: activeTab.value === 'url' ? url.value.trim() : undefined,
        // Text
        text_content: activeTab.value === 'text' ? text.value.trim() : undefined,
        // Email
        email_address: activeTab.value === 'email' ? emailAddress.value.trim() : undefined,
        email_subject: activeTab.value === 'email' ? emailSubject.value.trim() : undefined,
        email_body: activeTab.value === 'email' ? emailBody.value.trim() : undefined,
        // Phone
        phone_number: activeTab.value === 'phone' ? phone.value.trim() : undefined,
        // SMS
        sms_number: activeTab.value === 'sms' ? smsNumber.value.trim() : undefined,
        sms_message: activeTab.value === 'sms' ? smsMessage.value.trim() : undefined,
        // vCard
        vcard_first_name: activeTab.value === 'vcard' ? vcardFirstName.value.trim() : undefined,
        vcard_last_name: activeTab.value === 'vcard' ? vcardLastName.value.trim() : undefined,
        vcard_company: activeTab.value === 'vcard' ? vcardCompany.value.trim() : undefined,
        vcard_job_title: activeTab.value === 'vcard' ? vcardJobTitle.value.trim() : undefined,
        vcard_phone: activeTab.value === 'vcard' ? vcardPhone.value.trim() : undefined,
        vcard_email: activeTab.value === 'vcard' ? vcardEmail.value.trim() : undefined,
        vcard_website: activeTab.value === 'vcard' ? vcardWebsite.value.trim() : undefined,
        vcard_address: activeTab.value === 'vcard' ? vcardAddress.value.trim() : undefined,
        // Geo
        geo_lat: activeTab.value === 'geo' ? geoLat.value.trim() : undefined,
        geo_lng: activeTab.value === 'geo' ? geoLng.value.trim() : undefined,
        // WiFi
        wifi_ssid: activeTab.value === 'wifi' ? wifiSsid.value.trim() : undefined,
        wifi_security: activeTab.value === 'wifi' ? wifiSecurity.value : undefined,
        wifi_password: activeTab.value === 'wifi' ? wifiPassword.value : undefined,
        wifi_hidden: activeTab.value === 'wifi' ? wifiHidden.value : undefined,
        // PDF — Inertia auto-converts to multipart/form-data when File is present
        pdf_file: activeTab.value === 'pdf' ? pdfFile.value : undefined,
        // App Store / Play Store
        app_ios_url: activeTab.value === 'app' ? appIosUrl.value.trim() : undefined,
        app_android_url: activeTab.value === 'app' ? appAndroidUrl.value.trim() : undefined,
        app_fallback_url: activeTab.value === 'app' ? appFallbackUrl.value.trim() : undefined,
        // Review
        review_platform: activeTab.value === 'review' ? reviewPlatform.value : undefined,
        review_url: activeTab.value === 'review' ? reviewUrl.value.trim() : undefined,
        // Crypto
        crypto_coin: activeTab.value === 'crypto' ? cryptoCoin.value : undefined,
        crypto_address: activeTab.value === 'crypto' ? cryptoAddress.value.trim() : undefined,
        crypto_amount: activeTab.value === 'crypto' ? cryptoAmount.value.trim() : undefined,
        crypto_label: activeTab.value === 'crypto' ? cryptoLabel.value.trim() : undefined,
        crypto_message: activeTab.value === 'crypto' ? cryptoMessage.value.trim() : undefined,
        // Calendar
        calendar_title: activeTab.value === 'calendar' ? calendarTitle.value.trim() : undefined,
        calendar_start: activeTab.value === 'calendar' ? calendarStart.value : undefined,
        calendar_end: activeTab.value === 'calendar' ? calendarEnd.value : undefined,
        calendar_description: activeTab.value === 'calendar' ? calendarDescription.value.trim() : undefined,
        calendar_location: activeTab.value === 'calendar' ? calendarLocation.value.trim() : undefined,
        calendar_all_day: activeTab.value === 'calendar' ? calendarAllDay.value : undefined,
        // Visual settings
        settings: currentStyle.value,
    }, {
        onFinish: () => { isSaving.value = false },
    })
}

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
        case 'vcard':
            return buildVCardPreview()
        case 'wifi':
            return buildWifiPreview()
        case 'geo': {
            const lat = geoLat.value.trim()
            const lng = geoLng.value.trim()
            return lat && lng ? `geo:${lat},${lng}` : ''
        }
        case 'pdf':
            // URL is set server-side after creation; preview uses placeholder
            return pdfFile.value ? pdfFile.value.name : ''
        case 'bio_link':
            return ''
        case 'app':
            return appIosUrl.value.trim() || appAndroidUrl.value.trim() || appFallbackUrl.value.trim()
        case 'calendar':
            return buildCalendarPreview()
        case 'crypto':
            return buildCryptoPreview()
        case 'review':
            return reviewUrl.value.trim()
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

const currentStyle = computed(() => ({
    dotStyle: dotStyle.value,
    dotColor: dotColor.value,
    bgColor: backgroundColor.value,
    cornerSquare: cornerSquare.value,
    cornerDot: cornerDot.value,
    gradient: { ...gradient.value },
    frameType: frameType.value,
    frameText: frameText.value,
    frameColor: frameColor.value,
}))

function applyTemplate(tpl: QrTemplate | Omit<QrTemplate, 'name'>) {
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
                <!-- QR Code title (required to save) -->
                <div class="space-y-2">
                    <label class="text-sm font-medium">{{ t('qr.create.fields.title') }}</label>
                    <Input
                        v-model="qrTitle"
                        :placeholder="t('qr.create.fields.titlePlaceholder')"
                        autocomplete="off"
                    />
                </div>

                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="w-full overflow-x-auto">
                        <TabsTrigger value="url" class="flex-1">{{ t('qr.tabs.url') }}</TabsTrigger>
                        <TabsTrigger value="text" class="flex-1">{{ t('qr.tabs.text') }}</TabsTrigger>
                        <TabsTrigger value="email" class="flex-1">{{ t('qr.tabs.email') }}</TabsTrigger>
                        <TabsTrigger value="phone" class="flex-1">{{ t('qr.tabs.phone') }}</TabsTrigger>
                        <TabsTrigger value="sms" class="flex-1">{{ t('qr.tabs.sms') }}</TabsTrigger>
                        <TabsTrigger value="vcard" class="flex-1">{{ t('qr.tabs.vcard') }}</TabsTrigger>
                        <TabsTrigger value="wifi" class="flex-1">{{ t('qr.tabs.wifi') }}</TabsTrigger>
                        <TabsTrigger value="geo" class="flex-1">{{ t('qr.tabs.geo') }}</TabsTrigger>
                        <TabsTrigger value="pdf" class="flex-1">{{ t('qr.tabs.pdf') }}</TabsTrigger>
                        <TabsTrigger value="bio_link" class="flex-1">{{ t('qr.tabs.bio_link') }}</TabsTrigger>
                        <TabsTrigger value="app" class="flex-1">{{ t('qr.tabs.app') }}</TabsTrigger>
                        <TabsTrigger value="calendar" class="flex-1">{{ t('qr.tabs.calendar') }}</TabsTrigger>
                        <TabsTrigger value="crypto" class="flex-1">{{ t('qr.tabs.crypto') }}</TabsTrigger>
                        <TabsTrigger value="review" class="flex-1">{{ t('qr.tabs.review') }}</TabsTrigger>
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

                    <!-- WiFi -->
                    <TabsContent value="wifi" class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.wifi.ssid') }}</label>
                            <Input
                                v-model="wifiSsid"
                                :placeholder="t('qr.fields.wifi.ssidPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.wifi.security') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="sec in WIFI_SECURITY_TYPES"
                                    :key="sec"
                                    type="button"
                                    :class="[
                                        'rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors',
                                        wifiSecurity === sec
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'border-border text-muted-foreground hover:border-ring hover:text-foreground',
                                    ]"
                                    @click="wifiSecurity = sec"
                                >
                                    {{ t(`qr.fields.wifi.securityTypes.${sec}`) }}
                                </button>
                            </div>
                        </div>
                        <div v-if="wifiSecurity !== 'open'" class="space-y-2">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.wifi.password') }}</label>
                            <Input
                                v-model="wifiPassword"
                                type="password"
                                :placeholder="t('qr.fields.wifi.passwordPlaceholder')"
                                autocomplete="new-password"
                            />
                        </div>
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                v-model="wifiHidden"
                                type="checkbox"
                                class="size-4 rounded border-border accent-primary"
                            >
                            <span class="text-sm font-medium">{{ t('qr.fields.wifi.hidden') }}</span>
                            <span class="text-xs text-muted-foreground">{{ t('qr.fields.wifi.hiddenHint') }}</span>
                        </label>
                    </TabsContent>

                    <!-- Geo -->
                    <TabsContent value="geo" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('qr.fields.geo.lat') }}</label>
                                <Input
                                    v-model="geoLat"
                                    type="number"
                                    step="any"
                                    min="-90"
                                    max="90"
                                    :placeholder="t('qr.fields.geo.latPlaceholder')"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('qr.fields.geo.lng') }}</label>
                                <Input
                                    v-model="geoLng"
                                    type="number"
                                    step="any"
                                    min="-180"
                                    max="180"
                                    :placeholder="t('qr.fields.geo.lngPlaceholder')"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ t('qr.fields.geo.hint') }}</p>
                        <a
                            v-if="geoLat.trim() && geoLng.trim()"
                            :href="`https://www.google.com/maps?q=${geoLat.trim()},${geoLng.trim()}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs text-primary underline underline-offset-2"
                        >{{ t('qr.fields.geo.openMaps') }}</a>
                    </TabsContent>

                    <!-- vCard -->
                    <TabsContent value="vcard" class="mt-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">{{ t('qr.fields.vcard.firstName') }}</label>
                                <Input v-model="vcardFirstName" :placeholder="t('qr.fields.vcard.firstNamePlaceholder')" autocomplete="off" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium">{{ t('qr.fields.vcard.lastName') }}</label>
                                <Input v-model="vcardLastName" :placeholder="t('qr.fields.vcard.lastNamePlaceholder')" autocomplete="off" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.company') }}</label>
                                <Input v-model="vcardCompany" :placeholder="t('qr.fields.vcard.companyPlaceholder')" autocomplete="off" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.jobTitle') }}</label>
                                <Input v-model="vcardJobTitle" :placeholder="t('qr.fields.vcard.jobTitlePlaceholder')" autocomplete="off" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.phone') }}</label>
                                <Input
                                    v-model="vcardPhone"
                                    type="tel"
                                    :placeholder="t('qr.fields.vcard.phonePlaceholder')"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.email') }}</label>
                                <Input
                                    v-model="vcardEmail"
                                    type="email"
                                    :placeholder="t('qr.fields.vcard.emailPlaceholder')"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.website') }}</label>
                            <Input
                                v-model="vcardWebsite"
                                type="url"
                                :placeholder="t('qr.fields.vcard.websitePlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.address') }}</label>
                            <Input v-model="vcardAddress" :placeholder="t('qr.fields.vcard.addressPlaceholder')" autocomplete="off" />
                        </div>
                    </TabsContent>

                    <!-- PDF Menu -->
                    <TabsContent value="pdf" class="mt-4 space-y-4">
                        <p class="text-sm text-muted-foreground">{{ t('qr.fields.pdf.hint') }}</p>
                        <label
                            class="flex cursor-pointer flex-col items-center gap-3 rounded-lg border-2 border-dashed border-border p-6 text-center transition-colors hover:border-primary hover:bg-accent/30"
                            :class="{ 'border-primary bg-accent/30': pdfFile !== null }"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-8 text-muted-foreground"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <div>
                                <p v-if="pdfFile" class="text-sm font-medium text-foreground">{{ pdfFile.name }}</p>
                                <p v-else class="text-sm font-medium">{{ t('qr.fields.pdf.selectFile') }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ t('qr.fields.pdf.maxSize') }}</p>
                            </div>
                            <input
                                type="file"
                                accept="application/pdf"
                                class="sr-only"
                                @change="(e) => { const f = (e.target as HTMLInputElement).files?.[0]; pdfFile = f ?? null }"
                            >
                        </label>

                        <Button
                            v-if="pdfFile"
                            variant="ghost"
                            size="sm"
                            @click="pdfFile = null"
                        >
                            {{ t('qr.fields.pdf.remove') }}
                        </Button>
                    </TabsContent>

                    <!-- Bio-Link -->
                    <TabsContent value="bio_link" class="mt-4 space-y-3">
                        <div class="rounded-lg border border-border bg-accent/20 p-4 space-y-2">
                            <p class="text-sm font-medium">{{ t('qr.fields.bio_link.title') }}</p>
                            <p class="text-sm text-muted-foreground">{{ t('qr.fields.bio_link.hint') }}</p>
                            <ul class="text-sm text-muted-foreground list-disc list-inside space-y-1">
                                <li>{{ t('qr.fields.bio_link.feature1') }}</li>
                                <li>{{ t('qr.fields.bio_link.feature2') }}</li>
                                <li>{{ t('qr.fields.bio_link.feature3') }}</li>
                            </ul>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ t('qr.fields.bio_link.afterSave') }}</p>
                    </TabsContent>

                    <!-- App Store / Play Store -->
                    <TabsContent value="app" class="mt-4 space-y-4">
                        <p class="text-sm text-muted-foreground">{{ t('qr.fields.app.hint') }}</p>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.app.iosUrl') }}</label>
                            <Input
                                v-model="appIosUrl"
                                type="url"
                                :placeholder="t('qr.fields.app.iosPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.app.androidUrl') }}</label>
                            <Input
                                v-model="appAndroidUrl"
                                type="url"
                                :placeholder="t('qr.fields.app.androidPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.app.fallbackUrl') }}</label>
                            <Input
                                v-model="appFallbackUrl"
                                type="url"
                                :placeholder="t('qr.fields.app.fallbackPlaceholder')"
                                autocomplete="off"
                            />
                            <p class="text-xs text-muted-foreground">{{ t('qr.fields.app.fallbackHint') }}</p>
                        </div>
                    </TabsContent>

                    <!-- Calendar -->
                    <TabsContent value="calendar" class="mt-4 space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.calendar.title') }} *</label>
                            <Input v-model="calendarTitle" :placeholder="t('qr.fields.calendar.titlePlaceholder')" autocomplete="off" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="cal-allday"
                                v-model="calendarAllDay"
                                type="checkbox"
                                class="rounded"
                            >
                            <label for="cal-allday" class="text-sm">{{ t('qr.fields.calendar.allDay') }}</label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-sm font-medium">{{ t('qr.fields.calendar.start') }} *</label>
                                <Input
                                    v-model="calendarStart"
                                    :type="calendarAllDay ? 'date' : 'datetime-local'"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium">{{ t('qr.fields.calendar.end') }}</label>
                                <Input
                                    v-model="calendarEnd"
                                    :type="calendarAllDay ? 'date' : 'datetime-local'"
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.calendar.location') }}</label>
                            <Input v-model="calendarLocation" :placeholder="t('qr.fields.calendar.locationPlaceholder')" autocomplete="off" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.calendar.description') }}</label>
                            <textarea
                                v-model="calendarDescription"
                                rows="3"
                                maxlength="1000"
                                :placeholder="t('qr.fields.calendar.descriptionPlaceholder')"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                        </div>
                    </TabsContent>

                    <!-- Crypto Address -->
                    <TabsContent value="crypto" class="mt-4 space-y-4">
                        <!-- Coin selector -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.crypto.coin') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="coin in CRYPTO_COINS"
                                    :key="coin"
                                    type="button"
                                    class="rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
                                    :class="cryptoCoin === coin ? 'border-primary bg-primary/10' : 'border-border hover:border-muted-foreground'"
                                    @click="cryptoCoin = coin"
                                >
                                    {{ t('qr.fields.crypto.coins.' + coin) }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.crypto.address') }} *</label>
                            <Input v-model="cryptoAddress" :placeholder="t('qr.fields.crypto.addressPlaceholder')" autocomplete="off" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-sm font-medium">{{ t('qr.fields.crypto.amount') }}</label>
                                <Input
                                    v-model="cryptoAmount"
                                    type="number"
                                    step="any"
                                    min="0"
                                    placeholder="0.001"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium">{{ t('qr.fields.crypto.label') }}</label>
                                <Input v-model="cryptoLabel" :placeholder="t('qr.fields.crypto.labelPlaceholder')" autocomplete="off" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.crypto.message') }}</label>
                            <Input v-model="cryptoMessage" :placeholder="t('qr.fields.crypto.messagePlaceholder')" autocomplete="off" />
                        </div>

                        <p v-if="cryptoAddress.trim()" class="break-all rounded bg-muted px-3 py-2 font-mono text-xs text-muted-foreground">
                            {{ buildCryptoPreview() }}
                        </p>
                    </TabsContent>

                    <!-- Review -->
                    <TabsContent value="review" class="mt-4 space-y-4">
                        <p class="text-sm text-muted-foreground">{{ t('qr.fields.review.hint') }}</p>

                        <!-- Platform selector -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.review.platform') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="platform in REVIEW_PLATFORMS"
                                    :key="platform"
                                    type="button"
                                    class="rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
                                    :class="reviewPlatform === platform ? 'border-primary bg-primary/10' : 'border-border hover:border-muted-foreground'"
                                    @click="reviewPlatform = platform"
                                >
                                    {{ t('qr.fields.review.platforms.' + platform) }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">{{ t('qr.fields.review.url') }} *</label>
                            <Input
                                v-model="reviewUrl"
                                type="url"
                                :placeholder="t('qr.fields.review.urlPlaceholder')"
                                autocomplete="off"
                            />
                            <p class="text-xs text-muted-foreground">{{ t('qr.fields.review.urlHint') }}</p>
                        </div>
                    </TabsContent>
                </Tabs>

                <!-- Save button -->
                <div class="flex items-center gap-3 pt-1">
                    <Button
                        :disabled="!qrTitle.trim() || isSaving || (activeTab === 'pdf' && !pdfFile) || (activeTab === 'app' && !appIosUrl.trim() && !appAndroidUrl.trim())"
                        @click="saveQrCode"
                    >
                        {{ isSaving ? t('qr.create.saving') : t('qr.create.save') }}
                    </Button>
                    <span v-if="!qrTitle.trim()" class="text-muted-foreground text-xs">
                        {{ t('qr.create.titleRequired') }}
                    </span>
                </div>

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

                <!-- User saved templates -->
                <UserTemplatePicker
                    :templates="props.userTemplates"
                    :current-style="currentStyle"
                    @apply="applyTemplate"
                />

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
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium">{{ t('qr.colors.label') }}</p>
                        <SuggestPaletteButton
                            @apply="(dot, bg) => { dotColor = dot; backgroundColor = bg }"
                        />
                    </div>
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
                    :context="url || text || activeTab"
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
