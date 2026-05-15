<script setup lang="ts">
import { AccordionTrigger } from 'reka-ui'
import { ChevronDown } from 'lucide-vue-next'
</script>

<template>
    <AccordionTrigger
        class="flex flex-1 items-center justify-between py-4 text-sm font-medium transition-all hover:underline [&[data-state=open]>svg]:rotate-180"
    >
        <slot />
        <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" />
    </AccordionTrigger>
</template>
