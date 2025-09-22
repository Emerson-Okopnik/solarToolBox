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
            <dt class="col-6 text-muted">Arranjo</dt>
            <dd class="col-6">{{ getArranjoLabel(checagem) }}</dd>
            <dt class="col-6 text-muted">String</dt>
            <dd class="col-6">{{ getStringLabel(checagem) }}</dd>
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
          <div class="small d-flex flex-column gap-3">
            <div v-if="getCapacidadeResumo(checagem)">
              <div class="fw-semibold text-uppercase small text-muted mb-1">
                Resumo do inversor
              </div>
              <ul class="list-unstyled mb-0">
                <li>
                  <strong>Módulos conectados:</strong>
                  {{ getModuloLabel(getCapacidadeResumo(checagem)?.modulos_conectados) }}
                </li>
                <li>
                  <strong>Módulos disponíveis:</strong>
                  {{ getModuloLabel(getCapacidadeResumo(checagem)?.modulos_disponiveis) }}
                </li>
                <li v-if="(getCapacidadeResumo(checagem)?.modulos_excedentes ?? 0) > 0">
                  <strong>Módulos excedentes:</strong>
                  {{ getModuloLabel(getCapacidadeResumo(checagem)?.modulos_excedentes) }}
                </li>
              </ul>
            </div>
            <div v-if="getMpptsDetalhes(checagem).length">
              <div class="fw-semibold text-uppercase small text-muted mb-1">
                Resumo por MPPT
              </div>
              <ul class="list-unstyled mb-0">
                <li v-for="mppt in getMpptsDetalhes(checagem)" :key="mppt.mppt_id ?? mppt.numero">
                  {{ formatMpptResumo(mppt) }}
                </li>
              </ul>
            </div>

            <div v-if="checagem.valores_calculados.validacoes_globais">
              <div class="fw-semibold text-uppercase small text-muted mb-1">
                Validações globais
              </div>
              <ul class="list-unstyled mb-0">
                <li v-if="checagem.valores_calculados.validacoes_globais?.potencia_dc">
                  <strong>Potência DC:</strong>
                  {{ checagem.valores_calculados.validacoes_globais?.potencia_dc?.mensagem }}
                </li>
                <li v-if="checagem.valores_calculados.validacoes_globais?.dimensionamento">
                  <strong>Dimensionamento:</strong>
                  {{ checagem.valores_calculados.validacoes_globais?.dimensionamento?.mensagem }}
                </li>
              </ul>
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

const getArranjoLabel = (checagem) => {
  const arranjo = checagem?.arranjo
  if (!arranjo) {
    return 'Não informado'
  }

  return arranjo.nome || `Arranjo ${arranjo.id}`
}

const getStringLabel = (checagem) => {
  const string = checagem?.string

  if (string?.nome) {
    return string.nome
  }

  if (string?.id) {
    return `String ${string.id}`
  }

  if (checagem?.string_id) {
    return `String ${checagem.string_id}`
  }

  return 'Não informado'
}

const getResultadoLabel = (resultado) => {
  const labels = { aprovado: 'Aprovado', reprovado: 'Reprovado', aviso: 'Aviso' }
  return labels[resultado] || resultado
}

const integerFormatter = new Intl.NumberFormat('pt-BR')

const getCapacidadeResumo = (checagem) => checagem?.valores_calculados?.resumo ?? null

const getMpptsDetalhes = (checagem) => {
  const mppts = checagem?.valores_calculados?.mppts
  if (!mppts) {
    return []
  }

  return Object.values(mppts).sort((a, b) => {
    const numeroA = a?.numero ?? a?.mppt_id ?? 0
    const numeroB = b?.numero ?? b?.mppt_id ?? 0
    if (numeroA === numeroB) {
      return (a?.mppt_id ?? 0) - (b?.mppt_id ?? 0)
    }
    return numeroA - numeroB
  })
}

const formatInteger = (valor) => {
  if (valor === null || valor === undefined) {
    return '0'
  }

  const numero = typeof valor === 'number' ? valor : Number(valor)
  if (Number.isNaN(numero)) {
    return valor
  }

  return integerFormatter.format(Math.round(numero))
}

const getModuloLabel = (valor) => {
  const numero = valor === undefined || valor === null ? 0 : Number(valor)

  if (Number.isNaN(numero)) {
    return valor ?? '-'
  }

  const texto = formatInteger(numero)
  const plural = numero === 1 ? 'módulo' : 'módulos'
  return `${texto} ${plural}`
}

const formatMpptResumo = (mppt) => {
  const numero = mppt?.numero ?? mppt?.mppt_id ?? '?'
  const instaladosNumero = Number(mppt?.modulos_conectados ?? 0)
  const disponiveisNumero = Number(mppt?.modulos_disponiveis ?? 0)
  const excedentesNumero = Number(mppt?.modulos_excedentes ?? 0)

  const instaladosTexto = formatInteger(instaladosNumero)
  const pluralInstalados = instaladosNumero === 1 ? 'módulo' : 'módulos'

  let complemento = 'sem margem para mais módulos'
  if (excedentesNumero > 0) {
    const excedentesTexto = formatInteger(excedentesNumero)
    const pluralExcedentes = excedentesNumero === 1 ? 'módulo' : 'módulos'
    complemento = `excedente de ${excedentesTexto} ${pluralExcedentes}`
  } else if (disponiveisNumero > 0) {
    const disponiveisTexto = formatInteger(disponiveisNumero)
    const pluralDisponiveis = disponiveisNumero === 1 ? 'módulo' : 'módulos'
    complemento = `sobra para +${disponiveisTexto} ${pluralDisponiveis}`
  }

  return `MPPT ${numero} — ${instaladosTexto} ${pluralInstalados} instalados, ${complemento}`
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