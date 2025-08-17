<template>
  <div class="text-center py-12">
    <div class="mx-auto h-12 w-12 text-gray-400">
      <component :is="icon" />
    </div>
    <h3 class="mt-4 text-sm font-medium text-gray-900">{{ title }}</h3>
    <p class="mt-2 text-sm text-gray-500">{{ description }}</p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
import { FolderIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  icon: {
    type: Object,
    default: () => FolderIcon
  }
})
</script>
