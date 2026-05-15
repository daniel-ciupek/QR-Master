<script setup lang="ts">
import { AccordionContent } from 'reka-ui'
</script>

<template>
    <AccordionContent
        class="overflow-hidden text-sm data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down"
    >
        <div class="pb-4 pt-0">
            <slot />
        </div>
    </AccordionContent>
</template>
