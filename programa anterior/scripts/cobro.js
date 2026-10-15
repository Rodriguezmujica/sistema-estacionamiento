document.addEventListener('DOMContentLoaded', function() {
    const formCobro = document.getElementById('form-cobro-salida');
    const patenteCobroInput = document.getElementById('patente-cobro');
    const resultadoCobroDiv = document.getElementById('resultado-cobro');
    const btnCobrarTicket = document.getElementById('btn-cobrar-ticket');
    const btnPagarTUU = document.getElementById('btn-pagar-tuu');

    // Variables para almacenar datos del cobro
    let cobroActual = null;

    // --- MODAL PAGO MANUAL ---
    const modalPagoManual = new bootstrap.Modal(document.getElementById('modalPagoManual'));
    const btnConfirmarPagoManual = document.getElementById('btn-confirmar-pago-manual');

    // --- MODAL PAGO TUU ---
    const modalPagoTUU = new bootstrap.Modal(document.getElementById('modalPagoTUU'));
    const radioBoleta = document.getElementById('docBoleta');
    const radioFactura = document.getElementById('docFactura');
    const campoRutFactura = document.getElementById('campo-rut-factura');
    const inputRutFactura = document.getElementById('rut-factura');
    const btnIniciarPagoTUU = document.getElementById('btn-iniciar-pago-tuu');
    const spinnerPagoTUU = document.getElementById('spinner-pago-tuu');
    const contenedorBtnIniciarPago = document.getElementById('contenedor-btn-iniciar-pago');

    // Función para limpiar el estado de cobro
    function limpiarCobro() {
        cobroActual = null;
        patenteCobroInput.value = '';
        resultadoCobroDiv.innerHTML = '';
        resultadoCobroDiv.classList.add('d-none');
        btnCobrarTicket.disabled = true;
        btnPagarTUU.disabled = true;
        patenteCobroInput.focus();
    }

    // Evento para calcular el cobro
    formCobro.addEventListener('submit', async function(e) {
        e.preventDefault();
        const patente = patenteCobroInput.value.trim();
        if (!patente) return;

        const formData = new FormData();
        formData.append('patente', patente);

        try {
            const response = await fetch('api/calcular-cobro.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                cobroActual = data;
                mostrarResultadoCobro(data);
                btnCobrarTicket.disabled = false;
                btnPagarTUU.disabled = false;
            } else {
                mostrarAlerta('error', data.error || 'Error al calcular el cobro.');
                limpiarCobro();
            }
        } catch (error) {
            mostrarAlerta('error', 'Error de conexión al calcular el cobro.');
            limpiarCobro();
        }
    });

    function mostrarResultadoCobro(data) {
        let html = `
            <div class="alert alert-info">
                <h5 class="alert-heading">Detalle del Cobro</h5>
                <p><strong>Patente:</strong> ${data.patente}</p>
                <p><strong>Servicio:</strong> ${data.nombre_servicio}</p>
                <p><strong>Ingreso:</strong> ${new Date(data.fecha_ingreso).toLocaleString()}</p>
                ${data.minutos > 0 ? `<p><strong>Minutos Transcurridos:</strong> ${data.minutos}</p>` : ''}
                <hr>
                <p class="mb-0 fs-4"><strong>Total a Pagar: $${new Intl.NumberFormat('es-CL').format(data.total)}</strong></p>
            </div>
        `;
        resultadoCobroDiv.innerHTML = html;
        resultadoCobroDiv.classList.remove('d-none');
    }

    // --- LÓGICA PAGO MANUAL ---
    btnCobrarTicket.addEventListener('click', () => {
        if (!cobroActual) return;
        document.getElementById('patente-modal-manual').textContent = cobroActual.patente;
        document.getElementById('total-modal-manual').textContent = `$${new Intl.NumberFormat('es-CL').format(cobroActual.total)}`;
        modalPagoManual.show();
    });

    btnConfirmarPagoManual.addEventListener('click', async () => {
        const metodoPago = document.getElementById('metodo-pago-manual').value;
        const motivoManual = document.getElementById('motivo-pago-manual').value;

        if (!motivoManual) {
            mostrarAlerta('error', 'Debe seleccionar un motivo para el pago manual.');
            return;
        }

        const formData = new FormData();
        formData.append('id_ingreso', cobroActual.id);
        formData.append('patente', cobroActual.patente);
        formData.append('total', cobroActual.total);
        formData.append('metodo_pago', metodoPago);
        formData.append('motivo_manual', motivoManual);

        try {
            const response = await fetch('api/pago-manual.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                mostrarAlerta('success', 'Pago manual registrado correctamente.');
                modalPagoManual.hide();
                limpiarCobro();
                // Aquí podrías agregar lógica para imprimir un comprobante interno
            } else {
                mostrarAlerta('error', data.error || 'Error al registrar el pago manual.');
            }
        } catch (error) {
            mostrarAlerta('error', 'Error de conexión al registrar el pago manual.');
        }
    });

    // --- LÓGICA PAGO TUU ---
    btnPagarTUU.addEventListener('click', () => {
        if (!cobroActual) return;

        // Resetear el modal
        spinnerPagoTUU.classList.add('d-none');
        contenedorBtnIniciarPago.classList.remove('d-none');
        btnIniciarPagoTUU.disabled = false;
        radioBoleta.checked = true;
        campoRutFactura.classList.add('d-none');
        inputRutFactura.value = '';

        // Llenar datos en el modal
        document.getElementById('patente-modal-tuu').textContent = cobroActual.patente;
        document.getElementById('total-modal-tuu').textContent = `$${new Intl.NumberFormat('es-CL').format(cobroActual.total)}`;
        
        modalPagoTUU.show();
    });

    // Mostrar/ocultar campo RUT para factura
    radioBoleta.addEventListener('change', () => campoRutFactura.classList.add('d-none'));
    radioFactura.addEventListener('change', () => campoRutFactura.classList.remove('d-none'));

    // Evento para iniciar el pago en la máquina
    btnIniciarPagoTUU.addEventListener('click', async () => {
        const tipoDocumento = radioFactura.checked ? 'factura' : 'boleta';
        const rutCliente = inputRutFactura.value;

        if (tipoDocumento === 'factura' && !rutCliente) {
            mostrarAlerta('error', 'Debe ingresar un RUT para emitir factura.');
            return;
        }

        // Mostrar spinner y deshabilitar botón
        spinnerPagoTUU.classList.remove('d-none');
        contenedorBtnIniciarPago.classList.add('d-none');
        btnIniciarPagoTUU.disabled = true;

        const formData = new FormData();
        formData.append('id_ingreso', cobroActual.id);
        formData.append('patente', cobroActual.patente);
        formData.append('total', cobroActual.total);
        formData.append('tipo_documento', tipoDocumento);
        formData.append('rut_cliente', rutCliente);

        try {
            const response = await fetch('api/tuu-pago.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                mostrarAlerta('success', `Pago Aprobado con TUU. Código: ${data.authorization_code || 'N/A'}`);
                modalPagoTUU.hide();
                limpiarCobro();
                // Aquí podrías agregar lógica para imprimir el voucher si es necesario
            } else {
                // Si el pago es rechazado, cancelado o hay timeout
                mostrarAlerta('error', data.error || 'El pago fue rechazado o cancelado en la máquina.');
                // Volver a mostrar el botón para reintentar
                spinnerPagoTUU.classList.add('d-none');
                contenedorBtnIniciarPago.classList.remove('d-none');
                btnIniciarPagoTUU.disabled = false;
            }
        } catch (error) {
            mostrarAlerta('error', 'Error de conexión con el servidor de pagos.');
            spinnerPagoTUU.classList.add('d-none');
            contenedorBtnIniciarPago.classList.remove('d-none');
            btnIniciarPagoTUU.disabled = false;
        }
    });
});