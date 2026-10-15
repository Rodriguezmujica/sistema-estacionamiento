import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { ElapsedTimer } from './ElapsedTimer';
import {
  Sparkles,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  FileText,
  Link2,
  DollarSign,
  ChevronDown,
  ChevronUp,
  RotateCcw,
  Clock,
  Car
} from 'lucide-react';

/**
 * Tarjeta de Bahía de Lavado (Cola de Trabajo).
 * Diseño sobrio, moderno y profesional SaaS (Slate/Zinc).
 */
export const WashCard = ({ lavado, onCambiarEstado, onCobrar }) => {
  const { config, formatMoney } = useApp();
  const [showObs, setShowObs] = useState(false);
  const [isUpdating, setIsUpdating] = useState(false);

  const {
    id_lavado,
    id_ingreso,
    patente,
    estado = 'espera',
    nombre_servicio = 'Lavado Estándar',
    precio_base_servicio = 0,
    precio_base_formateado,
    precio_extra = 0,
    precio_extra_formateado,
    totalEstimado = 0,
    totalEstimadoFormateado,
    motivos_extra_list = [],
    observaciones = '',
    vinculado_estadia = 0,
    timestamp_ingreso,
    timestamp_proceso,
    timestamp_listo,
    horaIngresoLocal
  } = lavado;

  const handleAction = async (nuevoEstado) => {
    try {
      setIsUpdating(true);
      await onCambiarEstado(id_lavado, nuevoEstado);
    } catch (err) {
      console.error('Error al actualizar estado:', err);
    } finally {
      setIsUpdating(false);
    }
  };

  const handleCheckoutClick = () => {
    onCobrar(lavado);
  };

  const precioBaseVisual = precio_base_formateado || formatMoney(precio_base_servicio);
  const totalVisual = totalEstimadoFormateado || formatMoney(totalEstimado || (precio_base_servicio + precio_extra));

  return (
    <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card hover:border-slate-300 hover:shadow-card-hover transition-all flex flex-col justify-between gap-3 text-left">
      {/* Cabecera de la Tarjeta: Matrícula y Tipo de Estadía */}
      <div className="flex items-start justify-between gap-2">
        <div>
          {/* Matrícula con estética de placa vehicular sobria */}
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100/90 border border-slate-300/80 shadow-2xs">
            <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
            <span className="font-mono text-sm sm:text-base font-bold tracking-widest text-slate-900 select-all">
              {patente}
            </span>
          </div>

          {/* Subtítulo de horario de admisión */}
          {horaIngresoLocal && (
            <p className="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
              <Clock className="w-3 h-3 text-slate-300" />
              <span>Ingreso: {horaIngresoLocal}</span>
            </p>
          )}
        </div>

        {/* Badge de Detección de Estadía */}
        <div className="flex flex-col items-end gap-1">
          {vinculado_estadia === 1 ? (
            <span
              className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs"
              title="Se cobrará estadía por minuto más este lavado al salir"
            >
              <Link2 className="w-2.5 h-2.5 text-slate-500" />
              <span>Estadía + Lavado</span>
            </span>
          ) : (
            <span
              className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-600 border border-slate-200"
              title="Cobro independiente de lavado únicamente"
            >
              <span>Solo Lavado</span>
            </span>
          )}
        </div>
      </div>

      {/* Servicio Seleccionado y Precio Base */}
      <div className="space-y-1.5 pt-1 border-t border-slate-100">
        <div className="flex items-center justify-between text-xs">
          <span className="font-semibold text-slate-800 tracking-tight flex items-center gap-1.5">
            <Sparkles className="w-3.5 h-3.5 text-slate-400" />
            <span className="truncate max-w-[170px]" title={nombre_servicio}>
              {nombre_servicio}
            </span>
          </span>
          <span className="font-mono font-medium text-slate-600 text-[11px]">
            {precioBaseVisual}
          </span>
        </div>

        {/* Tags / Chips de Recargos y Condiciones Especiales */}
        {motivos_extra_list && motivos_extra_list.length > 0 && (
          <div className="flex flex-wrap gap-1 pt-1">
            {motivos_extra_list.map((tag, idx) => (
              <span
                key={idx}
                className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/90"
              >
                {tag}
              </span>
            ))}
            {precio_extra > 0 && (
              <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-50 text-slate-700 border border-slate-200">
                +{precio_extra_formateado || formatMoney(precio_extra)}
              </span>
            )}
          </div>
        )}

        {/* Desplegable o Preview de Observaciones de Recepción */}
        {observaciones && observaciones.trim().length > 0 && (
          <div className="pt-1">
            <button
              type="button"
              onClick={() => setShowObs(!showObs)}
              className="w-full flex items-center justify-between text-[11px] text-slate-500 hover:text-slate-700 py-1 px-1.5 rounded hover:bg-slate-50 transition-colors"
            >
              <span className="flex items-center gap-1.5 truncate">
                <FileText className="w-3 h-3 text-slate-400 shrink-0" />
                <span className="truncate">Observaciones de recepción</span>
              </span>
              {showObs ? (
                <ChevronUp className="w-3 h-3 text-slate-400 shrink-0" />
              ) : (
                <ChevronDown className="w-3 h-3 text-slate-400 shrink-0" />
              )}
            </button>

            {showObs && (
              <div className="mt-1 p-2 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-600 leading-relaxed animate-modal-in">
                {observaciones}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Temporizador en Vivo y Resumen de Cobro Estimado */}
      <div className="flex items-center justify-between pt-2 border-t border-slate-100">
        <ElapsedTimer
          timestampIngreso={timestamp_ingreso}
          timestampProceso={timestamp_proceso}
          timestampListo={timestamp_listo}
          estado={estado}
        />

        <div className="text-right">
          <span className="text-[10px] text-slate-400 block leading-tight">Total servicio</span>
          <span className="text-xs font-mono font-bold text-slate-900 tracking-tight">
            {totalVisual}
          </span>
        </div>
      </div>

      {/* Controles de Acción y Cambio de Fase */}
      <div className="pt-2 border-t border-slate-100/80 flex items-center gap-2">
        {estado === 'espera' && (
          <button
            type="button"
            disabled={isUpdating}
            onClick={() => handleAction('proceso')}
            className="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-medium text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300/80 transition-all cursor-pointer shadow-2xs hover:shadow-xs disabled:opacity-50"
          >
            <span>Iniciar Lavado</span>
            <ArrowRight className="w-3.5 h-3.5 text-slate-600" />
          </button>
        )}

        {estado === 'proceso' && (
          <div className="flex-1 flex items-center gap-1.5">
            <button
              type="button"
              disabled={isUpdating}
              onClick={() => handleAction('espera')}
              className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
              title="Revertir a cola en espera"
            >
              <RotateCcw className="w-3.5 h-3.5" />
            </button>
            <button
              type="button"
              disabled={isUpdating}
              onClick={() => handleAction('listo')}
              className="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-medium text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-all cursor-pointer shadow-2xs hover:shadow-xs disabled:opacity-50"
            >
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
              <span>Marcar como Listo</span>
            </button>
          </div>
        )}

        {estado === 'listo' && (
          <div className="flex-1 flex items-center gap-1.5">
            <button
              type="button"
              disabled={isUpdating}
              onClick={() => handleAction('proceso')}
              className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
              title="Regresar a bahía en proceso"
            >
              <RotateCcw className="w-3.5 h-3.5" />
            </button>
            <button
              type="button"
              onClick={handleCheckoutClick}
              className="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-all cursor-pointer shadow-xs active:scale-[0.98]"
            >
              <DollarSign className="w-3.5 h-3.5 text-emerald-400" />
              <span>Cobrar y Finalizar</span>
            </button>
          </div>
        )}

        {/* Acceso rápido a cobro directo en cualquier estado (ej: salida anticipada) */}
        {estado !== 'listo' && (
          <button
            type="button"
            onClick={handleCheckoutClick}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 border border-slate-200 transition-colors"
            title="Cobrar servicio anticipadamente"
          >
            <DollarSign className="w-3.5 h-3.5" />
          </button>
        )}
      </div>
    </div>
  );
};
