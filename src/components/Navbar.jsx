import React from 'react';
import { useApp } from '../context/AppContext';
import { Clock, RefreshCw, Database, DollarSign, ReceiptText, ShieldCheck, Edit3, Printer } from 'lucide-react';

export const Navbar = () => {
  const {
    config,
    perfilActivo,
    cambiarPerfil,
    cargarDemo,
    refrescarDatos,
    currentTime,
    loading,
    setIsCashClosingOpen,
    setIsModifyTicketOpen,
    setIsReprintModalOpen
  } = useApp();

  // Formatear hora y fecha en vivo
  const horaFormateada = currentTime.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });

  const fechaFormateada = currentTime.toLocaleDateString('es-ES', {
    weekday: 'short',
    day: 'numeric',
    month: 'short'
  });

  return (
    <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-subtle">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        {/* Marca & Título Sobrio */}
        <div className="flex items-center gap-3.5">
          <div className="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-base tracking-wider shadow-sm">
            P
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-semibold text-slate-900 text-sm tracking-tight">PARKFLOW</span>
              <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200/60">
                SaaS Desktop
              </span>
            </div>
            <p className="text-[11px] text-slate-500 font-normal">
              Gestión Integral de Estacionamiento & Lavados
            </p>
          </div>
        </div>

        {/* Zona Central: Reloj en vivo & Tarifa activa */}
        <div className="hidden md:flex items-center gap-4 text-xs text-slate-600">
          {/* Reloj del Sistema */}
          <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200/80">
            <Clock className="w-3.5 h-3.5 text-slate-400" />
            <span className="capitalize text-slate-500">{fechaFormateada}</span>
            <span className="font-mono-numbers font-medium text-slate-800 ml-1">
              {horaFormateada}
            </span>
          </div>

          {/* Badge de Tarifa Base por Minuto */}
          <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-50 border border-slate-200/80">
            <DollarSign className="w-3.5 h-3.5 text-slate-400" />
            <span className="text-slate-500">Tarifa:</span>
            <span className="font-mono-numbers font-semibold text-slate-900">
              {config.moneda_simbolo}{config.precio_minuto}
              <span className="text-[10px] font-normal text-slate-500">/min</span>
            </span>
          </div>
        </div>

        {/* Controles del Lado Derecho */}
        <div className="flex items-center gap-2.5">
          
          {/* Switch tipo "Pill" Multi-Moneda */}
          <div className="bg-slate-100 p-0.5 rounded-lg border border-slate-200 flex items-center text-xs">
            <button
              onClick={() => cambiarPerfil('chile')}
              disabled={loading}
              title="Cambiar a moneda Chile (Pesos CLP con redondeo en efectivo)"
              className={`px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5 ${
                perfilActivo === 'chile'
                  ? 'bg-white text-slate-900 shadow-sm font-semibold'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <span>🇨🇱</span>
              <span className="hidden sm:inline">Chile</span>
              <span className="font-mono-numbers text-[10px] text-slate-400">CLP</span>
            </button>

            <button
              onClick={() => cambiarPerfil('espana')}
              disabled={loading}
              title="Cambiar a moneda España (Euros EUR con céntimos exactos)"
              className={`px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5 ${
                perfilActivo === 'espana'
                  ? 'bg-white text-slate-900 shadow-sm font-semibold'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <span>🇪🇸</span>
              <span className="hidden sm:inline">España</span>
              <span className="font-mono-numbers text-[10px] text-slate-400">EUR</span>
            </button>
          </div>

          {/* Herramienta Rápida 1: Modificar Ticket */}
          <button
            onClick={() => setIsModifyTicketOpen(true)}
            disabled={loading}
            className="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-subtle active:scale-98 cursor-pointer"
            title="Modificar patente o cambiar tipo de servicio de un vehículo en recinto"
          >
            <Edit3 className="w-3.5 h-3.5 text-amber-600" />
            <span className="hidden md:inline">Modificar Ticket</span>
          </button>

          {/* Herramienta Rápida 2: Reimprimir Ticket */}
          <button
            onClick={() => setIsReprintModalOpen(true)}
            disabled={loading}
            className="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-subtle active:scale-98 cursor-pointer"
            title="Reimprimir último ingreso o última salida cobrada"
          >
            <Printer className="w-3.5 h-3.5 text-slate-700" />
            <span className="hidden sm:inline">Reimprimir</span>
          </button>

          {/* Botón Cierre de Caja Z */}
          <button
            onClick={() => setIsCashClosingOpen(true)}
            className="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-subtle active:scale-98 cursor-pointer"
            title="Cuadratura y Arqueo Z del día actual"
          >
            <ReceiptText className="w-3.5 h-3.5 text-slate-500" />
            <span className="hidden lg:inline">Cierre Z</span>
          </button>

          {/* Botón Demo Minimalista */}
          <button
            onClick={cargarDemo}
            disabled={loading}
            className="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-subtle active:scale-98"
            title="Sembrar datos de demostración para portafolio"
          >
            <Database className="w-3.5 h-3.5 text-slate-500" />
            <span className="hidden sm:inline">Cargar Demo</span>
          </button>

          {/* Botón Refrescar */}
          <button
            onClick={refrescarDatos}
            disabled={loading}
            className="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200"
            title="Sincronizar datos"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin text-slate-900' : ''}`} />
          </button>
        </div>

      </div>
    </header>
  );
};
