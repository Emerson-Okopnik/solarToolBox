import api from "./api"

export const catalogosService = {
  // Fabricantes
  async listarFabricantes() {
    const response = await api.get("/fabricantes")
    return response.data
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
    const response = await api.get("/modulos")
    return response.data
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
    const response = await api.get("/inversores")
    return response.data
  },

  async buscarInversor(id) {
    const response = await api.get(`/inversores/${id}`)
    return response.data
  },

  async listarMppts(inversorId) {
    const response = await api.get(`/inversores/${inversorId}/mppts`)
    return response.data
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
    const response = await api.get("/climas")
    return response.data
  },

  async criarClima(dados) {
    const response = await api.post("/climas", dados)
    return response.data
  },
}
