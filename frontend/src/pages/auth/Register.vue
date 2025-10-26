<template>
  <div class="container" style="max-width: 400px;">
    <div class="text-center mb-4">
      <h2 class="h4 fw-bold text-dark">Criar nova conta</h2>
      <p class="mt-2 small text-muted">
        Ou
        <router-link to="/auth/login" class="text-primary text-decoration-none">
          entrar na sua conta existente
        </router-link>
      </p>
    </div>

    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="name" class="form-label">Nome completo</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="form-control"
          :class="{ 'is-invalid': errors.name }"
          placeholder="Seu nome completo"
        />
        <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="form-control"
          :class="{ 'is-invalid': errors.email }"
          placeholder="seu@email.com"
        />
        <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
          <label for="company" class="form-label">Empresa (opcional)</label>
          <input
            id="company"
            v-model="form.company"
            type="text"
            class="form-control"
            placeholder="Nome da empresa"
          />
        </div>

        <div class="col-12 col-md-6">
          <label for="phone" class="form-label">Telefone (opcional)</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            class="form-control"
            placeholder="(11) 99999-9999"
          />
        </div>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="form-control"
          :class="{ 'is-invalid': errors.password }"
          placeholder="Mínimo 6 caracteres"
        />
        <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
      </div>

      <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirmar senha</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          class="form-control"
          placeholder="Confirme sua senha"
        />
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="btn btn-primary w-100"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        {{ loading ? 'Criando conta...' : 'Criar conta' }}
      </button>

      <router-link
        to="/catalogos"
        class="btn btn-secondary w-100 mt-4 text-center"
      >
        Acessar catálogo
      </router-link>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  email: '',
  company: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  const result = await authStore.register(form)

  if (result.success) {
    toast.success('Conta criada com sucesso!')
    router.push('/')
  } else {
    if (result.errors) {
      errors.value = result.errors
    } else {
      toast.error(result.message)
    }
  }

  loading.value = false
}
</script>
