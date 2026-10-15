import React, { useState, useEffect } from 'react';
import { useApp } from '../context/AppContext';
import { X, Receipt, Printer, Banknote, CreditCard, ArrowRightLeft, Calendar, Car } from 'lucide-react';

export const CashClosingModal = () => {
  const { isCashClosingOpen, setIsCashClosingOpen, config, formatMoney } = useApp();
  const [cierreData, setCierreData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!isCashClosingOpen) return;

    let isMounted = true;
    setLoading(true);

    const fetchCierre = async () => {
      try {
        if (window.api && (window.api.getCierreDiario || window.api.caja?.getCierreDiario)) {
          const res = window.api.getCierreDiario 
            ? await window.api.getCierreDiario()
            : await window.api.caja.getCierreDiario();
          if (isMounted) setCierreData(res);
        } else {
          // Fallback en desarrollo
          if (isMounted) {
            setCierreData({
              fecha: new Date().toISOString().split('T')[0],
              totalTransacciones: 0,
              totalRecaudado: 0,
              totalRecaudadoFormateado: formatMoney(0),
              totalEfectivo: 0,
              totalEfectivoFormateado: formatMoney(0),
              totalTarjeta: 0,
              totalTarjetaFormateado: formatMoney(0),
              totalTransferencia: 0,
              totalTransferenciaFormateado: formatMoney(0),
              desgloseMetodos: [],
              desgloseServicios: [],
              vehiculosIngresadosHoy: 0,
              vehiculosActivos: 0
            });
          }
        }
      } catch (err) {
        console.error('Error obteniendo cierre de caja:', err);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchCierre();

    return () => {
      isMounted = false;
    };
  }, [isCashClosingOpen, formatMoney]);

  if (!isCashClosingOpen) return null;

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in no-print-overlay">
      <div className="bg-white rounded-2xl shadow-modal border border-slate-200 overflow-hidden max-w-lg w-full flex flex-col max-h-[92vh]">
        
        {/* Cabecera */}
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 no-print">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-lg bg-slate-900 text-white">
              <Receipt className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-sm font-semibold text-slate-900">
                Cierre de Caja Diario (Reporte Z)
              </h3>
              <p className="text-[11px] text-slate-500 flex items-center gap-1">
                <Calendar className="w-3 h-3" />
                <span>Fecha: {new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
              </p>
            </div>
          </div>
          <button
            onClick={() => setIsCashClosingOpen(false)}
            className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Contenido Imprimible */}
        <div className="p-6 overflow-y-auto space-y-5 flex-1" id="printable-thermal-ticket">
          {loading ? (
            <div className="py-12 text-center text-slate-500 text-xs">
              Calculando arqueo y balances contables...
            </div>
          ) : cierreData ? (
            <>
              {/* Tarjeta de Gran Total Recaudado */}
              <div className="p-4 rounded-xl bg-slate-900 text-white space-y-1">
                <div className="text-[11px] font-medium text-slate-400 uppercase tracking-wider">
                  Total Recaudado en la Jornada
                </div>
                <div className="text-3xl font-bold font-mono-numbers tracking-tight">
                  {cierreData.totalRecaudadoFormateado}
                </div>
                <div className="text-xs text-slate-300 pt-1 flex items-center gap-2">
                  <span>Operaciones: <strong>{cierreData.totalTransacciones}</strong></span>
                  <span>•</span>
                  <span>Vehículos ingresados hoy: <strong>{cierreData.vehiculosIngresadosHoy}</strong></span>
                </div>
              </div>

              {/* Cuadratura por Método de Pago */}
              <div>
                <h4 className="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2.5">
                  Cuadratura por Método de Pago
                </h4>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                  {/* Efectivo */}
                  <div className="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                    <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                      <Banknote className="w-3.5 h-3.5 text-emerald-600" />
                      <span>Efectivo en Gaveta</span>
                    </div>
                    <div className="text-base font-bold text-slate-900 font-mono-numbers">
                      {cierreData.totalEfectivoFormateado}
                    </div>
                  </div>

                  {/* Tarjeta / POS */}
                  <div className="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                    <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                      <CreditCard className="w-3.5 h-3.5 text-blue-600" />
                      <span>Tarjeta / POS</span>
                    </div>
                    <div className="text-base font-bold text-slate-900 font-mono-numbers">
                      {cierreData.totalTarjetaFormateado}
                    </div>
                  </div>

                  {/* Transferencias */}
                  <div className="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                    <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                      <ArrowRightLeft className="w-3.5 h-3.5 text-purple-600" />
                      <span>Transferencias</span>
                    </div>
                    <div className="text-base font-bold text-slate-900 font-mono-numbers">
                      {cierreData.totalTransferenciaFormateado}
                    </div>
                  </div>
                </div>
              </div>

              {/* Desglose por Categoría de Servicio */}
              {cierreData.desgloseServicios && cierreData.desgloseServicios.length > 0 && (
                <div>
                  <h4 className="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    Ventas por Categoría de Servicio
                  </h4>
                  <div className="border border-slate-200 rounded-lg overflow-hidden">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-slate-50 text-[10px] text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                          <th className="py-2 px-3">Servicio</th>
                          <th className="py-2 px-3 text-center">Tickets</th>
                          <th className="py-2 px-3 text-right">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 font-mono-numbers">
                        {cierreData.desgloseServicios.map((s, idx) => (
                          <tr key={idx} className="hover:bg-slate-50/50">
                            <td className="py-2 px-3 font-sans text-slate-800">{s.nombre_servicio}</td>
                            <td className="py-2 px-3 text-center text-slate-600">{s.cantidad}</td>
                            <td className="py-2 px-3 text-right font-bold text-slate-900">{s.subtotalFormateado}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* Estado de Patio / Ocupación Actual */}
              <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs flex justify-between items-center text-slate-600">
                <span className="flex items-center gap-1.5">
                  <Car className="w-3.5 h-3.5 text-slate-500" />
                  Vehículos que permanecen dentro del recinto:
                </span>
                <span className="font-mono-numbers font-bold text-slate-900">
                  {cierreData.vehiculosActivos}
                </span>
              </div>
            </>
          ) : null}
        </div>

        {/* Acciones */}
        <div className="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between no-print">
          <button
            type="button"
            onClick={handlePrint}
            className="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium flex items-center gap-1.5 shadow-subtle transition-colors"
          >
            <Printer className="w-3.5 h-3.5 text-slate-500" />
            <span>Imprimir Cierre Z</span>
          </button>

          <button
            type="button"
            onClick={() => setIsCashClosingOpen(false)}
            className="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-all shadow-subtle"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  );
};
