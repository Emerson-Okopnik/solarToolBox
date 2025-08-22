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

    async carregarInversores() {
      this.loading = true
      try {
        const response = await catalogosService.listarInversores()
        this.inversores = response
      } catch (error) {
        this.error = error.response?.data?.message || "Erro ao carregar inversores"
        throw error
      } finally {
        this.loading = false
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
