/**
 * Cliente JavaScript para comunicarse con el servicio local de impresión
 * Incluir este archivo en las páginas donde necesites imprimir
 */

const PrintService = {
    // URL del servicio local
    baseURL: 'http://localhost:3000',
    
    // Configuración
    config: {
        timeout: 5000, // 5 segundos
        retries: 2
    },

    /**
     * Verifica si el servicio de impresión está disponible
     */
    async verificarEstado() {
        try {
            const response = await fetch(`${this.baseURL}/`, {
                method: 'GET',
                signal: AbortSignal.timeout(this.config.timeout)
            });
            
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
     * Obtiene lista de impresoras conectadas
     */
    async listarImpresoras() {
        try {
            const response = await fetch(`${this.baseURL}/printers`, {
                method: 'GET'
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error al listar impresoras:', error);
            return { 
                success: false, 
                message: 'No se pudo conectar con el servicio',
                printers: []
            };
        }
    },

    /**
     * Función principal para imprimir
     */
    async imprimir(tipo, datos, mostrarAlerta = true) {
        try {
            // Primero verificar que el servicio esté disponible
            const estado = await this.verificarEstado();
            
            if (!estado.disponible) {
                if (mostrarAlerta) {
                    this.mostrarNotificacion(
                        'Servicio de impresión no disponible', 
                        'error'
                    );
                }
                return { 
                    success: false, 
                    error: 'Servicio no disponible' 
                };
            }

            // Enviar solicitud de impresión
            const response = await fetch(`${this.baseURL}/print`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tipo, datos })
            });

            const result = await response.json();

            if (result.success) {
                if (mostrarAlerta) {
                    this.mostrarNotificacion('✅ ¡Ticket impreso con éxito!', 'success');
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
                timer: 2500,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            return;
        }

        // Si existe Bootstrap toast
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            // Crear elemento toast
            const toastHTML = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="2500">
                    <div class="toast-header bg-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} text-white">
                        <i class="fa fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        <strong class="me-auto">${tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Información'}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">${mensaje}</div>
                </div>
            `;
            
            // Crear contenedor si no existe
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
            }
            
            // Agregar toast y mostrar
            container.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = container.lastElementChild;
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 2500
            });
            toast.show();
            
            // Eliminar después de ocultar
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
            return;
        }

        // Fallback mejorado: crear un toast personalizado simple
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
            background: ${tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#17a2b8'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            min-width: 250px;
            max-width: 350px;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center;">
                    <span style="margin-right: 8px; font-size: 16px;">
                        ${tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : 'ℹ️'}
                    </span>
                    <span>${mensaje}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 10px; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                    ×
                </button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto ocultar después de 2.5 segundos
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }, 2500);
    },

    /**
     * Inicialización del servicio (opcional)
     * Llama esto al cargar la página para verificar disponibilidad
     */
    async inicializar() {
        console.log('🖨️ Inicializando servicio de impresión...');
        const estado = await this.verificarEstado();
        
        if (estado.disponible) {
            console.log(`✅ Servicio disponible (v${estado.version})`);
            
            // Verificar impresoras
            const impresoras = await this.listarImpresoras();
            if (impresoras.success) {
                console.log(`✅ ${impresoras.printers.length} impresora(s) conectada(s)`);
            } else {
                console.warn('⚠️ No se detectaron impresoras');
            }
        } else {
            console.warn('⚠️ Servicio de impresión no disponible');
            console.warn('   Asegúrate de que el servicio esté corriendo en http://localhost:3000');
        }
        
        return estado;
    }
};

// Inicializar automáticamente al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    PrintService.inicializar();
});

// Exponer globalmente
window.PrintService = PrintService;

