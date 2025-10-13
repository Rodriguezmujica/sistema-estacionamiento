/**
 * Script para instalar el servicio de impresión como servicio de Windows
 * Ejecutar con privilegios de administrador:
 * node install-service.js
 */

const Service = require('node-windows').Service;
const path = require('path');

// Crear objeto de servicio
const svc = new Service({
    name: 'PrintServiceEstacionamiento',
    description: 'Servicio de impresión térmica para Sistema de Estacionamiento',
    script: path.join(__dirname, 'server.js'),
    nodeOptions: [
        '--harmony',
        '--max_old_space_size=4096'
    ]
});

// Escuchar el evento de instalación
svc.on('install', function() {
    console.log('✅ Servicio instalado correctamente');
    console.log('🚀 Iniciando servicio...');
    svc.start();
});

svc.on('start', function() {
    console.log('✅ Servicio iniciado correctamente');
    console.log('📍 El servicio está corriendo en http://localhost:3000');
    console.log('');
    console.log('Para administrar el servicio:');
    console.log('  - services.msc (Servicios de Windows)');
    console.log('  - Buscar: PrintServiceEstacionamiento');
});

svc.on('alreadyinstalled', function() {
    console.log('⚠️  El servicio ya está instalado');
});

svc.on('error', function(err) {
    console.error('❌ Error:', err);
});

// Instalar el servicio
console.log('📦 Instalando servicio de Windows...');
svc.install();

