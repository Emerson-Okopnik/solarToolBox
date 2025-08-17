<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEditing ? 'Editar Projeto' : 'Novo Projeto' }}
        </h1>
        <p class="mt-2 text-gray-600">
          {{ isEditing ? 'Atualize as informações do projeto' : 'Crie um novo projeto de análise solar' }}
        </p>
      </div>
      <router-link to="/projetos" class="btn-outline">
        <ArrowLeftIcon class="h-4 w-4 mr-2" />
        Voltar
      </router-link>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-8">
      <!-- Basic Information -->
      <div class="card">
        <div class="card-header">
          <h3 class="text-lg font-medium text-gray-900">Informações Básicas</h3>
        </div>
        <div class="card-body space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="nome" class="form-label">Nome do Projeto *</label>
              <input
                id="nome"
                v-model="form.nome"
                type="text"
                required
                class="form-input"
                :class="{ 'border-danger-300': errors.nome }"
                placeholder="Ex: Sistema Residencial - Casa Silva"
              />
              <p v-if="errors.nome" class="form-error">{{ errors.nome[0] }}</p>
            </div>

            <div>
              <label for="cliente" class="form-label">Cliente *</label>
              <input
                id="cliente"
                v-model="form.cliente"
                type="text"
                required
                class="form-input"
                :class="{ 'border-danger-300': errors.cliente }"
                placeholder="Nome do cliente"
              />
              <p v-if="errors.cliente" class="form-error">{{ errors.cliente[0] }}</p>
            </div>
          </div>

          <div>
            <label for="descricao" class="form-label">Descrição</label>
            <textarea
              id="descricao"
              v-model="form.descricao"
              rows="3"
              class="form-input"
              placeholder="Descrição detalhada do projeto..."
            ></textarea>
          </div>

          <div>
            <label for="endereco" class="form-label">Endereço</label>
            <input
              id="endereco"
              v-model="form.endereco"
              type="text"
              class="form-input"
              placeholder="Endereço da instalação"
            />
          </div>
        </div>
      </div>

      <!-- Climate Configuration -->
      <div class="card">
        <div class="card-header">
          <h3 class="text-lg font-medium text-gray-900">Configuração Climática</h3>
        </div>
        <div class="card-body space-y-6">
          <div>
            <label for="clima_id" class="form-label">Localização Climática *</label>
            <select
              id="clima_id"
              v-model="form.clima_id"
              required
              class="form-input"
              :class="{ 'border-danger-300': errors.clima_id }"
            >
              <option value="">Selecione uma localização</option>
              <option
                v-for="clima in climas"
                :key="clima.id"
                :value="clima.id"
              >
                {{ clima.nome }} - {{ clima.cidade }}/{{ clima.estado }}
              </option>
            </select>
            <p v-if="errors.clima_id" class="form-error">{{ errors.clima_id[0] }}</p>
          </div>
        </div>
      </div>

      <!-- Advanced Settings -->
      <div class="card">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Configurações Avançadas</h3>
            <button
              type="button"
              @click="showAdvanced = !showAdvanced"
              class="text-sm text-primary-600 hover:text-primary-500"
            >
              {{ showAdvanced ? 'Ocultar' : 'Mostrar' }}
            </button>
          </div>
        </div>
        <div v-if="showAdvanced" class="card-body space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="limite_compatibilidade_tensao" class="form-label">
                Limite de Compatibilidade - Tensão (%)
              </label>
              <input
                id="limite_compatibilidade_tensao"
                v-model.number="form.limite_compatibilidade_tensao"
                type="number"
                min="1"
                max="20"
                step="0.1"
                class="form-input"
                placeholder="5.0"
              />
              <p class="text-xs text-gray-500 mt-1">
                Diferença máxima permitida entre tensões de módulos (padrão: 5%)
              </p>
            </div>

            <div>
              <label for="limite_compatibilidade_corrente" class="form-label">
                Limite de Compatibilidade - Corrente (%)
              </label>
              <input
                id="limite_compatibilidade_corrente"
                v-model.number="form.limite_compatibilidade_corrente"
                type="number"
                min="1"
                max="20"
                step="0.1"
                class="form-input"
                placeholder="5.0"
              />
              <p class="text-xs text-gray-500 mt-1">
                Diferença máxima permitida entre correntes de módulos (padrão: 5%)
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-4">
        <router-link to="/projetos" class="btn-outline">
          Cancelar
        </router-link>
        <button
          type="submit"
          :disabled="loading"
          class="btn-primary"
        >
          <div v-if="loading" class="loading-spinner mr-2"></div>
          {{ loading ? 'Salvando...' : (isEditing ? 'Atualizar' : 'Criar Projeto') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const showAdvanced = ref(false)
const errors = ref({})
const climas = ref([])

const isEditing = computed(() => !!route.params.id)

const form = reactive({
  nome: '',
  cliente: '',
  descricao: '',
  endereco: '',
  clima_id: '',
  limite_compatibilidade_tensao: 5.0,
  limite_compatibilidade_corrente: 5.0
})

const loadClimas = async () => {
  try {
    // Simular dados de climas
    climas.value = [
      { id: 1, nome: 'São Paulo Capital', cidade: 'São Paulo', estado: 'SP' },
      { id: 2, nome: 'Rio de Janeiro Capital', cidade: 'Rio de Janeiro', estado: 'RJ' },
      { id: 3, nome: 'Belo Horizonte', cidade: 'Belo Horizonte', estado: 'MG' }
    ]
  } catch (error) {
    toast.error('Erro ao carregar climas')
  }
}

const loadProjeto = async () => {
  if (!isEditing.value) return

  try {
    loading.value = true
    // Simular carregamento do projeto
    const projeto = {
      id: route.params.id,
      nome: 'Projeto Exemplo',
      cliente: 'Cliente Exemplo',
      descricao: 'Descrição do projeto',
      endereco: 'Rua Exemplo, 123',
      clima_id: 1,
      limite_compatibilidade_tensao: 5.0,
      limite_compatibilidade_corrente: 5.0
    }

    Object.assign(form, projeto)
  } catch (error) {
    toast.error('Erro ao carregar projeto')
    router.push('/projetos')
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    if (isEditing.value) {
      // await api.put(`/projetos/${route.params.id}`, form)
      toast.success('Projeto atualizado com sucesso!')
    } else {
      // await api.post('/projetos', form)
      toast.success('Projeto criado com sucesso!')
    }
    
    router.push('/projetos')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      toast.error('Erro ao salvar projeto')
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadClimas()
  loadProjeto()
})
</script>
