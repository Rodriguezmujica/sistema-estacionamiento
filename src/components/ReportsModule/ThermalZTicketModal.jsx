import React from 'react';
import { Printer, X, Receipt, CheckCircle2 } from 'lucide-react';

export const ThermalZTicketModal = ({
  isOpen,
  onClose,
  cierre,
  datosCuadratura,
  formatMoney,
  config
}) => {
  if (!isOpen || !cierre) return null;

  const handlePrint = () => {
    window.print();
  };

  const fechaEmision = new Date().toLocaleDateString('es-CL');
  const horaEmision = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  const totalElectronico = (cierre.totalTarjeta || 0) + (cierre.totalTransferencia || 0);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in no-print-overlay">
      <div className="bg-white rounded-2xl shadow-modal border border-slate-200 overflow-hidden max-w-md w-full flex flex-col max-h-[92vh]">
        
        {/* Cabecera del Modal (No se imprime) */}
        <div className="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 no-print">
          <div className="flex items-center gap-2">
            <div className="p-1.5 rounded-lg bg-slate-900 text-white">
              <Receipt className="w-4 h-4 text-slate-100" />
            </div>
            <div>
              <h3 className="text-xs font-semibold text-slate-900">
                Vista Previa Ticket Térmico (Star BSC10)
              </h3>
              <p className="text-[10px] text-slate-500">
                Formato estándar de 80mm ESC/POS listo para impresión física
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Contenedor del Ticket Térmico con Efecto Papel */}
        <div className="p-5 overflow-y-auto flex-1 bg-slate-100/60 flex justify-center">
          <div
            id="printable-thermal-ticket"
            className="w-full max-w-[340px] bg-white p-5 rounded-lg shadow-subtle border border-slate-200/80 font-mono text-[11px] leading-relaxed text-slate-900"
          >
            {/* Header del Ticket */}
            <div className="text-center space-y-0.5 pb-2">
              <div className="font-bold text-xs uppercase tracking-wider">
                PARKFLOW CONTROL
              </div>
              <div className="text-[10px] text-slate-600 uppercase">
                Sistema de Estacionamiento & Lavados
              </div>
              <div className="font-bold text-[11px] pt-1">
                ================================
              </div>
              <div className="font-bold text-xs">
                REPORTE DE CIERRE DE CAJA (Z)
              </div>
              <div className="font-bold text-[11px]">
                ================================
              </div>
            </div>

            {/* Metadatos de la Emisión */}
            <div className="space-y-0.5 py-1 text-[10px]">
              <div className="flex justify-between">
                <span>FECHA JORNADA:</span>
                <span className="font-bold">{cierre.fecha}</span>
              </div>
              <div className="flex justify-between">
                <span>HORA EMISION:</span>
                <span>{horaEmision}</span>
              </div>
              <div className="flex justify-between">
                <span>TERMINAL:</span>
                <span>CAJA-01 (PRINCIPAL)</span>
              </div>
              <div className="flex justify-between">
                <span>MONEDA:</span>
                <span>{config?.moneda_codigo || 'CLP'} ({config?.moneda_simbolo || '$'})</span>
              </div>
            </div>

            <div className="border-t border-dashed border-slate-400 my-2" />

            {/* Resumen Operativo */}
            <div className="space-y-0.5 text-[10px]">
              <div className="font-bold uppercase mb-0.5">RESUMEN OPERATIVO</div>
              <div className="flex justify-between">
                <span>SERVICIOS CERRADOS:</span>
                <span className="font-bold font-mono-numbers">{cierre.totalTransacciones} tickets</span>
              </div>
              <div className="flex justify-between">
                <span>VEHICULOS EN PATIO:</span>
                <span className="font-bold font-mono-numbers">{cierre.vehiculosActivos} activos</span>
              </div>
            </div>

            <div className="border-t border-dashed border-slate-400 my-2" />

            {/* Cuadratura por Medio de Pago */}
            <div className="space-y-1 text-[10px]">
              <div className="font-bold uppercase mb-0.5">DESGLOSE POR MEDIO DE PAGO</div>
              
              <div className="flex justify-between">
                <span>(+) EFECTIVO GAVETA:</span>
                <span className="font-bold font-mono-numbers">{cierre.totalEfectivoFormateado || formatMoney(cierre.totalEfectivo)}</span>
              </div>

              <div className="flex justify-between">
                <span>(+) TARJETAS (POS):</span>
                <span className="font-mono-numbers">{cierre.totalTarjetaFormateado || formatMoney(cierre.totalTarjeta)}</span>
              </div>

              <div className="flex justify-between">
                <span>(+) TRANSFERENCIAS:</span>
                <span className="font-mono-numbers">{cierre.totalTransferenciaFormateado || formatMoney(cierre.totalTransferencia)}</span>
              </div>

              <div className="border-t border-slate-400 pt-1 flex justify-between font-bold text-[11px]">
                <span>(=) TOTAL GENERAL:</span>
                <span className="font-mono-numbers">{cierre.totalRecaudadoFormateado || formatMoney(cierre.totalRecaudado)}</span>
              </div>
            </div>

            <div className="border-t border-dashed border-slate-400 my-2" />

            {/* Desglose por Servicio */}
            {cierre.desgloseServicios && cierre.desgloseServicios.length > 0 && (
              <div className="space-y-1 text-[10px]">
                <div className="font-bold uppercase mb-0.5">VENTAS POR SERVICIO</div>
                {cierre.desgloseServicios.map((s, idx) => (
                  <div key={idx} className="flex justify-between items-center text-[10px]">
                    <span className="truncate pr-1">{s.nombre_servicio} ({s.cantidad}):</span>
                    <span className="font-bold shrink-0 font-mono-numbers">{s.subtotalFormateado || formatMoney(s.subtotal)}</span>
                  </div>
                ))}
              </div>
            )}

            {/* Conciliación Física si se ingresó monto */}
            {datosCuadratura?.efectivoContado !== null && datosCuadratura?.efectivoContado !== undefined && (
              <>
                <div className="border-t border-dashed border-slate-400 my-2" />
                <div className="space-y-0.5 text-[10px]">
                  <div className="font-bold uppercase mb-0.5">ARQUEO FISICO GAVETA</div>
                  <div className="flex justify-between">
                    <span>EFECTIVO SISTEMA:</span>
                    <span className="font-mono-numbers">{formatMoney(cierre.totalEfectivo)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>EFECTIVO CONTADO:</span>
                    <span className="font-bold font-mono-numbers">{formatMoney(datosCuadratura.efectivoContado)}</span>
                  </div>
                  <div className="flex justify-between font-bold">
                    <span>DIFERENCIA:</span>
                    <span className="font-mono-numbers">
                      {datosCuadratura.diferencia === 0
                        ? '$0 (CUADRADA)'
                        : `${datosCuadratura.diferencia > 0 ? '+' : ''}${formatMoney(datosCuadratura.diferencia)}`}
                    </span>
                  </div>
                </div>
              </>
            )}

            <div className="border-t border-dashed border-slate-400 my-3" />

            {/* Firmas de Control */}
            <div className="space-y-4 pt-1 text-[10px]">
              <div>
                <div className="border-b border-slate-400 w-3/4 mx-auto mb-1" />
                <div className="text-center font-bold">FIRMA CAJERO / OPERADOR</div>
              </div>

              <div>
                <div className="border-b border-slate-400 w-3/4 mx-auto mb-1" />
                <div className="text-center font-bold">FIRMA ADMINISTRACIÓN / SUPERVISOR</div>
              </div>
            </div>

            {/* Pie del Ticket */}
            <div className="text-center pt-4 text-[9px] text-slate-500 space-y-0.5">
              <div className="font-bold">*** FIN DE TICKET DE CIERRE Z ***</div>
              <div>Impresora: Star BSC10 (Térmica 80mm)</div>
              <div>ParkFlow Software • {fechaEmision} {horaEmision}</div>
            </div>
          </div>
        </div>

        {/* Acciones del Modal (No se imprimen) */}
        <div className="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between no-print">
          <button
            type="button"
            onClick={handlePrint}
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-subtle transition-all cursor-pointer"
          >
            <Printer className="w-3.5 h-3.5 text-slate-300" />
            <span>Imprimir en Star BSC10</span>
          </button>

          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium transition-colors cursor-pointer"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  );
};
