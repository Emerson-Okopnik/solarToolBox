<template>
  <!-- Header -->
  <div class="catalog-header">
    <h1>Cadastros</h1>
    <p>Cadastre fabricantes, módulos, inversores e dados climáticos</p>
  </div>
    <div class="catalog-page">

      <!-- Grid de cards -->
      <div class="catalog-grid">
        <router-link to="/cadastro/fabricante" class="catalog-card">
          <h3>Fabricantes</h3>
          <p>Fabricantes de módulos e inversores</p>
          <span class="card-footer">{{ stats.fabricantes || 0 }} fabricantes →</span>
        </router-link>

        <router-link to="/cadastro/modulos" class="catalog-card">
          <h3>Módulos</h3>
          <p>Cadastro de módulos fotovoltaicos</p>
          <span class="card-footer">{{ stats.modulos || 0 }} módulos →</span>
        </router-link>

        <router-link to="/cadastro/inversores" class="catalog-card">
          <h3>Inversores</h3>
          <p>Cadastro de inversores solares</p>
          <span class="card-footer">{{ stats.inversores || 0 }} inversores →</span>
        </router-link>

        <router-link to="/cadastro/climas" class="catalog-card">
          <h3>Climas</h3>
          <p>Dados climáticos por localização</p>
          <span class="card-footer">{{ stats.climas || 0 }} localizações →</span>
        </router-link>
      </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  BuildingOfficeIcon,
  RectangleStackIcon,
  CpuChipIcon,
  CloudIcon
} from '@heroicons/vue/24/outline'
import { projetosService } from '@/services/projetos'
import { catalogosService } from '@/services/catalogos'

const stats = ref({})
const recentProjects = ref([])
const loadingProjects = ref(true)

const loadStats = async () => {
  try {
    stats.value = await catalogosService.obterEstatisticas()
  } catch (error) {
    console.error('Erro ao carregar dados dos catálogos:', error)
    stats.value = {}
  }
}

const loadRecentProjects = async () => {
  loadingProjects.value = true
  try {
    const response = await projetosService.listar({ per_page: 2 })
    recentProjects.value = response.data?.data || []
  } catch (error) {
    console.error('Erro ao carregar projetos recentes:', error)
    recentProjects.value = []
  } finally {
    loadingProjects.value = false
  }
}

onMounted(() => {
  loadStats()
  loadRecentProjects()
})
</script>

<style scoped>
.catalog-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.catalog-header h1 {
  font-size: 28px;
  font-weight: bold;
  margin: 0;
  color: #222;
}

.catalog-header p {
  margin-top: 6px;
  color: #555;
}

.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.catalog-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;

  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;

  background: #fff;
  text-decoration: none;
  color: inherit;

  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.catalog-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.catalog-card h3 {
  margin-top: 15px;
  font-size: 18px;
  color: #222;
}

.catalog-card p {
  font-size: 14px;
  color: #666;
  margin: 10px 0 15px;
}

.card-footer {
  font-size: 14px;
  font-weight: 500;
  color: #1a73e8;
}

.stat-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: var(--space-3);
  color: var(--white);
}
</style>