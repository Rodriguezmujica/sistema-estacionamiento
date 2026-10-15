import React from 'react';
import { useApp } from '../context/AppContext';
import { QuickEntryForm } from './QuickEntryForm';
import { ParkedVehiclesTable } from './ParkedVehiclesTable';
import { Car, DollarSign, Sparkles, Users, CheckCircle2, AlertTriangle, Info } from 'lucide-react';

export const Dashboard = () => {
  const { resumen, toast, config } = useApp();

  return (
    <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      
      {/* Toast de Notificaciones Flotante */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-50 animate-modal-in">
          <div
            className={`flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-modal border text-xs font-medium backdrop-blur-md ${
              toast.type === 'success'
                ? 'bg-slate-900/95 text-white border-slate-700'
                : toast.type === 'error'
                ? 'bg-rose-950/95 text-rose-100 border-rose-800'
                : 'bg-slate-900/95 text-white border-slate-700'
            }`}
          >
            {toast.type === 'success' && <CheckCircle2 className="w-4 h-4 text-emerald-400" />}
            {toast.type === 'error' && <AlertTriangle className="w-4 h-4 text-rose-400" />}
            {toast.type === 'info' && <Info className="w-4 h-4 text-blue-400" />}
            <span>{toast.message}</span>
          </div>
        </div>
      )}

      {/* Fila Superior de KPIs (Estilo SaaS Minimalista) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {/* KPI 1: Vehículos Dentro */}
        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-card hover:border-slate-300 transition-all">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium uppercase tracking-wider text-slate-500">
              Vehículos en Recinto
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Car className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {resumen.autosEstacionados}
            </span>
            <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
              Activos
            </span>
          </div>
          <p className="text-[11px] text-slate-400 mt-1.5">
            Ocupación en tiempo real
          </p>
        </div>

        {/* KPI 2: Recaudación del Día */}
        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-card hover:border-slate-300 transition-all">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium uppercase tracking-wider text-slate-500">
              Ingresos del Día
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <DollarSign className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {resumen.recaudadoHoyFormateado || '$0'}
            </span>
          </div>
          <p className="text-[11px] text-slate-400 mt-1.5">
            Jornada actual ({config.moneda_codigo})
          </p>
        </div>

        {/* KPI 3: Lavados Pendientes */}
        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-card hover:border-slate-300 transition-all">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium uppercase tracking-wider text-slate-500">
              Lavados en Cola
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Sparkles className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {resumen.lavadosPendientes}
            </span>
            {resumen.lavadosPendientes > 0 && (
              <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                Pendientes
              </span>
            )}
          </div>
          <p className="text-[11px] text-slate-400 mt-1.5">
            Vehículos en proceso de limpieza
          </p>
        </div>

        {/* KPI 4: Clientes Abonados Mensuales */}
        <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-card hover:border-slate-300 transition-all">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium uppercase tracking-wider text-slate-500">
              Clientes Abonados
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Users className="w-4 h-4" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {resumen.clientesAbonados}
            </span>
            <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200">
              Mensuales
            </span>
          </div>
          <p className="text-[11px] text-slate-400 mt-1.5">
            Pases fijos registrados en BD
          </p>
        </div>

      </div>

      {/* Formulario de Entrada Rápida */}
      <QuickEntryForm />

      {/* Tabla de Vehículos Estacionados */}
      <ParkedVehiclesTable />

    </div>
  );
};
