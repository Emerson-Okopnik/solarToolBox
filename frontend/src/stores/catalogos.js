import { defineStore } from "pinia"
import { catalogosService } from "@/services/catalogos"

export const useCatalogosStore = defineStore("catalogos", {
  state: () => ({
    fabricantes: [],
    modulos: [],
    inversores: [],
    climas: [],
    loading: false,
    error: null,
  }),

  actions: {
    async carregarFabricantes() {
      this.loading = true
      try {
        const response = await catalogosService.listarFabricantes()
        this.fabricantes = response
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar fabricantes"
        throw error
      } finally {
        this.loading = false
      }
    },

    async criarFabricante(dados) {
      try {
        const response = await catalogosService.criarFabricante(dados)
        this.fabricantes.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar fabricante"
        throw error
      }
    },

    async atualizarFabricante(id, dados) {
      try {
        const response = await catalogosService.atualizarFabricante(id, dados)
        const index = this.fabricantes.findIndex(f => f.id === id)
        if (index !== -1) {
          this.fabricantes[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar fabricante"
        throw error
      }
    },

    async removerFabricante(id) {
      try {
        await catalogosService.excluirFabricante(id)
        this.fabricantes = this.fabricantes.filter(f => f.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover fabricante"
        throw error
      }
    },

    async carregarModulos() {
      this.loading = true
      try {
        const response = await catalogosService.listarModulos()
        this.modulos = response
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar módulos"
        throw error
      } finally {
        this.loading = false
      }
    },

    async criarModulo(dados) {
      try {
        const response = await catalogosService.criarModulo(dados)
        this.modulos.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar módulo"
        throw error
      }
    },

    async atualizarModulo(id, dados) {
      try {
        const response = await catalogosService.atualizarModulo(id, dados)
        const index = this.modulos.findIndex(m => m.id === id)
        if (index !== -1) {
          this.modulos[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar módulo"
        throw error
      }
    },

    async removerModulo(id) {
      try {
        await catalogosService.excluirModulo(id)
        this.modulos = this.modulos.filter(m => m.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover módulo"
        throw error
      }
    },

    async carregarInversores() {
      this.loading = true
      try {
        const response = await catalogosService.listarInversores()
        const inversores = await Promise.all(
          response.map(async inv => {
            const mppts = await catalogosService.listarMppts(inv.id)
            return { ...inv, mppts }
          })
        )
        this.inversores = inversores
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar inversores"
        throw error
      } finally {
        this.loading = false
      }
    },

    async carregarMppts(inversorId) {
      try {
        const mppts = await catalogosService.listarMppts(inversorId)
        const index = this.inversores.findIndex(i => i.id === inversorId)
        if (index !== -1) {
          this.inversores[index].mppts = mppts
        }
        return mppts
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar MPPTs"
        throw error
      }
    },

    async criarInversor(dados) {
      try {
        const response = await catalogosService.criarInversor(dados)
        this.inversores.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar inversor"
        throw error
      }
    },

    async atualizarInversor(id, dados) {
      try {
        const response = await catalogosService.atualizarInversor(id, dados)
        const index = this.inversores.findIndex(i => i.id === id)
        if (index !== -1) {
          this.inversores[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar inversor"
        throw error
      }
    },

    async removerInversor(id) {
      try {
        await catalogosService.excluirInversor(id)
        this.inversores = this.inversores.filter(i => i.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover inversor"
        throw error
      }
    },

    async carregarClimas() {
      this.loading = true
      try {
        const response = await catalogosService.listarClimas()
        this.climas = response
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar climas"
        throw error
      } finally {
        this.loading = false
      }
    },

    async criarClima(dados) {
      try {
        const response = await catalogosService.criarClima(dados)
        this.climas.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao criar clima"
        throw error
      }
    },

    async atualizarClima(id, dados) {
      try {
        const response = await catalogosService.atualizarClima(id, dados)
        const index = this.climas.findIndex(c => c.id === id)
        if (index !== -1) {
          this.climas[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao atualizar clima"
        throw error
      }
    },

    async removerClima(id) {
      try {
        await catalogosService.excluirClima(id)
        this.climas = this.climas.filter(c => c.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao remover clima"
        throw error
      }
    },

    async carregarTodos() {
      await Promise.all([
        this.carregarFabricantes(),
        this.carregarModulos(),
        this.carregarInversores(),
        this.carregarClimas(),
      ])
    },
  },
})
