import api from "./api"

export const inversoresService = {
  async recomendar(dados) {
    const response = await api.post("/inversores/recomendados", dados)
    return response.data
  }
}