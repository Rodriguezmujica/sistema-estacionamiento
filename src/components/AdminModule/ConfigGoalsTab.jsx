import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Sliders,
  DollarSign,
  Target,
  TrendingUp,
  Receipt,
  CheckCircle2,
  Calendar,
  Globe,
  Save,
  Check,
  RefreshCw,
  Percent,
  Layers,
  Briefcase
} from 'lucide-react';

export const ConfigGoalsTab = () => {
  const {
    config,
    perfilActivo,
    cambiarPerfil,
    formatMoney,
    showToast,
    refrescarDatos
  } = useApp();

  // Estados de Tarifas de Estacionamiento
  const [precioMinuto, setPrecioMinuto] = useState(config.precio_minuto || 35);
  const [precioMinimo, setPrecioMinimo] = useState(config.precio_minimo || 500);
  const [guardandoTarifas, setGuardandoTarifas] = useState(false);

  // Estados de Metas Mensuales
  const fechaHoy = new Date();
  const [mesSeleccionado, setMesSeleccionado] = useState(fechaHoy.getMonth() + 1);
  const [anioSeleccionado, setAnioSeleccionado] = useState(fechaHoy.getFullYear());
  const [metaMontoInput, setMetaMontoInput] = useState(1850000);
  const [soloHabiles, setSoloHabiles] = useState(false);
  const [sumarPlanes, setSumarPlanes] = useState(true);

  const [resumenMeta, setResumenMeta] = useState({
    metaMonto: 0,
    metaMontoFormateado: '$0',
    totalFacturado: 0,
    totalFacturadoFormateado: '$0',
    totalTickets: 0,
    ticketPromedio: 0,
    ticketPromedioFormateado: '$0',
    porcentajeAlcanzado: 0,
    porcentajeReal: 0
  });
  const [loadingMeta, setLoadingMeta] = useState(true);
  const [guardandoMeta, setGuardandoMeta] = useState(false);

  // Sincronizar inputs de tarifas al cambiar config global
  useEffect(() => {
    setPrecioMinuto(config.precio_minuto);
    setPrecioMinimo(config.precio_minimo);
  }, [config.precio_minuto, config.precio_minimo]);

  // Cargar resumen de meta del mes desde SQLite
  const cargarResumenMeta = useCallback(async () => {
    setLoadingMeta(true);
    try {
      if (typeof window !== 'undefined' && window.api?.metas?.get) {
        const datos = await window.api.metas.get({
          mes: mesSeleccionado,
          anio: anioSeleccionado,
          soloHabiles,
          sumarPlanes
        });
        if (datos) {
          setResumenMeta(datos);
          setMetaMontoInput(datos.metaMonto || 0);
        }
      }
    } catch (err) {
      console.error('Error cargando metas mensuales:', err);
      showToast('Error cargando meta mensual: ' + err.message, 'error');
    } finally {
      setLoadingMeta(false);
    }
  }, [mesSeleccionado, anioSeleccionado, soloHabiles, sumarPlanes, showToast]);

  useEffect(() => {
    cargarResumenMeta();
  }, [cargarResumenMeta]);

  // Guardar tarifas de estacionamiento
  const handleGuardarTarifas = async (e) => {
    e.preventDefault();
    const pMin = parseFloat(precioMinuto);
    const pMinimoVal = parseFloat(precioMinimo);

    if (isNaN(pMin) || pMin < 0) {
      showToast('El precio por minuto debe ser mayor o igual a 0', 'error');
      return;
    }
    if (isNaN(pMinimoVal) || pMinimoVal < 0) {
      showToast('La tarifa mínima debe ser mayor o igual a 0', 'error');
      return;
    }

    setGuardandoTarifas(true);
    try {
      if (typeof window !== 'undefined' && window.api?.tarifas?.guardar) {
        await window.api.tarifas.guardar({
          precio_minuto: pMin,
          precio_minimo: pMinimoVal
        });
      } else if (typeof window !== 'undefined' && window.api?.updateConfig) {
        await window.api.updateConfig({
          precio_minuto: pMin,
          precio_minimo: pMinimoVal
        });
      }

      await refrescarDatos();
      showToast(
        `Tarifas actualizadas: ${config.moneda_simbolo}${pMin}/min, Mínimo: ${config.moneda_simbolo}${pMinimoVal}`,
        'success'
      );
    } catch (err) {
      console.error('Error guardando tarifas:', err);
      showToast(err.message || 'Error al guardar tarifas', 'error');
    } finally {
      setGuardandoTarifas(false);
    }
  };

  // Guardar meta económica
  const handleGuardarMeta = async (e) => {
    e.preventDefault();
    const monto = parseFloat(metaMontoInput);
    if (isNaN(monto) || monto < 0) {
      showToast('La meta económica debe ser un valor válido mayor o igual a 0', 'error');
      return;
    }

    setGuardandoMeta(true);
    try {
      if (typeof window !== 'undefined' && window.api?.metas?.guardar) {
        await window.api.metas.guardar({
          mes: mesSeleccionado,
          anio: anioSeleccionado,
          meta_monto: monto,
          soloHabiles,
          sumarPlanes
        });
      }
      await cargarResumenMeta();
      showToast(`Meta económica para el período actualizada a ${formatMoney(monto)}`, 'success');
    } catch (err) {
      console.error('Error guardando meta mensual:', err);
      showToast(err.message || 'Error al guardar meta mensual', 'error');
    } finally {
      setGuardandoMeta(false);
    }
  };

  // Nombres de los meses
  const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  return (
    <div className="space-y-6">
      {/* 1. PANEL DE CONFIGURACIÓN REGIONAL Y MULTI-MONEDA */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-5 shadow-card space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Globe className="w-4 h-4 text-slate-700" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                Configuración Regional y Perfil Monetario
              </h3>
              <p className="text-xs text-slate-500">
                Determina el estándar monetario, decimales y la aplicación legal del redondeo en efectivo.
              </p>
            </div>
          </div>
          <span className="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
            Perfil Activo: {perfilActivo === 'chile' ? 'Chile (CLP)' : 'España (EUR)'}
          </span>
        </div>

        {/* Tarjetas de Selección de Perfil */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
          {/* Opción 1: Chile */}
          <div
            onClick={() => cambiarPerfil('chile')}
            className={`p-4 rounded-xl border transition-all cursor-pointer relative ${
              perfilActivo === 'chile'
                ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-slate-900/20'
                : 'bg-slate-50 hover:bg-white border-slate-200 text-slate-800'
            }`}
          >
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <span className="text-base font-bold tracking-tight">
                    Chile - CLP ($)
                  </span>
                  {perfilActivo === 'chile' && (
                    <span className="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500 text-white">
                      Activo
                    </span>
                  )}
                </div>
                <div className={`mt-2 text-xs space-y-1 ${perfilActivo === 'chile' ? 'text-slate-300' : 'text-slate-600'}`}>
                  <div>• Moneda: <strong>Pesos Chilenos (CLP)</strong> sin decimales</div>
                  <div>• Ley 20.956: <strong>Redondeo a decenas ($10) activo</strong> en efectivo</div>
                  <div>• Símbolo: <strong>$</strong> (ej: $2.500)</div>
                </div>
              </div>
              <div
                className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 ${
                  perfilActivo === 'chile'
                    ? 'border-white bg-white text-slate-900'
                    : 'border-slate-300 bg-white'
                }`}
              >
                {perfilActivo === 'chile' && <Check className="w-3.5 h-3.5 stroke-[3]" />}
              </div>
            </div>
          </div>

          {/* Opción 2: España */}
          <div
            onClick={() => cambiarPerfil('espana')}
            className={`p-4 rounded-xl border transition-all cursor-pointer relative ${
              perfilActivo === 'espana'
                ? 'bg-slate-900 text-white border-slate-900 shadow-md ring-2 ring-slate-900/20'
                : 'bg-slate-50 hover:bg-white border-slate-200 text-slate-800'
            }`}
          >
            <div className="flex items-start justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <span className="text-base font-bold tracking-tight">
                    España - EUR (€)
                  </span>
                  {perfilActivo === 'espana' && (
                    <span className="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500 text-white">
                      Activo
                    </span>
                  )}
                </div>
                <div className={`mt-2 text-xs space-y-1 ${perfilActivo === 'espana' ? 'text-slate-300' : 'text-slate-600'}`}>
                  <div>• Moneda: <strong>Euros (€)</strong> con 2 decimales estándar</div>
                  <div>• Redondeo: <strong>Sin redondeo a múltiplos de 10</strong></div>
                  <div>• Símbolo: <strong>€</strong> (ej: 2,50 €)</div>
                </div>
              </div>
              <div
                className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 ${
                  perfilActivo === 'espana'
                    ? 'border-white bg-white text-slate-900'
                    : 'border-slate-300 bg-white'
                }`}
              >
                {perfilActivo === 'espana' && <Check className="w-3.5 h-3.5 stroke-[3]" />}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* 2. CONFIGURACIÓN DE TARIFAS DE ESTACIONAMIENTO */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-5 shadow-card space-y-4">
        <div className="flex items-center gap-2 pb-3 border-b border-slate-100">
          <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
            <Sliders className="w-4 h-4 text-slate-700" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-slate-900 tracking-tight">
              Tarifas de Estacionamiento (precios / configuracion_regional)
            </h3>
            <p className="text-xs text-slate-500">
              Cálculo matemático por minuto para vehículos en el recinto (ID 18).
            </p>
          </div>
        </div>

        <form onSubmit={handleGuardarTarifas} className="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
          {/* Precio por Minuto */}
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Precio por Minuto ({config.moneda_codigo}) *
            </label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                {config.moneda_simbolo}
              </span>
              <input
                type="number"
                step="any"
                value={precioMinuto}
                onChange={(e) => setPrecioMinuto(e.target.value)}
                placeholder={config.moneda_codigo === 'EUR' ? '0.05' : '35'}
                className="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers font-semibold"
              />
            </div>
            <p className="text-[10px] text-slate-400 mt-1">
              Ej: {config.moneda_codigo === 'EUR' ? '0,05 €' : '$35 por minuto'}
            </p>
          </div>

          {/* Tarifa Mínima Cobrable */}
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Tarifa Mínima Cobrable ({config.moneda_codigo}) *
            </label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                {config.moneda_simbolo}
              </span>
              <input
                type="number"
                step="any"
                value={precioMinimo}
                onChange={(e) => setPrecioMinimo(e.target.value)}
                placeholder={config.moneda_codigo === 'EUR' ? '1.50' : '500'}
                className="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers font-semibold"
              />
            </div>
            <p className="text-[10px] text-slate-400 mt-1">
              Ej: {config.moneda_codigo === 'EUR' ? '1,50 €' : '$500 cobro base mínimo'}
            </p>
          </div>

          {/* Botón Guardar Tarifas */}
          <div>
            <button
              type="submit"
              disabled={guardandoTarifas}
              className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save className="w-3.5 h-3.5" />
              <span>{guardandoTarifas ? 'Guardando...' : 'Guardar Tarifas'}</span>
            </button>
          </div>
        </form>
      </div>

      {/* 3. META MENSUAL DE INGRESOS Y RESUMEN EJECUTIVO */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-5 shadow-card space-y-5">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Target className="w-4 h-4 text-slate-700" />
            </div>
            <div>
              <h3 className="text-sm font-bold text-slate-900 tracking-tight">
                Meta Mensual de Ingresos y Resumen Ejecutivo
              </h3>
              <p className="text-xs text-slate-500">
                Control de metas financieras y medición del desempeño comercial en SQLite.
              </p>
            </div>
          </div>

          {/* Selector de Período Mes/Año */}
          <div className="flex items-center gap-2">
            <select
              value={mesSeleccionado}
              onChange={(e) => setMesSeleccionado(parseInt(e.target.value, 10))}
              className="px-2.5 py-1 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-medium"
            >
              {nombresMeses.map((m, idx) => (
                <option key={idx + 1} value={idx + 1}>
                  {m}
                </option>
              ))}
            </select>
            <select
              value={anioSeleccionado}
              onChange={(e) => setAnioSeleccionado(parseInt(e.target.value, 10))}
              className="px-2.5 py-1 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white text-slate-800 font-medium"
            >
              <option value={2025}>2025</option>
              <option value={2026}>2026</option>
              <option value={2027}>2027</option>
            </select>
          </div>
        </div>

        {/* Formulario de Meta & Checkboxes de Control */}
        <form onSubmit={handleGuardarMeta} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            {/* Input Meta Económica */}
            <div className="sm:col-span-2">
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Meta Económica del Mes ({nombresMeses[mesSeleccionado - 1]} {anioSeleccionado}) *
              </label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">
                  {config.moneda_simbolo}
                </span>
                <input
                  type="number"
                  step="any"
                  value={metaMontoInput}
                  onChange={(e) => setMetaMontoInput(e.target.value)}
                  placeholder={config.moneda_codigo === 'EUR' ? '6000' : '6750000'}
                  className="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers font-bold text-slate-900"
                />
              </div>
            </div>

            {/* Botón Guardar Meta */}
            <div>
              <button
                type="submit"
                disabled={guardandoMeta}
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save className="w-3.5 h-3.5" />
                <span>{guardandoMeta ? 'Guardando...' : 'Actualizar Meta'}</span>
              </button>
            </div>
          </div>

          {/* Checkboxes de Control */}
          <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-1 text-xs">
            {/* Checkbox 1: Contabilizar únicamente días hábiles */}
            <label className="flex items-center gap-2 cursor-pointer select-none text-slate-700">
              <input
                type="checkbox"
                checked={soloHabiles}
                onChange={(e) => setSoloHabiles(e.target.checked)}
                className="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer"
              />
              <span>Contabilizar únicamente días hábiles (Lunes a Viernes)</span>
            </label>

            {/* Checkbox 2: Sumar ingresos de planes mensuales */}
            <label className="flex items-center gap-2 cursor-pointer select-none text-slate-700">
              <input
                type="checkbox"
                checked={sumarPlanes}
                onChange={(e) => setSumarPlanes(e.target.checked)}
                className="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer"
              />
              <span>Sumar ingresos de planes mensuales a la meta</span>
            </label>
          </div>
        </form>

        {/* Resumen Ejecutivo & Barra de Progreso Minimalista */}
        <div className="p-5 rounded-xl bg-slate-50/80 border border-slate-200/90 space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {/* Facturación Real del Mes */}
            <div>
              <span className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                Facturación Real ({nombresMeses[mesSeleccionado - 1]})
              </span>
              <div className="text-xl sm:text-2xl font-bold font-mono-numbers text-slate-900 tracking-tight">
                {loadingMeta ? '...' : resumenMeta.totalFacturadoFormateado}
              </div>
              <span className="text-[11px] text-slate-400">
                {resumenMeta.totalTickets} operaciones registradas
              </span>
            </div>

            {/* Meta Establecida */}
            <div>
              <span className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                Meta Establecida
              </span>
              <div className="text-xl sm:text-2xl font-bold font-mono-numbers text-slate-900 tracking-tight">
                {loadingMeta ? '...' : resumenMeta.metaMontoFormateado}
              </div>
              <span className="text-[11px] text-slate-400">
                Período mensual SQLite
              </span>
            </div>

            {/* KPI Ticket Promedio del Mes */}
            <div className="sm:border-l sm:border-slate-200 sm:pl-4">
              <span className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                Ticket Promedio
              </span>
              <div className="text-xl sm:text-2xl font-bold font-mono-numbers text-slate-900 tracking-tight">
                {loadingMeta ? '...' : resumenMeta.ticketPromedioFormateado}
              </div>
              <span className="text-[11px] text-slate-400">
                Por vehículo facturado
              </span>
            </div>
          </div>

          {/* Barra de Progreso Horizontal Minimalista */}
          <div className="space-y-1.5 pt-2 border-t border-slate-200/60">
            <div className="flex items-center justify-between text-xs font-semibold">
              <span className="text-slate-600">Progreso de la Meta:</span>
              <span className="font-mono-numbers text-slate-900">
                {loadingMeta ? '...' : `${resumenMeta.porcentajeReal}% alcanzado`}
              </span>
            </div>

            {/* Barra */}
            <div className="w-full h-3 rounded-full bg-slate-200 overflow-hidden relative">
              <div
                className="h-full bg-slate-900 rounded-full transition-all duration-500 ease-out"
                style={{ width: `${Math.min(resumenMeta.porcentajeAlcanzado, 100)}%` }}
              />
            </div>

            <div className="flex justify-between text-[10px] text-slate-400 font-mono-numbers">
              <span>0%</span>
              <span>50%</span>
              <span>100% ({resumenMeta.metaMontoFormateado})</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ConfigGoalsTab;
