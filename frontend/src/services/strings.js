import api from "./api"

export const stringsService = {
  async criar(arranjoId, dados) {
    const response = await api.post(`/arranjos/${arranjoId}/strings`, dados)
    return response.data
  },
}