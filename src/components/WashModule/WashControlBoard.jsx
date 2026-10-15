import React, { useState, useMemo } from 'react';
import { useApp } from '../../context/AppContext';
import { WashCard } from './WashCard';
import {
  Clock,
  Play,
  CheckCircle2,
  Search,
  LayoutGrid,
  ListFilter,
  Layers,
  Sparkles,
  Link2,
  DollarSign
} from 'lucide-react';

export const WashControlBoard = ({ onCobrar }) => {
  const { lavados, cambiarEstadoLavado, formatMoney } = useApp();
  const [vista, setVista] = useState('columnas'); // 'columnas' | 'lista'
  const [filtroBusqueda, setFiltroBusqueda] = useState('');

  // Filtrado reactivo por matrícula o servicio
  const lavadosFiltrados = useMemo(() => {
    if (!filtroBusqueda.trim()) return lavados;
    const term = filtroBusqueda.trim().toUpperCase();
    return lavados.filter(
      (l) =>
        l.patente.toUpperCase().includes(term) ||
        (l.nombre_servicio && l.nombre_servicio.toUpperCase().includes(term))
    );
  }, [lavados, filtroBusqueda]);

  // Clasificación por las 3 fases
  const enEspera = useMemo(
    () => lavadosFiltrados.filter((l) => l.estado === 'espera'),
    [lavadosFiltrados]
  );
  const enProceso = useMemo(
    () => lavadosFiltrados.filter((l) => l.estado === 'proceso'),
    [lavadosFiltrados]
  );
  const finalizados = useMemo(
    () => lavadosFiltrados.filter((l) => l.estado === 'listo'),
    [lavadosFiltrados]
  );

  return (
    <div className="space-y-4">
      {/* Barra de Filtros, Búsqueda y Selector de Vista */}
      <div className="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-card flex flex-col sm:flex-row items-center justify-between gap-3">
        {/* Campo de Búsqueda Rápida por Matrícula */}
        <div className="relative w-full sm:max-w-xs">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <Search className="w-3.5 h-3.5" />
          </div>
          <input
            type="text"
            value={filtroBusqueda}
            onChange={(e) => setFiltroBusqueda(e.target.value)}
            placeholder="Buscar por matrícula o servicio..."
            className="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 focus:border-slate-400 focus:ring-1 focus:ring-slate-300 text-xs font-mono uppercase placeholder:text-slate-400 placeholder:normal-case placeholder:font-sans transition-all"
          />
        </div>

        {/* Resumen Rápido y Selector de Vista */}
        <div className="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
          <div className="flex items-center gap-1.5 text-xs text-slate-500">
            <Layers className="w-3.5 h-3.5 text-slate-400" />
            <span>
              Total: <strong>{lavados.length} bahías activas</strong>
            </span>
          </div>

          <div className="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200">
            <button
              type="button"
              onClick={() => setVista('columnas')}
              className={`p-1.5 rounded-md text-xs font-medium transition-all cursor-pointer ${
                vista === 'columnas'
                  ? 'bg-white text-slate-900 shadow-2xs font-semibold'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
              title="Vista por Columnas (Kanban)"
            >
              <LayoutGrid className="w-3.5 h-3.5" />
            </button>
            <button
              type="button"
              onClick={() => setVista('lista')}
              className={`p-1.5 rounded-md text-xs font-medium transition-all cursor-pointer ${
                vista === 'lista'
                  ? 'bg-white text-slate-900 shadow-2xs font-semibold'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
              title="Vista de Lista / Tabla"
            >
              <ListFilter className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      {/* =================================================================== */}
      {/* VISTA 1: TABLERO DE CONTROL POR COLUMNAS (KANBAN 3 ESTADOS)         */}
      {/* =================================================================== */}
      {vista === 'columnas' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
          
          {/* COLUMNA 1: EN ESPERA */}
          <div className="bg-slate-100/60 border border-slate-200/80 rounded-2xl p-3 space-y-3">
            <div className="flex items-center justify-between pb-2 border-b border-slate-200/80 px-1">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-amber-500"></span>
                <h3 className="text-xs font-bold text-slate-800 tracking-tight uppercase">
                  En Espera
                </h3>
              </div>
              <span className="text-[10px] font-mono-numbers font-semibold px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200 shadow-2xs">
                {enEspera.length}
              </span>
            </div>

            <div className="space-y-3 min-h-[140px]">
              {enEspera.length > 0 ? (
                enEspera.map((item) => (
                  <WashCard
                    key={item.id_lavado || item.id}
                    lavado={item}
                    onCambiarEstado={cambiarEstadoLavado}
                    onCobrar={onCobrar}
                  />
                ))
              ) : (
                <div className="p-6 text-center rounded-xl border border-dashed border-slate-200 text-slate-400 space-y-1">
                  <Clock className="w-5 h-5 mx-auto text-slate-300" />
                  <p className="text-xs font-medium">Sin vehículos en espera</p>
                  <p className="text-[10px]">Las nuevas admisiones aparecerán aquí.</p>
                </div>
              )}
            </div>
          </div>

          {/* COLUMNA 2: EN PROCESO */}
          <div className="bg-slate-100/60 border border-slate-200/80 rounded-2xl p-3 space-y-3">
            <div className="flex items-center justify-between pb-2 border-b border-slate-200/80 px-1">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <h3 className="text-xs font-bold text-slate-800 tracking-tight uppercase">
                  En Proceso
                </h3>
              </div>
              <span className="text-[10px] font-mono-numbers font-semibold px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200 shadow-2xs">
                {enProceso.length}
              </span>
            </div>

            <div className="space-y-3 min-h-[140px]">
              {enProceso.length > 0 ? (
                enProceso.map((item) => (
                  <WashCard
                    key={item.id_lavado || item.id}
                    lavado={item}
                    onCambiarEstado={cambiarEstadoLavado}
                    onCobrar={onCobrar}
                  />
                ))
              ) : (
                <div className="p-6 text-center rounded-xl border border-dashed border-slate-200 text-slate-400 space-y-1">
                  <Play className="w-5 h-5 mx-auto text-slate-300" />
                  <p className="text-xs font-medium">No hay lavados en curso</p>
                  <p className="text-[10px]">Inicie un lavado desde la cola de espera.</p>
                </div>
              )}
            </div>
          </div>

          {/* COLUMNA 3: FINALIZADO / POR ENTREGAR */}
          <div className="bg-slate-100/60 border border-slate-200/80 rounded-2xl p-3 space-y-3">
            <div className="flex items-center justify-between pb-2 border-b border-slate-200/80 px-1">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h3 className="text-xs font-bold text-slate-800 tracking-tight uppercase">
                  Finalizado / Por Entregar
                </h3>
              </div>
              <span className="text-[10px] font-mono-numbers font-semibold px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200 shadow-2xs">
                {finalizados.length}
              </span>
            </div>

            <div className="space-y-3 min-h-[140px]">
              {finalizados.length > 0 ? (
                finalizados.map((item) => (
                  <WashCard
                    key={item.id_lavado || item.id}
                    lavado={item}
                    onCambiarEstado={cambiarEstadoLavado}
                    onCobrar={onCobrar}
                  />
                ))
              ) : (
                <div className="p-6 text-center rounded-xl border border-dashed border-slate-200 text-slate-400 space-y-1">
                  <CheckCircle2 className="w-5 h-5 mx-auto text-slate-300" />
                  <p className="text-xs font-medium">Sin vehículos listos</p>
                  <p className="text-[10px]">Los autos finalizados se cobran y entregan aquí.</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* =================================================================== */}
      {/* VISTA 2: LISTA DETALLADA / TABLA CONDENSADA                         */}
      {/* =================================================================== */}
      {vista === 'lista' && (
        <div className="bg-white border border-slate-200/90 rounded-2xl shadow-card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-left text-xs">
              <thead className="bg-slate-50 text-slate-500 font-semibold tracking-wider uppercase text-[10px]">
                <tr>
                  <th className="px-4 py-3">Matrícula</th>
                  <th className="px-4 py-3">Servicio</th>
                  <th className="px-4 py-3">Fase Activa</th>
                  <th className="px-4 py-3">Modalidad</th>
                  <th className="px-4 py-3">Total Est.</th>
                  <th className="px-4 py-3 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 bg-white">
                {lavadosFiltrados.length > 0 ? (
                  lavadosFiltrados.map((item) => (
                    <tr key={item.id_lavado || item.id} className="hover:bg-slate-50/70 transition-colors">
                      <td className="px-4 py-3">
                        <span className="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                          {item.patente}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <div className="font-semibold text-slate-800">{item.nombre_servicio}</div>
                        {item.motivos_extra_list && item.motivos_extra_list.length > 0 && (
                          <div className="text-[10px] text-slate-400 truncate max-w-xs">
                            {item.motivos_extra_list.join(', ')}
                          </div>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider ${
                            item.estado === 'listo'
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : item.estado === 'proceso'
                              ? 'bg-blue-50 text-blue-700 border border-blue-200'
                              : 'bg-amber-50 text-amber-700 border border-amber-200'
                          }`}
                        >
                          {item.estado === 'listo' ? 'Listo' : item.estado === 'proceso' ? 'Lavando' : 'En Espera'}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-slate-500">
                        {item.vinculado_estadia === 1 ? (
                          <span className="inline-flex items-center gap-1 text-[11px] font-medium text-slate-700">
                            <Link2 className="w-3 h-3 text-slate-400" />
                            <span>Estadía + Lavado</span>
                          </span>
                        ) : (
                          <span className="text-[11px]">Solo Lavado</span>
                        )}
                      </td>
                      <td className="px-4 py-3 font-mono font-bold text-slate-900">
                        {item.totalEstimadoFormateado || formatMoney(item.totalEstimado)}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          {item.estado === 'espera' && (
                            <button
                              type="button"
                              onClick={() => cambiarEstadoLavado(item.id_lavado, 'proceso')}
                              className="px-2.5 py-1 rounded-md text-[11px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors"
                            >
                              Iniciar
                            </button>
                          )}
                          {item.estado === 'proceso' && (
                            <button
                              type="button"
                              onClick={() => cambiarEstadoLavado(item.id_lavado, 'listo')}
                              className="px-2.5 py-1 rounded-md text-[11px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors"
                            >
                              Listo
                            </button>
                          )}
                          <button
                            type="button"
                            onClick={() => onCobrar(item)}
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-900 hover:bg-slate-800 text-white transition-colors"
                          >
                            <DollarSign className="w-3 h-3 text-emerald-400" />
                            <span>Cobrar</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-slate-400">
                      No se encontraron vehículos que coincidan con la búsqueda.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};
