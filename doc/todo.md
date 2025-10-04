To‑Do – Estacionamiento y Lavado Los Ríos
🔹 Index (Frontend)
[ ] Terminar secciones del frontend:

Pulir vista principal (index).

Diseñar espacio para cálculo de cobro en Cobro de Salidas:

Campo patente → botón “Calcular” → mostrar “Total a pagar: $____”.

Botones: “Cobrar e Imprimir Ticket” y “Pagar con TUU”.

[ ] Eliminar o reemplazar botón Abrir Caja en Acciones Rápidas.

[ ] Flujo de Tipo de Servicio → Lavado:

Opción recomendada: abrir modal emergente con servicios de lavado y patente ya cargada.

[ ] Mejorar estilos generales (colores, tipografía, consistencia visual).

🔹 Cobro de Salidas
[ ] Conectar con BD para calcular monto:

Tiempo transcurrido desde fecha_ingreso.

Tarifa por minuto ($35/min).

Sumar costo de lavado si corresponde.

[ ] Mostrar cálculo en tiempo real al ingresar patente.

[ ] Botón “Cobrar e Imprimir Ticket” que cierre el ciclo.

🔹 Integración TUU
[ ] Habilitar Modo Integración en el POS desde Espacio de Trabajo TUU.

[ ] Diseñar botón “Pagar con TUU” en Cobro de Salidas.

[ ] Simular integración (modal “Pago en proceso”).

[ ] Conectar con API real de TUU para confirmar pagos y registrar en BD.

[ ] Nota: traer una máquina TUU para pruebas reales.

🔹 Servicios de Lavado
[ ] Mejorar ventana Registrar Lavado:

Consulta de historial por patente (último servicio, monto, fecha).

Campo de comentarios libres.

Comentarios predeterminados (hongos, pelos de mascota, barro, etc.).

Guardar todo en BD para trazabilidad.

🔹 Reportes
[ ] Mejorar colores y agregar footer.

[ ] Agregar cuadro de consulta por rango de fechas (de tal a tal).

[ ] Implementar cálculo de ventas mensuales sin incluir sábados (bonos).

[ ] Mostrar comparativo:

Total mes (Lunes a Viernes).

Total mes (incluyendo Sábado).

🔹 Extras
[ ] Mostrar tiempo transcurrido en “Vehículos Estacionados Ahora”.

[ ] Resumen diario (lavados, estacionamientos, total recaudado).

[ ] Botón de exportar reporte (PDF/Excel).

[ ] Mejorar sección de “Acciones rápidas” para que sea más intuitiva.