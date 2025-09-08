<template>
  <div class="d-flex flex-column gap-3">
    <div v-for="checagem in checagens" :key="checagem.id" class="card">
      <div class="card-header d-flex justify-content-between align-items-start">
        <div>
          <h4 class="h6 mb-1">{{ checagem.titulo }}</h4>
          <p class="text-muted small mb-0">{{ checagem.descricao }}</p>
        </div>
        <span class="badge" :class="getResultadoBadgeClass(checagem.resultado)">
          {{ getResultadoLabel(checagem.resultado) }}
        </span>
      </div>
      <div class="card-body">
        <!-- Resumos específicos por tipo -->
        <template v-if="checagem.tipo === 'compatibilidade_modulos'">
          <dl class="row small mb-2">
            <dt class="col-6 text-muted">Módulos em série</dt>
            <dd class="col-6">{{ checagem.valores_calculados.num_modulos_serie }}</dd>
            <dt class="col-6 text-muted">Strings em paralelo</dt>
            <dd class="col-6">{{ checagem.valores_calculados.num_strings_paralelo }}</dd>
          </dl>
        </template>

        <template v-else-if="checagem.tipo === 'distribuicao_orientacao'">
          <dl class="row small mb-2" v-if="getPrimeiroMppt(checagem)">
            <dt class="col-6 text-muted">Tensão MPPT mín</dt>
            <dd class="col-6">{{ getPrimeiroMppt(checagem).mppt?.tensao_mppt_min }}</dd>
            <dt class="col-6 text-muted">Tensão MPPT máx</dt>
            <dd class="col-6">{{ getPrimeiroMppt(checagem).mppt?.tensao_mppt_max }}</dd>
          </dl>
        </template>

        <template v-else-if="checagem.tipo === 'capacidade_mppt'">
          <div class="small mb-2">
            <div>
              <strong>Potência DC:</strong>
              {{ checagem.valores_calculados.validacoes_globais?.potencia_dc?.mensagem }}
            </div>
            <div>
              <strong>Dimensionamento:</strong>
              {{ checagem.valores_calculados.validacoes_globais?.dimensionamento?.mensagem }}
            </div>
          </div>
        </template>

        <button
          class="btn btn-link btn-sm p-0"
          @click="toggleDetalhes(checagem.id)"
        >
          {{ detalhes[checagem.id] ? 'menos detalhes' : 'mais detalhes' }}
        </button>

        <div v-if="detalhes[checagem.id]" class="mt-2">
          <JsonViewer :value="checagem.valores_calculados" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref } from 'vue'
import JsonViewer from './ui/JsonViewer.vue'

defineProps({
  checagens: { type: Array, default: () => [] }
})

// controle de exibição de detalhes por checagem
const detalhes = ref({})
const toggleDetalhes = (id) => {
  detalhes.value[id] = !detalhes.value[id]
}

// obtém o primeiro MPPT disponível para extração de tensões
const getPrimeiroMppt = (checagem) => {
  const mppts = checagem?.valores_calculados?.mppts
  return mppts ? Object.values(mppts)[0] : null
}

const getResultadoLabel = (resultado) => {
  const labels = { aprovado: 'Aprovado', reprovado: 'Reprovado', aviso: 'Aviso' }
  return labels[resultado] || resultado
}

const getResultadoBadgeClass = (resultado) => {
  const classes = {
    aprovado: 'text-bg-success',
    reprovado: 'text-bg-danger',
    aviso: 'text-bg-warning'
  }
  return classes[resultado] || 'text-bg-secondary'
}
</script>