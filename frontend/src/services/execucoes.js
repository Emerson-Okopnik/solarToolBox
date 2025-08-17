import api from "./api"

export const execucoesService = {
  async buscar(id) {
    const response = await api.get(`/execucoes/${id}`)
    return response.data
  },

  async listarChecagens(execucaoId) {
    const response = await api.get(`/execucoes/${execucaoId}/checagens`)
    return response.data
  },

  async listarRecomendacoes(execucaoId) {
    const response = await api.get(`/execucoes/${execucaoId}/recomendacoes`)
    return response.data
  },
}
