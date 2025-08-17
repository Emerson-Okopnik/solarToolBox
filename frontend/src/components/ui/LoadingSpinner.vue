<template>
  <div class="flex items-center justify-center" :class="containerClass">
    <div class="loading-spinner" :class="spinnerClass"></div>
    <span v-if="text" class="ml-2 text-sm text-gray-600">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  text: {
    type: String,
    default: ''
  },
  center: {
    type: Boolean,
    default: false
  }
})

const containerClass = computed(() => ({
  'py-8': props.center,
  'py-4': !props.center
}))

const spinnerClass = computed(() => ({
  'h-4 w-4': props.size === 'sm',
  'h-6 w-6': props.size === 'md',
  'h-8 w-8': props.size === 'lg'
}))
</script>
