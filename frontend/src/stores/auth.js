import { defineStore } from "pinia"
import { ref, computed } from "vue"
import api from "@/services/api"

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null)
  const token = ref(localStorage.getItem("token"))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === "admin")
  const isEngineer = computed(() => ["admin", "engineer"].includes(user.value?.role))

  const setAuth = (userData, tokenValue) => {
    user.value = userData
    token.value = tokenValue
    localStorage.setItem("token", tokenValue)
    api.defaults.headers.common["Authorization"] = `Bearer ${tokenValue}`
  }

  const clearAuth = () => {
    user.value = null
    token.value = null
    localStorage.removeItem("token")
    delete api.defaults.headers.common["Authorization"]
  }

  const login = async (credentials) => {
    loading.value = true
    try {
      const response = await api.post("/login", credentials)
      const { token: tokenValue, user: userData } = response.data

      setAuth(userData ?? null, tokenValue)
      if (!userData) {
        await checkAuth()
      }
      return { success: true }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || "Erro ao fazer login",
        errors: error.response?.data?.errors || {},
      }
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      const response = await api.post("/register", userData)
      const { user: newUser, token: tokenValue } = response.data

      setAuth(newUser, tokenValue)
      return { success: true }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || "Erro ao criar conta",
        errors: error.response?.data?.errors || {},
      }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      await api.post("/logout")
    } catch (error) {
      console.error("Erro ao fazer logout:", error)
    } finally {
      clearAuth()
    }
  }

  const checkAuth = async () => {
    if (!token.value) return

    try {
      api.defaults.headers.common["Authorization"] = `Bearer ${token.value}`
      const response = await api.get("/user")
      user.value = response.data
    } catch (error) {
      clearAuth()
    }
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    isEngineer,
    login,
    register,
    logout,
    checkAuth,
  }
})
