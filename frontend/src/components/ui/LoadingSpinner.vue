<template>
  <div class="d-flex align-items-center justify-content-center" :class="containerClass">
    <div class="loading-spinner" :class="spinnerClass"></div>
    <span v-if="text" class="ms-2 small text-muted">{{ text }}</span>
  </div>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'LoadingSpinner',
  props: {
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
  },
  computed: {
    containerClass() {
      return {
        'py-5': this.center,
        'py-3': !this.center
      }
    },
    spinnerClass() {
      return {
        'loading-spinner-sm': this.size === 'sm',
        'loading-spinner': this.size === 'md',
        'loading-spinner-lg': this.size === 'lg'
      }
    }
  }
})
</script>
