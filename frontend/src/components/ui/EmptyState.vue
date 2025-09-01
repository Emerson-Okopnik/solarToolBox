<template>
  <div class="text-center py-5">
    <!-- Estado vazio simplificado -->
    <div class="mx-auto text-muted mb-3" style="width: 3rem; height: 3rem; opacity: 0.5;">
      <component :is="icon" />
    </div>
    <h6 class="fw-semibold text-dark mb-2">{{ title }}</h6>
    <p class="text-muted mb-3">{{ description }}</p>
    <div v-if="$slots.action">
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
