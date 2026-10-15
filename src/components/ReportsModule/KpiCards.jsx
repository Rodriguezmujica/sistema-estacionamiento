import React from 'react';
import {
  DollarSign,
  Briefcase,
  TrendingUp,
  Car,
  CalendarCheck
} from 'lucide-react';

export const KpiCards = ({ metricas, loading }) => {
  const diaria = metricas?.facturacionDiaria || { totalFormateado: '$0', operaciones: 0 };
  const habiles = metricas?.mensualHabiles || { totalFormateado: '$0', operaciones: 0 };
  const mensual = metricas?.mensualTotal || { totalFormateado: '$0', operaciones: 0 };
  const ocupacion = metricas?.ocupacionActiva || { total: 0 };

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {/* KPI 1: Facturación Diaria */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
        <div className="flex items-center justify-between mb-2">
          <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
            Facturación Diaria
          </span>
          <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
            <DollarSign className="w-4 h-4 text-slate-700" />
          </div>
        </div>
        <div className="space-y-1">
          <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
            {loading ? (
              <span className="inline-block w-24 h-7 bg-slate-100 animate-pulse rounded" />
            ) : (
              diaria.totalFormateado
            )}
          </div>
          <div className="flex items-center gap-1.5 text-xs text-slate-500">
            <CalendarCheck className="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span>
              <strong>{diaria.operaciones}</strong> {diaria.operaciones === 1 ? 'operación hoy' : 'operaciones hoy'}
            </span>
          </div>
        </div>
      </div>

      {/* KPI 2: Mensual Hábiles (Lun-Vie) */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Mensual Hábiles
            </span>
            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200/60">
              Lun-Vie
            </span>
          </div>
          <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
            <Briefcase className="w-4 h-4 text-slate-700" />
          </div>
        </div>
        <div className="space-y-1">
          <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
            {loading ? (
              <span className="inline-block w-24 h-7 bg-slate-100 animate-pulse rounded" />
            ) : (
              habiles.totalFormateado
            )}
          </div>
          <div className="flex items-center gap-1.5 text-xs text-slate-500">
            <span>Días laborales:</span>
            <strong>{habiles.operaciones}</strong> operaciones
          </div>
        </div>
      </div>

      {/* KPI 3: Mensual Total */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Mensual Total
            </span>
            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200/60">
              Mes Completo
            </span>
          </div>
          <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
            <TrendingUp className="w-4 h-4 text-slate-700" />
          </div>
        </div>
        <div className="space-y-1">
          <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
            {loading ? (
              <span className="inline-block w-24 h-7 bg-slate-100 animate-pulse rounded" />
            ) : (
              mensual.totalFormateado
            )}
          </div>
          <div className="flex items-center gap-1.5 text-xs text-slate-500">
            <span>Acumulado:</span>
            <strong>{mensual.operaciones}</strong> operaciones totales
          </div>
        </div>
      </div>

      {/* KPI 4: Ocupación Activa */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
        <div className="flex items-center justify-between mb-2">
          <div className="flex items-center gap-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Ocupación Activa
            </span>
            <span className="flex h-2 w-2 relative">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
              <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
            </span>
          </div>
          <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
            <Car className="w-4 h-4 text-slate-700" />
          </div>
        </div>
        <div className="space-y-1">
          <div className="flex items-baseline gap-2">
            <span className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {loading ? (
                <span className="inline-block w-12 h-7 bg-slate-100 animate-pulse rounded" />
              ) : (
                ocupacion.total
              )}
            </span>
            <span className="text-xs font-medium text-slate-400">vehículos</span>
          </div>
          <div className="text-xs text-slate-500">
            Permanecen dentro del estacionamiento
          </div>
        </div>
      </div>
    </div>
  );
};
