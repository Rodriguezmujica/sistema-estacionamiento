import React, { useState, useEffect, useMemo } from 'react';
import { useApp } from '../context/AppContext';
import { X, Banknote, CreditCard, ArrowRightLeft, CheckCircle2, AlertCircle, ShieldCheck } from 'lucide-react';

export const CheckoutModal = () => {
  const {
    checkoutVehicle,
    setCheckoutVehicle,
    config,
    calcularCobro,
    registrarSalida,
    formatMoney
  } = useApp();

  const [metodoPago, setMetodoPago] = useState('EFECTIVO'); // 'EFECTIVO' | 'TARJETA' | 'TRANSFERENCIA'
  const [montoRecibido, setMontoRecibido] = useState('');
  const [comprobante, setComprobante] = useState('');
  const [calculo, setCalculo] = useState(null);
  const [loadingCalculo, setLoadingCalculo] = useState(true);
  const [isProcessing, setIsProcessing] = useState(false);
  const [posSimuladoStatus, setPosSimuladoStatus] = useState('idle'); // 'idle' | 'swiping' | 'approved'

  // Consultar cálculo de backend al abrir el modal o cambiar el método de pago
  useEffect(() => {
    if (!checkoutVehicle) return;

    let isMounted = true;
    setLoadingCalculo(true);

    calcularCobro(checkoutVehicle.id, metodoPago)
      .then((res) => {
        if (isMounted && res) {
          setCalculo(res);
          // Si el método es efectivo y el monto recibido está vacío, pre-llenar con el monto exacto
          if (metodoPago === 'EFECTIVO' && !montoRecibido) {
            setMontoRecibido(String(res.totalFinal));
          }
        }
      })
      .catch((err) => {
        console.error('Error calculando cobro:', err);
      })
      .finally(() => {
        if (isMounted) setLoadingCalculo(false);
      });

    return () => {
      isMounted = false;
    };
  }, [checkoutVehicle, metodoPago]);

  // Cálculo en tiempo real del vuelto en efectivo
  const { vuelto, vueltoSuficiente, vueltoFormateado } = useMemo(() => {
    if (!calculo) return { vuelto: 0, vueltoSuficiente: false, vueltoFormateado: '$0' };

    const total = calculo.totalFinal;
    const recibidoNum = parseFloat(montoRecibido) || 0;

    if (recibidoNum < total) {
      return {
        vuelto: 0,
        vueltoSuficiente: false,
        vueltoFormateado: formatMoney(0)
      };
    }

    let diff = recibidoNum - total;
    if (config.activar_redondeo_chile) {
      diff = Math.round(diff);
    } else {
      diff = Math.round(diff * 100) / 100;
    }

    return {
      vuelto: diff,
      vueltoSuficiente: true,
      vueltoFormateado: formatMoney(diff)
    };
  }, [montoRecibido, calculo, config, formatMoney]);

  // Si no hay vehículo seleccionado, no renderizar
  if (!checkoutVehicle) return null;

  // Atajos rápidos de billetes comunes
  const atajosBilletes = config.moneda_codigo === 'EUR'
    ? [5, 10, 20, 50]
    : [1000, 2000, 5000, 10000, 20000];

  const handleSimularPOS = () => {
    setPosSimuladoStatus('swiping');
    setTimeout(() => {
      setPosSimuladoStatus('approved');
    }, 900);
  };

  const handleConfirmarCobro = async () => {
    if (!calculo) return;

    if (metodoPago === 'EFECTIVO' && !vueltoSuficiente) {
      alert(`El importe recibido debe ser mayor o igual al total a pagar (${calculo.totalFinalFormateado})`);
      return;
    }

    if (metodoPago === 'TARJETA' && posSimuladoStatus !== 'approved') {
      alert('Por favor complete la simulación de autorización POS con tarjeta');
      return;
    }

    try {
      setIsProcessing(true);
      await registrarSalida({
        id_ingreso: checkoutVehicle.id,
        patente: checkoutVehicle.patente,
        metodo_pago: metodoPago,
        monto_recibido: metodoPago === 'EFECTIVO' ? parseFloat(montoRecibido) : calculo.totalFinal,
        tipo_pago: metodoPago === 'TARJETA' ? 'pos_simulado' : 'manual',
        comprobante: comprobante.trim()
      });
    } catch (err) {
      // El toast ya captura el error
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in">
      <div className="bg-white w-full max-w-lg rounded-2xl shadow-modal border border-slate-200 overflow-hidden flex flex-col max-h-[92vh]">
        
        {/* Cabecera del Modal */}
        <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
          <div className="flex items-center gap-3">
            <div className="px-2.5 py-1 rounded bg-slate-900 text-white font-mono font-bold text-sm tracking-widest shadow-xs">
              {checkoutVehicle.patente}
            </div>
            <div>
              <h3 className="text-sm font-semibold text-slate-900">
                Liquidación y Salida
              </h3>
              <p className="text-[11px] text-slate-500">
                {checkoutVehicle.nombre_servicio || 'Servicio Estacionamiento'}
              </p>
            </div>
          </div>
          <button
            onClick={() => setCheckoutVehicle(null)}
            className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Cuerpo del Modal */}
        <div className="p-6 overflow-y-auto space-y-5 flex-1">
          
          {/* Tarjeta de Resumen de Cobro */}
          <div className="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5">
            <div className="flex justify-between items-center text-xs text-slate-600">
              <span>Tiempo de estadía transcurrido:</span>
              <span className="font-mono-numbers font-semibold text-slate-900">
                {calculo ? calculo.tiempoLegible : `${checkoutVehicle.minutos || 1} min`}
              </span>
            </div>

            <div className="flex justify-between items-center text-xs text-slate-600">
              <span>Tarifa aplicada:</span>
              <span className="font-mono-numbers text-slate-700">
                {calculo?.esAbonado
                  ? 'Plan Abonado Mensual'
                  : calculo?.esLavado
                  ? 'Tarifa Servicio de Lavado'
                  : `${config.moneda_simbolo}${config.precio_minuto}/min (mínimo ${config.moneda_simbolo}${config.precio_minimo})`}
              </span>
            </div>

            {calculo && calculo.desglose && calculo.desglose.precioExtra > 0 && (
              <div className="flex justify-between items-center text-xs text-amber-700 bg-amber-50 px-2 py-1 rounded border border-amber-200">
                <span>Cargos extras (Lavado):</span>
                <span className="font-mono-numbers font-semibold">
                  +{calculo.desglose.precioExtraFormateado}
                </span>
              </div>
            )}

            <div className="pt-2 border-t border-slate-200 flex justify-between items-baseline">
              <span className="text-xs font-semibold text-slate-800">Total a Pagar:</span>
              <span className="text-2xl font-bold text-slate-900 font-mono-numbers tracking-tight">
                {loadingCalculo ? '...' : (calculo ? calculo.totalFinalFormateado : '$0')}
              </span>
            </div>

            {config.activar_redondeo_chile && metodoPago === 'EFECTIVO' && (
              <p className="text-[10px] text-slate-400 italic text-right">
                * Ley de Redondeo Chile aplicada al total en efectivo
              </p>
            )}
          </div>

          {/* Selector de Pestañas de Métodos de Pago */}
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-2">
              Método de Pago
            </label>
            <div className="grid grid-cols-3 gap-2 p-1 bg-slate-100 rounded-xl border border-slate-200">
              <button
                type="button"
                onClick={() => setMetodoPago('EFECTIVO')}
                className={`py-2 px-3 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5 ${
                  metodoPago === 'EFECTIVO'
                    ? 'bg-white text-slate-900 shadow-sm font-semibold'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <Banknote className="w-3.5 h-3.5" />
                <span>Efectivo</span>
              </button>

              <button
                type="button"
                onClick={() => setMetodoPago('TARJETA')}
                className={`py-2 px-3 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5 ${
                  metodoPago === 'TARJETA'
                    ? 'bg-white text-slate-900 shadow-sm font-semibold'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <CreditCard className="w-3.5 h-3.5" />
                <span>Tarjeta POS</span>
              </button>

              <button
                type="button"
                onClick={() => setMetodoPago('TRANSFERENCIA')}
                className={`py-2 px-3 rounded-lg text-xs font-medium transition-all flex items-center justify-center gap-1.5 ${
                  metodoPago === 'TRANSFERENCIA'
                    ? 'bg-white text-slate-900 shadow-sm font-semibold'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                <ArrowRightLeft className="w-3.5 h-3.5" />
                <span>Transferencia</span>
              </button>
            </div>
          </div>

          {/* Vista 1: Pestaña EFECTIVO */}
          {metodoPago === 'EFECTIVO' && (
            <div className="space-y-3.5 p-4 bg-slate-50 rounded-xl border border-slate-200/80 animate-modal-in">
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1.5">
                  Importe Recibido del Cliente ({config.moneda_simbolo})
                </label>
                <input
                  type="number"
                  step={config.moneda_codigo === 'EUR' ? '0.01' : '1'}
                  value={montoRecibido}
                  onChange={(e) => setMontoRecibido(e.target.value)}
                  placeholder="0"
                  className="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-900 text-lg font-mono-numbers font-bold focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
              </div>

              {/* Atajos de billetes */}
              <div>
                <span className="text-[11px] text-slate-500 mb-1.5 block">
                  Billetes rápidos:
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {atajosBilletes.map((billete) => (
                    <button
                      key={billete}
                      type="button"
                      onClick={() => setMontoRecibido(String(billete))}
                      className="px-2.5 py-1 rounded bg-white border border-slate-200 hover:bg-slate-100 text-xs font-mono-numbers text-slate-700 font-medium transition-colors"
                    >
                      {config.moneda_simbolo}{billete}
                    </button>
                  ))}
                  <button
                    type="button"
                    onClick={() => calculo && setMontoRecibido(String(calculo.totalFinal))}
                    className="px-2.5 py-1 rounded bg-slate-200/80 border border-slate-300 hover:bg-slate-300 text-xs font-mono-numbers text-slate-800 font-medium transition-colors"
                  >
                    Exacto
                  </button>
                </div>
              </div>

              {/* Indicador de Vuelto en Tiempo Real */}
              <div className="pt-3 border-t border-slate-200 flex items-center justify-between">
                <span className="text-xs font-medium text-slate-600">
                  Vuelto / Cambio a Entregar:
                </span>
                <div className="text-right">
                  <span
                    className={`text-xl font-mono-numbers font-bold ${
                      vueltoSuficiente ? 'text-emerald-600' : 'text-rose-600'
                    }`}
                  >
                    {vueltoFormateado}
                  </span>
                  {!vueltoSuficiente && (
                    <p className="text-[10px] text-rose-500 mt-0.5">
                      Importe insuficiente
                    </p>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Vista 2: Pestaña TARJETA / POS */}
          {metodoPago === 'TARJETA' && (
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3 animate-modal-in">
              <div className="flex items-center justify-between text-xs text-slate-600">
                <span>Terminal POS:</span>
                <span className="font-mono font-medium">TERMINAL-01 (Simulador Integrado)</span>
              </div>

              <div className="p-3 bg-white rounded-lg border border-slate-200 text-center space-y-2">
                {posSimuladoStatus === 'idle' && (
                  <>
                    <p className="text-xs text-slate-600">
                      Inserte, deslice o acerque la tarjeta en el lector.
                    </p>
                    <button
                      type="button"
                      onClick={handleSimularPOS}
                      className="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow-subtle transition-all active:scale-95"
                    >
                      Simular Tarjeta Aprobada (Contactless)
                    </button>
                  </>
                )}

                {posSimuladoStatus === 'swiping' && (
                  <div className="py-2 flex items-center justify-center gap-2 text-xs text-blue-600 font-medium">
                    <span className="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                    <span>Conectando con red adquirente y autorizando...</span>
                  </div>
                )}

                {posSimuladoStatus === 'approved' && (
                  <div className="py-2 flex flex-col items-center justify-center gap-1 text-emerald-600">
                    <div className="flex items-center gap-1.5 text-xs font-bold">
                      <CheckCircle2 className="w-4 h-4" />
                      <span>Transacción Bancaria Aprobada</span>
                    </div>
                    <span className="text-[11px] font-mono text-slate-500">
                      Código Auth: AUTH-{Math.floor(100000 + Math.random() * 900000)}
                    </span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Vista 3: Pestaña TRANSFERENCIA */}
          {metodoPago === 'TRANSFERENCIA' && (
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3 animate-modal-in">
              <p className="text-xs text-slate-600">
                Confirme la recepción de los fondos en la cuenta corriente de la empresa.
              </p>
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1">
                  Número de Operación / Comprobante (Opcional)
                </label>
                <input
                  type="text"
                  value={comprobante}
                  onChange={(e) => setComprobante(e.target.value)}
                  placeholder="Ej: TRX-99238491"
                  className="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-800 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-slate-900"
                />
              </div>
            </div>
          )}

        </div>

        {/* Pie del Modal con Botones */}
        <div className="px-6 py-4 bg-slate-50/90 border-t border-slate-100 flex items-center justify-between gap-3">
          <button
            type="button"
            onClick={() => setCheckoutVehicle(null)}
            disabled={isProcessing}
            className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 transition-colors"
          >
            Cancelar
          </button>

          <button
            type="button"
            onClick={handleConfirmarCobro}
            disabled={
              isProcessing ||
              loadingCalculo ||
              (metodoPago === 'EFECTIVO' && !vueltoSuficiente) ||
              (metodoPago === 'TARJETA' && posSimuladoStatus !== 'approved')
            }
            className="px-5 py-2.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white transition-all shadow-subtle flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-98"
          >
            <ShieldCheck className="w-4 h-4 text-emerald-400" />
            <span>{isProcessing ? 'Procesando...' : 'Finalizar Cobro y Emitir Ticket'}</span>
          </button>
        </div>

      </div>
    </div>
  );
};
