import React, { useState, useEffect, useRef, useMemo } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Car,
  Sparkles,
  Link2,
  Unlink,
  History,
  ChevronDown,
  ChevronUp,
  AlertCircle,
  CheckCircle2,
  Info,
  DollarSign,
  Plus,
  RotateCcw
} from 'lucide-react';

// Lista de condiciones y recargos especiales (estrictamente sobrio, sin emojis)
const CONDICIONES_ESPECIALES = [
  'Hongos',
  'Pelos de mascota',
  'Barro excesivo',
  'Grasa / aceite',
  'Insectos adheridos',
  'Daño en pintura',
  'Interior sucio',
  'Otro motivo'
];

export const WashAdmissionForm = ({ onLavadoRegistrado }) => {
  const {
    tiposIngreso,
    vehiculos,
    config,
    formatMoney,
    registrarLavado,
    getHistorialVehiculo
  } = useApp();

  // Estados del Formulario
  const [patente, setPatente] = useState('');
  const [idServicio, setIdServicio] = useState('');
  const [condicionesSeleccionadas, setCondicionesSeleccionadas] = useState([]);
  const [recargoExtra, setRecargoExtra] = useState('');
  const [observaciones, setObservaciones] = useState('');
  const [vincularEstadia, setVincularEstadia] = useState(true);

  // Estados de Búsqueda Inteligente e Historial
  const [vehiculoEstacionado, setVehiculoEstacionado] = useState(null);
  const [historialVisitas, setHistorialVisitas] = useState([]);
  const [isSearchingHistory, setIsSearchingHistory] = useState(false);
  const [isHistoryExpanded, setIsHistoryExpanded] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const inputPatenteRef = useRef(null);

  // Filtrar catálogo de servicios de lavado activos
  const catalogoLavados = useMemo(() => {
    const lavados = tiposIngreso.filter((t) => {
      const nombre = t.nombre_servicio.toLowerCase();
      return (
        t.activo === 1 &&
        (nombre.includes('lavad') ||
          nombre.includes('detailing') ||
          nombre.includes('aspirad') ||
          nombre.includes('pulid') ||
          (t.idtipo_ingresos !== 17 && t.idtipo_ingresos !== 18 && t.idtipo_ingresos !== 19))
      );
    });

    if (lavados.length > 0) return lavados;

    // Fallback defensivo en caso de catálogo vacío
    return [
      { idtipo_ingresos: 1, nombre_servicio: 'Lavado Simple Exterior', precio: config.moneda_codigo === 'EUR' ? 7.0 : 7000 },
      { idtipo_ingresos: 2, nombre_servicio: 'Lavado Completo (Int/Ext)', precio: config.moneda_codigo === 'EUR' ? 12.0 : 12000 },
      { idtipo_ingresos: 3, nombre_servicio: 'Lavado Full SUV / Camioneta', precio: config.moneda_codigo === 'EUR' ? 16.0 : 16000 }
    ];
  }, [tiposIngreso, config.moneda_codigo]);

  // Preseleccionar el primer servicio de lavado disponible
  useEffect(() => {
    if (catalogoLavados.length > 0 && !idServicio) {
      setIdServicio(String(catalogoLavados[0].idtipo_ingresos));
    }
  }, [catalogoLavados, idServicio]);

  // Búsqueda automática de estadía activa e historial de visitas al ingresar patente
  useEffect(() => {
    const clean = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

    if (clean.length < 3) {
      setVehiculoEstacionado(null);
      setHistorialVisitas([]);
      setIsHistoryExpanded(false);
      return;
    }

    // 1. Detección Inteligente de Estadía Actual (Vehículo en recinto con salida = 0)
    const activo = vehiculos.find(
      (v) => v.patente.toUpperCase() === clean && (v.salida === 0 || v.salida === false)
    );
    setVehiculoEstacionado(activo || null);
    if (activo) {
      setVincularEstadia(true); // Default recomendado si ya está estacionado
    }

    // 2. Consulta automática de historial anterior de visitas (Debounced)
    const timer = setTimeout(async () => {
      try {
        setIsSearchingHistory(true);
        const historial = await getHistorialVehiculo(clean);
        // Filtrar sólo visitas completadas o anteriores para el panel histórico
        const visitasPasadas = (historial || []).filter(
          (h) => h.idIngreso !== activo?.id
        );
        setHistorialVisitas(visitasPasadas);
      } catch (err) {
        console.error('Error buscando historial:', err);
      } finally {
        setIsSearchingHistory(false);
      }
    }, 280);

    return () => clearTimeout(timer);
  }, [patente, vehiculos, getHistorialVehiculo]);

  // Manejador de cambio de patente con normalización
  const handlePatenteChange = (e) => {
    const val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 8);
    setPatente(val);
  };

  // Toggle de condición especial
  const toggleCondicion = (condicion) => {
    setCondicionesSeleccionadas((prev) => {
      if (prev.includes(condicion)) {
        return prev.filter((c) => c !== condicion);
      }
      return [...prev, condicion];
    });
  };

  // Atajos rápidos de recargo extra
  const atajosRecargo = config.moneda_codigo === 'EUR' ? [2, 5, 10] : [2000, 5000, 10000];

  const handleAplicarAtajo = (monto) => {
    const actual = parseFloat(recargoExtra) || 0;
    setRecargoExtra(String(actual + monto));
  };

  // Servicio seleccionado actualmente y cálculo de total estimado
  const servicioActivo = catalogoLavados.find((s) => String(s.idtipo_ingresos) === idServicio);
  const precioBaseNum = Number(servicioActivo?.precio || 0);
  const recargoExtraNum = Math.max(0, parseFloat(recargoExtra) || 0);
  const totalEstimado = precioBaseNum + recargoExtraNum;

  // Enviar formulario
  const handleSubmit = async (e) => {
    e.preventDefault();
    const cleanPatente = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

    if (cleanPatente.length < 4) {
      alert('Por favor ingrese una matrícula o patente válida (mínimo 4 caracteres).');
      return;
    }

    if (!servicioActivo) {
      alert('Por favor seleccione un servicio de lavado del catálogo.');
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = {
        patente: cleanPatente,
        id_servicio: servicioActivo.idtipo_ingresos,
        nombre_servicio: servicioActivo.nombre_servicio,
        precio_base: precioBaseNum,
        motivos_extra: condicionesSeleccionadas,
        precio_extra: recargoExtraNum,
        observaciones: observaciones.trim(),
        vincularEstadia: Boolean(vehiculoEstacionado && vincularEstadia)
      };

      await registrarLavado(payload);

      // Limpiar formulario y reenfocar
      setPatente('');
      setCondicionesSeleccionadas([]);
      setRecargoExtra('');
      setObservaciones('');
      setVehiculoEstacionado(null);
      setHistorialVisitas([]);
      setIsHistoryExpanded(false);

      if (onLavadoRegistrado) {
        onLavadoRegistrado();
      }

      if (inputPatenteRef.current) {
        inputPatenteRef.current.focus();
      }
    } catch (err) {
      // Toast del contexto notifica error
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="bg-white border border-slate-200/90 rounded-2xl shadow-card p-5 sm:p-6 space-y-6">
      {/* Título de Sección */}
      <div className="flex items-center justify-between pb-3 border-b border-slate-100">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200/90 text-slate-700 flex items-center justify-center">
            <Sparkles className="w-4 h-4" />
          </div>
          <div>
            <h2 className="text-sm font-bold text-slate-900 tracking-tight">
              Admisión de Lavado
            </h2>
            <p className="text-[11px] text-slate-400">
              Recepción, verificación de estadía e inspección previa
            </p>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-5">
        {/* ================================================================= */}
        {/* 1. MATRÍCULA CON BÚSQUEDA AUTOMÁTICA & DETECCIÓN INTELIGENTE      */}
        {/* ================================================================= */}
        <div className="space-y-2">
          <label className="block text-xs font-semibold text-slate-700 tracking-tight">
            Matrícula / Patente del Vehículo
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <Car className="w-4 h-4" />
            </div>
            <input
              ref={inputPatenteRef}
              type="text"
              value={patente}
              onChange={handlePatenteChange}
              placeholder="Ej: AA1001 o LJRP90"
              maxLength={8}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100 font-mono text-base font-bold tracking-widest uppercase text-slate-900 placeholder:text-slate-300 placeholder:font-normal placeholder:tracking-normal transition-all"
              required
            />
          </div>

          {/* Detección Inteligente de Estadía Actual */}
          {vehiculoEstacionado && (
            <div className="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5 animate-modal-in">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-1.5 text-xs font-medium text-slate-800">
                  <Link2 className="w-3.5 h-3.5 text-slate-600" />
                  <span>Vehículo detectado actualmente en estacionamiento</span>
                </div>
                <span className="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-200/80 text-slate-700">
                  Folio #{vehiculoEstacionado.id}
                </span>
              </div>

              <p className="text-[11px] text-slate-500 leading-relaxed">
                El vehículo ingresó a las{' '}
                <strong className="text-slate-700 font-mono">
                  {vehiculoEstacionado.horaLocal || vehiculoEstacionado.fechaLocal || 'reciente'}
                </strong>
                . Seleccione cómo desea procesar el cobro:
              </p>

              {/* Selector de Modo: Vincular vs Independiente */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                <button
                  type="button"
                  onClick={() => setVincularEstadia(true)}
                  className={`flex items-start gap-2 p-2.5 rounded-lg border text-left transition-all cursor-pointer ${
                    vincularEstadia
                      ? 'bg-slate-900 text-white border-slate-900 shadow-xs'
                      : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <div className={`mt-0.5 w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0 ${
                    vincularEstadia ? 'border-white bg-white text-slate-900' : 'border-slate-300'
                  }`}>
                    {vincularEstadia && <div className="w-1.5 h-1.5 rounded-full bg-slate-900" />}
                  </div>
                  <div>
                    <span className="text-xs font-semibold block leading-tight">
                      Vincular a estadía actual
                    </span>
                    <span className={`text-[10px] block mt-0.5 ${vincularEstadia ? 'text-slate-300' : 'text-slate-400'}`}>
                      Se cobrará estadía + lavado al momento de la salida.
                    </span>
                  </div>
                </button>

                <button
                  type="button"
                  onClick={() => setVincularEstadia(false)}
                  className={`flex items-start gap-2 p-2.5 rounded-lg border text-left transition-all cursor-pointer ${
                    !vincularEstadia
                      ? 'bg-slate-900 text-white border-slate-900 shadow-xs'
                      : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <div className={`mt-0.5 w-3.5 h-3.5 rounded-full border flex items-center justify-center shrink-0 ${
                    !vincularEstadia ? 'border-white bg-white text-slate-900' : 'border-slate-300'
                  }`}>
                    {!vincularEstadia && <div className="w-1.5 h-1.5 rounded-full bg-slate-900" />}
                  </div>
                  <div>
                    <span className="text-xs font-semibold block leading-tight">
                      Servicio independiente
                    </span>
                    <span className={`text-[10px] block mt-0.5 ${!vincularEstadia ? 'text-slate-300' : 'text-slate-400'}`}>
                      Cobro únicamente por el servicio de lavado.
                    </span>
                  </div>
                </button>
              </div>
            </div>
          )}

          {/* Historial del Vehículo (Panel Colapsable Sobrio) */}
          {historialVisitas && historialVisitas.length > 0 && (
            <div className="mt-2 border border-slate-200 rounded-xl overflow-hidden animate-modal-in">
              <button
                type="button"
                onClick={() => setIsHistoryExpanded(!isHistoryExpanded)}
                className="w-full flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100/80 text-left transition-colors cursor-pointer"
              >
                <div className="flex items-center gap-2">
                  <History className="w-3.5 h-3.5 text-slate-500" />
                  <span className="text-xs font-semibold text-slate-800">
                    Historial del Vehículo
                  </span>
                  <span className="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-medium">
                    {historialVisitas.length} {historialVisitas.length === 1 ? 'visita previa' : 'visitas previas'}
                  </span>
                </div>
                {isHistoryExpanded ? (
                  <ChevronUp className="w-4 h-4 text-slate-400" />
                ) : (
                  <ChevronDown className="w-4 h-4 text-slate-400" />
                )}
              </button>

              {isHistoryExpanded && (
                <div className="p-3 bg-white space-y-2 max-h-56 overflow-y-auto divide-y divide-slate-100">
                  {historialVisitas.map((visita, idx) => (
                    <div key={idx} className="pt-2 first:pt-0 space-y-1 text-xs">
                      <div className="flex items-center justify-between text-slate-800">
                        <span className="font-medium text-[11px] text-slate-500">
                          {visita.fechaIngresoLocal || visita.fechaSalidaLocal || 'Visita'}
                        </span>
                        <span className="font-mono font-bold text-slate-900">
                          {visita.totalFormateado}
                        </span>
                      </div>
                      <div className="flex items-center justify-between text-[11px]">
                        <span className="font-semibold text-slate-700">
                          {visita.servicio}
                        </span>
                        <span className="text-slate-400 text-[10px]">
                          {visita.metodoPago} • {visita.tiempoEstadia}
                        </span>
                      </div>
                      {visita.observaciones && (
                        <p className="text-[10px] text-slate-500 italic bg-slate-50 p-1.5 rounded border border-slate-100">
                          Obs: {visita.observaciones}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        {/* ================================================================= */}
        {/* 2. SELECTOR DE SERVICIO DE LAVADO (CATÁLOGO ACTIVO)              */}
        {/* ================================================================= */}
        <div className="space-y-2">
          <label className="block text-xs font-semibold text-slate-700 tracking-tight">
            Catálogo de Servicios de Lavado
          </label>
          <div className="grid grid-cols-1 gap-2">
            {catalogoLavados.map((item) => {
              const isSelected = String(item.idtipo_ingresos) === idServicio;
              return (
                <div
                  key={item.idtipo_ingresos}
                  onClick={() => setIdServicio(String(item.idtipo_ingresos))}
                  className={`flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer ${
                    isSelected
                      ? 'border-slate-900 bg-slate-900 text-white shadow-xs'
                      : 'border-slate-200 bg-white hover:border-slate-300 text-slate-800'
                  }`}
                >
                  <div className="flex items-center gap-2.5">
                    <div
                      className={`w-4 h-4 rounded-full border flex items-center justify-center shrink-0 ${
                        isSelected ? 'border-white bg-white text-slate-900' : 'border-slate-300'
                      }`}
                    >
                      {isSelected && <div className="w-1.5 h-1.5 rounded-full bg-slate-900" />}
                    </div>
                    <div>
                      <span className="text-xs font-semibold block leading-tight">
                        {item.nombre_servicio}
                      </span>
                    </div>
                  </div>
                  <span className="font-mono text-xs font-bold">
                    {formatMoney(item.precio)}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        {/* ================================================================= */}
        {/* 3. SELECTOR DE RECARGOS Y CONDICIONES ESPECIALES (CHIPS SLATE)   */}
        {/* ================================================================= */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <label className="block text-xs font-semibold text-slate-700 tracking-tight">
              Recargos y Condiciones Especiales
            </label>
            <span className="text-[10px] text-slate-400">
              Selección múltiple (sin emojis)
            </span>
          </div>

          <div className="flex flex-wrap gap-1.5">
            {CONDICIONES_ESPECIALES.map((cond) => {
              const active = condicionesSeleccionadas.includes(cond);
              return (
                <button
                  key={cond}
                  type="button"
                  onClick={() => toggleCondicion(cond)}
                  className={`px-2.5 py-1.5 rounded-lg text-xs font-medium border transition-all cursor-pointer select-none ${
                    active
                      ? 'bg-slate-900 text-white border-slate-900 shadow-2xs'
                      : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                  }`}
                >
                  {cond}
                </button>
              );
            })}
          </div>
        </div>

        {/* ================================================================= */}
        {/* 4. CAMPO NUMÉRICO "RECARGO EXTRA" & TOTAL ESTIMADO               */}
        {/* ================================================================= */}
        <div className="space-y-2">
          <label className="block text-xs font-semibold text-slate-700 tracking-tight">
            Monto de Recargo Adicional ({config.moneda_codigo})
          </label>
          <div className="flex items-center gap-2">
            <div className="relative flex-1">
              <span className="absolute inset-y-0 left-0 pl-3 flex items-center font-mono font-medium text-slate-400 text-xs">
                {config.moneda_simbolo}
              </span>
              <input
                type="number"
                min="0"
                step="any"
                value={recargoExtra}
                onChange={(e) => setRecargoExtra(e.target.value)}
                placeholder="0"
                className="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100 font-mono text-sm font-semibold text-slate-900 transition-all placeholder:text-slate-300"
              />
            </div>

            {/* Atajos de recargo rápido */}
            <div className="flex items-center gap-1">
              {atajosRecargo.map((monto) => (
                <button
                  key={monto}
                  type="button"
                  onClick={() => handleAplicarAtajo(monto)}
                  className="px-2 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 border border-slate-200 text-[11px] font-mono font-medium text-slate-700 transition-colors"
                  title={`Sumar ${formatMoney(monto)}`}
                >
                  +{config.moneda_codigo === 'EUR' ? `${monto}€` : `$${monto / 1000}k`}
                </button>
              ))}
              {recargoExtra && (
                <button
                  type="button"
                  onClick={() => setRecargoExtra('')}
                  className="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                  title="Limpiar recargo"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>

          {/* Desglose de Cálculo en Tiempo Real */}
          <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <span className="text-slate-500 text-[11px]">
              Base ({formatMoney(precioBaseNum)}) + Extra ({formatMoney(recargoExtraNum)})
            </span>
            <div className="text-right">
              <span className="text-[10px] text-slate-400 block leading-none">Total servicio</span>
              <span className="font-mono font-bold text-slate-900 text-sm">
                {formatMoney(totalEstimado)}
              </span>
            </div>
          </div>
        </div>

        {/* ================================================================= */}
        {/* 5. OBSERVACIONES DE RECEPCIÓN (INSPECCIÓN PREVIA)                */}
        {/* ================================================================= */}
        <div className="space-y-1.5">
          <label className="block text-xs font-semibold text-slate-700 tracking-tight">
            Observaciones de Recepción
          </label>
          <textarea
            value={observaciones}
            onChange={(e) => setObservaciones(e.target.value)}
            rows={2}
            placeholder="Registrar estado físico previo: rayones preexistentes, abolladuras o pertenencias..."
            className="w-full px-3 py-2 rounded-xl border border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100 text-xs text-slate-800 placeholder:text-slate-400 transition-all resize-none"
          />
        </div>

        {/* ================================================================= */}
        {/* 6. BOTÓN DE ACCIÓN: REGISTRAR INGRESO A LAVADO                    */}
        {/* ================================================================= */}
        <div className="pt-2">
          <button
            type="submit"
            disabled={isSubmitting || !patente.trim()}
            className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 active:scale-[0.99] transition-all cursor-pointer shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Sparkles className="w-4 h-4 text-amber-300" />
            <span>
              {isSubmitting ? 'Registrando...' : 'Registrar Ingreso a Lavado'}
            </span>
          </button>
        </div>
      </form>
    </div>
  );
};
