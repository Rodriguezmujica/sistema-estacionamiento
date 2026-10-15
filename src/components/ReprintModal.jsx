import React, { useState, useEffect } from 'react';
import { useApp } from '../context/AppContext';
import { Printer, LogIn, Receipt, ArrowRight, X, AlertTriangle, Loader2 } from 'lucide-react';

export const ReprintModal = () => {
  const {
    isReprintModalOpen,
    setIsReprintModalOpen,
    reimprimirUltimoIngreso,
    reimprimirUltimaSalida
  } = useApp();

  const [loadingIngreso, setLoadingIngreso] = useState(false);
  const [loadingSalida, setLoadingSalida] = useState(false);
  const [error, setError] = useState('');

  // Reset al abrir
  useEffect(() => {
    if (isReprintModalOpen) {
      setError('');
      setLoadingIngreso(false);
      setLoadingSalida(false);
    }
  }, [isReprintModalOpen]);

  // Manejo de tecla Escape
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isReprintModalOpen) {
        setIsReprintModalOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isReprintModalOpen, setIsReprintModalOpen]);

  const handleReprintIngreso = async () => {
    try {
      setError('');
      setLoadingIngreso(true);
      await reimprimirUltimoIngreso();
    } catch (err) {
      setError(err.message || 'No se pudo recuperar el último ticket de ingreso.');
    } finally {
      setLoadingIngreso(false);
    }
  };

  const handleReprintSalida = async () => {
    try {
      setError('');
      setLoadingSalida(true);
      await reimprimirUltimaSalida();
    } catch (err) {
      setError(err.message || 'No se pudo recuperar el último comprobante de salida.');
    } finally {
      setLoadingSalida(false);
    }
  };

  if (!isReprintModalOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in no-print-overlay">
      <div className="bg-white rounded-2xl shadow-modal border border-slate-200 overflow-hidden max-w-md w-full flex flex-col">
        
        {/* Cabecera del Modal */}
        <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shadow-xs">
              <Printer className="w-4 h-4 text-emerald-400" />
            </div>
            <div>
              <h3 className="text-sm font-semibold text-slate-900">
                Reimprimir Ticket Térmico
              </h3>
              <p className="text-[11px] text-slate-500">
                Seleccione el tipo de comprobante que desea reemitir
              </p>
            </div>
          </div>
          <button
            onClick={() => setIsReprintModalOpen(false)}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 transition-colors cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Contenido con las 2 Opciones Claras */}
        <div className="p-6 space-y-4">
          
          {error && (
            <div className="p-3 rounded-lg bg-rose-50 border border-rose-200 flex items-start gap-2.5 text-xs text-rose-800 animate-modal-in">
              <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
              <span>{error}</span>
            </div>
          )}

          {/* Opción 1: Reimprimir Último Ingreso */}
          <button
            type="button"
            disabled={loadingIngreso || loadingSalida}
            onClick={handleReprintIngreso}
            className="w-full p-4 rounded-xl border border-slate-200 hover:border-slate-400 bg-white hover:bg-slate-50/80 transition-all text-left flex items-start justify-between gap-3 group shadow-2xs hover:shadow-card cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div className="flex items-start gap-3.5">
              <div className="w-10 h-10 rounded-xl bg-sky-50 border border-sky-200 text-sky-700 flex items-center justify-center shrink-0 group-hover:bg-slate-900 group-hover:text-white transition-colors">
                {loadingIngreso ? (
                  <Loader2 className="w-5 h-5 animate-spin text-sky-600 group-hover:text-white" />
                ) : (
                  <LogIn className="w-5 h-5" />
                )}
              </div>
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <h4 className="text-xs font-bold text-slate-900 group-hover:text-slate-950">
                    Reimprimir Último Ingreso
                  </h4>
                  <span className="text-[10px] font-medium px-1.5 py-0.2 rounded bg-sky-100 text-sky-800">
                    Entrada
                  </span>
                </div>
                <p className="text-[11px] text-slate-500 leading-snug">
                  Busca y despliega el ticket del último vehículo ingresado al estacionamiento con su código de barras.
                </p>
              </div>
            </div>

            <div className="pt-2 text-slate-400 group-hover:text-slate-900 transition-colors">
              <ArrowRight className="w-4 h-4" />
            </div>
          </button>

          {/* Opción 2: Reimprimir Última Salida */}
          <button
            type="button"
            disabled={loadingIngreso || loadingSalida}
            onClick={handleReprintSalida}
            className="w-full p-4 rounded-xl border border-slate-200 hover:border-slate-400 bg-white hover:bg-slate-50/80 transition-all text-left flex items-start justify-between gap-3 group shadow-2xs hover:shadow-card cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div className="flex items-start gap-3.5">
              <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-slate-900 group-hover:text-white transition-colors">
                {loadingSalida ? (
                  <Loader2 className="w-5 h-5 animate-spin text-emerald-600 group-hover:text-white" />
                ) : (
                  <Receipt className="w-5 h-5" />
                )}
              </div>
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <h4 className="text-xs font-bold text-slate-900 group-hover:text-slate-950">
                    Reimprimir Última Salida
                  </h4>
                  <span className="text-[10px] font-medium px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">
                    Pago / Salida
                  </span>
                </div>
                <p className="text-[11px] text-slate-500 leading-snug">
                  Busca y despliega el comprobante térmico con el total pagado, vuelto y desglose del último cobro realizado.
                </p>
              </div>
            </div>

            <div className="pt-2 text-slate-400 group-hover:text-slate-900 transition-colors">
              <ArrowRight className="w-4 h-4" />
            </div>
          </button>

        </div>

        {/* Footer del Modal */}
        <div className="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-[11px] text-slate-400">
          <span>Formato estándar térmico: 80mm</span>
          <button
            onClick={() => setIsReprintModalOpen(false)}
            className="hover:text-slate-600 transition-colors cursor-pointer"
          >
            Cancelar [ESC]
          </button>
        </div>

      </div>
    </div>
  );
};
