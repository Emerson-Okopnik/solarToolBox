import { defineStore } from "pinia"
import { projetosService } from "@/services/projetos"

export const useProjetosStore = defineStore("projetos", {
  state: () => ({
    projetos: [],
    projetoAtual: null,
    loading: false,
    error: null,
  }),

  actions: {
    async listarProjetos() {
      this.loading = true
      this.error = null
      try {
        const response = await projetosService.listar()
        this.projetos = response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar projetos"
        throw error
      } finally {
        this.loading = false
      }
    },

    async buscarProjeto(id) {
      this.loading = true
      this.error = null
      try {
        const response = await projetosService.buscar(id)
        this.projetoAtual = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar projeto"
        throw error
      } finally {
        this.loading = false
      }
    },

    async criarProjeto(dados) {
      this.loading = true
      this.error = null
      try {
        const response = await projetosService.criar(dados)
        this.projetos.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar projeto"
        throw error
      } finally {
        this.loading = false
      }
    },

    async executarAnalise(id) {
      this.loading = true
      this.error = null
      try {
        const response = await projetosService.executar(id)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao executar análise"
        throw error
      } finally {
        this.loading = false
      }
    },

    async criarArranjo(projetoId, dados) {
      this.loading = true
      this.error = null
      try {
        const response = await projetosService.criarArranjo(projetoId, dados)
        if (this.projetoAtual && this.projetoAtual.id === projetoId) {
          this.projetoAtual.arranjos.push(response.data)
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar arranjo"
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
