/**
 * Servicio Local de Impresión para Star BSC10
 * Puerto: 3000
 * 
 * Este servicio se ejecuta en la PC donde está conectada la impresora
 * y recibe peticiones del navegador para imprimir tickets
 */

const express = require('express');
const cors = require('cors');
const escpos = require('escpos');
escpos.USB = require('escpos-usb');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Log de inicio
console.log('🚀 Servicio de Impresión iniciado');
console.log(`📍 Escuchando en http://localhost:${PORT}`);

/**
 * Endpoint de prueba
 */
app.get('/', (req, res) => {
    res.json({
        status: 'online',
        message: 'Servicio de impresión activo',
        version: '1.0.0'
    });
});

/**
 * Endpoint para verificar si hay impresoras conectadas
 */
app.get('/printers', (req, res) => {
    try {
        const devices = escpos.USB.findPrinter();
        
        if (devices.length === 0) {
            return res.json({
                success: false,
                message: 'No se encontraron impresoras USB',
                printers: []
            });
        }

        const printerList = devices.map((device, index) => ({
            id: index,
            vendorId: device.deviceDescriptor.idVendor,
            productId: device.deviceDescriptor.idProduct
        }));

        res.json({
            success: true,
            message: `Se encontraron ${devices.length} impresora(s)`,
            printers: printerList
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error al buscar impresoras',
            error: error.message
        });
    }
});

/**
 * Endpoint principal de impresión
 * POST /print
 * Body: { tipo, datos }
 */
app.post('/print', async (req, res) => {
    try {
        const { tipo, datos } = req.body;

        if (!tipo || !datos) {
            return res.status(400).json({
                success: false,
                message: 'Faltan parámetros: tipo y datos son requeridos'
            });
        }

        // Buscar impresora
        const device = new escpos.USB();
        
        device.open(function(error) {
            if (error) {
                console.error('❌ Error al abrir impresora:', error.message);
                return res.status(500).json({
                    success: false,
                    message: 'No se pudo conectar con la impresora',
                    error: error.message
                });
            }

            const printer = new escpos.Printer(device);

            try {
                // Según el tipo de ticket, imprimir diferente formato
                switch(tipo) {
                    case 'ingreso':
                        imprimirTicketIngreso(printer, datos);
                        break;
                    case 'salida':
                        imprimirTicketSalida(printer, datos);
                        break;
                    case 'lavado':
                        imprimirTicketLavado(printer, datos);
                        break;
                    case 'cierre_caja':
                        imprimirCierreCaja(printer, datos);
                        break;
                    case 'test':
                        imprimirTest(printer, datos);
                        break;
                    default:
                        throw new Error('Tipo de ticket no reconocido');
                }

                console.log(`✅ Ticket de ${tipo} impreso correctamente`);
                
                res.json({
                    success: true,
                    message: 'Impresión exitosa',
                    tipo: tipo
                });

            } catch (printError) {
                console.error('❌ Error durante la impresión:', printError.message);
                res.status(500).json({
                    success: false,
                    message: 'Error durante la impresión',
                    error: printError.message
                });
            }
        });

    } catch (error) {
        console.error('❌ Error general:', error.message);
        res.status(500).json({
            success: false,
            message: 'Error en el servicio de impresión',
            error: error.message
        });
    }
});

/**
 * Función para imprimir ticket de ingreso
 */
function imprimirTicketIngreso(printer, datos) {
    printer
        .font('a')
        .align('ct')
        .style('bu')
        .size(1, 1)
        .text('ESTACIONAMIENTO')
        .text('TICKET DE INGRESO')
        .text('--------------------------------')
        .style('normal')
        .align('lt')
        .size(0, 0)
        .text(`Ticket: ${datos.ticket_id || 'N/A'}`)
        .text(`Patente: ${datos.patente || 'N/A'}`)
        .text(`Tipo: ${datos.tipo_vehiculo || 'Auto'}`)
        .text(`Entrada: ${datos.fecha_ingreso || ''}`)
        .text(`Hora: ${datos.hora_ingreso || ''}`)
        .text('--------------------------------')
        .align('ct')
        .text('Conserve este ticket')
        .text('Gracias por su visita')
        .feed(2)
        .cut()
        .close();
}

/**
 * Función para imprimir ticket de salida/cobro
 */
function imprimirTicketSalida(printer, datos) {
    printer
        .font('a')
        .align('ct')
        .style('bu')
        .size(1, 1)
        .text('ESTACIONAMIENTO')
        .text('COMPROBANTE DE PAGO')
        .text('--------------------------------')
        .style('normal')
        .align('lt')
        .size(0, 0)
        .text(`Ticket: ${datos.ticket_id || 'N/A'}`)
        .text(`Patente: ${datos.patente || 'N/A'}`)
        .text(`Entrada: ${datos.fecha_ingreso || ''}`)
        .text(`Salida: ${datos.fecha_salida || ''}`)
        .text(`Tiempo: ${datos.tiempo_estadia || ''}`)
        .text('--------------------------------')
        .align('rt')
        .size(1, 1)
        .text(`TOTAL: $${datos.monto || '0'}`)
        .size(0, 0)
        .text('--------------------------------')
        .align('lt')
        .text(`Método: ${datos.metodo_pago || 'Efectivo'}`)
        .text(`Fecha: ${datos.fecha_pago || ''}`)
        .text('--------------------------------')
        .align('ct')
        .text('Gracias por su visita')
        .feed(2)
        .cut()
        .close();
}

/**
 * Función para imprimir ticket de lavado
 */
function imprimirTicketLavado(printer, datos) {
    printer
        .font('a')
        .align('ct')
        .style('bu')
        .size(1, 1)
        .text('SERVICIO DE LAVADO')
        .text('--------------------------------')
        .style('normal')
        .align('lt')
        .size(0, 0)
        .text(`Ticket: ${datos.ticket_id || 'N/A'}`)
        .text(`Patente: ${datos.patente || 'N/A'}`)
        .text(`Servicio: ${datos.servicio || 'Lavado Simple'}`)
        .text(`Fecha: ${datos.fecha || ''}`)
        .text('--------------------------------')
        .align('rt')
        .size(1, 1)
        .text(`TOTAL: $${datos.monto || '0'}`)
        .size(0, 0)
        .text('--------------------------------')
        .align('ct')
        .text('Gracias por su preferencia')
        .feed(2)
        .cut()
        .close();
}

/**
 * Función para imprimir cierre de caja
 */
function imprimirCierreCaja(printer, datos) {
    printer
        .font('a')
        .align('ct')
        .style('bu')
        .size(1, 1)
        .text('CIERRE DE CAJA')
        .text('--------------------------------')
        .style('normal')
        .align('lt')
        .size(0, 0)
        .text(`Fecha: ${datos.fecha || ''}`)
        .text(`Hora: ${datos.hora || ''}`)
        .text(`Usuario: ${datos.usuario || ''}`)
        .text('--------------------------------')
        .text('INGRESOS ESTACIONAMIENTO')
        .align('rt')
        .text(`Efectivo: $${datos.efectivo_estacionamiento || '0'}`)
        .text(`TUU: $${datos.tuu_estacionamiento || '0'}`)
        .align('lt')
        .text('--------------------------------')
        .text('INGRESOS LAVADO')
        .align('rt')
        .text(`Efectivo: $${datos.efectivo_lavado || '0'}`)
        .text(`TUU: $${datos.tuu_lavado || '0'}`)
        .align('lt')
        .text('--------------------------------')
        .text('TOTALES')
        .align('rt')
        .size(1, 1)
        .text(`TOTAL: $${datos.total || '0'}`)
        .size(0, 0)
        .text('--------------------------------')
        .align('ct')
        .feed(2)
        .cut()
        .close();
}

/**
 * Función para imprimir ticket de prueba
 */
function imprimirTest(printer, datos) {
    printer
        .font('a')
        .align('ct')
        .style('bu')
        .size(1, 1)
        .text('TEST DE IMPRESION')
        .text('--------------------------------')
        .style('normal')
        .size(0, 0)
        .text(datos.mensaje || 'Impresora funcionando correctamente')
        .text(`Fecha: ${new Date().toLocaleString('es-AR')}`)
        .text('--------------------------------')
        .feed(2)
        .cut()
        .close();
}

/**
 * Manejo de errores global
 */
app.use((error, req, res, next) => {
    console.error('❌ Error no manejado:', error);
    res.status(500).json({
        success: false,
        message: 'Error interno del servidor',
        error: error.message
    });
});

/**
 * Iniciar servidor
 */
app.listen(PORT, () => {
    console.log(`✅ Servidor corriendo en http://localhost:${PORT}`);
    console.log('📝 Endpoints disponibles:');
    console.log('   GET  /          - Estado del servicio');
    console.log('   GET  /printers  - Listar impresoras');
    console.log('   POST /print     - Imprimir ticket');
});

// Manejo de cierre graceful
process.on('SIGINT', () => {
    console.log('\n🛑 Cerrando servicio de impresión...');
    process.exit(0);
});

