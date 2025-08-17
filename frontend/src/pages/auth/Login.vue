<template>
  <div>
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900">Entrar na sua conta</h2>
      <p class="mt-2 text-sm text-gray-600">
        <router-link to="/auth/register" class="font-medium text-primary-600 hover:text-primary-500">
          criar uma nova conta
        </router-link>
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
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

      <div>
        <label for="password" class="form-label">Senha</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="form-input"
          :class="{ 'border-danger-300': errors.password }"
          placeholder="Sua senha"
        />
        <p v-if="errors.password" class="form-error">{{ errors.password[0] }}</p>
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
          />
          <label for="remember" class="ml-2 block text-sm text-gray-900">
            Lembrar de mim
          </label>
        </div>

        <div class="text-sm">
          <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
            Esqueceu a senha?
          </a>
        </div>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full btn-primary"
      >
        <div v-if="loading" class="loading-spinner mr-2"></div>
        {{ loading ? 'Entrando...' : 'Entrar' }}
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
  email: '',
  password: '',
  remember: false
})

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  const result = await authStore.login({
    email: form.email,
    password: form.password
  })

  if (result.success) {
    toast.success('Login realizado com sucesso!')
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
