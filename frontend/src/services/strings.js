import api from "./api"

export const stringsService = {
  async criar(arranjoId, dados) {
    const response = await api.post(`/arranjos/${arranjoId}/strings`, dados)
    return response.data
  },
  async atualizar(id, dados) {
    const response = await api.put(`/strings/${id}`, dados)
    return response.data
  },
  async remover(id) {
    const response = await api.delete(`/strings/${id}`)
    return response.data
  },
}