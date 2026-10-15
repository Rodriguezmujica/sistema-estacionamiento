import React, { useState, useEffect } from 'react';
import { useApp } from '../context/AppContext';
import { Clock, Search, CreditCard, Sparkles, AlertCircle, ShieldAlert } from 'lucide-react';

export const ParkedVehiclesTable = () => {
  const { vehiculos, setCheckoutVehicle, config, formatMoney } = useApp();
  const [busqueda, setBusqueda] = useState('');
  // Tick cada 60 segundos para recalcular tiempo transcurrido en vivo sin saturar
  const [tick, setTick] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setTick((prev) => prev + 1);
    }, 60000); // 1 minuto
    return () => clearInterval(timer);
  }, []);

  // Función para calcular tiempo en vivo de cada vehículo
  const calcularEstadiaEnVivo = (vehiculo) => {
    const ts = Number(vehiculo.timestamp) || new Date(vehiculo.fecha_ingreso).getTime();
    const ahora = Date.now();
    const diffMs = Math.max(0, ahora - ts);
    const minutosTotales = Math.max(1, Math.ceil(diffMs / (1000 * 60)));

    const horas = Math.floor(minutosTotales / 60);
    const mins = minutosTotales % 60;
    const tiempoTexto = horas > 0 ? `${horas}h ${mins}m` : `${mins} min`;

    // Cálculo dinámico de total estimado
    let estimado = 0;
    if (vehiculo.idtipo_ingreso === 17 || vehiculo.es_plan === 1) {
      estimado = 0;
    } else if (vehiculo.idtipo_ingreso === 18) {
      const base = minutosTotales * config.precio_minuto;
      estimado = Math.max(base, config.precio_minimo);
    } else if (vehiculo.idtipo_ingreso === 19) {
      estimado = config.moneda_codigo === 'EUR' ? 0.01 : 1;
    } else {
      estimado = (vehiculo.precio_base_servicio || 0) + (vehiculo.precio_extra || 0);
    }

    return {
      minutosTotales,
      tiempoTexto,
      estimado
    };
  };

  // Filtrado reactivo por matrícula o servicio
  const vehiculosFiltrados = vehiculos.filter((v) => {
    const term = busqueda.toLowerCase().trim();
    return (
      v.patente.toLowerCase().includes(term) ||
      (v.nombre_servicio && v.nombre_servicio.toLowerCase().includes(term))
    );
  });

  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-card overflow-hidden">
      
      {/* Barra superior de la tabla: título, contador y buscador */}
      <div className="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
        <div className="flex items-center gap-2.5">
          <h3 className="text-sm font-semibold text-slate-900 tracking-tight">
            Vehículos en Recinto
          </h3>
          <span className="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200/70 text-slate-700">
            {vehiculos.length}
          </span>
        </div>

        {/* Buscador de matrícula en tiempo real */}
        <div className="relative w-full sm:w-64">
          <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
          <input
            type="text"
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
            placeholder="Filtrar por patente..."
            className="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-900 transition-all font-mono"
          />
        </div>
      </div>

      {/* Contenedor de la Tabla */}
      <div className="overflow-x-auto">
        <table className="w-full text-left text-xs">
          <thead>
            <tr className="border-b border-slate-200/80 bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-5">Matrícula</th>
              <th className="py-3 px-4">Servicio</th>
              <th className="py-3 px-4">Hora de Entrada</th>
              <th className="py-3 px-4">Estadía (En Vivo)</th>
              <th className="py-3 px-4 text-right">Total Acumulado</th>
              <th className="py-3 px-5 text-right">Acción</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {vehiculosFiltrados.length === 0 ? (
              <tr>
                <td colSpan="6" className="py-12 text-center text-slate-400">
                  <div className="flex flex-col items-center justify-center gap-2">
                    <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                      🚗
                    </div>
                    <p className="font-medium text-slate-600 text-xs">
                      {busqueda ? 'No se encontraron vehículos coincidentes' : 'No hay vehículos actualmente estacionados'}
                    </p>
                    <p className="text-[11px] text-slate-400">
                      {busqueda ? 'Intenta con otra matrícula' : 'Utiliza el formulario de arriba o presiona "Cargar Demo"'}
                    </p>
                  </div>
                </td>
              </tr>
            ) : (
              vehiculosFiltrados.map((item) => {
                const estadia = calcularEstadiaEnVivo(item);
                const esAbonado = item.idtipo_ingreso === 17 || item.es_plan === 1;
                const esLavado = item.nombre_servicio && item.nombre_servicio.toLowerCase().includes('lavad');

                return (
                  <tr
                    key={item.id}
                    className="hover:bg-slate-50/80 transition-colors group"
                  >
                    {/* Matrícula en estilo placa europea / limpia */}
                    <td className="py-3.5 px-5 font-mono">
                      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 border border-slate-300 text-slate-900 font-bold tracking-wider text-xs shadow-2xs">
                        <span>{item.patente}</span>
                      </div>
                    </td>

                    {/* Servicio */}
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-1.5">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ${
                            esAbonado
                              ? 'bg-purple-50 text-purple-700 border border-purple-200'
                              : esLavado
                              ? 'bg-cyan-50 text-cyan-700 border border-cyan-200'
                              : 'bg-slate-100 text-slate-700 border border-slate-200'
                          }`}
                        >
                          {esAbonado && '⭐ '}
                          {esLavado && '🧼 '}
                          {item.nombre_servicio}
                        </span>

                        {item.precio_extra > 0 && (
                          <span
                            className="text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 font-mono"
                            title={`Extra: ${item.motivos_extra || 'Servicio especial'}`}
                          >
                            +{formatMoney(item.precio_extra)}
                          </span>
                        )}
                      </div>
                    </td>

                    {/* Hora de Entrada */}
                    <td className="py-3.5 px-4 text-slate-600">
                      <div className="flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5 text-slate-400" />
                        <span className="font-mono-numbers">
                          {item.horaLocal || (item.fecha_ingreso ? item.fecha_ingreso.split(' ')[1] : '--:--')}
                        </span>
                      </div>
                      <span className="text-[10px] text-slate-400 block mt-0.5">
                        {item.fechaLocal || (item.fecha_ingreso ? item.fecha_ingreso.split(' ')[0] : '')}
                      </span>
                    </td>

                    {/* Estadía en vivo */}
                    <td className="py-3.5 px-4 font-mono-numbers">
                      <span className="inline-flex items-center gap-1 font-medium text-slate-700 bg-slate-100/70 px-2 py-0.5 rounded text-[11px]">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        {estadia.tiempoTexto}
                      </span>
                    </td>

                    {/* Total Acumulado */}
                    <td className="py-3.5 px-4 text-right font-mono-numbers">
                      {esAbonado ? (
                        <span className="text-purple-600 font-semibold text-xs">
                          Plan Activo ($0)
                        </span>
                      ) : (
                        <span className="font-bold text-slate-900 text-xs tracking-tight">
                          {formatMoney(estadia.estimado)}
                        </span>
                      )}
                    </td>

                    {/* Botón Cobrar */}
                    <td className="py-3.5 px-5 text-right">
                      <button
                        onClick={() => setCheckoutVehicle(item)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white transition-all shadow-subtle active:scale-95"
                      >
                        <CreditCard className="w-3.5 h-3.5 text-slate-300" />
                        <span>Cobrar</span>
                      </button>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

    </div>
  );
};
