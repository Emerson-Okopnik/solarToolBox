#!/bin/bash

echo "🚀 Configurando Solar Toolbox..."

# Verificar pré-requisitos
command -v php >/dev/null 2>&1 || { echo "❌ PHP não encontrado. Instale PHP 8.2+"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "❌ Composer não encontrado"; exit 1; }
command -v node >/dev/null 2>&1 || { echo "❌ Node.js não encontrado. Instale Node.js 18+"; exit 1; }

echo "✅ Pré-requisitos verificados"

# Setup Backend
echo "📦 Configurando backend..."
cd backend
composer install
cp .env.example .env
php artisan key:generate

echo "🗄️ Configurando banco de dados..."
read -p "Nome do banco de dados: " db_name
read -p "Usuário do banco: " db_user
read -s -p "Senha do banco: " db_pass
echo

# Atualizar .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$db_name/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$db_user/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$db_pass/" .env

# Executar migrations
php artisan migrate --seed

echo "✅ Backend configurado"

# Setup Frontend
echo "📦 Configurando frontend..."
cd ../frontend
npm install
cp .env.example .env.local

echo "✅ Frontend configurado"

echo "🎉 Setup concluído!"
echo "Para iniciar o desenvolvimento:"
echo "  Backend:  cd backend && php artisan serve"
echo "  Frontend: cd frontend && npm run dev"
