/**
 * Script de prueba para verificar la impresión
 * Ejecutar: node test-print.js
 */

const axios = require('axios');

const BASE_URL = 'http://localhost:3000';

// Colores para consola
const colors = {
    reset: '\x1b[0m',
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    cyan: '\x1b[36m'
};

async function testService() {
    console.log(`${colors.cyan}🧪 Iniciando pruebas del servicio de impresión${colors.reset}\n`);

    try {
        // Test 1: Verificar que el servicio esté activo
        console.log('1️⃣ Verificando estado del servicio...');
        const statusResponse = await axios.get(`${BASE_URL}/`);
        console.log(`${colors.green}✅ Servicio activo${colors.reset}`);
        console.log(`   Status: ${statusResponse.data.status}`);
        console.log('');

        // Test 2: Verificar impresoras conectadas
        console.log('2️⃣ Buscando impresoras conectadas...');
        const printersResponse = await axios.get(`${BASE_URL}/printers`);
        
        if (printersResponse.data.success) {
            console.log(`${colors.green}✅ ${printersResponse.data.message}${colors.reset}`);
            printersResponse.data.printers.forEach((printer, idx) => {
                console.log(`   Impresora ${idx + 1}:`);
                console.log(`     VendorID: 0x${printer.vendorId.toString(16)}`);
                console.log(`     ProductID: 0x${printer.productId.toString(16)}`);
            });
        } else {
            console.log(`${colors.red}❌ ${printersResponse.data.message}${colors.reset}`);
            console.log(`${colors.yellow}⚠️  Verifica que la impresora esté conectada y encendida${colors.reset}`);
            return;
        }
        console.log('');

        // Test 3: Imprimir ticket de prueba
        console.log('3️⃣ Imprimiendo ticket de prueba...');
        const printResponse = await axios.post(`${BASE_URL}/print`, {
            tipo: 'test',
            datos: {
                mensaje: 'Impresora Star BSC10 - Test exitoso!'
            }
        });

        if (printResponse.data.success) {
            console.log(`${colors.green}✅ ${printResponse.data.message}${colors.reset}`);
        } else {
            console.log(`${colors.red}❌ Error en la impresión${colors.reset}`);
        }
        console.log('');

        console.log(`${colors.cyan}🎉 Pruebas completadas${colors.reset}`);

    } catch (error) {
        console.error(`${colors.red}❌ Error durante las pruebas:${colors.reset}`);
        
        if (error.code === 'ECONNREFUSED') {
            console.error(`   El servicio no está corriendo en ${BASE_URL}`);
            console.error(`   Ejecuta primero: npm start`);
        } else {
            console.error(`   ${error.message}`);
        }
    }
}

// Ejecutar pruebas
testService();

