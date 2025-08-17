<template>
  <div>
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900">Criar nova conta</h2>
      <p class="mt-2 text-sm text-gray-600">
        Ou
        <router-link to="/auth/login" class="font-medium text-primary-600 hover:text-primary-500">
          entrar na sua conta existente
        </router-link>
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div>
        <label for="name" class="form-label">Nome completo</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          required
          class="form-input"
          :class="{ 'border-danger-300': errors.name }"
          placeholder="Seu nome completo"
        />
        <p v-if="errors.name" class="form-error">{{ errors.name[0] }}</p>
      </div>

      <div>
        <label for="email" class="form-label">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="form-input"
          :class="{ 'border-danger-300': errors.email }"
          placeholder="seu@email.com"
        />
        <p v-if="errors.email" class="form-error">{{ errors.email[0] }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="company" class="form-label">Empresa (opcional)</label>
          <input
            id="company"
            v-model="form.company"
            type="text"
            class="form-input"
            placeholder="Nome da empresa"
          />
        </div>

        <div>
          <label for="phone" class="form-label">Telefone (opcional)</label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            class="form-input"
            placeholder="(11) 99999-9999"
          />
        </div>
      </div>

      <div>
        <label for="password" class="form-label">Senha</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="form-input"
          :class="{ 'border-danger-300': errors.password }"
          placeholder="Mínimo 6 caracteres"
        />
        <p v-if="errors.password" class="form-error">{{ errors.password[0] }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="form-label">Confirmar senha</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          class="form-input"
          placeholder="Confirme sua senha"
        />
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full btn-primary"
      >
        <div v-if="loading" class="loading-spinner mr-2"></div>
        {{ loading ? 'Criando conta...' : 'Criar conta' }}
      </button>
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
