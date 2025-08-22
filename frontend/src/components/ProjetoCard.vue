<template>
  <div
    data-testid="projeto-card"
    @click="$emit('click', projeto)"
    class="p-4 border rounded cursor-pointer"
  >
    <div class="font-medium">{{ projeto.nome }}</div>
    <div class="text-sm text-gray-600">{{ projeto.cliente }}</div>
    <div class="text-sm text-gray-600">{{ projeto.arranjos.length }} arranjos</div>
    <div v-if="projeto.ultima_execucao" data-testid="status-badge" class="mt-2 badge">
      {{ statusLabel(projeto.ultima_execucao.status) }}
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  projeto: {
    type: Object,
    required: true
  }
})

const statusLabel = (status) => {
  const labels = {
    rascunho: 'Rascunho',
    em_analise: 'Em Análise',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado'
  }
  return labels[status] || status
}
</script>