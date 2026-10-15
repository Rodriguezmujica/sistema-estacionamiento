import React from 'react';
import { useApp } from '../context/AppContext';
import { Printer, Check, X } from 'lucide-react';

export const VirtualTicketModal = () => {
  const { virtualTicketData, setVirtualTicketData, config } = useApp();

  if (!virtualTicketData) return null;

  const handlePrint = () => {
    window.print();
  };

  const ticket = virtualTicketData;
  const esChile = config.moneda_codigo === 'CLP';
  const esIngreso = ticket.tipoTicket === 'ingreso';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in no-print-overlay">
      <div className="bg-white rounded-2xl shadow-modal border border-slate-200 overflow-hidden max-w-sm w-full flex flex-col max-h-[92vh]">
        
        {/* Cabecera del Modal de Previsualización */}
        <div className="px-5 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50 no-print">
          <div className="flex items-center gap-2">
            <span className="text-xs font-semibold text-slate-800">
              {esIngreso ? 'Ticket de Ingreso (80mm)' : 'Ticket Térmico (80mm)'}
            </span>
            <span className={`text-[10px] px-1.5 py-0.5 rounded font-medium ${
              esIngreso ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800'
            }`}>
              {esIngreso ? 'Entrada Registrada' : 'Comprobante de Pago'}
            </span>
          </div>
          <button
            onClick={() => setVirtualTicketData(null)}
            className="p-1 rounded text-slate-400 hover:text-slate-600 hover:bg-slate-200/50"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Contenedor del Ticket Térmico con aspecto de papel auténtico */}
        <div className="p-5 overflow-y-auto bg-slate-100/70 flex justify-center">
          <div
            id="printable-thermal-ticket"
            className="w-full max-w-[280px] bg-white text-slate-900 p-5 rounded shadow-sm border border-slate-200 font-mono text-[11px] leading-tight select-text"
            style={{ fontFamily: "'JetBrains Mono', 'Courier New', monospace" }}
          >
            {/* Encabezado del Comercio */}
            <div className="text-center space-y-1 pb-3 border-b border-dashed border-slate-400">
              <h4 className="text-sm font-bold tracking-wider uppercase">
                ESTACIONAMIENTO CENTRAL
              </h4>
              <p className="text-[10px] text-slate-600">
                {esChile ? 'RUT: 76.842.109-4' : 'NIF / CIF: B-98342110'}
              </p>
              <p className="text-[10px] text-slate-600">
                {esChile ? 'Av. Los Ríos 1040, Valdivia' : 'Gran Vía 42, Madrid'}
              </p>
              <p className="text-[10px] text-slate-600">
                Tel: {esChile ? '+56 9 8412 9901' : '+34 912 345 678'}
              </p>
              <div className="text-[9px] text-slate-500 pt-1 font-semibold uppercase tracking-wider">
                {esIngreso ? 'COMPROBANTE DE ENTRADA' : 'COMPROBANTE DE PAGO VÁLIDO'}
              </div>
            </div>

            {/* Datos Principales del Vehículo */}
            <div className="py-3 border-b border-dashed border-slate-400 text-center">
              <div className="text-[10px] text-slate-500 uppercase">MATRÍCULA / PATENTE</div>
              <div className="text-xl font-black tracking-widest my-0.5">
                {ticket.patente}
              </div>
              <div className="text-[10px] text-slate-600">
                {esIngreso 
                  ? `Folio Entrada N°: ${String(ticket.idIngreso || ticket.id || 1).padStart(6, '0')}`
                  : `Ticket N°: ${String(ticket.idSalida || ticket.id || 1042).padStart(6, '0')}`
                }
              </div>
            </div>

            {/* Caso 1: Ticket de Entrada */}
            {esIngreso ? (
              <div className="py-2.5 border-b border-dashed border-slate-400 space-y-1.5 text-[10px]">
                <div className="flex justify-between">
                  <span className="text-slate-600">Fecha/Hora Ingreso:</span>
                  <span className="font-semibold">
                    {ticket.fechaHoraIngreso || `${ticket.fechaIngresoLocal || ''} ${ticket.horaIngresoLocal || ''}` || new Date().toLocaleString()}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-600">Servicio:</span>
                  <span className="font-semibold">{ticket.nombre_servicio || ticket.servicio || 'Estacionamiento'}</span>
                </div>
                <div className="flex justify-between pt-1 border-t border-dotted border-slate-300">
                  <span className="text-slate-600">Tarifa Vigente:</span>
                  <span className="font-semibold">
                    {ticket.idtipo_ingreso === 18
                      ? `${config.moneda_simbolo}${config.precio_minuto}/min`
                      : (ticket.precioFormateado || `${config.moneda_simbolo}${ticket.precio_base_servicio || 0}`)}
                  </span>
                </div>
                {ticket.idtipo_ingreso === 18 && (
                  <div className="text-[9px] text-slate-500">
                    Tarifa mínima de estadía: {config.moneda_simbolo}{config.precio_minimo}
                  </div>
                )}
              </div>
            ) : (
              /* Caso 2: Ticket de Salida / Pago */
              <>
                {/* Fechas y Tiempo */}
                <div className="py-2.5 border-b border-dashed border-slate-400 space-y-1 text-[10px]">
                  <div className="flex justify-between">
                    <span className="text-slate-600">Salida:</span>
                    <span className="font-semibold">
                      {ticket.fechaHoraSalida || `${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-slate-600">Tiempo Estadía:</span>
                    <span className="font-semibold">{ticket.tiempo_estadia || '45 min'}</span>
                  </div>
                </div>

                {/* Desglose Monetario */}
                <div className="py-2.5 border-b border-dashed border-slate-400 space-y-1.5">
                  <div className="flex justify-between text-[10px]">
                    <span>Servicio:</span>
                    <span>{ticket.servicio || (ticket.total > 0 ? 'Tarifa Estacionamiento' : 'Abono Mensual')}</span>
                  </div>

                  <div className="flex justify-between items-baseline pt-1 font-bold text-xs">
                    <span>TOTAL PAGADO:</span>
                    <span className="text-sm">
                      {ticket.totalFormateado || `${config.moneda_simbolo}${ticket.total}`}
                    </span>
                  </div>

                  <div className="flex justify-between text-[10px] text-slate-600 pt-1">
                    <span>Forma de Pago:</span>
                    <span className="font-semibold">{ticket.metodo_pago}</span>
                  </div>

                  {ticket.metodo_pago === 'EFECTIVO' && ticket.montoRecibido !== undefined && (
                    <>
                      <div className="flex justify-between text-[9px] text-slate-500">
                        <span>Recibido:</span>
                        <span>{ticket.montoRecibidoFormateado || `${config.moneda_simbolo}${ticket.montoRecibido}`}</span>
                      </div>
                      <div className="flex justify-between text-[9px] text-slate-500">
                        <span>Vuelto:</span>
                        <span>{ticket.vueltoFormateado || `${config.moneda_simbolo}${ticket.vuelto || 0}`}</span>
                      </div>
                    </>
                  )}

                  {ticket.authCode && (
                    <div className="text-[9px] text-slate-500 flex justify-between">
                      <span>Cód. Autorización:</span>
                      <span className="font-mono">{ticket.authCode}</span>
                    </div>
                  )}
                </div>
              </>
            )}

            {/* Código de Barras SVG Simulado */}
            <div className="pt-3 pb-1 text-center space-y-1">
              <svg className="w-full h-10 mx-auto" viewBox="0 0 200 40">
                <rect x="0" y="0" width="200" height="40" fill="white" />
                {/* Barras de ancho variable */}
                <rect x="10" y="5" width="2" height="30" fill="black" />
                <rect x="14" y="5" width="4" height="30" fill="black" />
                <rect x="22" y="5" width="1" height="30" fill="black" />
                <rect x="25" y="5" width="3" height="30" fill="black" />
                <rect x="32" y="5" width="2" height="30" fill="black" />
                <rect x="38" y="5" width="5" height="30" fill="black" />
                <rect x="46" y="5" width="2" height="30" fill="black" />
                <rect x="52" y="5" width="1" height="30" fill="black" />
                <rect x="56" y="5" width="4" height="30" fill="black" />
                <rect x="64" y="5" width="2" height="30" fill="black" />
                <rect x="70" y="5" width="3" height="30" fill="black" />
                <rect x="76" y="5" width="2" height="30" fill="black" />
                <rect x="82" y="5" width="4" height="30" fill="black" />
                <rect x="90" y="5" width="1" height="30" fill="black" />
                <rect x="95" y="5" width="3" height="30" fill="black" />
                <rect x="102" y="5" width="2" height="30" fill="black" />
                <rect x="108" y="5" width="4" height="30" fill="black" />
                <rect x="116" y="5" width="2" height="30" fill="black" />
                <rect x="122" y="5" width="1" height="30" fill="black" />
                <rect x="127" y="5" width="5" height="30" fill="black" />
                <rect x="136" y="5" width="2" height="30" fill="black" />
                <rect x="142" y="5" width="3" height="30" fill="black" />
                <rect x="148" y="5" width="1" height="30" fill="black" />
                <rect x="152" y="5" width="4" height="30" fill="black" />
                <rect x="160" y="5" width="2" height="30" fill="black" />
                <rect x="166" y="5" width="4" height="30" fill="black" />
                <rect x="174" y="5" width="1" height="30" fill="black" />
                <rect x="178" y="5" width="3" height="30" fill="black" />
                <rect x="185" y="5" width="2" height="30" fill="black" />
              </svg>
              <div className="text-[8px] text-slate-500 tracking-widest font-mono">
                * {ticket.patente} *
              </div>
            </div>

            {/* Mensaje de Despedida */}
            <div className="text-center pt-2 text-[9px] text-slate-500 border-t border-dotted border-slate-300">
              {esIngreso ? (
                <>
                  ¡Bienvenido a ParkFlow Central!
                  <br />
                  Conserve este ticket para retirar su vehículo.
                </>
              ) : (
                <>
                  ¡Gracias por su preferencia!
                  <br />
                  Conserve este comprobante para salir del recinto.
                </>
              )}
            </div>
          </div>
        </div>

        {/* Barra de Acciones */}
        <div className="px-5 py-3 border-t border-slate-200 bg-white flex items-center justify-between gap-2 no-print">
          <button
            type="button"
            onClick={handlePrint}
            className="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold flex items-center gap-1.5 transition-colors"
          >
            <Printer className="w-3.5 h-3.5 text-slate-600" />
            <span>Imprimir</span>
          </button>

          <button
            type="button"
            onClick={() => setVirtualTicketData(null)}
            className="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center gap-1.5 transition-colors active:scale-98"
          >
            <Check className="w-3.5 h-3.5 text-emerald-400" />
            <span>Listo</span>
          </button>
        </div>

      </div>
    </div>
  );
};
