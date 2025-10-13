/**
 * Cliente JavaScript para Servicio de Impresión PHP (Windows 7 Compatible)
 * Este cliente se comunica con el servicio PHP local
 */

const PrintServiceWin7 = {
    // URL del servicio PHP local
    baseURL: 'http://localhost:8080/sistemaEstacionamiento/print-service-php/imprimir.php',
    
    // Nombre de la impresora (ajustar según aparezca en Windows)
    nombreImpresora: 'POSESTACIONAMIENTO',
    
    /**
     * Verifica si el servicio está disponible
     */
    async verificarEstado() {
        try {
            const response = await fetch(`${this.baseURL}?action=status`);
            
            if (response.ok) {
                const data = await response.json();
                return { 
                    disponible: true, 
                    status: data.status,
                    version: data.version 
                };
            }
            return { disponible: false, error: 'Servicio no responde' };
        } catch (error) {
            return { 
                disponible: false, 
                error: 'Servicio de impresión no disponible' 
            };
        }
    },

    /**
     * Función principal para imprimir
     */
    async imprimir(tipo, datos, mostrarAlerta = true) {
        try {
            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    tipo, 
                    datos,
                    impresora: this.nombreImpresora
                })
            });

            const result = await response.json();

            if (result.success) {
                if (mostrarAlerta) {
                    this.mostrarNotificacion('Ticket impreso correctamente', 'success');
                }
                console.log(`✅ Ticket de ${tipo} impreso`);
            } else {
                if (mostrarAlerta) {
                    this.mostrarNotificacion(
                        `Error al imprimir: ${result.message}`, 
                        'error'
                    );
                }
                console.error(`❌ Error al imprimir: ${result.message}`);
            }

            return result;

        } catch (error) {
            console.error('Error en impresión:', error);
            if (mostrarAlerta) {
                this.mostrarNotificacion(
                    'No se pudo conectar con el servicio de impresión', 
                    'error'
                );
            }
            return { success: false, error: error.message };
        }
    },

    /**
     * Imprimir ticket de ingreso
     */
    async imprimirTicketIngreso(ticketId, patente, tipoVehiculo, fechaIngreso, horaIngreso) {
        return await this.imprimir('ingreso', {
            ticket_id: ticketId,
            patente: patente.toUpperCase(),
            tipo_vehiculo: tipoVehiculo,
            fecha_ingreso: fechaIngreso,
            hora_ingreso: horaIngreso
        });
    },

    /**
     * Imprimir ticket de salida/cobro
     */
    async imprimirTicketSalida(datosCompletos) {
        return await this.imprimir('salida', {
            ticket_id: datosCompletos.ticket_id,
            patente: datosCompletos.patente.toUpperCase(),
            fecha_ingreso: datosCompletos.fecha_ingreso,
            fecha_salida: datosCompletos.fecha_salida,
            tiempo_estadia: datosCompletos.tiempo_estadia,
            monto: datosCompletos.monto,
            metodo_pago: datosCompletos.metodo_pago,
            fecha_pago: datosCompletos.fecha_pago
        });
    },

    /**
     * Imprimir ticket de lavado
     */
    async imprimirTicketLavado(ticketId, patente, servicio, monto, fecha) {
        return await this.imprimir('lavado', {
            ticket_id: ticketId,
            patente: patente.toUpperCase(),
            servicio: servicio,
            monto: monto,
            fecha: fecha
        });
    },

    /**
     * Imprimir cierre de caja
     */
    async imprimirCierreCaja(datosCierre) {
        return await this.imprimir('cierre_caja', datosCierre);
    },

    /**
     * Imprimir ticket de prueba
     */
    async imprimirTest(mensaje = 'Prueba de impresión') {
        return await this.imprimir('test', { mensaje });
    },

    /**
     * Muestra notificaciones al usuario
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Si existe SweetAlert2
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'success' ? 'success' : tipo === 'error' ? 'error' : 'info',
                title: mensaje,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }

        // Fallback: alert simple
        alert(mensaje);
    },

    /**
     * Inicialización del servicio
     */
    async inicializar() {
        console.log('🖨️ Inicializando servicio de impresión PHP...');
        const estado = await this.verificarEstado();
        
        if (estado.disponible) {
            console.log(`✅ Servicio disponible (v${estado.version})`);
        } else {
            console.warn('⚠️ Servicio de impresión no disponible');
        }
        
        return estado;
    }
};

// Inicializar automáticamente al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    PrintServiceWin7.inicializar();
});

// Exponer globalmente
window.PrintService = PrintServiceWin7;

