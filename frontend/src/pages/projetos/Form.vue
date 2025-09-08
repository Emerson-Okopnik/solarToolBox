<template>
  <div class="d-flex flex-column gap-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h1 class="h3 fw-bold mb-1">{{ isEditing ? 'Editar Projeto' : 'Novo Projeto' }}</h1>
        <p class="text-muted mb-0">
          {{ isEditing ? 'Atualize as informações do projeto' : 'Crie um novo projeto de análise solar' }}
        </p>
      </div>
      <router-link to="/projetos" class="btn btn-outline-secondary d-inline-flex align-items-center">
        <ArrowLeftIcon class="me-2" style="width:1rem;height:1rem" />
        Voltar
      </router-link>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" novalidate class="d-flex flex-column gap-4">
      <!-- Informações Básicas -->
      <div class="card">
        <div class="card-header section-header">
          <h3 class="h6 mb-0">Informações Básicas</h3>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="nome" class="form-label">Nome do Projeto *</label>
              <input
                id="nome"
                v-model="form.nome"
                type="text"
                required
                class="form-control"
                :class="{ 'is-invalid': errors.nome }"
                placeholder="Ex: Sistema Residencial - Casa Silva"
              />
              <div v-if="errors.nome" class="invalid-feedback">{{ errors.nome[0] }}</div>
            </div>

            <div class="col-md-6">
              <label for="cliente" class="form-label">Cliente *</label>
              <input
                id="cliente"
                v-model="form.cliente"
                type="text"
                required
                class="form-control"
                :class="{ 'is-invalid': errors.cliente }"
                placeholder="Nome do cliente"
              />
              <div v-if="errors.cliente" class="invalid-feedback">{{ errors.cliente[0] }}</div>
            </div>

            <div class="col-12">
              <label for="descricao" class="form-label">Descrição</label>
              <textarea
                id="descricao"
                v-model="form.descricao"
                rows="3"
                class="form-control"
                placeholder="Descrição detalhada do projeto..."
              ></textarea>
            </div>

            <div class="col-12">
              <label for="endereco" class="form-label">Endereço</label>
              <input
                id="endereco"
                v-model="form.endereco"
                type="text"
                class="form-control"
                placeholder="Endereço da instalação"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Configuração Climática -->
      <div class="card">
        <div class="card-header section-header">
          <h3 class="h6 mb-0">Configuração Climática</h3>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6 col-lg-4">
              <label for="clima_id" class="form-label">Localização Climática *</label>
              <select
                id="clima_id"
                v-model.number="form.clima_id"
                required
                class="form-control"
                :class="{ 'is-invalid': errors.clima_id }"
              >
                <option value="">Selecione uma localização</option>
                <option v-for="clima in climas" :key="clima.id" :value="clima.id">
                  {{ clima.nome }} - {{ clima.cidade }}/{{ clima.estado }}
                </option>
              </select>
              <div v-if="errors.clima_id" class="invalid-feedback">{{ errors.clima_id[0] }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Configurações Avançadas -->
      <div class="card">
        <div class="card-header section-header d-flex align-items-center justify-content-between">
          <h3 class="h6 mb-0">Configurações Avançadas</h3>
          <button type="button" class="btn btn-link btn-sm p-0 section-toggle" @click="showAdvanced = !showAdvanced">
            {{ showAdvanced ? 'Ocultar' : 'Mostrar' }}
          </button>
        </div>

        <div v-if="showAdvanced" class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
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
                class="form-control"
                placeholder="5.0"
              />
              <small class="text-muted d-block mt-1">
                Diferença máxima permitida entre tensões de módulos (padrão: 5%)
              </small>
            </div>

            <div class="col-md-6">
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
                class="form-control"
                placeholder="5.0"
              />
              <small class="text-muted d-block mt-1">
                Diferença máxima permitida entre correntes de módulos (padrão: 5%)
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Ações -->
      <div class="d-flex justify-content-end gap-2">
        <router-link to="/projetos" class="btn btn-outline-secondary">Cancelar</router-link>
        <button type="submit" :disabled="loading" class="btn btn-primary d-inline-flex align-items-center">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          {{ loading ? 'Salvando...' : (isEditing ? 'Atualizar' : 'Criar Projeto') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, toRaw } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { catalogosService } from '@/services/catalogos'
import { projetosService } from '@/services/projetos'

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
    climas.value = await catalogosService.listarClimas()
  } catch (error) {
    toast.error('Erro ao carregar climas')
  }
}

const loadProjeto = async () => {
  if (!isEditing.value) return

  try {
    const response = await projetosService.buscar(route.params.id)
    const projeto =
      response.data?.data?.projeto ||
      response.data?.projeto ||
      response.data
    Object.assign(form, projeto)
  } catch (error) {
    toast.error(error.response?.data?.message || 'Erro ao carregar projeto')
    router.push('/projetos')
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  const payload = { ...toRaw(form), clima_id: Number(form.clima_id) }

  try {
    if (isEditing.value) {
      await projetosService.atualizar(route.params.id, payload)
      toast.success('Projeto atualizado com sucesso!')
    } else {
      await projetosService.criar(payload)
      toast.success('Projeto criado com sucesso!')
    }
    
    router.push('/projetos')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      toast.error(error.response?.data?.message || 'Erro ao salvar projeto')
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

<style scoped>
.section-header {
  background: #f8fbff;
  border-bottom: 1px solid #e9eef6;
}
.section-toggle {
  color: #0d6efd;
  text-decoration: none;
}
.section-toggle:hover { text-decoration: underline; }

.form-control {
  height: 42px;
}
textarea.form-control { height: auto; }
</style>