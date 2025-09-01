<template>
  <div
    data-testid="projeto-card"
    @click="$emit('click', projeto)"
    class="card card-custom h-100"
    style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
    @mouseenter="$event.target.style.transform = 'translateY(-2px)'; $event.target.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)'"
    @mouseleave="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)'"
  >
    <div class="card-body-custom">
      <h6 class="fw-medium text-dark">{{ projeto.nome }}</h6>
      <p class="text-muted small">{{ projeto.cliente }}</p>
      <p class="text-muted small">{{ projeto.arranjos.length }} arranjos</p>
      <div v-if="projeto.ultima_execucao" data-testid="status-badge" class="mt-2">
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
    rascunho: 'bg-info text-dark',
    em_analise: 'badge-solar',
    aprovado: 'bg-success',
    rejeitado: 'bg-danger'
  }
  return classes[status] || 'bg-info text-dark'
}
</script>
