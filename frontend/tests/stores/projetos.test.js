import { describe, it, expect, beforeEach, vi } from "vitest"
import { setActivePinia, createPinia } from "pinia"
import { useProjetosStore } from "@/stores/projetos"
import * as projetosService from "@/services/projetos"

vi.mock("@/services/projetos")

describe("Projetos Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it("carrega projetos corretamente", async () => {
    const mockProjetos = [
      { id: 1, nome: "Projeto 1" },
      { id: 2, nome: "Projeto 2" },
    ]

    vi.mocked(projetosService.projetosService.listar).mockResolvedValue({
      data: mockProjetos,
    })

    const store = useProjetosStore()
    await store.listarProjetos()

    expect(store.projetos).toEqual(mockProjetos)
    expect(store.loading).toBe(false)
    expect(store.error).toBe(null)
  })

  it("trata erros ao carregar projetos", async () => {
    const mockError = {
      response: {
        data: { message: "Erro no servidor" },
      },
    }

    vi.mocked(projetosService.projetosService.listar).mockRejectedValue(mockError)

    const store = useProjetosStore()

    try {
      await store.listarProjetos()
    } catch (error) {
      expect(store.error).toBe("Erro no servidor")
      expect(store.loading).toBe(false)
    }
  })

  it("cria novo projeto e adiciona à lista", async () => {
    const novoProjeto = { id: 3, nome: "Projeto Novo" }

    vi.mocked(projetosService.projetosService.criar).mockResolvedValue({
      data: novoProjeto,
    })

    const store = useProjetosStore()
    store.projetos = [{ id: 1, nome: "Projeto 1" }]

    await store.criarProjeto({ nome: "Projeto Novo" })

    expect(store.projetos).toHaveLength(2)
    expect(store.projetos[1]).toEqual(novoProjeto)
  })
})
