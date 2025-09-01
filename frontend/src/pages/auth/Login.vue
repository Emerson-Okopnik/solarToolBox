<template>
  <div>
    <div class="text-center mb-8">
      <h2 class="heading-2">Entrar na sua conta</h2>
      <p class="mt-2 text-sm text-muted">
        Não tem uma conta?
        <router-link to="/auth/register" class="text-primary font-medium">
          criar uma nova conta
        </router-link>
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="form-input"
          :class="{ 'error': errors.email }"
          placeholder="seu@email.com"
        />
        <p v-if="errors.email" class="form-error">{{ errors.email[0] }}</p>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Senha</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="form-input"
          :class="{ 'error': errors.password }"
          placeholder="Sua senha"
        />
        <p v-if="errors.password" class="form-error">{{ errors.password[0] }}</p>
      </div>

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="checkbox"
          />
          <label for="remember" class="checkbox-label">
            Lembrar de mim
          </label>
        </div>

        <div>
          <a href="#" class="text-primary text-sm font-medium">
            Esqueceu a senha?
          </a>
        </div>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="btn btn-primary btn-full"
      >
        <div v-if="loading" class="loading-spinner"></div>
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

<style scoped>
.checkbox {
  width: 1rem;
  height: 1rem;
  margin-right: var(--space-2);
  accent-color: var(--primary);
}

.checkbox-label {
  font-size: 0.875rem;
  color: var(--gray-700);
  cursor: pointer;
}
</style>
