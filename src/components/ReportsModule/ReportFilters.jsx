import React from 'react';
import {
  Calendar,
  Search,
  X,
  RotateCcw,
  RefreshCw,
  Filter
} from 'lucide-react';

export const ReportFilters = ({
  filtros,
  onFiltrosChange,
  onReset,
  onRefresh,
  loading
}) => {
  // Helpers para calcular rangos predefinidos
  const hoyStr = new Date().toISOString().split('T')[0];

  const setPeriodoRapido = (periodo) => {
    const ahora = new Date();
    let desde = hoyStr;
    const hasta = hoyStr;

    if (periodo === 'hoy') {
      desde = hoyStr;
    } else if (periodo === '7dias') {
      const d7 = new Date();
      d7.setDate(ahora.getDate() - 6);
      desde = d7.toISOString().split('T')[0];
    } else if (periodo === 'esteMes') {
      const primerDia = new Date(ahora.getFullYear(), ahora.getMonth(), 1);
      desde = primerDia.toISOString().split('T')[0];
    }

    onFiltrosChange({
      ...filtros,
      desde,
      hasta,
      periodoRapido: periodo
    });
  };

  const handleDesdeChange = (e) => {
    onFiltrosChange({
      ...filtros,
      desde: e.target.value,
      periodoRapido: 'personalizado'
    });
  };

  const handleHastaChange = (e) => {
    onFiltrosChange({
      ...filtros,
      hasta: e.target.value,
      periodoRapido: 'personalizado'
    });
  };

  const handlePatenteChange = (e) => {
    onFiltrosChange({
      ...filtros,
      patente: e.target.value.toUpperCase()
    });
  };

  const limpiarPatente = () => {
    onFiltrosChange({
      ...filtros,
      patente: ''
    });
  };

  return (
    <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card space-y-3.5">
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        {/* Presets Rápidos */}
        <div className="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
          <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1 mr-1 shrink-0">
            <Filter className="w-3.5 h-3.5 text-slate-400" />
            <span>Período:</span>
          </span>

          <button
            type="button"
            onClick={() => setPeriodoRapido('hoy')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all shrink-0 cursor-pointer ${
              filtros.periodoRapido === 'hoy'
                ? 'bg-slate-900 text-white shadow-xs'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200/70 border border-slate-200/60'
            }`}
          >
            Hoy
          </button>

          <button
            type="button"
            onClick={() => setPeriodoRapido('7dias')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all shrink-0 cursor-pointer ${
              filtros.periodoRapido === '7dias'
                ? 'bg-slate-900 text-white shadow-xs'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200/70 border border-slate-200/60'
            }`}
          >
            Últimos 7 días
          </button>

          <button
            type="button"
            onClick={() => setPeriodoRapido('esteMes')}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all shrink-0 cursor-pointer ${
              filtros.periodoRapido === 'esteMes'
                ? 'bg-slate-900 text-white shadow-xs'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200/70 border border-slate-200/60'
            }`}
          >
            Este mes
          </button>

          {filtros.periodoRapido === 'personalizado' && (
            <span className="px-2 py-1 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
              Personalizado
            </span>
          )}
        </div>

        {/* Acciones de Refrescar / Limpiar */}
        <div className="flex items-center gap-2 justify-end">
          <button
            type="button"
            onClick={onReset}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors cursor-pointer"
            title="Restablecer filtros a la jornada actual"
          >
            <RotateCcw className="w-3.5 h-3.5 text-slate-400" />
            <span>Restablecer</span>
          </button>

          <button
            type="button"
            onClick={onRefresh}
            disabled={loading}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors cursor-pointer disabled:opacity-50"
            title="Actualizar consultas"
          >
            <RefreshCw className={`w-3.5 h-3.5 text-slate-500 ${loading ? 'animate-spin' : ''}`} />
            <span>Actualizar</span>
          </button>
        </div>
      </div>

      {/* Rango de Fechas & Buscador por Matrícula */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-2 border-t border-slate-100">
        {/* Selector Desde */}
        <div className="lg:col-span-3">
          <label className="block text-[11px] font-medium text-slate-600 mb-1">
            Desde
          </label>
          <div className="relative">
            <input
              type="date"
              value={filtros.desde || ''}
              onChange={handleDesdeChange}
              max={filtros.hasta || hoyStr}
              className="w-full pl-9 pr-3 py-2 text-xs bg-slate-50/50 border border-slate-200 rounded-lg text-slate-800 font-mono-numbers focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 transition-all cursor-pointer"
            />
            <Calendar className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        {/* Selector Hasta */}
        <div className="lg:col-span-3">
          <label className="block text-[11px] font-medium text-slate-600 mb-1">
            Hasta
          </label>
          <div className="relative">
            <input
              type="date"
              value={filtros.hasta || ''}
              onChange={handleHastaChange}
              min={filtros.desde || ''}
              className="w-full pl-9 pr-3 py-2 text-xs bg-slate-50/50 border border-slate-200 rounded-lg text-slate-800 font-mono-numbers focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 transition-all cursor-pointer"
            />
            <Calendar className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        {/* Buscador por Matrícula */}
        <div className="sm:col-span-2 lg:col-span-6">
          <label className="block text-[11px] font-medium text-slate-600 mb-1">
            Búsqueda por Matrícula / Patente
          </label>
          <div className="relative">
            <input
              type="text"
              value={filtros.patente || ''}
              onChange={handlePatenteChange}
              placeholder="Buscar por matrícula (ej: AA1001, LAVA88...)"
              maxLength={10}
              className="w-full pl-9 pr-8 py-2 text-xs bg-slate-50/50 border border-slate-200 rounded-lg text-slate-900 placeholder:text-slate-400 font-mono uppercase tracking-wider focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-900 focus:border-slate-900 transition-all"
            />
            <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            {filtros.patente && (
              <button
                type="button"
                onClick={limpiarPatente}
                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
