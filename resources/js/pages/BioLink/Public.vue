<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

interface BioLinkItem {
    id: number
    title: string
    icon: string | null
    click_url: string
}

interface BioLinkData {
    title: string
    bio: string | null
    template: 'minimal' | 'bold' | 'glassmorphism' | 'retro'
    theme: {
        primary_color: string
        bg_color: string
        text_color: string
        button_shape: 'rounded' | 'square' | 'pill'
        font: 'inter' | 'serif' | 'mono'
    }
    avatar_url: string | null
}

const props = defineProps<{
    bioLink: BioLinkData
    items: BioLinkItem[]
}>()

const t = props.bioLink.theme

const fontClass: Record<string, string> = {
    inter: 'font-sans',
    serif: 'font-serif',
    mono: 'font-mono',
}

const shapeClass: Record<string, string> = {
    rounded: 'rounded-lg',
    square: 'rounded-none',
    pill: 'rounded-full',
}
</script>

<template>
    <Head :title="props.bioLink.title" />

    <!-- Minimal template -->
    <div
        v-if="props.bioLink.template === 'minimal'"
        class="min-h-screen px-4 py-12"
        :class="fontClass[t.font]"
        :style="{ background: t.bg_color, color: t.text_color }"
    >
        <div class="mx-auto max-w-sm space-y-6">
            <!-- Avatar -->
            <div class="flex flex-col items-center gap-3">
                <img
                    v-if="props.bioLink.avatar_url"
                    :src="props.bioLink.avatar_url"
                    :alt="props.bioLink.title"
                    class="size-20 rounded-full object-cover"
                >
                <div v-else class="flex size-20 items-center justify-center rounded-full text-4xl" :style="{ background: t.primary_color + '22' }">👤</div>
                <h1 class="text-lg font-semibold">{{ props.bioLink.title }}</h1>
                <p v-if="props.bioLink.bio" class="text-center text-sm opacity-70">{{ props.bioLink.bio }}</p>
            </div>

            <!-- Links -->
            <div class="space-y-3">
                <a
                    v-for="item in props.items"
                    :key="item.id"
                    :href="item.click_url"
                    class="flex w-full items-center justify-center gap-2 border px-4 py-3 text-sm font-medium transition-opacity hover:opacity-80"
                    :class="shapeClass[t.button_shape]"
                    :style="{ borderColor: t.primary_color, color: t.primary_color }"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>
    </div>

    <!-- Bold template -->
    <div
        v-else-if="props.bioLink.template === 'bold'"
        class="min-h-screen"
        :class="fontClass[t.font]"
    >
        <!-- Header -->
        <div class="px-4 py-14 text-center text-white" :style="{ background: t.primary_color }">
            <img
                v-if="props.bioLink.avatar_url"
                :src="props.bioLink.avatar_url"
                :alt="props.bioLink.title"
                class="mx-auto mb-4 size-24 rounded-full object-cover ring-4 ring-white/30"
            >
            <div v-else class="mx-auto mb-4 flex size-24 items-center justify-center rounded-full text-5xl ring-4 ring-white/30" :style="{ background: 'rgba(255,255,255,0.15)' }">👤</div>
            <h1 class="text-2xl font-bold">{{ props.bioLink.title }}</h1>
            <p v-if="props.bioLink.bio" class="mt-2 text-sm text-white/80">{{ props.bioLink.bio }}</p>
        </div>

        <!-- Links -->
        <div class="mx-auto max-w-sm space-y-3 px-4 py-8" :style="{ background: t.bg_color, color: t.text_color }">
            <a
                v-for="item in props.items"
                :key="item.id"
                :href="item.click_url"
                class="flex w-full items-center justify-center gap-2 px-4 py-3.5 text-sm font-bold text-white transition-opacity hover:opacity-90"
                :class="shapeClass[t.button_shape]"
                :style="{ background: t.primary_color }"
            >
                <span v-if="item.icon">{{ item.icon }}</span>
                {{ item.title }}
            </a>
        </div>
    </div>

    <!-- Glassmorphism template -->
    <div
        v-else-if="props.bioLink.template === 'glassmorphism'"
        class="relative min-h-screen overflow-hidden px-4 py-12"
        :class="fontClass[t.font]"
        :style="{ background: `linear-gradient(135deg, ${t.primary_color} 0%, #6366f1 50%, #a855f7 100%)` }"
    >
        <!-- Blurred background blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-20 -top-20 size-96 rounded-full opacity-30 blur-3xl" :style="{ background: t.primary_color }" />
            <div class="absolute -bottom-20 -right-20 size-96 rounded-full bg-purple-400 opacity-30 blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-sm space-y-6">
            <!-- Profile card -->
            <div class="rounded-2xl p-6 text-center text-white backdrop-blur-md" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25)">
                <img
                    v-if="props.bioLink.avatar_url"
                    :src="props.bioLink.avatar_url"
                    :alt="props.bioLink.title"
                    class="mx-auto mb-4 size-20 rounded-full object-cover ring-2 ring-white/40"
                >
                <div v-else class="mx-auto mb-4 flex size-20 items-center justify-center rounded-full text-4xl" style="background: rgba(255,255,255,0.2)">👤</div>
                <h1 class="text-xl font-bold">{{ props.bioLink.title }}</h1>
                <p v-if="props.bioLink.bio" class="mt-2 text-sm text-white/80">{{ props.bioLink.bio }}</p>
            </div>

            <!-- Links -->
            <div class="space-y-3">
                <a
                    v-for="item in props.items"
                    :key="item.id"
                    :href="item.click_url"
                    class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-white backdrop-blur-sm transition-all hover:scale-[1.02]"
                    :class="shapeClass[t.button_shape]"
                    style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25)"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>
    </div>

    <!-- Retro template -->
    <div
        v-else-if="props.bioLink.template === 'retro'"
        class="min-h-screen px-4 py-12"
        :class="fontClass[t.font]"
        :style="{ background: '#fef9f0', color: '#3d2c1e' }"
    >
        <div class="mx-auto max-w-sm space-y-6">
            <!-- Profile -->
            <div class="text-center">
                <div class="mb-4 flex justify-center">
                    <img
                        v-if="props.bioLink.avatar_url"
                        :src="props.bioLink.avatar_url"
                        :alt="props.bioLink.title"
                        class="size-20 rounded-full object-cover"
                        style="border: 3px solid #3d2c1e; box-shadow: 4px 4px 0 #3d2c1e"
                    >
                    <div v-else class="flex size-20 items-center justify-center rounded-full text-4xl" style="border: 3px solid #3d2c1e; box-shadow: 4px 4px 0 #3d2c1e">👤</div>
                </div>
                <h1 class="text-2xl font-bold" style="font-family: Georgia, serif; letter-spacing: -0.5px">{{ props.bioLink.title }}</h1>
                <p v-if="props.bioLink.bio" class="mt-2 text-sm opacity-70">{{ props.bioLink.bio }}</p>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-2">
                <div class="h-px flex-1 border-t-2 border-dashed border-current opacity-30" />
                <span class="text-xs opacity-40">✦</span>
                <div class="h-px flex-1 border-t-2 border-dashed border-current opacity-30" />
            </div>

            <!-- Links -->
            <div class="space-y-3">
                <a
                    v-for="item in props.items"
                    :key="item.id"
                    :href="item.click_url"
                    class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-medium transition-all hover:translate-x-0.5 hover:translate-y-0.5"
                    :class="shapeClass[t.button_shape]"
                    :style="{
                        background: '#fef9f0',
                        color: '#3d2c1e',
                        border: '2px solid #3d2c1e',
                        boxShadow: '3px 3px 0 #3d2c1e',
                    }"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>
    </div>
</template>
