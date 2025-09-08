<template>
  <div>
    <table v-if="parsed" class="table table-sm mb-0">
      <tbody>
        <JsonItem
          v-for="(val, key) in parsed"
          :key="key"
          :label="key"
          :value="val"
          :level="0"
          :parent="parsed"
        />
      </tbody>
    </table>
    <div v-else class="text-muted small">-</div>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue'
import JsonItem from './JsonItem.vue'

const props = defineProps({
  value: {
    type: [Object, Array, String],
    default: null
  }
})

const parsed = computed(() => {
  if (typeof props.value === 'string') {
    try {
      return JSON.parse(props.value)
    } catch (e) {
      return null
    }
  }
  return props.value
})

</script>