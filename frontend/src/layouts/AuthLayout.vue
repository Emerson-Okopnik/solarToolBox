<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-solar-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo and title -->
      <div class="text-center">
        <div class="mx-auto h-16 w-16 bg-gradient-to-r from-primary-600 to-solar-500 rounded-full flex items-center justify-center">
          <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <h2 class="mt-6 text-3xl font-bold text-gray-900">
          Solar Toolbox
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Sistema de análise de compatibilidade solar
        </p>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-lg shadow-lg p-8">
        <router-view />
      </div>

      <!-- Footer -->
      <div class="text-center text-sm text-gray-500">
        <p>&copy; 2024 Solar Toolbox. Todos os direitos reservados.</p>
      </div>
    </div>
  </div>
</template>
