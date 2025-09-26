import { createRouter, createWebHistory } from "vue-router"
import { useAuthStore } from "@/stores/auth"

// Layouts
import AuthLayout from "@/layouts/AuthLayout.vue"
import AppLayout from "@/layouts/AppLayout.vue"

// Pages
import Login from "@/pages/auth/Login.vue"
import Register from "@/pages/auth/Register.vue"
import Projetos from "@/pages/projetos/Index.vue"
import ProjetoDetalhes from "@/pages/projetos/Show.vue"
import ProjetoForm from "@/pages/projetos/Form.vue"
import Catalogos from "@/pages/catalogos/Index.vue"
import Fabricantes from "@/pages/catalogos/Fabricantes.vue"
import Modulos from "@/pages/catalogos/Modulos.vue"
import Inversores from "@/pages/catalogos/Inversores.vue"
import Climas from "@/pages/catalogos/Climas.vue"
import CadastroIndex from "@/pages/cadastro/Index.vue"
import CadastroFabricante from "@/pages/cadastro/Fabricante.vue"
import CadastroModulos from "@/pages/cadastro/Modulos.vue"
import CadastroInversores from "@/pages/cadastro/Inversores.vue"
import CadastroClimas from "@/pages/cadastro/Climas.vue"

const routes = [
  {
    path: "/auth",
    component: AuthLayout,
    meta: { requiresGuest: true },
    children: [
      {
        path: "login",
        name: "login",
        component: Login,
        meta: { title: "Login" },
      },
      {
        path: "register",
        name: "register",
        component: Register,
        meta: { title: "Cadastro" },
      },
    ],
  },
  {
    path: "/",
    component: AppLayout,
    children: [
      {
        path: "",
        redirect: "/catalogos"
      },
      {
        path: "projetos",
        name: "projetos",
        component: Projetos,
        meta: { title: "Projetos", requiresAuth: true },
      },
      {
        path: "projetos/novo",
        name: "projeto-novo",
        component: ProjetoForm,
        meta: { title: "Novo Projeto", requiresAuth: true },
      },
      {
        path: "projetos/:id",
        name: "projeto-detalhes",
        component: ProjetoDetalhes,
        meta: { title: "Detalhes do Projeto", requiresAuth: true },
      },
      {
        path: "projetos/:id/editar",
        name: "projeto-editar",
        component: ProjetoForm,
        meta: { title: "Editar Projeto", requiresAuth: true },
      },
      {
        path: "catalogos",
        name: "catalogos",
        component: Catalogos,
        meta: { title: "Catálogos" },
      },
      {
        path: "catalogos/fabricantes",
        name: "fabricantes",
        component: Fabricantes,
        meta: { title: "Fabricantes" },
      },
      {
        path: "catalogos/modulos",
        name: "modulos",
        component: Modulos,
        meta: { title: "Módulos" },
      },
      {
        path: "catalogos/inversores",
        name: "inversores",
        component: Inversores,
        meta: { title: "Inversores" },
      },
      {
        path: "catalogos/climas",
        name: "climas",
        component: Climas,
        meta: { title: "Climas" },
      },
      {
        path: "cadastro",
        name: "cadastro",
        component: CadastroIndex,
        meta: { title: "Cadastro", requiresAuth: true, requiresAdmin: true },
      },
            {
        path: "cadastro/fabricante",
        name: "cadastro-fabricante",
        component: CadastroFabricante,
        meta: { title: "Cadastro Fabricante", requiresAuth: true, requiresAdmin: true },
      },
      {
        path: "cadastro/modulos",
        name: "cadastro-modulos",
        component: CadastroModulos,
        meta: { title: "Cadastro Módulos", requiresAuth: true, requiresAdmin: true },
      },
      {
        path: "cadastro/inversores",
        name: "cadastro-inversores",
        component: CadastroInversores,
        meta: { title: "Cadastro Inversores", requiresAuth: true, requiresAdmin: true },
      },
      {
        path: "cadastro/climas",
        name: "cadastro-climas",
        component: CadastroClimas,
        meta: { title: "Cadastro Climas", requiresAuth: true, requiresAdmin: true },
      },
    ],
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  },
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Set page title
  if (to.meta.title) {
    document.title = `${to.meta.title} - Solar Toolbox`
  }

  // Check authentication requirements
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: "login" })
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: "catalogos" })
  } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: "catalogos" })
  } else {
    next()
  }
})

export default router
