import React, { useState } from 'react';
import {
  Receipt,
  Printer,
  Banknote,
  CreditCard,
  Calendar,
  CheckCircle2,
  AlertCircle,
  HelpCircle,
  Calculator
} from 'lucide-react';

export const CashClosingPanel = ({
  cierre,
  fechaSeleccionada,
  onFechaChange,
  onGenerarTicketZ,
  formatMoney,
  loading = false
}) => {
  // Estado local para cuadratura física (arqueo ciego o conciliación)
  const [efectivoContado, setEfectivoContado] = useState('');

  const hoyStr = new Date().toISOString().split('T')[0];
  const fechaActiva = fechaSeleccionada || hoyStr;

  const totalEfectivo = cierre?.totalEfectivo || 0;
  const totalTarjeta = cierre?.totalTarjeta || 0;
  const totalTransferencia = cierre?.totalTransferencia || 0;
  const totalElectronico = totalTarjeta + totalTransferencia;
  const totalGeneral = cierre?.totalRecaudado || 0;
  const totalTransacciones = cierre?.totalTransacciones || 0;

  // Cálculo de diferencia física
  const montoContadoNum = efectivoContado !== '' ? parseFloat(efectivoContado) : null;
  const diferencia = montoContadoNum !== null ? montoContadoNum - totalEfectivo : null;

  return (
    <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden">
      {/* Cabecera del Panel */}
      <div className="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
        <div className="flex items-center gap-2.5">
          <div className="p-2 rounded-lg bg-slate-900 text-white shadow-2xs">
            <Receipt className="w-4 h-4 text-slate-100" />
          </div>
          <div>
            <h3 className="text-sm font-semibold text-slate-900 tracking-tight">
              Panel de Arqueo y Cierre de Caja (Cierre Z de Turno)
            </h3>
            <p className="text-xs text-slate-500">
              Conciliación contable diaria y cuadratura física de gaveta
            </p>
          </div>
        </div>

        {/* Selector de Fecha para el Cierre Z */}
        <div className="flex items-center gap-2">
          <label className="text-[11px] font-medium text-slate-500">
            Jornada de Cierre:
          </label>
          <div className="relative">
            <input
              type="date"
              value={fechaActiva}
              onChange={(e) => onFechaChange && onFechaChange(e.target.value)}
              max={hoyStr}
              className="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-800 font-mono-numbers focus:outline-none focus:ring-1 focus:ring-slate-900 cursor-pointer shadow-2xs"
            />
            <Calendar className="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>
      </div>

      <div className="p-5 space-y-6">
        {/* Resumen de Operaciones Cerradas */}
        <div className="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100 text-xs">
          <div className="flex items-center gap-2">
            <span className="text-slate-500 font-medium">Servicios cerrados en la fecha:</span>
            <span className="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-900 font-mono-numbers font-bold border border-slate-200">
              {totalTransacciones} {totalTransacciones === 1 ? 'operación' : 'operaciones'}
            </span>
          </div>

          <div className="flex items-center gap-2 text-slate-500">
            <span>Vehículos activos en recinto:</span>
            <strong className="font-mono-numbers text-slate-700">
              {cierre?.vehiculosActivos || 0}
            </strong>
          </div>
        </div>

        {/* Desglose Estricto en 3 Bloques (Efectivo Físico vs Pagos Electrónicos vs Total General) */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Bloque 1: Efectivo Físico en Cajón */}
          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                Efectivo Físico en Cajón
              </span>
              <div className="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/60">
                <Banknote className="w-3.5 h-3.5" />
              </div>
            </div>
            <div className="text-2xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {loading ? (
                <span className="inline-block w-24 h-7 bg-slate-100 animate-pulse rounded" />
              ) : (
                cierre?.totalEfectivoFormateado || formatMoney(totalEfectivo)
              )}
            </div>
            <p className="text-[11px] text-slate-500 leading-relaxed">
              Dinero en efectivo que debe cuadrar exactamente con los billetes y monedas en gaveta.
            </p>
          </div>

          {/* Bloque 2: Pagos Electrónicos (Tarjetas + Transferencias) */}
          <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                Pagos Electrónicos
              </span>
              <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-200/60">
                <CreditCard className="w-3.5 h-3.5" />
              </div>
            </div>
            <div className="text-2xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {loading ? (
                <span className="inline-block w-24 h-7 bg-slate-100 animate-pulse rounded" />
              ) : (
                formatMoney(totalElectronico)
              )}
            </div>
            <div className="space-y-0.5 text-[11px] text-slate-500 font-mono-numbers">
              <div className="flex justify-between">
                <span>Tarjetas / POS:</span>
                <span className="font-semibold text-slate-700">{cierre?.totalTarjetaFormateado || formatMoney(totalTarjeta)}</span>
              </div>
              <div className="flex justify-between">
                <span>Transferencias:</span>
                <span className="font-semibold text-slate-700">{cierre?.totalTransferenciaFormateado || formatMoney(totalTransferencia)}</span>
              </div>
            </div>
          </div>

          {/* Bloque 3: Total General del Cierre */}
          <div className="p-4 rounded-xl bg-slate-900 text-white space-y-2 shadow-xs">
            <div className="flex items-center justify-between">
              <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Total General del Cierre
              </span>
              <div className="w-7 h-7 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center border border-slate-700">
                <Receipt className="w-3.5 h-3.5" />
              </div>
            </div>
            <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-white tracking-tight">
              {loading ? (
                <span className="inline-block w-28 h-7 bg-slate-800 animate-pulse rounded" />
              ) : (
                cierre?.totalRecaudadoFormateado || formatMoney(totalGeneral)
              )}
            </div>
            <p className="text-[11px] text-slate-400">
              Suma global de ventas efectivas y electrónicas del turno.
            </p>
          </div>
        </div>

        {/* Conciliador de Cuadratura Física de Gaveta */}
        <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/70 space-y-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Calculator className="w-4 h-4 text-slate-600" />
              <h4 className="text-xs font-semibold text-slate-900">
                Verificación de Cuadratura Física (Arqueo de Gaveta)
              </h4>
            </div>
            <span className="text-[10px] text-slate-400">Opcional para cálculo de diferencia</span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div className="sm:col-span-5">
              <label className="block text-[11px] font-medium text-slate-600 mb-1">
                Efectivo Físico Contado en Gaveta:
              </label>
              <div className="relative">
                <input
                  type="number"
                  value={efectivoContado}
                  onChange={(e) => setEfectivoContado(e.target.value)}
                  placeholder="Ingrese el monto físico contado"
                  min="0"
                  step="any"
                  className="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-numbers focus:outline-none focus:ring-1 focus:ring-slate-900 shadow-2xs"
                />
              </div>
            </div>

            <div className="sm:col-span-7">
              {diferencia === null ? (
                <div className="text-xs text-slate-500 bg-white p-2.5 rounded-lg border border-slate-200 flex items-center gap-2">
                  <HelpCircle className="w-4 h-4 text-slate-400 shrink-0" />
                  <span>Ingrese el monto contado en la gaveta para calcular si existen diferencias de caja.</span>
                </div>
              ) : diferencia === 0 ? (
                <div className="text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 p-2.5 rounded-lg flex items-center gap-2 font-medium">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Gaveta perfectamente cuadrada. Diferencia: <strong>$0</strong></span>
                </div>
              ) : diferencia > 0 ? (
                <div className="text-xs text-blue-800 bg-blue-50 border border-blue-200 p-2.5 rounded-lg flex items-center gap-2 font-medium">
                  <AlertCircle className="w-4 h-4 text-blue-600 shrink-0" />
                  <span>Sobrante en gaveta: <strong>+{formatMoney(diferencia)}</strong></span>
                </div>
              ) : (
                <div className="text-xs text-red-800 bg-red-50 border border-red-200 p-2.5 rounded-lg flex items-center gap-2 font-medium">
                  <AlertCircle className="w-4 h-4 text-red-600 shrink-0" />
                  <span>Faltante en gaveta: <strong>{formatMoney(diferencia)}</strong></span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Botón de Acción Principal: Generar Ticket de Cierre Z */}
        <div className="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-100">
          <div className="text-xs text-slate-500">
            El ticket térmico de Cierre Z incluye desglose por categoría, resumen de medios de pago y líneas para firmas de supervisión.
          </div>

          <button
            type="button"
            onClick={() => onGenerarTicketZ && onGenerarTicketZ({
              cierre,
              efectivoContado: montoContadoNum,
              diferencia
            })}
            disabled={loading}
            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-subtle hover:shadow-card transition-all cursor-pointer disabled:opacity-50"
          >
            <Printer className="w-4 h-4 text-slate-300" />
            <span>Generar Ticket de Cierre Z</span>
          </button>
        </div>
      </div>
    </div>
  );
};
