<script setup lang="ts">
import { AccordionItem, type AccordionItemProps } from 'reka-ui'

const props = defineProps<AccordionItemProps>()
</script>

<template>
    <AccordionItem v-bind="props" class="border-b border-border">
        <slot />
    </AccordionItem>
</template>
