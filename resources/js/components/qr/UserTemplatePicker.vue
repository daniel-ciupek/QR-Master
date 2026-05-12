<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { QrTemplate } from '@/components/qr/TemplatePicker.vue'

interface UserTemplate {
    id: number
    name: string
    settings: Omit<QrTemplate, 'name'>
}

const props = defineProps<{
    templates: UserTemplate[]
    currentStyle: Omit<QrTemplate, 'name'>
}>()

const emit = defineEmits<{ apply: [settings: Omit<QrTemplate, 'name'>] }>()

const { t } = useI18n()

const saveOpen = ref(false)
const saveName = ref('')

function save() {
    if (!saveName.value.trim()) return
    router.post('/qr-templates', {
        name: saveName.value.trim(),
        settings: JSON.stringify(props.currentStyle),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            saveName.value = ''
            saveOpen.value = false
        },
    })
}

function deleteTemplate(id: number) {
    router.delete(`/qr-templates/${id}`, { preserveScroll: true })
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium leading-none">{{ t('qr.userTemplates.label') }}</p>
            <button
                type="button"
                class="text-xs text-primary hover:underline"
                @click="saveOpen = !saveOpen"
            >
                {{ saveOpen ? t('ui.cancel') : t('qr.userTemplates.save') }}
            </button>
        </div>

        <!-- Save form -->
        <div v-if="saveOpen" class="flex gap-2">
            <Input
                v-model="saveName"
                :placeholder="t('qr.userTemplates.namePlaceholder')"
                class="h-8 text-sm"
                @keydown.enter.prevent="save"
            />
            <Button
                size="sm"
                type="button"
                :disabled="!saveName.trim()"
                @click="save"
            >
                {{ t('qr.userTemplates.confirm') }}
            </Button>
        </div>

        <!-- Saved templates -->
        <div v-if="templates.length > 0" class="flex flex-wrap gap-2">
            <div
                v-for="tpl in templates"
                :key="tpl.id"
                class="group flex items-center gap-1 rounded-full border border-border pl-2.5 pr-1 py-0.5 text-xs hover:border-ring transition-colors"
            >
                <div class="flex gap-0.5 mr-1">
                    <span
                        class="size-3 rounded-sm border border-black/10"
                        :style="{ backgroundColor: tpl.settings.dotColor }"
                    />
                    <span
                        class="size-3 rounded-sm border border-black/10"
                        :style="{ backgroundColor: tpl.settings.bgColor }"
                    />
                </div>
                <button
                    type="button"
                    class="text-foreground hover:text-primary"
                    @click="emit('apply', tpl.settings)"
                >
                    {{ tpl.name }}
                </button>
                <button
                    type="button"
                    class="text-muted-foreground hover:text-destructive ml-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                    :title="t('qr.userTemplates.delete')"
                    @click="deleteTemplate(tpl.id)"
                >
                    ×
                </button>
            </div>
        </div>

        <p v-else-if="!saveOpen" class="text-xs text-muted-foreground">
            {{ t('qr.userTemplates.empty') }}
        </p>
    </div>
</template>
