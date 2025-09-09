<template>
  <div class="p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
 <h1 class="text-2xl font-bold mb-4">Cadastros</h1>
      <ul class="space-y-2">
        <li>
          <RouterLink to="/cadastro/fabricante" class="text-blue-600 hover:underline">
            Fabricantes
          </RouterLink>
        </li>
        <li>
          <RouterLink to="/cadastro/modulos" class="text-blue-600 hover:underline">
            Módulos
          </RouterLink>
        </li>
        <li>
          <RouterLink to="/cadastro/inversores" class="text-blue-600 hover:underline">
            Inversores
          </RouterLink>
        </li>
        <li>
          <RouterLink to="/cadastro/climas" class="text-blue-600 hover:underline">
            Climas
          </RouterLink>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
</script>