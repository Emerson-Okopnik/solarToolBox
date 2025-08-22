import api from "./api"

export const projetosService = {
  async listar(params = {}) {
    const response = await api.get("/projetos", { params })
    return response.data
  },

  async buscar(id) {
    const response = await api.get(`/projetos/${id}`)
    return response.data
  },

  async criar(dados) {
    const response = await api.post("/projetos", dados)
    return response.data
  },

  async atualizar(id, dados) {
    const response = await api.put(`/projetos/${id}`, dados)
    return response.data
  },

  async excluir(id) {
    const response = await api.delete(`/projetos/${id}`)
    return response.data
  },

  async executar(id) {
    const response = await api.post(`/projetos/${id}/executar`)
    return response.data
  },

  async criarArranjo(projetoId, dados) {
    const response = await api.post(`/projetos/${projetoId}/arranjos`, dados)
    return response.data
  },
}
