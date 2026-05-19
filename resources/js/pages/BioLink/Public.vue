<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { QrCode } from 'lucide-vue-next'

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
        class="min-h-screen px-4 py-12 flex flex-col"
        :class="fontClass[t.font]"
        :style="{ background: t.bg_color, color: t.text_color }"
    >
        <div class="mx-auto w-full max-w-sm flex-1 space-y-6">
            <!-- Profile -->
            <div class="flex flex-col items-center gap-3 pt-4">
                <div
                    class="relative size-20 rounded-full overflow-hidden"
                    :style="{ boxShadow: `0 0 0 3px ${t.primary_color}33, 0 0 0 6px ${t.primary_color}11` }"
                >
                    <img
                        v-if="props.bioLink.avatar_url"
                        :src="props.bioLink.avatar_url"
                        :alt="props.bioLink.title"
                        class="size-full object-cover"
                    >
                    <div
                        v-else
                        class="flex size-full items-center justify-center text-4xl"
                        :style="{ background: t.primary_color + '22' }"
                    >
                        <span class="text-2xl opacity-60">👤</span>
                    </div>
                </div>
                <div class="text-center">
                    <h1 class="text-lg font-bold">{{ props.bioLink.title }}</h1>
                    <p v-if="props.bioLink.bio" class="mt-1 text-center text-sm opacity-70 max-w-xs">{{ props.bioLink.bio }}</p>
                </div>
            </div>

            <!-- Links -->
            <div class="space-y-3">
                <a
                    v-for="item in props.items"
                    :key="item.id"
                    :href="item.click_url"
                    class="flex w-full items-center justify-center gap-2 border px-4 py-3 text-sm font-medium transition-all duration-200 hover:opacity-80 hover:scale-[1.01] active:scale-[0.99]"
                    :class="shapeClass[t.button_shape]"
                    :style="{ borderColor: t.primary_color, color: t.primary_color }"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>

        <!-- Powered by footer -->
        <div class="mx-auto mt-8 flex items-center gap-1.5 opacity-40 hover:opacity-70 transition-opacity duration-200">
            <QrCode class="size-3.5" />
            <span class="text-xs">Powered by QR Master</span>
        </div>
    </div>

    <!-- Bold template -->
    <div
        v-else-if="props.bioLink.template === 'bold'"
        class="min-h-screen flex flex-col"
        :class="fontClass[t.font]"
    >
        <!-- Header -->
        <div class="px-4 py-14 text-center" :style="{ background: t.primary_color }">
            <div
                class="mx-auto mb-4 overflow-hidden"
                :style="{
                    width: '96px',
                    height: '96px',
                    borderRadius: '9999px',
                    boxShadow: '0 0 0 4px rgba(255,255,255,0.2), 0 0 0 8px rgba(255,255,255,0.08)'
                }"
            >
                <img
                    v-if="props.bioLink.avatar_url"
                    :src="props.bioLink.avatar_url"
                    :alt="props.bioLink.title"
                    class="size-full object-cover"
                >
                <div
                    v-else
                    class="flex size-full items-center justify-center text-5xl"
                    style="background: rgba(255,255,255,0.15)"
                >
                    <span>👤</span>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-foreground" style="color: white">{{ props.bioLink.title }}</h1>
            <p v-if="props.bioLink.bio" class="mt-2 text-sm" style="color: rgba(255,255,255,0.8)">{{ props.bioLink.bio }}</p>
        </div>

        <!-- Links -->
        <div class="mx-auto w-full max-w-sm flex-1 space-y-3 px-4 py-8" :style="{ background: t.bg_color, color: t.text_color }">
            <a
                v-for="item in props.items"
                :key="item.id"
                :href="item.click_url"
                class="flex w-full items-center justify-center gap-2 px-4 py-3.5 text-sm font-bold transition-all duration-200 hover:opacity-90 hover:scale-[1.01] active:scale-[0.99]"
                :class="shapeClass[t.button_shape]"
                :style="{ background: t.primary_color, color: 'white' }"
            >
                <span v-if="item.icon">{{ item.icon }}</span>
                {{ item.title }}
            </a>
        </div>

        <!-- Powered by footer -->
        <div class="flex justify-center pb-6" :style="{ background: t.bg_color }">
            <div class="flex items-center gap-1.5 opacity-30 hover:opacity-60 transition-opacity duration-200">
                <QrCode class="size-3.5" />
                <span class="text-xs">Powered by QR Master</span>
            </div>
        </div>
    </div>

    <!-- Glassmorphism template -->
    <div
        v-else-if="props.bioLink.template === 'glassmorphism'"
        class="relative min-h-screen overflow-hidden px-4 py-12 flex flex-col"
        :class="fontClass[t.font]"
        :style="{ background: `linear-gradient(135deg, ${t.primary_color} 0%, #6366f1 50%, #a855f7 100%)` }"
    >
        <!-- Blurred background blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -left-20 -top-20 size-96 rounded-full opacity-30 blur-3xl"
                :style="{ background: t.primary_color }"
            />
            <div class="absolute -bottom-20 -right-20 size-96 rounded-full blur-3xl" style="background: #a855f7; opacity: 0.3" />
        </div>

        <div class="relative mx-auto w-full max-w-sm flex-1 space-y-6">
            <!-- Profile card -->
            <div
                class="rounded-2xl p-6 text-center backdrop-blur-md"
                style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white"
            >
                <div
                    class="mx-auto mb-4 size-20 overflow-hidden rounded-full"
                    style="box-shadow: 0 0 0 2px rgba(255,255,255,0.4)"
                >
                    <img
                        v-if="props.bioLink.avatar_url"
                        :src="props.bioLink.avatar_url"
                        :alt="props.bioLink.title"
                        class="size-full object-cover"
                    >
                    <div
                        v-else
                        class="flex size-full items-center justify-center text-4xl"
                        style="background: rgba(255,255,255,0.2)"
                    >
                        <span>👤</span>
                    </div>
                </div>
                <h1 class="text-xl font-bold">{{ props.bioLink.title }}</h1>
                <p v-if="props.bioLink.bio" class="mt-2 text-sm" style="color: rgba(255,255,255,0.8)">{{ props.bioLink.bio }}</p>
            </div>

            <!-- Links -->
            <div class="space-y-3">
                <a
                    v-for="item in props.items"
                    :key="item.id"
                    :href="item.click_url"
                    class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-medium backdrop-blur-sm transition-all duration-200 hover:scale-[1.02] hover:brightness-110 active:scale-[0.99]"
                    :class="shapeClass[t.button_shape]"
                    style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: white"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>

        <!-- Powered by footer -->
        <div class="relative flex justify-center mt-8">
            <div class="flex items-center gap-1.5 opacity-40 hover:opacity-70 transition-opacity duration-200" style="color: white">
                <QrCode class="size-3.5" />
                <span class="text-xs">Powered by QR Master</span>
            </div>
        </div>
    </div>

    <!-- Retro template -->
    <div
        v-else-if="props.bioLink.template === 'retro'"
        class="min-h-screen px-4 py-12 flex flex-col"
        :class="fontClass[t.font]"
        :style="{ background: t.bg_color || '#fef9f0', color: t.text_color || '#3d2c1e' }"
    >
        <div class="mx-auto w-full max-w-sm flex-1 space-y-6">
            <!-- Profile -->
            <div class="text-center">
                <div class="mb-4 flex justify-center">
                    <img
                        v-if="props.bioLink.avatar_url"
                        :src="props.bioLink.avatar_url"
                        :alt="props.bioLink.title"
                        class="size-20 rounded-full object-cover"
                        :style="{ border: `3px solid ${t.text_color || '#3d2c1e'}`, boxShadow: `4px 4px 0 ${t.text_color || '#3d2c1e'}` }"
                    >
                    <div
                        v-else
                        class="flex size-20 items-center justify-center rounded-full text-4xl"
                        :style="{ border: `3px solid ${t.text_color || '#3d2c1e'}`, boxShadow: `4px 4px 0 ${t.text_color || '#3d2c1e'}` }"
                    >
                        <span>👤</span>
                    </div>
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
                    class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-medium transition-all duration-150 hover:translate-x-0.5 hover:translate-y-0.5 active:translate-x-0 active:translate-y-0"
                    :class="shapeClass[t.button_shape]"
                    :style="{
                        background: t.bg_color || '#fef9f0',
                        color: t.text_color || '#3d2c1e',
                        border: `2px solid ${t.text_color || '#3d2c1e'}`,
                        boxShadow: `3px 3px 0 ${t.text_color || '#3d2c1e'}`,
                    }"
                >
                    <span v-if="item.icon">{{ item.icon }}</span>
                    {{ item.title }}
                </a>
            </div>
        </div>

        <!-- Powered by footer -->
        <div class="mx-auto mt-8 flex items-center gap-1.5 opacity-30 hover:opacity-60 transition-opacity duration-200">
            <QrCode class="size-3.5" />
            <span class="text-xs" style="font-family: Georgia, serif">Powered by QR Master</span>
        </div>
    </div>
</template>
