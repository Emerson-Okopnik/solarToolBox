import { defineStore } from "pinia"
import { projetosService } from "@/services/projetos"
import { stringsService } from "@/services/strings"

export const useProjetosStore = defineStore("projetos", {
  state: () => ({
    projetos: [],
    projetoAtual: null,
    loading: false,
    error: null,
  }),

  actions: {
    async listarProjetos(params) {
      this.loading = true
      this.error = null
      try {
        const data = await projetosService.listar(params)
        this.projetos = data.data ?? data
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
        const data = await projetosService.buscar(id)
        const projeto = data?.data?.projeto || data?.projeto || data
        this.projetoAtual = projeto
        return projeto
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
        const data = await projetosService.criar(dados)
        const projeto = data.data ?? data
        this.projetos.push(projeto)
        return projeto
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
        const data = await projetosService.executar(id)
        return data.data ?? data
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
        const data = await projetosService.criarArranjo(projetoId, dados)
        const arranjo = data.data ?? data
        if (this.projetoAtual && this.projetoAtual.id === projetoId) {
          this.projetoAtual.arranjos.push(arranjo)
        }
        return arranjo
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar arranjo"
        throw error
      } finally {
        this.loading = false
      }
    },
    
        async atualizarArranjo(arranjoId, dados) {
      this.loading = true
      this.error = null
      try {
        const data = await projetosService.atualizarArranjo(arranjoId, dados)
        const arranjoAtualizado = data.data ?? data
        if (this.projetoAtual) {
          const index = this.projetoAtual.arranjos.findIndex(a => a.id === arranjoId)
          if (index !== -1) {
            this.projetoAtual.arranjos[index] = arranjoAtualizado
          }
        }
        return arranjoAtualizado
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar arranjo"
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletarArranjo(arranjoId) {
      this.loading = true
      this.error = null
      try {
        await projetosService.removerArranjo(arranjoId)
        if (this.projetoAtual) {
          this.projetoAtual.arranjos = this.projetoAtual.arranjos.filter(a => a.id !== arranjoId)
        }
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover arranjo"
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async criarString(arranjoId, dados) {
      this.loading = true
      this.error = null
      try {
        const data = await stringsService.criar(arranjoId, dados)
        const novaString = data.data ?? data
        if (this.projetoAtual) {
          const arranjo = this.projetoAtual.arranjos.find(a => a.id === arranjoId)
          if (arranjo) {
            arranjo.strings = arranjo.strings || []
            arranjo.strings.push(novaString)
          }
        }
        return novaString
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar string"
        throw error
      } finally {
        this.loading = false
      }
    },

        async atualizarString(arranjoId, stringId, dados) {
      this.loading = true
      this.error = null
      try {
        const data = await stringsService.atualizar(stringId, dados)
        const stringAtualizada = data.data ?? data
        if (this.projetoAtual) {
          const arranjo = this.projetoAtual.arranjos.find(a => a.id === arranjoId)
          if (arranjo && arranjo.strings) {
            const index = arranjo.strings.findIndex(s => s.id === stringId)
            if (index !== -1) {
              arranjo.strings[index] = stringAtualizada
            }
          }
        }
        return stringAtualizada
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar string"
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletarString(arranjoId, stringId) {
      this.loading = true
      this.error = null
      try {
        await stringsService.remover(stringId)
        if (this.projetoAtual) {
          const arranjo = this.projetoAtual.arranjos.find(a => a.id === arranjoId)
          if (arranjo && arranjo.strings) {
            arranjo.strings = arranjo.strings.filter(s => s.id !== stringId)
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover string"
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
