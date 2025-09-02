<template>
  <div class="container" style="max-width: 400px;">

    <form @submit.prevent="handleSubmit">
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" v-model="form.email" type="email" required class="form-control" :class="{ 'is-invalid': errors.email }" placeholder="seu@email.com"/>
       <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Senha</label>
        <input id="password" v-model="form.password" type="password" required class="form-control" :class="{ 'is-invalid': errors.password }" placeholder="Sua senha"/>
        <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input"/>
          <label for="remember" class="form-check-label"> Lembrar de mim </label>
        </div>
        <a href="#" class="small text-primary"> Esqueceu a senha? </a>
      </div>

      <button type="submit" :disabled="loading" class="btn btn-primary w-100">
        <span v-if="loading" class="spinner-border spinner-border-sm"></span>
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
    </form>
  </div>
  <p class="text-center text-muted my-4">
    Não tem uma conta?
    <router-link to="/auth/register" class="text-primary">
      criar uma nova conta
    </router-link>
  </p>
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