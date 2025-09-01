<template>
  <div
    data-testid="projeto-card"
    @click="$emit('click', projeto)"
    class="card card-custom h-100"
    style="cursor: pointer;"
  >
    <div class="card-body-custom">
      <h6 class="fw-semibold text-dark mb-2">{{ projeto.nome }}</h6>
      <p class="text-muted small mb-2">{{ projeto.cliente }}</p>
      <p class="text-muted small mb-3">{{ projeto.arranjos.length }} arranjos</p>
      
      <div v-if="projeto.ultima_execucao" data-testid="status-badge">
        <span class="badge" :class="getStatusBadgeClass(projeto.ultima_execucao.status)">
          {{ statusLabel(projeto.ultima_execucao.status) }}
        </span>
      </div>
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

const getStatusBadgeClass = (status) => {
  const classes = {
    rascunho: 'bg-secondary text-white',
    em_analise: 'badge-solar',
    aprovado: 'bg-success text-white',
    rejeitado: 'bg-danger text-white'
  }
  return classes[status] || 'bg-secondary text-white'
}
</script>
