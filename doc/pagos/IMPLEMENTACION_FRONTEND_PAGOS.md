# 💻 Implementación Frontend: Sistema Dual de Pagos

## 📋 Índice

1. [Estructura HTML](#estructura-html)
2. [JavaScript: Función de Pago TUU](#función-pago-tuu)
3. [JavaScript: Función de Pago Manual](#función-pago-manual)
4. [Modal de Confirmación](#modal-de-confirmación)
5. [Impresión de Comprobante](#impresión-de-comprobante)
6. [Manejo de Errores](#manejo-de-errores)

---

## 1. Estructura HTML

### Botones de Pago (Ejemplo para Modal de Cobro)

```html
<!-- En tu modal o sección de cobro -->
<div class="payment-buttons">
    <!-- Botón Principal: Pago con TUU -->
    <button 
        type="button" 
        class="btn btn-success btn-lg" 
        id="btn-pagar-tuu"
        onclick="procesarPagoTUU(idIngreso, patente, total)"
    >
        <i class="fas fa-receipt"></i>
        Pagar con TUU (Boleta Oficial)
    </button>
    
    <!-- Botón Secundario: Pago Manual -->
    <button 
        type="button" 
        class="btn btn-warning btn-lg" 
        id="btn-pagar-manual"
        onclick="abrirModalPagoManual(idIngreso, patente, total)"
    >
        <i class="fas fa-file-invoice"></i>
        Pago Manual (Comprobante Interno)
    </button>
    
    <!-- Tooltip explicativo -->
    <small class="text-muted d-block mt-2">
        <i class="fas fa-info-circle"></i>
        Usa "Pago Manual" solo si TUU está caído o no hay Internet
    </small>
</div>
```

### CSS para los Botones

```css
.payment-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 20px 0;
}

.payment-buttons .btn {
    flex: 1;
    min-width: 250px;
}

#btn-pagar-tuu {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);
}

#btn-pagar-manual {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border: none;
    box-shadow: 0 4px 6px rgba(255, 193, 7, 0.3);
}

#btn-pagar-manual:hover {
    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
}
```

---

## 2. Función de Pago TUU

```javascript
/**
 * Procesar pago con TUU (Boleta Oficial)
 * @param {number} idIngreso - ID del ingreso
 * @param {string} patente - Patente del vehículo
 * @param {number} total - Monto a cobrar
 */
function procesarPagoTUU(idIngreso, patente, total) {
    // Mostrar loading
    Swal.fire({
        title: 'Procesando con TUU...',
        html: 'Esperando confirmación del pago<br><b>Por favor, siga las instrucciones en el dispositivo TUU</b>',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar datos
    const formData = new FormData();
    formData.append('id_ingreso', idIngreso);
    formData.append('patente', patente);
    formData.append('total', total);
    formData.append('metodo_tarjeta', 'credito'); // o 'debito' o 'efectivo'
    formData.append('tipo_documento', 'boleta'); // o 'factura'
    
    // Llamada al endpoint TUU
    fetch('../api/tuu-pago.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Pago exitoso
            Swal.fire({
                icon: 'success',
                title: '✅ Pago Aprobado',
                html: `
                    <strong>Boleta Oficial Generada</strong><br>
                    Patente: ${patente}<br>
                    Total: $${total.toLocaleString('es-CL')}<br>
                    ${data.transaction_id ? `Transacción: ${data.transaction_id}` : ''}
                `,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Recargar página o actualizar lista
                location.reload();
            });
        } else {
            // Error en el pago
            Swal.fire({
                icon: 'error',
                title: '❌ Pago Rechazado',
                html: `
                    <p>${data.error || 'Error al procesar el pago'}</p>
                    <small class="text-muted">¿Desea intentar con Pago Manual?</small>
                `,
                showCancelButton: true,
                confirmButtonText: 'Pago Manual',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    abrirModalPagoManual(idIngreso, patente, total);
                }
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor. Intente con Pago Manual.',
            confirmButtonText: 'Pago Manual'
        }).then((result) => {
            if (result.isConfirmed) {
                abrirModalPagoManual(idIngreso, patente, total);
            }
        });
    });
}
```

---

## 3. Función de Pago Manual

```javascript
/**
 * Abrir modal de confirmación para pago manual
 * @param {number} idIngreso - ID del ingreso
 * @param {string} patente - Patente del vehículo
 * @param {number} total - Monto a cobrar
 */
function abrirModalPagoManual(idIngreso, patente, total) {
    Swal.fire({
        title: '⚠️ Pago Manual',
        html: `
            <div class="text-start">
                <p><strong>Este método genera un comprobante INTERNO, NO una boleta oficial.</strong></p>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>Usar solo si:</small>
                    <ul class="small mb-0">
                        <li>TUU está caído</li>
                        <li>No hay conexión a Internet</li>
                        <li>Es un ingreso por error</li>
                        <li>Es una prueba/simulación</li>
                    </ul>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <label class="form-label">Motivo del pago manual:</label>
                    <select class="form-select" id="motivo-pago-manual">
                        <option value="TUU caído">TUU caído</option>
                        <option value="Sin Internet">Sin Internet</option>
                        <option value="Ingreso por error">Ingreso por error</option>
                        <option value="Modo test">Modo test/prueba</option>
                        <option value="Cliente no requiere boleta">Cliente no requiere boleta</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Método de pago:</label>
                    <select class="form-select" id="metodo-pago-manual">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TRANSFERENCIA">Transferencia</option>
                        <option value="TARJETA">Tarjeta (sin TUU)</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <strong>Datos del pago:</strong><br>
                    Patente: <strong>${patente}</strong><br>
                    Total: <strong>$${total.toLocaleString('es-CL')}</strong>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Confirmar Pago Manual',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ff9800',
        width: '600px',
        preConfirm: () => {
            const motivo = document.getElementById('motivo-pago-manual').value;
            const metodoPago = document.getElementById('metodo-pago-manual').value;
            return { motivo, metodoPago };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            procesarPagoManual(idIngreso, patente, total, result.value.motivo, result.value.metodoPago);
        }
    });
}

/**
 * Procesar el pago manual
 */
function procesarPagoManual(idIngreso, patente, total, motivo, metodoPago) {
    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Generando comprobante interno',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar datos
    const formData = new FormData();
    formData.append('id_ingreso', idIngreso);
    formData.append('patente', patente);
    formData.append('total', total);
    formData.append('metodo_pago', metodoPago);
    formData.append('motivo_manual', motivo);
    
    // Llamada al endpoint de pago manual
    fetch('../api/pago-manual.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Pago exitoso
            Swal.fire({
                icon: 'success',
                title: '✅ Pago Manual Registrado',
                html: `
                    <div class="alert alert-warning">
                        <strong>⚠️ Comprobante Interno (No es boleta oficial)</strong>
                    </div>
                    <p>Patente: <strong>${patente}</strong></p>
                    <p>Total: <strong>$${total.toLocaleString('es-CL')}</strong></p>
                    <p>Método: <strong>${metodoPago}</strong></p>
                    <p>Motivo: <em>${motivo}</em></p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Imprimir Comprobante',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    imprimirComprobanteManual(data.data);
                }
                // Recargar página o actualizar lista
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: '❌ Error',
                text: data.error || 'Error al procesar el pago manual'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo procesar el pago. Intente nuevamente.'
        });
    });
}
```

---

## 4. Impresión de Comprobante

```javascript
/**
 * Imprimir comprobante interno
 */
function imprimirComprobanteManual(datos) {
    const ventana = window.open('', '_blank', 'width=800,height=600');
    
    const fechaIngreso = new Date(datos.fecha_ingreso).toLocaleString('es-CL');
    const fechaSalida = new Date(datos.fecha_salida).toLocaleString('es-CL');
    
    ventana.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Comprobante Interno #${datos.id_ingreso}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 600px;
                    margin: 20px auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .warning {
                    background: #fff3cd;
                    border: 2px solid #ff9800;
                    padding: 15px;
                    margin: 20px 0;
                    text-align: center;
                    font-weight: bold;
                }
                .details {
                    margin: 20px 0;
                    line-height: 1.8;
                }
                .total {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: right;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 2px solid #333;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
                @media print {
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Estacionamiento Los Ríos</h1>
                <p>COMPROBANTE INTERNO DE PAGO</p>
                <p>Nº ${datos.id_ingreso}</p>
            </div>
            
            <div class="warning">
                ⚠️ ESTE NO ES UN DOCUMENTO TRIBUTARIO VÁLIDO<br>
                Comprobante interno - No es boleta oficial
            </div>
            
            <div class="details">
                <p><strong>Patente:</strong> ${datos.patente}</p>
                <p><strong>Servicio:</strong> ${datos.servicio}</p>
                <p><strong>Ingreso:</strong> ${fechaIngreso}</p>
                <p><strong>Salida:</strong> ${fechaSalida}</p>
                <p><strong>Método de Pago:</strong> ${datos.metodo_pago}</p>
                <p><strong>Motivo Pago Manual:</strong> ${datos.motivo_manual}</p>
            </div>
            
            <div class="total">
                TOTAL: $${datos.total.toLocaleString('es-CL')}
            </div>
            
            <div class="footer">
                <p>Fecha de emisión: ${new Date().toLocaleString('es-CL')}</p>
                <p>Este comprobante es solo para control interno.</p>
                <p>No tiene validez tributaria ante el SII.</p>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" class="btn btn-primary">
                    Imprimir
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    Cerrar
                </button>
            </div>
        </body>
        </html>
    `);
    
    ventana.document.close();
}
```

---

## 5. Manejo de Errores

```javascript
/**
 * Función genérica para manejar errores de pago
 */
function manejarErrorPago(error, contexto = 'pago') {
    console.error(`Error en ${contexto}:`, error);
    
    let mensaje = 'Ocurrió un error inesperado';
    let sugerencia = '';
    
    // Identificar tipo de error
    if (error.message && error.message.includes('NetworkError')) {
        mensaje = 'No hay conexión a Internet';
        sugerencia = 'Intente con Pago Manual o verifique su conexión';
    } else if (error.message && error.message.includes('timeout')) {
        mensaje = 'Tiempo de espera agotado';
        sugerencia = 'El sistema TUU no responde. Intente con Pago Manual';
    }
    
    Swal.fire({
        icon: 'error',
        title: 'Error de Pago',
        html: `
            <p><strong>${mensaje}</strong></p>
            ${sugerencia ? `<p class="text-muted">${sugerencia}</p>` : ''}
        `,
        confirmButtonText: 'Entendido'
    });
}
```

---

## 6. Integración Completa (Ejemplo)

```javascript
// Al hacer clic en "Cobrar" desde el dashboard
function abrirModalCobro(idIngreso, patente, total) {
    // Aquí va tu modal existente con los dos botones
    const modalHTML = `
        <div class="modal fade" id="modalCobro" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cobrar Servicio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Patente: <strong>${patente}</strong></p>
                        <p>Total a cobrar: <strong>$${total.toLocaleString('es-CL')}</strong></p>
                        
                        <div class="payment-buttons">
                            <button 
                                class="btn btn-success btn-lg w-100 mb-2" 
                                onclick="procesarPagoTUU(${idIngreso}, '${patente}', ${total})"
                            >
                                <i class="fas fa-receipt"></i> Pagar con TUU
                            </button>
                            
                            <button 
                                class="btn btn-warning btn-lg w-100" 
                                onclick="abrirModalPagoManual(${idIngreso}, '${patente}', ${total})"
                            >
                                <i class="fas fa-file-invoice"></i> Pago Manual
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insertar y mostrar modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
    modal.show();
}
```

---

## 📚 Notas Finales

- **SweetAlert2:** Estos ejemplos usan SweetAlert2 para los modales. Si no lo tienes, instálalo o usa los modals de Bootstrap.
- **Recargar datos:** Después de cada pago exitoso, recarga la lista de ingresos o actualiza el estado.
- **Testing:** Prueba ambos flujos antes de poner en producción.

---

**¡Listo para implementar!** 🚀

