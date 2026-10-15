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
                try {
                    const responseText = await response.text();
                    const data = JSON.parse(responseText);
                    return { 
                        disponible: true, 
                        status: data.status,
                        version: data.version 
                    };
                } catch (parseError) {
                    console.error('Error parsing JSON en verificarEstado:', parseError);
                    return { disponible: false, error: 'Respuesta del servidor no válida' };
                }
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

            // Verificar que la respuesta sea válida antes de parsear JSON
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Obtener el texto de la respuesta primero para debug
            let responseText = await response.text();
            console.log('Respuesta del servidor:', responseText);

            let result;
            try {
                // Intentar parsear directamente
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.warn('Error parsing JSON, intentando limpiar respuesta...', parseError);
                
                // Intentar extraer solo la parte JSON válida (antes del primer <br /> o caracteres HTML)
                const jsonMatch = responseText.match(/^({.*?})(?:<br|<html|\s*$)/s);
                if (jsonMatch && jsonMatch[1]) {
                    try {
                        result = JSON.parse(jsonMatch[1].trim());
                        console.log('JSON parseado correctamente después de limpiar:', result);
                    } catch (secondParseError) {
                        console.error('Error parsing JSON incluso después de limpiar:', secondParseError);
                        console.error('Response text completo:', responseText);
                        throw new Error(`Respuesta del servidor no es JSON válido: ${parseError.message}`);
                    }
                } else {
                    console.error('No se pudo extraer JSON válido de la respuesta');
                    console.error('Response text completo:', responseText);
                    throw new Error(`Respuesta del servidor no es JSON válido: ${parseError.message}`);
                }
            }

            if (result.success) {
                if (mostrarAlerta && tipo !== 'ingreso') { // No mostrar alerta para ingresos, ya se notifica antes
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
        
        // Fallback: Crear toast personalizado simple (NUNCA alertas bloqueantes)
        this.crearToastPersonalizado(mensaje, tipo);
    },

    /**
     * Crea un toast personalizado como fallback cuando no hay Bootstrap o SweetAlert
     */
    crearToastPersonalizado(mensaje, tipo) {
        const toast = document.createElement('div');
        toast.className = `toast-personalizado toast-${tipo}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            padding: 12px 16px;
            background: ${tipo === 'success' ? '#d4edda' : tipo === 'error' ? '#f8d7da' : tipo === 'warning' ? '#fff3cd' : '#d1ecf1'};
            color: ${tipo === 'success' ? '#155724' : tipo === 'error' ? '#721c24' : tipo === 'warning' ? '#856404' : '#0c5460'};
            border: 1px solid ${tipo === 'success' ? '#c3e6cb' : tipo === 'error' ? '#f5c6cb' : tipo === 'warning' ? '#ffeaa7' : '#bee5eb'};
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        `;
        
        const icon = tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : tipo === 'warning' ? '⚠️' : 'ℹ️';
        toast.innerHTML = `<div style="display: flex; align-items: center;">
            <span style="margin-right: 8px; font-size: 16px;">${icon}</span>
            <span>${mensaje}</span></div>`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 100);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
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
