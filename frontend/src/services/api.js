"use client"

import axios from "axios"
import { useToast } from "vue-toastification"

const api = axios.create({
  baseURL: "/api",
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
})

const toast = useToast()

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token")
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    // Show success message for certain operations
    if (["post", "put", "patch", "delete"].includes(response.config.method)) {
      const message = response.data?.message
      if (message && !response.config.skipSuccessToast) {
        toast.success(message)
      }
    }
    return response
  },
  (error) => {
    const { response } = error

    if (response?.status === 401) {
      // Unauthorized - redirect to login
      localStorage.removeItem("token")
      window.location.href = "/auth/login"
      return Promise.reject(error)
    }

    if (response?.status === 403) {
      toast.error("Você não tem permissão para realizar esta ação")
    } else if (response?.status === 404) {
      toast.error("Recurso não encontrado")
    } else if (response?.status === 422) {
      // Validation errors - don't show toast, let component handle
      return Promise.reject(error)
    } else if (response?.status >= 500) {
      toast.error("Erro interno do servidor. Tente novamente mais tarde.")
    } else if (response?.data?.message) {
      toast.error(response.data.message)
    } else if (error.code === "ECONNABORTED") {
      toast.error("Tempo limite excedido. Verifique sua conexão.")
    } else {
      toast.error("Erro inesperado. Tente novamente.")
    }

    return Promise.reject(error)
  },
)

export default api
