<template>
  <div class="p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Climas</h1>
        <p class="text-gray-600">Dados climáticos para cálculos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow border p-6">
        <input
          v-model="busca"
          type="text"
          placeholder="Buscar climas..."
          class="w-full px-4 py-2 border rounded-lg mb-4"
        />

        <div class="grid gap-4">
          <div
            v-for="clima in climasFiltrados"
            :key="clima.id"
            class="border rounded-lg p-4"
          >
            <h3 class="font-semibold">{{ clima.nome }}</h3>
            <p class="text-sm text-gray-600">{{ clima.cidade }}, {{ clima.estado }}</p>
            <div class="mt-2 text-sm">
              <span class="mr-4">Temp: {{ clima.temp_media_anual }}°C</span>
              <span>Irradiação: {{ clima.irradiacao_global_horizontal }} kWh/m²/dia</span>
            </div>
          </div>
        </div>

        <div v-if="climasFiltrados.length === 0" class="text-center py-8 text-gray-500">
          Nenhum clima encontrado
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCatalogosStore } from '@/stores/catalogos'

const store = useCatalogosStore()
const busca = ref('')

const climasFiltrados = computed(() => {
  if (!busca.value) return store.climas
  
  const termo = busca.value.toLowerCase()
  return store.climas.filter(clima => 
    clima.nome.toLowerCase().includes(termo) ||
    clima.cidade.toLowerCase().includes(termo)
  )
})

onMounted(() => {
  store.carregarClimas()
})
</script>
