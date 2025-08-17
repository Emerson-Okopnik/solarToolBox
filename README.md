# Solar Toolbox - Sistema de Análise de Compatibilidade Solar

Sistema completo para análise de compatibilidade entre módulos fotovoltaicos e inversores, com cálculos de capacidade por MPPT, verificação de conexões série/paralelo e distribuição otimizada de strings.

## 🚀 Funcionalidades

### Requisitos Funcionais Implementados

- **RF01**: Consulta de compatibilidade entre módulos (série/paralelo) com limite de diferença ≤ 5% (configurável)
- **RF02**: Cálculo de capacidade técnica do inversor por MPPT:
  - Validação da janela MPPT (Vmppt_min ≤ Vmp_string_op ≤ Vmppt_max)
  - Verificação de Voc a frio ≤ Vdc_max
  - Controle de I_total ≤ Idc_in_max por MPPT
- **RF03**: Distribuição inteligente de strings por orientação/tilt nos MPPTs

### Características Técnicas

- **Backend**: Laravel 10 com API RESTful
- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **Banco de Dados**: MySQL/PostgreSQL
- **Autenticação**: Laravel Sanctum
- **Testes**: PHPUnit (Backend) + Vitest (Frontend)

## 📋 Pré-requisitos

### Backend
- PHP 8.2+
- Composer
- MySQL 8.0+ ou PostgreSQL 13+
- Redis (opcional, para cache)

### Frontend
- Node.js 18+
- npm ou yarn

## 🛠️ Instalação

### 1. Clone o repositório
\`\`\`bash
git clone https://github.com/seu-usuario/solar-toolbox.git
cd solar-toolbox
\`\`\`

### 2. Configuração do Backend

\`\`\`bash
cd backend

# Instalar dependências
composer install

# Copiar arquivo de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Configurar banco de dados no .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=solar_toolbox
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Executar migrations e seeders
php artisan migrate --seed

# Iniciar servidor de desenvolvimento
php artisan serve
\`\`\`

### 3. Configuração do Frontend

\`\`\`bash
cd frontend

# Instalar dependências
npm install

# Configurar variáveis de ambiente
cp .env.example .env.local

# Configurar URL da API no .env.local
VITE_API_URL=http://localhost:8000/api

# Iniciar servidor de desenvolvimento
npm run dev
\`\`\`

## 🧪 Executando Testes

### Backend
\`\`\`bash
cd backend

# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=SeriesCalculatorTest

# Executar com cobertura
php artisan test --coverage
\`\`\`

### Frontend
\`\`\`bash
cd frontend

# Executar testes
npm run test

# Executar testes em modo watch
npm run test:watch

# Executar com cobertura
npm run test:coverage
\`\`\`

## 📚 Documentação da API

### Autenticação
```http
POST /api/login
POST /api/register
POST /api/logout
