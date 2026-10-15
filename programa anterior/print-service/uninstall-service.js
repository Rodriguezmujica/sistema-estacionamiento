/**
 * Script para desinstalar el servicio de Windows
 * Ejecutar con privilegios de administrador:
 * node uninstall-service.js
 */

const Service = require('node-windows').Service;
const path = require('path');

// Crear objeto de servicio (debe coincidir con el de instalación)
const svc = new Service({
    name: 'PrintServiceEstacionamiento',
    script: path.join(__dirname, 'server.js')
});

// Escuchar el evento de desinstalación
svc.on('uninstall', function() {
    console.log('✅ Servicio desinstalado correctamente');
});

svc.on('alreadyuninstalled', function() {
    console.log('⚠️  El servicio no está instalado');
});

svc.on('error', function(err) {
    console.error('❌ Error:', err);
});

// Desinstalar el servicio
console.log('🗑️  Desinstalando servicio de Windows...');
svc.uninstall();

