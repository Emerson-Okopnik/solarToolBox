import { describe, it, expect } from "vitest"
import { mount } from "@vue/test-utils"
import ProjetoCard from "@/components/ProjetoCard.vue"

describe("ProjetoCard", () => {
  const projeto = {
    id: 1,
    nome: "Projeto Teste",
    cliente: "Cliente Teste",
    created_at: "2024-01-01T00:00:00Z",
    arranjos: [
      { id: 1, nome: "Arranjo 1" },
      { id: 2, nome: "Arranjo 2" },
    ],
  }

  it("renderiza informações do projeto corretamente", () => {
    const wrapper = mount(ProjetoCard, {
      props: { projeto },
    })

    expect(wrapper.text()).toContain("Projeto Teste")
    expect(wrapper.text()).toContain("Cliente Teste")
    expect(wrapper.text()).toContain("2 arranjos")
  })

  it("emite evento ao clicar no projeto", async () => {
    const wrapper = mount(ProjetoCard, {
      props: { projeto },
    })

    await wrapper.find('[data-testid="projeto-card"]').trigger("click")

    expect(wrapper.emitted("click")).toBeTruthy()
    expect(wrapper.emitted("click")[0]).toEqual([projeto])
  })

  it("mostra badge de status quando projeto tem execuções", () => {
    const projetoComExecucao = {
      ...projeto,
      ultima_execucao: {
        status: "aprovado",
        created_at: "2024-01-01T00:00:00Z",
      },
    }

    const wrapper = mount(ProjetoCard, {
      props: { projeto: projetoComExecucao },
    })

    expect(wrapper.find('[data-testid="status-badge"]').exists()).toBe(true)
    expect(wrapper.text()).toContain("Aprovado")
  })
})
