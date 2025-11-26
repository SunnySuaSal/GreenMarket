#!/bin/bash

# Script de inicio rápido para GreenMarket
# Uso: ./iniciar.sh

echo "🌱 GreenMarket - Script de Inicio"
echo "=================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar si PHP está instalado
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP no está instalado${NC}"
    echo "Por favor, instala PHP primero"
    exit 1
fi

echo -e "${GREEN}✅ PHP encontrado${NC}"

# Verificar si MySQL está disponible (opcional)
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✅ MySQL encontrado${NC}"
else
    echo -e "${YELLOW}⚠️  MySQL no encontrado en PATH (puede estar instalado)${NC}"
fi

echo ""
echo "=================================="
echo "Configuración:"
echo "=================================="
echo ""

# Verificar si existe la base de datos
read -p "¿Ya configuraste la base de datos? (s/n): " db_configured

if [ "$db_configured" != "s" ] && [ "$db_configured" != "S" ]; then
    echo ""
    echo "📋 Pasos para configurar la base de datos:"
    echo "1. Crea la base de datos:"
    echo "   mysql -u root -p -e 'CREATE DATABASE greenmarket;'"
    echo ""
    echo "2. Importa el esquema:"
    echo "   mysql -u root -p greenmarket < database.sql"
    echo ""
    echo "3. (Opcional) Importa usuarios de ejemplo:"
    echo "   mysql -u root -p greenmarket < usuarios_ejemplo.sql"
    echo ""
    echo "4. Edita api/config.php con tus credenciales de MySQL"
    echo ""
    read -p "Presiona Enter cuando hayas completado estos pasos..."
fi

echo ""
echo "=================================="
echo "Iniciando servidor..."
echo "=================================="
echo ""
echo -e "${GREEN}🚀 Servidor iniciado en: http://localhost:8000${NC}"
echo ""
echo "Usuarios disponibles:"
echo "  👤 Admin: admin@greenmarket.com / admin123"
echo "  👤 Usuario 1: maria@example.com / password123"
echo "  👤 Usuario 2: juan@example.com / password123"
echo "  👤 Usuario 3: ana@example.com / password123"
echo ""
echo "O crea tu propio usuario desde la pantalla de registro"
echo ""
echo -e "${YELLOW}Presiona Ctrl+C para detener el servidor${NC}"
echo ""

# Iniciar servidor PHP
php -S localhost:8000

