#!/bin/bash
# Script de ayuda para hacer push rápido a GitHub
# Equivalente a gitpush.bat para Linux/Ubuntu

echo "================================"
echo "  GIT PUSH RÁPIDO"
echo "================================"
echo ""
read -p "Escribe el mensaje del commit: " msg

if [ -z "$msg" ]; then
    echo "❌ Error: El mensaje no puede estar vacío"
    exit 1
fi

echo ""
echo "📦 Agregando cambios..."
git add .

echo "💾 Creando commit..."
git commit -m "$msg"

echo "🚀 Subiendo a GitHub..."
git push origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ ¡Cambios subidos exitosamente!"
else
    echo ""
    echo "❌ Error al subir cambios"
    exit 1
fi

echo ""
read -p "Presiona Enter para continuar..."

