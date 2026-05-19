<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowUpDown, ExternalLink, Link2, Palette, Save, Sparkles, User } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const { t } = useI18n()

const aiProfession = ref('')
const suggestingBio = ref(false)
const bioLinkSuggestions = ref<string[]>([])

async function suggestBio() {
    if (!aiProfession.value.trim() || suggestingBio.value) return
    suggestingBio.value = true
    bioLinkSuggestions.value = []
    try {
        const res = await fetch('/api/ai/suggest-bio-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ profession: aiProfession.value.trim() }),
        })
        if (res.ok) {
            const data = await res.json() as { bio: string; emoji: string; link_suggestions: string[] }
            profileForm.bio = data.bio
            bioLinkSuggestions.value = data.link_suggestions ?? []
        }
    } finally {
        suggestingBio.value = false
    }
}

interface BioLinkItem {
    id: number
    title: string
    url: string
    icon: string | null
    sort_order: number
    is_active: boolean
    click_count: number
}

interface BioLinkProp {
    id: number
    slug: string
    title: string
    bio: string | null
    template: string
    theme: Record<string, string>
    avatar_url: string | null
    public_url: string
}

const props = defineProps<{
    bioLink: BioLinkProp
    items: BioLinkItem[]
    templates: string[]
}>()

// ── Profile form ────────────────────────────────────────────────────
const profileForm = useForm({
    bio: props.bioLink.bio ?? '',
    template: props.bioLink.template,
    theme: { ...props.bioLink.theme },
})

function saveProfile() {
    profileForm.patch(`/bio-links/${props.bioLink.id}`)
}

// ── Avatar ──────────────────────────────────────────────────────────
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(props.bioLink.avatar_url)
const isUploadingAvatar = ref(false)

function onAvatarChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
}

function uploadAvatar() {
    if (!avatarFile.value) return
    isUploadingAvatar.value = true
    router.post(
        `/bio-links/${props.bioLink.id}/avatar`,
        { avatar: avatarFile.value },
        {
            onFinish: () => { isUploadingAvatar.value = false; avatarFile.value = null },
        },
    )
}

function deleteAvatar() {
    router.delete(`/bio-links/${props.bioLink.id}/avatar`, { preserveScroll: true })
    avatarPreview.value = null
}

// ── Links ───────────────────────────────────────────────────────────
const showAddForm = ref(false)
const addForm = useForm({ title: '', url: '', icon: '' })

function submitAdd() {
    addForm.post(`/bio-links/${props.bioLink.id}/items`, {
        preserveScroll: true,
        onSuccess: () => { addForm.reset(); showAddForm.value = false },
    })
}

// Editing an existing item
const editingId = ref<number | null>(null)
const editForm = useForm({ title: '', url: '', icon: '', is_active: true })

function startEdit(item: BioLinkItem) {
    editingId.value = item.id
    editForm.title = item.title
    editForm.url = item.url
    editForm.icon = item.icon ?? ''
    editForm.is_active = item.is_active
}

function cancelEdit() {
    editingId.value = null
    editForm.reset()
}

function submitEdit(item: BioLinkItem) {
    editForm.patch(`/bio-links/${props.bioLink.id}/items/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    })
}

function deleteItem(item: BioLinkItem) {
    if (!confirm(t('bioLink.items.deleteConfirm'))) return
    router.delete(`/bio-links/${props.bioLink.id}/items/${item.id}`, { preserveScroll: true })
}

function moveUp(item: BioLinkItem) {
    const idx = props.items.findIndex((i) => i.id === item.id)
    if (idx <= 0) return
    const ids = props.items.map((i) => i.id)
    const tmp = ids[idx - 1]!
    ids[idx - 1] = ids[idx]!
    ids[idx] = tmp
    router.post(`/bio-links/${props.bioLink.id}/items/reorder`, { ids }, { preserveScroll: true })
}

function moveDown(item: BioLinkItem) {
    const idx = props.items.findIndex((i) => i.id === item.id)
    if (idx < 0 || idx >= props.items.length - 1) return
    const ids = props.items.map((i) => i.id)
    const tmp = ids[idx]!
    ids[idx] = ids[idx + 1]!
    ids[idx + 1] = tmp
    router.post(`/bio-links/${props.bioLink.id}/items/reorder`, { ids }, { preserveScroll: true })
}

// ── Template labels ─────────────────────────────────────────────────
const TEMPLATE_LABELS: Record<string, string> = {
    minimal: 'Minimal',
    bold: 'Bold',
    glassmorphism: 'Glassmorphism',
    retro: 'Retro',
}
</script>

<template>
    <Head :title="props.bioLink.title + ' — Bio-Link'" />

    <div class="mx-auto max-w-2xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="relative rounded-xl border border-border bg-card p-5 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/20">
                        <Link2 class="size-5 text-primary" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl font-bold bg-gradient-to-r from-violet-400 via-primary to-cyan-400 bg-clip-text text-transparent truncate">
                            {{ props.bioLink.title }}
                        </h1>
                        <a
                            :href="props.bioLink.public_url"
                            target="_blank"
                            class="text-xs text-muted-foreground hover:text-primary transition-colors duration-150 truncate block"
                        >
                            {{ props.bioLink.public_url }}
                        </a>
                    </div>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="gap-1.5 hover:border-primary/40 transition-colors duration-150 shrink-0"
                    as="a"
                    :href="props.bioLink.public_url"
                    target="_blank"
                >
                    <ExternalLink class="size-3.5" />
                    {{ t('bioLink.preview') }}
                </Button>
            </div>
        </div>

        <!-- Section divider -->
        <div class="h-px bg-gradient-to-r from-transparent via-border/60 to-transparent" />

        <Tabs default-value="profile">
            <TabsList class="w-full">
                <TabsTrigger value="profile" class="flex-1 gap-1.5">
                    <User class="size-3.5" />
                    {{ t('bioLink.tabs.profile') }}
                </TabsTrigger>
                <TabsTrigger value="links" class="flex-1 gap-1.5">
                    <Link2 class="size-3.5" />
                    {{ t('bioLink.tabs.links') }}
                </TabsTrigger>
                <TabsTrigger value="appearance" class="flex-1 gap-1.5">
                    <Palette class="size-3.5" />
                    {{ t('bioLink.tabs.appearance') }}
                </TabsTrigger>
            </TabsList>

            <!-- Profile tab -->
            <TabsContent value="profile" class="mt-4 space-y-6">
                <!-- Avatar -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
                    <p class="text-sm font-semibold">{{ t('bioLink.profile.avatar') }}</p>
                    <div class="flex items-center gap-4">
                        <div class="relative size-20 overflow-hidden rounded-full border-2 border-border bg-muted ring-2 ring-primary/10 transition-all duration-200 hover:ring-primary/30">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="props.bioLink.title"
                                class="size-full object-cover"
                            >
                            <span v-else class="flex size-full items-center justify-center text-2xl text-muted-foreground">
                                <User class="size-8" />
                            </span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="cursor-pointer">
                                <span class="inline-flex items-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-secondary hover:border-primary/30 transition-colors duration-150">
                                    {{ t('bioLink.profile.changeAvatar') }}
                                </span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    @change="onAvatarChange"
                                >
                            </label>
                            <Button
                                v-if="avatarFile"
                                size="sm"
                                class="gap-1.5"
                                :disabled="isUploadingAvatar"
                                @click="uploadAvatar"
                            >
                                <Save class="size-3.5" />
                                {{ isUploadingAvatar ? t('common.saving') : t('common.save') }}
                            </Button>
                            <Button
                                v-if="avatarPreview && !avatarFile"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                                @click="deleteAvatar"
                            >
                                {{ t('bioLink.profile.removeAvatar') }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                    <label class="text-sm font-semibold">{{ t('bioLink.profile.bio') }}</label>
                    <textarea
                        v-model="profileForm.bio"
                        rows="3"
                        maxlength="500"
                        :placeholder="t('bioLink.profile.bioPlaceholder')"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary/50 transition-colors duration-150"
                    />
                    <p class="text-right text-xs text-muted-foreground">{{ profileForm.bio.length }}/500</p>

                    <!-- AI bio generator -->
                    <div class="rounded-lg border border-dashed border-primary/30 bg-primary/5 p-4 space-y-3">
                        <p class="text-xs font-semibold text-primary flex items-center gap-1.5">
                            <Sparkles class="size-3.5" />
                            {{ t('ai.suggestBio') }}
                        </p>
                        <div class="flex gap-2">
                            <Input
                                v-model="aiProfession"
                                :placeholder="t('bioLink.ai.professionPlaceholder')"
                                class="flex-1 text-sm h-8 focus-visible:ring-primary/50 focus-visible:border-primary/50"
                                @keydown.enter.prevent="suggestBio"
                            />
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="hover:border-primary/40 transition-colors duration-150"
                                :disabled="suggestingBio || !aiProfession.trim()"
                                @click="suggestBio"
                            >
                                <span v-if="suggestingBio" class="animate-spin">⏳</span>
                                <span v-else>{{ t('bioLink.ai.generate') }}</span>
                            </Button>
                        </div>
                        <!-- Link title suggestions -->
                        <div v-if="bioLinkSuggestions.length" class="space-y-1.5">
                            <p class="text-xs text-muted-foreground">{{ t('bioLink.ai.linkSuggestions') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="s in bioLinkSuggestions"
                                    :key="s"
                                    class="rounded-full bg-background border border-border px-2.5 py-0.5 text-xs hover:border-primary/40 hover:text-primary transition-colors duration-150 cursor-pointer"
                                >
                                    {{ s }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <Button
                    class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                    :disabled="profileForm.processing"
                    @click="saveProfile"
                >
                    <Save class="size-4" />
                    {{ profileForm.processing ? t('common.saving') : t('common.save') }}
                </Button>
            </TabsContent>

            <!-- Links tab -->
            <TabsContent value="links" class="mt-4 space-y-3">
                <!-- Empty state -->
                <div v-if="props.items.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-10 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 ring-1 ring-primary/20 mb-3">
                        <Link2 class="size-7 text-primary" />
                    </div>
                    <p class="text-sm font-medium mb-1">{{ t('bioLink.items.empty') }}</p>
                    <p class="text-xs text-muted-foreground">Add your first link below</p>
                </div>

                <!-- Existing items -->
                <div
                    v-for="(item, idx) in props.items"
                    :key="item.id"
                    class="relative rounded-xl border border-border bg-card overflow-hidden hover:border-border/80 transition-colors duration-150"
                >
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
                    <!-- View mode -->
                    <div v-if="editingId !== item.id" class="flex items-center gap-3 p-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-base">
                            <span v-if="item.icon">{{ item.icon }}</span>
                            <Link2 v-else class="size-4 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium"
                                :class="{ 'text-muted-foreground line-through': !item.is_active }"
                            >
                                {{ item.title }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">{{ item.url }}</p>
                        </div>
                        <span class="text-xs text-muted-foreground shrink-0 hidden sm:block">
                            {{ item.click_count }} {{ t('bioLink.items.clicks') }}
                        </span>
                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 text-muted-foreground hover:text-foreground transition-colors duration-150"
                                :disabled="idx === 0"
                                @click="moveUp(item)"
                            >
                                <ArrowUpDown class="size-3.5 rotate-180" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 text-muted-foreground hover:text-foreground transition-colors duration-150"
                                :disabled="idx === props.items.length - 1"
                                @click="moveDown(item)"
                            >
                                <ArrowUpDown class="size-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-xs hover:text-primary transition-colors duration-150"
                                @click="startEdit(item)"
                            >{{ t('common.edit') }}</Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-xs text-destructive hover:text-destructive hover:bg-destructive/10 transition-colors duration-150"
                                @click="deleteItem(item)"
                            >{{ t('common.delete') }}</Button>
                        </div>
                    </div>

                    <!-- Edit mode -->
                    <div v-else class="space-y-3 p-4">
                        <div class="flex gap-2">
                            <Input
                                v-model="editForm.icon"
                                placeholder="🔗"
                                class="w-16 text-center focus-visible:ring-primary/50"
                                maxlength="4"
                            />
                            <Input
                                v-model="editForm.title"
                                :placeholder="t('bioLink.items.titlePlaceholder')"
                                class="flex-1 focus-visible:ring-primary/50"
                            />
                        </div>
                        <Input
                            v-model="editForm.url"
                            type="url"
                            :placeholder="t('bioLink.items.urlPlaceholder')"
                            class="focus-visible:ring-primary/50"
                        />
                        <div class="flex items-center gap-2">
                            <input
                                id="is-active"
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="rounded accent-primary"
                            >
                            <label for="is-active" class="text-sm">{{ t('bioLink.items.active') }}</label>
                        </div>
                        <div class="flex gap-2">
                            <Button size="sm" :disabled="editForm.processing" @click="submitEdit(item)">{{ t('common.save') }}</Button>
                            <Button size="sm" variant="ghost" @click="cancelEdit">{{ t('common.cancel') }}</Button>
                        </div>
                    </div>
                </div>

                <!-- Add new link -->
                <div v-if="showAddForm" class="relative space-y-3 rounded-xl border border-primary/30 bg-card p-4 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />
                    <div class="flex gap-2">
                        <Input
                            v-model="addForm.icon"
                            placeholder="🔗"
                            class="w-16 text-center focus-visible:ring-primary/50"
                            maxlength="4"
                        />
                        <Input
                            v-model="addForm.title"
                            :placeholder="t('bioLink.items.titlePlaceholder')"
                            class="flex-1 focus-visible:ring-primary/50"
                        />
                    </div>
                    <Input
                        v-model="addForm.url"
                        type="url"
                        :placeholder="t('bioLink.items.urlPlaceholder')"
                        class="focus-visible:ring-primary/50"
                    />
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            :disabled="!addForm.title || !addForm.url || addForm.processing"
                            @click="submitAdd"
                        >
                            {{ t('bioLink.items.add') }}
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="showAddForm = false; addForm.reset()"
                        >{{ t('common.cancel') }}</Button>
                    </div>
                </div>

                <Button
                    v-if="!showAddForm"
                    variant="outline"
                    class="gap-2 hover:border-primary/40 hover:text-primary transition-colors duration-150"
                    @click="showAddForm = true"
                >
                    <span class="text-lg leading-none">+</span>
                    {{ t('bioLink.items.addLink') }}
                </Button>
            </TabsContent>

            <!-- Appearance tab -->
            <TabsContent value="appearance" class="mt-4 space-y-6">
                <!-- Template selector -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent" />
                    <p class="text-sm font-semibold">{{ t('bioLink.appearance.template') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="tpl in props.templates"
                            :key="tpl"
                            type="button"
                            class="flex flex-col items-center gap-2 rounded-xl border-2 p-4 text-sm font-medium transition-all duration-200"
                            :class="profileForm.template === tpl
                                ? 'border-primary bg-primary/10 shadow-[0_0_16px_oklch(0.66_0.25_285/0.15)]'
                                : 'border-border hover:border-primary/40 hover:bg-secondary/50'"
                            @click="profileForm.template = tpl"
                        >
                            <!-- Mini template preview -->
                            <div
                                class="w-full rounded-lg overflow-hidden"
                                :class="{
                                    'bg-secondary': tpl === 'minimal',
                                    'bg-indigo-600': tpl === 'bold',
                                    'bg-gradient-to-br from-purple-500 to-indigo-600': tpl === 'glassmorphism',
                                    'bg-amber-100': tpl === 'retro',
                                }"
                                style="height:56px"
                            />
                            {{ TEMPLATE_LABELS[tpl] ?? tpl }}
                        </button>
                    </div>
                </div>

                <!-- Theme colors -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-4 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                    <p class="text-sm font-semibold">{{ t('bioLink.appearance.colors') }}</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs text-muted-foreground">{{ t('bioLink.appearance.primaryColor') }}</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="profileForm.theme.primary_color"
                                    type="color"
                                    class="size-8 cursor-pointer rounded border border-input"
                                >
                                <span class="font-mono text-xs text-muted-foreground">{{ profileForm.theme.primary_color }}</span>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs text-muted-foreground">{{ t('bioLink.appearance.bgColor') }}</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="profileForm.theme.bg_color"
                                    type="color"
                                    class="size-8 cursor-pointer rounded border border-input"
                                >
                                <span class="font-mono text-xs text-muted-foreground">{{ profileForm.theme.bg_color }}</span>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs text-muted-foreground">{{ t('bioLink.appearance.textColor') }}</label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="profileForm.theme.text_color"
                                    type="color"
                                    class="size-8 cursor-pointer rounded border border-input"
                                >
                                <span class="font-mono text-xs text-muted-foreground">{{ profileForm.theme.text_color }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button shape -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-3 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
                    <p class="text-sm font-semibold">{{ t('bioLink.appearance.buttonShape') }}</p>
                    <div class="flex gap-2">
                        <button
                            v-for="shape in ['rounded', 'square', 'pill']"
                            :key="shape"
                            type="button"
                            class="flex-1 border px-3 py-2 text-sm transition-all duration-150"
                            :class="[
                                profileForm.theme.button_shape === shape
                                    ? 'border-primary bg-primary/10 font-semibold text-primary'
                                    : 'border-border hover:border-primary/40 hover:bg-secondary/50',
                                shape === 'rounded' ? 'rounded-md' : shape === 'pill' ? 'rounded-full' : 'rounded-none',
                            ]"
                            @click="profileForm.theme.button_shape = shape"
                        >
                            {{ t('bioLink.appearance.shapes.' + shape) }}
                        </button>
                    </div>
                </div>

                <!-- Font -->
                <div class="relative rounded-xl border border-border bg-card p-5 space-y-3 overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
                    <p class="text-sm font-semibold">{{ t('bioLink.appearance.font') }}</p>
                    <div class="flex gap-2">
                        <button
                            v-for="font in ['inter', 'serif', 'mono']"
                            :key="font"
                            type="button"
                            class="flex-1 rounded-md border px-3 py-2 text-sm transition-all duration-150"
                            :class="profileForm.theme.font === font
                                ? 'border-primary bg-primary/10 font-semibold text-primary'
                                : 'border-border hover:border-primary/40 hover:bg-secondary/50'"
                            @click="profileForm.theme.font = font"
                        >
                            {{ t('bioLink.appearance.fonts.' + font) }}
                        </button>
                    </div>
                </div>

                <Button
                    class="gap-2 shadow-[0_0_16px_oklch(0.66_0.25_285/0.25)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.45)] transition-shadow duration-200"
                    :disabled="profileForm.processing"
                    @click="saveProfile"
                >
                    <Save class="size-4" />
                    {{ profileForm.processing ? t('common.saving') : t('common.save') }}
                </Button>
            </TabsContent>
        </Tabs>
    </div>
</template>
