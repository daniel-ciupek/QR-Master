<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import type { ErrorCorrectionLevel } from 'qr-code-styling'
import { computed, ref, watch } from 'vue'
import type { CornerDotStyle, CornerSquareStyle, DotStyle, FrameType } from '@/types/qr-visual'
import { useI18n } from 'vue-i18n'
import {
    AlignLeft,
    Bitcoin,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Contact,
    FileText,
    Globe,
    Link2,
    Mail,
    MapPin,
    MessageSquare,
    Phone,
    QrCode,
    Save,
    Download,
    Smartphone,
    Star,
    Wifi,
} from 'lucide-vue-next'
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
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent } from '@/components/ui/tabs'
import { useContrastChecker } from '@/composables/useContrastChecker'
import { useOfflineDrafts } from '@/composables/useOfflineDrafts'
import OfflineBanner from '@/components/OfflineBanner.vue'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    userTemplates: Array<{ id: number; name: string; settings: Omit<QrTemplate, 'name'> }>
}>()

const { t } = useI18n()

type TabId = 'url' | 'text' | 'email' | 'phone' | 'sms' | 'vcard' | 'wifi' | 'geo' | 'pdf' | 'bio_link' | 'app' | 'calendar' | 'crypto' | 'review'

interface QrTypeItem {
    id: TabId
    labelKey: string
    descKey: string
    icon: unknown
    iconColor: string
    iconBg: string
    iconRing: string
    activeBorder: string
    activeGlow: string
    topBorder: string
    labelColor: string
}

const QR_TYPES: QrTypeItem[] = [
    { id: 'url',      labelKey: 'qr.tabs.url',      descKey: 'qr.tabs.desc.url',      icon: Globe,          iconColor: 'text-violet-400',  iconBg: 'bg-violet-400/10',  iconRing: 'ring-violet-400/30',  activeBorder: 'border-violet-400/50',  activeGlow: 'shadow-[0_0_16px_oklch(0.65_0.22_292/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-violet-400/70 to-transparent',  labelColor: 'text-violet-400' },
    { id: 'text',     labelKey: 'qr.tabs.text',     descKey: 'qr.tabs.desc.text',     icon: AlignLeft,      iconColor: 'text-slate-300',   iconBg: 'bg-slate-400/10',   iconRing: 'ring-slate-400/30',   activeBorder: 'border-slate-400/50',   activeGlow: 'shadow-[0_0_16px_oklch(0.65_0.01_272/0.2)]',   topBorder: 'bg-gradient-to-r from-transparent via-slate-400/70 to-transparent',   labelColor: 'text-slate-300' },
    { id: 'email',    labelKey: 'qr.tabs.email',    descKey: 'qr.tabs.desc.email',    icon: Mail,           iconColor: 'text-gold-500',    iconBg: 'bg-gold-500/10',    iconRing: 'ring-gold-500/30',    activeBorder: 'border-gold-500/50',    activeGlow: 'shadow-[0_0_16px_oklch(0.78_0.15_85/0.18)]',   topBorder: 'bg-gradient-to-r from-transparent via-gold-500/70 to-transparent',    labelColor: 'text-gold-500' },
    { id: 'phone',    labelKey: 'qr.tabs.phone',    descKey: 'qr.tabs.desc.phone',    icon: Phone,          iconColor: 'text-green-400',   iconBg: 'bg-green-400/10',   iconRing: 'ring-green-400/30',   activeBorder: 'border-green-400/50',   activeGlow: 'shadow-[0_0_16px_oklch(0.65_0.19_142/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-green-400/70 to-transparent',   labelColor: 'text-green-400' },
    { id: 'sms',      labelKey: 'qr.tabs.sms',      descKey: 'qr.tabs.desc.sms',      icon: MessageSquare,  iconColor: 'text-teal-400',    iconBg: 'bg-teal-400/10',    iconRing: 'ring-teal-400/30',    activeBorder: 'border-teal-400/50',    activeGlow: 'shadow-[0_0_16px_oklch(0.70_0.14_180/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-teal-400/70 to-transparent',    labelColor: 'text-teal-400' },
    { id: 'vcard',    labelKey: 'qr.tabs.vcard',    descKey: 'qr.tabs.desc.vcard',    icon: Contact,        iconColor: 'text-cyan-400',    iconBg: 'bg-cyan-400/10',    iconRing: 'ring-cyan-400/30',    activeBorder: 'border-cyan-400/50',    activeGlow: 'shadow-[0_0_16px_oklch(0.72_0.15_200/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-cyan-400/70 to-transparent',    labelColor: 'text-cyan-400' },
    { id: 'wifi',     labelKey: 'qr.tabs.wifi',     descKey: 'qr.tabs.desc.wifi',     icon: Wifi,           iconColor: 'text-sky-400',     iconBg: 'bg-sky-400/10',     iconRing: 'ring-sky-400/30',     activeBorder: 'border-sky-400/50',     activeGlow: 'shadow-[0_0_16px_oklch(0.67_0.17_220/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-sky-400/70 to-transparent',     labelColor: 'text-sky-400' },
    { id: 'geo',      labelKey: 'qr.tabs.geo',      descKey: 'qr.tabs.desc.geo',      icon: MapPin,         iconColor: 'text-emerald-400', iconBg: 'bg-emerald-400/10', iconRing: 'ring-emerald-400/30', activeBorder: 'border-emerald-400/50', activeGlow: 'shadow-[0_0_16px_oklch(0.69_0.17_162/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-emerald-400/70 to-transparent', labelColor: 'text-emerald-400' },
    { id: 'pdf',      labelKey: 'qr.tabs.pdf',      descKey: 'qr.tabs.desc.pdf',      icon: FileText,       iconColor: 'text-red-400',     iconBg: 'bg-red-400/10',     iconRing: 'ring-red-400/30',     activeBorder: 'border-red-400/50',     activeGlow: 'shadow-[0_0_16px_oklch(0.65_0.22_25/0.18)]',   topBorder: 'bg-gradient-to-r from-transparent via-red-400/70 to-transparent',     labelColor: 'text-red-400' },
    { id: 'bio_link', labelKey: 'qr.tabs.bio_link', descKey: 'qr.tabs.desc.bio_link', icon: Link2,          iconColor: 'text-pink-400',    iconBg: 'bg-pink-400/10',    iconRing: 'ring-pink-400/30',    activeBorder: 'border-pink-400/50',    activeGlow: 'shadow-[0_0_16px_oklch(0.70_0.20_340/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-pink-400/70 to-transparent',    labelColor: 'text-pink-400' },
    { id: 'app',      labelKey: 'qr.tabs.app',      descKey: 'qr.tabs.desc.app',      icon: Smartphone,     iconColor: 'text-indigo-400',  iconBg: 'bg-indigo-400/10',  iconRing: 'ring-indigo-400/30',  activeBorder: 'border-indigo-400/50',  activeGlow: 'shadow-[0_0_16px_oklch(0.55_0.25_270/0.18)]',  topBorder: 'bg-gradient-to-r from-transparent via-indigo-400/70 to-transparent',  labelColor: 'text-indigo-400' },
    { id: 'calendar', labelKey: 'qr.tabs.calendar', descKey: 'qr.tabs.desc.calendar', icon: Calendar,       iconColor: 'text-rose-400',    iconBg: 'bg-rose-400/10',    iconRing: 'ring-rose-400/30',    activeBorder: 'border-rose-400/50',    activeGlow: 'shadow-[0_0_16px_oklch(0.67_0.22_15/0.18)]',   topBorder: 'bg-gradient-to-r from-transparent via-rose-400/70 to-transparent',    labelColor: 'text-rose-400' },
    { id: 'crypto',   labelKey: 'qr.tabs.crypto',   descKey: 'qr.tabs.desc.crypto',   icon: Bitcoin,        iconColor: 'text-amber-400',   iconBg: 'bg-amber-400/10',   iconRing: 'ring-amber-400/30',   activeBorder: 'border-amber-400/50',   activeGlow: 'shadow-[0_0_16px_oklch(0.76_0.17_70/0.18)]',   topBorder: 'bg-gradient-to-r from-transparent via-amber-400/70 to-transparent',   labelColor: 'text-amber-400' },
    { id: 'review',   labelKey: 'qr.tabs.review',   descKey: 'qr.tabs.desc.review',   icon: Star,           iconColor: 'text-orange-400',  iconBg: 'bg-orange-400/10',  iconRing: 'ring-orange-400/30',  activeBorder: 'border-orange-400/50',  activeGlow: 'shadow-[0_0_16px_oklch(0.70_0.18_40/0.18)]',   topBorder: 'bg-gradient-to-r from-transparent via-orange-400/70 to-transparent',  labelColor: 'text-orange-400' },
]

const carouselRef = ref<HTMLDivElement | null>(null)
function scrollCarousel(dir: 'left' | 'right') {
    carouselRef.value?.scrollBy({ left: dir === 'left' ? -220 : 220, behavior: 'smooth' })
}

// QR data capacity: conservative limit for good scannability at ECC M
const MAX_CHARS = 900

const ALLOWED_URL_SCHEMES = ['https://', 'http://']

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

const { isOnline, drafts, saveDraft, deleteDraft } = useOfflineDrafts()

let currentDraftId: string | undefined
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null

function scheduleAutoSave() {
    if (autoSaveTimer) clearTimeout(autoSaveTimer)
    autoSaveTimer = setTimeout(async () => {
        if (!qrTitle.value.trim()) return
        currentDraftId = await saveDraft({
            id: currentDraftId,
            title: qrTitle.value,
            type: activeTab.value,
            formData: { url: url.value, text: text.value },
        })
    }, 2000)
}

function loadFromDraft(draft: import('@/composables/useOfflineDrafts').QrDraft) {
    qrTitle.value = draft.title
    activeTab.value = draft.type as typeof activeTab.value
    if (draft.formData.url) url.value = draft.formData.url as string
    if (draft.formData.text) text.value = draft.formData.text as string
    currentDraftId = draft.id
}

watch([qrTitle, url, text, activeTab], scheduleAutoSave)

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
        <OfflineBanner
            :is-online="isOnline"
            :drafts="drafts"
            @load-draft="loadFromDraft"
            @delete-draft="deleteDraft"
            @sync="() => {}"
        />

        <!-- Page header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20 shrink-0">
                    <QrCode class="size-5 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent">
                        {{ t('qr.create.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-0.5">{{ t('qr.create.subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- Two-panel layout: mobile stack, desktop side-by-side -->
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <!-- Form panel -->
            <div class="flex-1 min-w-0 space-y-5">
                <!-- QR Code title -->
                <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ t('qr.create.fields.title') }}</label>
                        <Input
                            v-model="qrTitle"
                            :placeholder="t('qr.create.fields.titlePlaceholder')"
                            autocomplete="off"
                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                        />
                    </div>
                </div>

                <!-- Type tabs + content -->
                <div class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-border/80 transition-colors duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent" />
                    <div class="p-5">
                        <Tabs v-model="activeTab" class="w-full">
                            <!-- Type selector — karuzela z przewijaniem -->
                            <div class="relative">
                                <!-- Left arrow + fade -->
                                <div class="absolute left-0 top-0 bottom-1 z-10 flex items-center">
                                    <div class="pointer-events-none absolute inset-y-0 left-6 w-6 bg-gradient-to-r from-card to-transparent" />
                                    <button
                                        type="button"
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full border border-border bg-card shadow-sm transition-all duration-150 hover:bg-muted hover:shadow-[0_0_8px_oklch(0.66_0.25_285/0.2)]"
                                        @click="scrollCarousel('left')"
                                    >
                                        <ChevronLeft class="size-3.5 text-muted-foreground" />
                                    </button>
                                </div>

                                <!-- Scrollable strip -->
                                <TooltipProvider :delay-duration="300">
                                    <div
                                        ref="carouselRef"
                                        class="flex gap-2 overflow-x-auto scroll-smooth px-9 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                    >
                                        <Tooltip
                                            v-for="type in QR_TYPES"
                                            :key="type.id"
                                        >
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="group relative flex w-[68px] shrink-0 flex-col items-center gap-1.5 overflow-hidden rounded-xl border p-2.5 transition-all duration-200"
                                                    :class="activeTab === type.id
                                                        ? [type.activeBorder, type.activeGlow, 'bg-card']
                                                        : 'border-border bg-transparent hover:border-border/70 hover:bg-muted/25'"
                                                    @click="activeTab = type.id"
                                                >
                                                    <!-- Gradient top-border (active only) -->
                                                    <div
                                                        v-if="activeTab === type.id"
                                                        class="absolute inset-x-0 top-0 h-px"
                                                        :class="type.topBorder"
                                                    />
                                                    <!-- Icon circle -->
                                                    <div
                                                        class="flex size-8 items-center justify-center rounded-full ring-1 transition-all duration-200"
                                                        :class="activeTab === type.id
                                                            ? [type.iconBg, type.iconRing, 'scale-110']
                                                            : 'bg-muted/50 ring-border group-hover:bg-muted'"
                                                    >
                                                        <component
                                                            :is="type.icon"
                                                            class="size-4 transition-colors duration-200"
                                                            :class="activeTab === type.id ? type.iconColor : 'text-muted-foreground group-hover:text-foreground/70'"
                                                        />
                                                    </div>
                                                    <!-- Label -->
                                                    <span
                                                        class="text-center text-[10px] font-medium leading-tight transition-colors duration-200"
                                                        :class="activeTab === type.id ? type.labelColor : 'text-muted-foreground group-hover:text-foreground/70'"
                                                    >{{ t(type.labelKey) }}</span>
                                                </button>
                                            </TooltipTrigger>
                                            <TooltipContent side="bottom" class="max-w-[180px] text-center text-xs">
                                                <p class="font-semibold" :class="type.labelColor">{{ t(type.labelKey) }}</p>
                                                <p class="mt-0.5 text-muted-foreground">{{ t(type.descKey) }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>

                                <!-- Right arrow + fade -->
                                <div class="absolute right-0 top-0 bottom-1 z-10 flex items-center">
                                    <div class="pointer-events-none absolute inset-y-0 right-6 w-6 bg-gradient-to-l from-card to-transparent" />
                                    <button
                                        type="button"
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full border border-border bg-card shadow-sm transition-all duration-150 hover:bg-muted hover:shadow-[0_0_8px_oklch(0.66_0.25_285/0.2)]"
                                        @click="scrollCarousel('right')"
                                    >
                                        <ChevronRight class="size-3.5 text-muted-foreground" />
                                    </button>
                                </div>
                            </div>

                            <!-- URL -->
                            <TabsContent value="url" class="mt-4 space-y-2">
                                <label class="text-sm font-medium">{{ t('qr.fields.url.label') }}</label>
                                <Input
                                    v-model="url"
                                    type="url"
                                    :placeholder="t('qr.fields.url.placeholder')"
                                    :aria-invalid="urlError !== null"
                                    autocomplete="off"
                                    class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-primary/50 focus-visible:ring-primary/50 focus-visible:ring-[3px] resize-none transition-[color,box-shadow]"
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
                                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.email.subject') }}</label>
                                    <Input
                                        v-model="emailSubject"
                                        :placeholder="t('qr.fields.email.subjectPlaceholder')"
                                        autocomplete="off"
                                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.email.body') }}</label>
                                    <textarea
                                        v-model="emailBody"
                                        rows="4"
                                        :placeholder="t('qr.fields.email.bodyPlaceholder')"
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-primary/50 focus-visible:ring-primary/50 focus-visible:ring-[3px] resize-none transition-[color,box-shadow]"
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
                                    class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.sms.message') }}</label>
                                    <textarea
                                        v-model="smsMessage"
                                        rows="4"
                                        :placeholder="t('qr.fields.sms.messagePlaceholder')"
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-primary/50 focus-visible:ring-primary/50 focus-visible:ring-[3px] resize-none transition-[color,box-shadow]"
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
                                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                                                'rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors duration-150',
                                                wifiSecurity === sec
                                                    ? 'bg-primary text-primary-foreground border-primary'
                                                    : 'border-border text-muted-foreground hover:border-primary/50 hover:text-foreground',
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
                                        class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
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
                                            class="focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                        />
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground">{{ t('qr.fields.geo.hint') }}</p>
                                <a
                                    v-if="geoLat.trim() && geoLng.trim()"
                                    :href="`https://www.google.com/maps?q=${geoLat.trim()},${geoLng.trim()}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-xs text-primary underline underline-offset-2 hover:text-primary/80 transition-colors duration-150"
                                >{{ t('qr.fields.geo.openMaps') }}</a>
                            </TabsContent>

                            <!-- vCard -->
                            <TabsContent value="vcard" class="mt-4 space-y-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium">{{ t('qr.fields.vcard.firstName') }}</label>
                                        <Input v-model="vcardFirstName" :placeholder="t('qr.fields.vcard.firstNamePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium">{{ t('qr.fields.vcard.lastName') }}</label>
                                        <Input v-model="vcardLastName" :placeholder="t('qr.fields.vcard.lastNamePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.company') }}</label>
                                        <Input v-model="vcardCompany" :placeholder="t('qr.fields.vcard.companyPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.jobTitle') }}</label>
                                        <Input v-model="vcardJobTitle" :placeholder="t('qr.fields.vcard.jobTitlePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.phone') }}</label>
                                        <Input v-model="vcardPhone" type="tel" :placeholder="t('qr.fields.vcard.phonePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.email') }}</label>
                                        <Input v-model="vcardEmail" type="email" :placeholder="t('qr.fields.vcard.emailPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.website') }}</label>
                                    <Input v-model="vcardWebsite" type="url" :placeholder="t('qr.fields.vcard.websitePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-muted-foreground">{{ t('qr.fields.vcard.address') }}</label>
                                    <Input v-model="vcardAddress" :placeholder="t('qr.fields.vcard.addressPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>
                            </TabsContent>

                            <!-- PDF Menu -->
                            <TabsContent value="pdf" class="mt-4 space-y-4">
                                <p class="text-sm text-muted-foreground">{{ t('qr.fields.pdf.hint') }}</p>
                                <label
                                    class="flex cursor-pointer flex-col items-center gap-3 rounded-lg border-2 border-dashed border-border p-6 text-center transition-all duration-200 hover:border-primary/60 hover:bg-primary/5"
                                    :class="{ 'border-primary bg-primary/5': pdfFile !== null }"
                                >
                                    <div class="flex size-12 items-center justify-center rounded-xl bg-muted ring-1 ring-border">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="size-6 text-muted-foreground"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
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
                                <div class="relative rounded-lg border border-primary/20 bg-primary/5 p-4 space-y-2 overflow-hidden">
                                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
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
                                    <Input v-model="appIosUrl" type="url" :placeholder="t('qr.fields.app.iosPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.app.androidUrl') }}</label>
                                    <Input v-model="appAndroidUrl" type="url" :placeholder="t('qr.fields.app.androidPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.app.fallbackUrl') }}</label>
                                    <Input v-model="appFallbackUrl" type="url" :placeholder="t('qr.fields.app.fallbackPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    <p class="text-xs text-muted-foreground">{{ t('qr.fields.app.fallbackHint') }}</p>
                                </div>
                            </TabsContent>

                            <!-- Calendar -->
                            <TabsContent value="calendar" class="mt-4 space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.calendar.title') }} *</label>
                                    <Input v-model="calendarTitle" :placeholder="t('qr.fields.calendar.titlePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        id="cal-allday"
                                        v-model="calendarAllDay"
                                        type="checkbox"
                                        class="rounded accent-primary"
                                    >
                                    <label for="cal-allday" class="text-sm cursor-pointer">{{ t('qr.fields.calendar.allDay') }}</label>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <label class="text-sm font-medium">{{ t('qr.fields.calendar.start') }} *</label>
                                        <Input v-model="calendarStart" :type="calendarAllDay ? 'date' : 'datetime-local'" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-medium">{{ t('qr.fields.calendar.end') }}</label>
                                        <Input v-model="calendarEnd" :type="calendarAllDay ? 'date' : 'datetime-local'" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.calendar.location') }}</label>
                                    <Input v-model="calendarLocation" :placeholder="t('qr.fields.calendar.locationPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.calendar.description') }}</label>
                                    <textarea
                                        v-model="calendarDescription"
                                        rows="3"
                                        maxlength="1000"
                                        :placeholder="t('qr.fields.calendar.descriptionPlaceholder')"
                                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus-visible:border-primary/50 focus-visible:ring-primary/50 focus-visible:ring-[3px] transition-[color,box-shadow]"
                                    />
                                </div>
                            </TabsContent>

                            <!-- Crypto Address -->
                            <TabsContent value="crypto" class="mt-4 space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.crypto.coin') }}</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="coin in CRYPTO_COINS"
                                            :key="coin"
                                            type="button"
                                            class="rounded-md border px-3 py-1.5 text-sm font-medium transition-colors duration-150"
                                            :class="cryptoCoin === coin ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:border-primary/50 hover:text-foreground'"
                                            @click="cryptoCoin = coin"
                                        >
                                            {{ t('qr.fields.crypto.coins.' + coin) }}
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.crypto.address') }} *</label>
                                    <Input v-model="cryptoAddress" :placeholder="t('qr.fields.crypto.addressPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <label class="text-sm font-medium">{{ t('qr.fields.crypto.amount') }}</label>
                                        <Input v-model="cryptoAmount" type="number" step="any" min="0" placeholder="0.001" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-medium">{{ t('qr.fields.crypto.label') }}</label>
                                        <Input v-model="cryptoLabel" :placeholder="t('qr.fields.crypto.labelPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.crypto.message') }}</label>
                                    <Input v-model="cryptoMessage" :placeholder="t('qr.fields.crypto.messagePlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                </div>

                                <p v-if="cryptoAddress.trim()" class="break-all rounded-lg bg-muted px-3 py-2 font-mono text-xs text-muted-foreground border border-border">
                                    {{ buildCryptoPreview() }}
                                </p>
                            </TabsContent>

                            <!-- Review -->
                            <TabsContent value="review" class="mt-4 space-y-4">
                                <p class="text-sm text-muted-foreground">{{ t('qr.fields.review.hint') }}</p>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.review.platform') }}</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="platform in REVIEW_PLATFORMS"
                                            :key="platform"
                                            type="button"
                                            class="rounded-md border px-3 py-1.5 text-sm font-medium transition-colors duration-150"
                                            :class="reviewPlatform === platform ? 'border-primary bg-primary/10 text-primary' : 'border-border text-muted-foreground hover:border-primary/50 hover:text-foreground'"
                                            @click="reviewPlatform = platform"
                                        >
                                            {{ t('qr.fields.review.platforms.' + platform) }}
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">{{ t('qr.fields.review.url') }} *</label>
                                    <Input v-model="reviewUrl" type="url" :placeholder="t('qr.fields.review.urlPlaceholder')" autocomplete="off" class="focus-visible:ring-primary/50 focus-visible:border-primary/50" />
                                    <p class="text-xs text-muted-foreground">{{ t('qr.fields.review.urlHint') }}</p>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>

                <!-- Save + char counter -->
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <Button
                        :disabled="!qrTitle.trim() || isSaving || (activeTab === 'pdf' && !pdfFile) || (activeTab === 'app' && !appIosUrl.trim() && !appAndroidUrl.trim())"
                        class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="saveQrCode"
                    >
                        <Save class="size-4" />
                        <span>{{ isSaving ? t('qr.create.saving') : t('qr.create.save') }}</span>
                    </Button>
                    <span v-if="!qrTitle.trim()" class="text-muted-foreground text-xs">
                        {{ t('qr.create.titleRequired') }}
                    </span>
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
                </div>

                <!-- Section divider -->
                <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

                <!-- Style section -->
                <div class="space-y-5">
                    <p class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Style</p>

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
                                :logo-data-url="logoDataUrl"
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

                    <!-- Logo upload -->
                    <LogoUpload
                        :current-logo-url="logoDataUrl"
                        @upload="onLogoUpload"
                        @remove="onLogoRemove"
                    />

                    <!-- Logo size/margin controls -->
                    <LogoControls
                        v-if="logoDataUrl"
                        v-model:logo-size="logoSize"
                        v-model:logo-margin="logoMargin"
                    />

                    <!-- ECC selector -->
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="text-sm font-medium shrink-0">{{ t('qr.ecc.label') }}</label>
                        <div class="flex gap-1">
                            <button
                                v-for="level in ECC_LEVELS"
                                :key="level"
                                type="button"
                                :class="[
                                    'px-3 py-1 rounded-md text-xs font-mono font-semibold border transition-colors duration-150',
                                    eccLevel === level
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'border-border text-muted-foreground hover:border-primary/50 hover:text-foreground',
                                ]"
                                @click="eccLevel = level"
                            >
                                {{ t(`qr.ecc.${level}`) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview panel (sticky on desktop) -->
            <div class="w-full lg:w-80 xl:w-96 shrink-0 lg:sticky lg:top-6">
                <div class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-primary/30 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.08)] transition-all duration-200">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/60 to-transparent" />
                    <div class="p-4 md:p-5">
                        <p class="text-sm font-semibold mb-4">{{ t('qr.preview.title') }}</p>

                        <!-- QR render area -->
                        <div class="flex items-center justify-center w-full min-h-[260px] rounded-lg bg-muted/30">
                            <template v-if="canPreview">
                                <QrFrame
                                    :data="qrData"
                                    :size="240"
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
                            <div v-else class="flex flex-col items-center justify-center py-8 text-center px-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-muted ring-1 ring-border mb-3">
                                    <QrCode class="size-6 text-muted-foreground" />
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ t('qr.preview.empty') }}
                                </p>
                            </div>
                        </div>

                        <!-- Contrast warning -->
                        <div
                            v-if="contrast && contrast.level !== 'good'"
                            :class="[
                                'mt-3 flex items-start gap-2 rounded-md px-3 py-2 text-xs',
                                contrast.level === 'warn'
                                    ? 'bg-yellow-500/10 text-yellow-300 border border-yellow-500/30'
                                    : 'bg-destructive/10 text-destructive border border-destructive/30',
                            ]"
                        >
                            <span class="mt-0.5 shrink-0">{{ contrast.level === 'warn' ? '⚠️' : '🚫' }}</span>
                            <span>{{ t(`qr.contrast.${contrast.level}`, { ratio: contrast.ratio }) }}</span>
                        </div>

                        <!-- Export trigger -->
                        <Button
                            v-if="canPreview"
                            class="mt-4 w-full gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                            @click="exportOpen = true"
                        >
                            <Download class="size-4" />
                            <span>{{ t('qr.export.trigger') }}</span>
                        </Button>
                    </div>
                </div>
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
