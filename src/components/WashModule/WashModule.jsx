import React from 'react';
import { useApp } from '../../context/AppContext';
import { WashAdmissionForm } from './WashAdmissionForm';
import { WashControlBoard } from './WashControlBoard';
import {
  Sparkles,
  Clock,
  Play,
  CheckCircle2,
  Layers,
  RefreshCw,
  Car
} from 'lucide-react';

export const WashModule = () => {
  const { lavados, refrescarDatos, loading, setCheckoutVehicle } = useApp();

  // Contadores de estado para métricas rápidas
  const enEspera = lavados.filter((l) => l.estado === 'espera').length;
  const enProceso = lavados.filter((l) => l.estado === 'proceso').length;
  const finalizados = lavados.filter((l) => l.estado === 'listo').length;

  // Invocar modal de cobro unificado existente
  const handleCobrar = (lavado) => {
    // Estructura compatible con CheckoutModal (requiere id = id_ingreso y patente)
    const vehiculoParaCheckout = {
      id: lavado.id_ingreso || lavado.id,
      patente: lavado.patente,
      nombre_servicio: lavado.nombre_servicio || 'Servicio de Lavado',
      idtipo_ingreso: lavado.idtipo_ingreso_vehiculo || lavado.id_servicio_lavado,
      precio_base_servicio: lavado.precio_base_servicio,
      precio_extra: lavado.precio_extra,
      motivos_extra: lavado.motivos_extra,
      vinculado_estadia: lavado.vinculado_estadia,
      fecha_ingreso: lavado.fecha_ingreso,
      timestamp: lavado.timestamp_vehiculo || lavado.timestamp_ingreso
    };

    setCheckoutVehicle(vehiculoParaCheckout);
  };

  return (
    <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      {/* =================================================================== */}
      {/* 1. HEADER DEL MÓDULO & CONTADORES KPI (ESTILO SAAS SLATE/ZINC)      */}
      {/* =================================================================== */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-2 rounded-xl bg-slate-900 text-white shadow-xs">
              <Sparkles className="w-5 h-5 text-amber-300" />
            </span>
            <h1 className="text-xl font-bold text-slate-900 tracking-tight">
              Módulo de Lavados y Estética Automotriz
            </h1>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Gestión integral de admisión, inspección previa, control de bahías y facturación unificada.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={refrescarDatos}
            disabled={loading}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs hover:border-slate-300 transition-all cursor-pointer disabled:opacity-50"
            title="Refrescar estado de bahías"
          >
            <RefreshCw className={`w-3.5 h-3.5 text-slate-500 ${loading ? 'animate-spin' : ''}`} />
            <span>Actualizar</span>
          </button>
        </div>
      </div>

      {/* Tarjetas KPI de Estado Rápido */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* KPI 1: Total Bahías Ocupadas */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card">
          <div className="flex items-center justify-between text-slate-500 mb-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Bahías Ocupadas
            </span>
            <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Layers className="w-3.5 h-3.5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {lavados.length}
            </span>
            <span className="text-[11px] text-slate-400">vehículos</span>
          </div>
        </div>

        {/* KPI 2: En Espera */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card">
          <div className="flex items-center justify-between text-slate-500 mb-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              En Espera
            </span>
            <div className="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center border border-amber-200/60">
              <Clock className="w-3.5 h-3.5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {enEspera}
            </span>
            <span className="text-[11px] text-amber-600 font-medium">por iniciar</span>
          </div>
        </div>

        {/* KPI 3: En Proceso */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card">
          <div className="flex items-center justify-between text-slate-500 mb-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              En Proceso
            </span>
            <div className="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center border border-blue-200/60">
              <Play className="w-3.5 h-3.5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {enProceso}
            </span>
            <span className="text-[11px] text-blue-600 font-medium">en lavado</span>
          </div>
        </div>

        {/* KPI 4: Finalizados / Por Entregar */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card">
          <div className="flex items-center justify-between text-slate-500 mb-1.5">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Por Entregar
            </span>
            <div className="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/60">
              <CheckCircle2 className="w-3.5 h-3.5" />
            </div>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {finalizados}
            </span>
            <span className="text-[11px] text-emerald-600 font-medium">listos para cobro</span>
          </div>
        </div>
      </div>

      {/* =================================================================== */}
      {/* 2. DISTRIBUCIÓN MODULAR: ADMISIÓN (IZQ) & TABLERO DE BAHÍAS (DER)   */}
      {/* =================================================================== */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {/* Columna Izquierda: Panel de Entrada / Admisión */}
        <div className="lg:col-span-5 xl:col-span-4">
          <WashAdmissionForm onLavadoRegistrado={refrescarDatos} />
        </div>

        {/* Columna Derecha: Tablero de Control de Bahías (Cola de Trabajo) */}
        <div className="lg:col-span-7 xl:col-span-8">
          <WashControlBoard onCobrar={handleCobrar} />
        </div>
      </div>
    </div>
  );
};
