<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Globe } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import LottieAnimation from '@/components/LottieAnimation.vue'
import { Button } from '@/components/ui/button'
import welcomeAnimation from '@/animations/welcome'
import personalizeAnimation from '@/animations/personalize'
import readyAnimation from '@/animations/ready'
import { useLocale } from '@/composables/useLocale'

const { t } = useI18n()
const { locale, setLocale } = useLocale()

const step = ref(1)
const totalSteps = 3

const isLastStep = computed(() => step.value === totalSteps)

const animations = [welcomeAnimation, personalizeAnimation, readyAnimation]
const currentAnimation = computed(() => animations[step.value - 1] ?? welcomeAnimation)

function next() {
    if (isLastStep.value) {
        router.post('/onboarding/complete')
    } else {
        step.value++
    }
}
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center bg-background px-4 overflow-hidden">
        <!-- Dot-grid background -->
        <div class="absolute inset-0 bg-[radial-gradient(oklch(0.66_0.25_285/0.04)_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none" />
        <!-- Radial glow -->
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 size-[600px] rounded-full bg-primary/5 blur-3xl pointer-events-none" />

        <div class="relative w-full max-w-md">
            <!-- Progress dots -->
            <div class="mb-8 flex justify-center gap-2">
                <div
                    v-for="i in totalSteps"
                    :key="i"
                    class="h-2 rounded-full transition-all duration-300"
                    :class="i === step
                        ? 'w-8 bg-primary shadow-[0_0_8px_oklch(0.66_0.25_285/0.5)]'
                        : i < step ? 'w-2 bg-primary/40' : 'w-2 bg-muted'"
                />
            </div>

            <!-- Card -->
            <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/80 p-8 shadow-xl backdrop-blur-sm space-y-6">
                <!-- Gradient top-border -->
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/60 to-transparent" />
                <!-- Animation -->
                <div class="flex justify-center">
                    <div class="size-40">
                        <LottieAnimation
                            :animation-data="currentAnimation"
                            :loop="step !== totalSteps"
                            :autoplay="true"
                        />
                    </div>
                </div>

                <!-- Step content -->
                <div class="text-center space-y-2">
                    <!-- Step 1: Welcome -->
                    <template v-if="step === 1">
                        <h1 class="text-2xl font-bold">{{ t('onboarding.steps.welcome.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('onboarding.steps.welcome.subtitle') }}</p>
                    </template>

                    <!-- Step 2: Personalize -->
                    <template v-else-if="step === 2">
                        <h1 class="text-2xl font-bold">{{ t('onboarding.steps.personalize.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('onboarding.steps.personalize.subtitle') }}</p>

                        <div class="mt-6 space-y-4 text-left">
                            <!-- Language -->
                            <div class="space-y-2">
                                <p class="text-sm font-medium">{{ t('onboarding.steps.personalize.language') }}</p>
                                <div class="flex gap-2">
                                    <Button
                                        v-for="lang in ['pl', 'en'] as const"
                                        :key="lang"
                                        :variant="locale === lang ? 'default' : 'outline'"
                                        size="sm"
                                        class="flex-1 gap-1.5"
                                        @click="setLocale(lang)"
                                    >
                                        <Globe class="size-3.5" />
                                        {{ t(`lang.${lang}`) }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Step 3: Ready -->
                    <template v-else>
                        <h1 class="text-2xl font-bold">{{ t('onboarding.steps.ready.title') }}</h1>
                        <p class="text-sm text-muted-foreground">{{ t('onboarding.steps.ready.subtitle') }}</p>
                    </template>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs text-muted-foreground">
                        {{ t('onboarding.step', { current: step, total: totalSteps }) }}
                    </span>
                    <Button
                        class="shadow-[0_0_16px_oklch(0.66_0.25_285/0.3)] hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.5)] transition-shadow duration-200"
                        @click="next"
                    >
                        {{ isLastStep ? t('onboarding.finish') : t('onboarding.next') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
