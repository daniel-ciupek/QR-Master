<script setup lang="ts">
import { AccordionRoot, type AccordionRootEmits, type AccordionRootProps } from 'reka-ui'
import { useForwardPropsEmits } from 'reka-ui'

const props = defineProps<AccordionRootProps>()
const emits = defineEmits<AccordionRootEmits>()
const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
    <AccordionRoot v-bind="forwarded">
        <slot />
    </AccordionRoot>
</template>
