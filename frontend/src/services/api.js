import axios from "axios"

const api = axios.create({
  baseURL: "/api",
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
})

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
      console.error("Você não tem permissão para realizar esta ação")
    } else if (response?.status === 404) {
      console.error("Recurso não encontrado")
    } else if (response?.status === 422) {
      // Validation errors - let component handle
      return Promise.reject(error)
    } else if (response?.status >= 500) {
      console.error("Erro interno do servidor. Tente novamente mais tarde.")
    } else if (response?.data?.message) {
      console.error(response.data.message)
    } else if (error.code === "ECONNABORTED") {
      console.error("Tempo limite excedido. Verifique sua conexão.")
    } else {
      console.error("Erro inesperado. Tente novamente.")
    }

    return Promise.reject(error)
  },
)

export default api
