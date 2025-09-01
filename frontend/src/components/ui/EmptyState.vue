<template>
  <div class="text-center py-5">
    <div class="mx-auto text-muted mb-3" style="width: 3rem; height: 3rem;">
      <component :is="icon" />
    </div>
    <h6 class="fw-medium text-dark">{{ title }}</h6>
    <p class="text-muted small">{{ description }}</p>
    <div v-if="$slots.action" class="mt-3">
      <slot name="action" />
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { FolderIcon } from '@heroicons/vue/24/outline'

export default defineComponent({
  name: 'EmptyState',
  props: {
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
  }
})
</script>
