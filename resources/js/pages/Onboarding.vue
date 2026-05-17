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
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md">
            <!-- Progress dots -->
            <div class="mb-8 flex justify-center gap-2">
                <div
                    v-for="i in totalSteps"
                    :key="i"
                    class="h-2 rounded-full transition-all duration-300"
                    :class="i === step ? 'w-8 bg-primary' : 'w-2 bg-muted'"
                />
            </div>

            <!-- Card -->
            <div class="rounded-2xl border bg-card p-8 shadow-sm space-y-6">
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
                    <Button @click="next">
                        {{ isLastStep ? t('onboarding.finish') : t('onboarding.next') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
