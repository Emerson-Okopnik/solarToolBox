import api from "./api"

export const catalogosService = {
  // Fabricantes
  async listarFabricantes() {
    const response = await api.get("/catalogos/fabricantes")
    return response.data?.data?.data || []
  },

  async criarFabricante(dados) {
    const response = await api.post("/fabricantes", dados)
    return response.data
  },

  async atualizarFabricante(id, dados) {
    const response = await api.put(`/fabricantes/${id}`, dados)
    return response.data
  },

  async excluirFabricante(id) {
    const response = await api.delete(`/fabricantes/${id}`)
    return response.data
  },

  // Módulos
  async listarModulos() {
    const response = await api.get("/catalogos/modulos")
    return response.data?.data?.data || []
  },

  async criarModulo(dados) {
    const response = await api.post("/modulos", dados)
    return response.data
  },

  async atualizarModulo(id, dados) {
    const response = await api.put(`/modulos/${id}`, dados)
    return response.data
  },

  async excluirModulo(id) {
    const response = await api.delete(`/modulos/${id}`)
    return response.data
  },

  // Inversores
  async listarInversores() {
    const response = await api.get("/catalogos/inversores")
    return response.data?.data?.data || []
  },

  async buscarInversor(id) {
    const response = await api.get(`/inversores/${id}`)
    return response.data
  },

  async listarMppts(inversorId) {
    const response = await api.get(`/catalogos/inversores/${inversorId}/mppts`)
    return response.data?.data || []
  },

  async criarInversor(dados) {
    const response = await api.post("/inversores", dados)
    return response.data
  },

  async atualizarInversor(id, dados) {
    const response = await api.put(`/inversores/${id}`, dados)
    return response.data
  },

  async excluirInversor(id) {
    const response = await api.delete(`/inversores/${id}`)
    return response.data
  },

  // Climas
  async listarClimas() {
    const response = await api.get("/catalogos/climas")
    return response.data?.data?.data || []
  },

  async criarClima(dados) {
    const response = await api.post("/climas", dados)
    return response.data
  },

  async atualizarClima(id, dados) {
    const response = await api.put(`/climas/${id}`, dados)
    return response.data
  },

  async excluirClima(id) {
    const response = await api.delete(`/climas/${id}`)
    return response.data
  },

  async obterEstatisticas() {
    const [fabricantes, modulos, inversores, climas] = await Promise.all([
      api.get("/catalogos/fabricantes", { params: { per_page: 1 } }),
      api.get("/catalogos/modulos", { params: { per_page: 1 } }),
      api.get("/catalogos/inversores", { params: { per_page: 1 } }),
      api.get("/catalogos/climas", { params: { per_page: 1 } }),
    ])

    return {
      fabricantes: fabricantes.data?.data?.total || 0,
      modulos: modulos.data?.data?.total || 0,
      inversores: inversores.data?.data?.total || 0,
      climas: climas.data?.data?.total || 0,
    }
  },
}
