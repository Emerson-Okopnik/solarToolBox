# Solar Toolbox

Sistema completo para análise de compatibilidade entre módulos fotovoltaicos e inversores. O projeto oferece cálculos de capacidade por MPPT, verificação de conexões série/paralelo e distribuição otimizada de strings, com API RESTful em Laravel e interface Vue 3.

## 📦 Visão geral
- **Compatibilidade de módulos** com validação de diferença ≤ 5% (configurável).
- **Capacidade técnica por MPPT**: janela MPPT (Vmppt_min ≤ Vmp_string_op ≤ Vmppt_max), Voc a frio ≤ Vdc_max e I_total ≤ Idc_in_max.
- **Distribuição inteligente de strings** por orientação/tilt entre os MPPTs.
- **Autenticação** via Laravel Sanctum.
- **Testes**: PHPUnit no backend e Vitest no frontend.

## 🗂️ Estrutura do projeto
```
.
├── backend/    # API Laravel 10
└── frontend/   # SPA Vue 3 + Vite + Tailwind
```

## 🔧 Requisitos
### Backend
- PHP 8.2+
- Composer
- PostgreSQL 13+
- Redis (opcional, para cache)

### Frontend
- Node.js 18+
- npm ou yarn

## 🚀 Como executar
### 1. Clonar o repositório
```bash
git clone https://github.com/seu-usuario/solar-toolbox.git
cd solar-toolbox
```

### 2. Backend (Laravel)
```bash
cd backend

# Instalar dependências
composer install

# Copiar e ajustar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=solar_toolbox
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Rodar migrations e seeders
php artisan migrate --seed

# Subir o servidor (defina uma porta livre, ex.: 8001)
php artisan serve --port=8001
```

### 3. Frontend (Vue 3 + Vite)
```bash
cd frontend

# Instalar dependências
npm install

# Variáveis de ambiente
touch .env.local
# Ajuste a URL da API para a porta usada pelo backend
VITE_API_URL=http://localhost:8001/api

# Subir o servidor de desenvolvimento (porta configurada em package.json)
npm run dev
```
> Observação: o script `npm run dev` está configurado para usar a porta 8000. Caso o backend esteja nessa porta, escolha outra (por exemplo 8001) ao iniciar o Laravel ou ajuste o script/variável `VITE_API_URL` conforme necessário.

## 🧪 Testes
### Backend
```bash
cd backend
php artisan test                 # Todos os testes
php artisan test --filter=NomeDoTeste  # Teste específico
php artisan test --coverage      # Cobertura
```

### Frontend
```bash
cd frontend
npm run test          # Vitest
npm run test:watch    # Modo watch
npm run test:coverage # Cobertura
```

## 📚 Endpoints básicos
- `POST /api/login`
- `POST /api/register`
- `POST /api/logout`

## 🤝 Contribuição
Sinta-se à vontade para abrir issues ou pull requests. Descreva o contexto da alteração, passos para reproduzir (quando aplicável) e inclua testes relevantes.

## 📜 Licença
Este projeto está licenciado sob os termos especificados no repositório. Verifique o arquivo de licença correspondente, se disponível.
