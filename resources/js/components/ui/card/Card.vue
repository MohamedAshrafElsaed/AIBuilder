<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-lg border border-border py-6 shadow-sm transition-all duration-200 dark:bg-[#171717] dark:border-[#262626]',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
