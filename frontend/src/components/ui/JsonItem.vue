<template>
  <tr v-if="!isComplex">
    <td :style="{ paddingLeft: level * 16 + 'px' }">{{ label }}</td>
    <td><span :class="messageClass">{{ formatted }}</span></td>
  </tr>
  <template v-else-if="isObject">
    <tr @click.stop="toggle" style="cursor: pointer;">
      <td :style="{ paddingLeft: level * 16 + 'px' }">{{ label }}</td>
      <td>{{ open ? '-' : '+' }}</td>
    </tr>
    <template v-if="open">
      <JsonItem
        v-for="(v, k) in value"
        :key="k"
        :label="k"
        :value="v"
        :level="level + 1"
        :parent="value"
      />
    </template>
  </template>

  <template v-else>
    <tr @click.stop="toggle" style="cursor: pointer;">
      <td :style="{ paddingLeft: level * 16 + 'px' }">{{ label }}</td>
      <td>{{ open ? '-' : '+' }}</td>
    </tr>
    <template v-if="open">
      <tr>
        <td :style="{ paddingLeft: (level + 1) * 16 + 'px' }" colspan="2">
          <ol class="mb-0">
            <li v-for="(item, idx) in value" :key="idx">
              <template v-if="typeof item === 'object' && item !== null">
                <table class="table table-sm mb-2">
                  <tbody>
                    <JsonItem
                      v-for="(v, k) in item"
                      :key="k"
                      :label="k"
                      :value="v"
                      :level="level + 2"
                      :parent="item"
                    />
                  </tbody>
                </table>
              </template>
              <template v-else>{{ formatValue(item) }}</template>
            </li>
          </ol>
        </td>
      </tr>
    </template>
  </template>
</template>

<script setup>
import { ref, computed } from 'vue'

defineOptions({ name: 'JsonItem' })

const props = defineProps({
  label: { type: [String, Number], default: '' },
  value: { type: null, required: true },
  level: { type: Number, default: 0 },
  parent: { type: Object, default: null }
})

const open = ref(true)
const toggle = () => (open.value = !open.value)

const isArray = computed(() => Array.isArray(props.value))
const isObject = computed(
  () => typeof props.value === 'object' && props.value !== null && !isArray.value
)
const isComplex = computed(() => isArray.value || isObject.value)

const formatValue = (val) => {
  if (typeof val === 'number') return val.toFixed(2)
  if (typeof val === 'string' && !isNaN(parseFloat(val))) {
    return parseFloat(val).toFixed(2)
  }
  return val
}
const formatted = computed(() => formatValue(props.value))

const messageClass = computed(() => {
  if (props.label === 'mensagem' && props.parent) {
    if (Object.prototype.hasOwnProperty.call(props.parent, 'aprovado')) {
      return props.parent.aprovado ? 'text-success' : 'text-danger'
    }
    if (props.parent.status) {
      if (props.parent.status === 'aprovado') return 'text-success'
      if (props.parent.status === 'reprovado') return 'text-danger'
      if (props.parent.status === 'aviso') return 'text-warning'
    }
  }
  return ''
})
</script>