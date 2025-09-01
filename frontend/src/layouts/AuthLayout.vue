<template>
  <div class="auth-layout">
    <div class="auth-card">
       Simplificando header com CSS puro 
      <div class="auth-header">
        <div class="auth-logo">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <h2 class="heading-2">Solar Toolbox</h2>
        <p class="text-muted text-sm">Sistema de análise de compatibilidade solar</p>
      </div>

       Conteúdo principal 
      <router-view />

       Footer simplificado 
      <div class="text-center mt-6">
        <p class="text-sm text-muted">&copy; 2024 Solar Toolbox. Todos os direitos reservados.</p>
      </div>
    </div>
  </div>
</template>
