import React, { useState, useEffect, useMemo } from 'react';
import { useApp } from '../context/AppContext';
import { Edit3, Search, Car, Check, X, AlertTriangle, ArrowRight, Save, Clock } from 'lucide-react';

export const ModifyTicketModal = () => {
  const {
    isModifyTicketOpen,
    setIsModifyTicketOpen,
    vehiculos,
    tiposIngreso,
    modificarIngreso,
    config
  } = useApp();

  const [searchQuery, setSearchQuery] = useState('');
  const [selectedVehicle, setSelectedVehicle] = useState(null);
  const [newPatente, setNewPatente] = useState('');
  const [newIdTipoIngreso, setNewIdTipoIngreso] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  // Reset al abrir o cerrar
  useEffect(() => {
    if (isModifyTicketOpen) {
      setSearchQuery('');
      setErrorMessage('');
      // Si hay un solo vehículo o preseleccionar el primero si no hay muchos
      if (vehiculos.length === 1) {
        selectVehicle(vehiculos[0]);
      } else {
        setSelectedVehicle(null);
      }
    } else {
      setSelectedVehicle(null);
      setNewPatente('');
      setNewIdTipoIngreso('');
      setErrorMessage('');
    }
  }, [isModifyTicketOpen, vehiculos]);

  // Cerrar con Escape
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isModifyTicketOpen) {
        setIsModifyTicketOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isModifyTicketOpen, setIsModifyTicketOpen]);

  // Filtrar vehículos activos por búsqueda de patente o servicio
  const vehiculosFiltrados = useMemo(() => {
    if (!searchQuery.trim()) return vehiculos;
    const q = searchQuery.trim().toUpperCase();
    return vehiculos.filter(
      (v) =>
        v.patente.toUpperCase().includes(q) ||
        (v.nombre_servicio && v.nombre_servicio.toUpperCase().includes(q))
    );
  }, [vehiculos, searchQuery]);

  const selectVehicle = (vehicle) => {
    setSelectedVehicle(vehicle);
    setNewPatente(vehicle.patente);
    setNewIdTipoIngreso(String(vehicle.idtipo_ingreso));
    setErrorMessage('');
  };

  const handlePatenteChange = (e) => {
    const val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 8);
    setNewPatente(val);
  };

  const hasChanges = useMemo(() => {
    if (!selectedVehicle) return false;
    const cleanNew = newPatente.trim().replace(/[^A-Z0-9]/g, '');
    const cleanOld = selectedVehicle.patente.trim().replace(/[^A-Z0-9]/g, '');
    return cleanNew !== cleanOld || Number(newIdTipoIngreso) !== Number(selectedVehicle.idtipo_ingreso);
  }, [selectedVehicle, newPatente, newIdTipoIngreso]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedVehicle) return;

    const cleanPatente = newPatente.trim().replace(/[^A-Z0-9]/g, '');
    if (cleanPatente.length < 4) {
      setErrorMessage('La matrícula debe tener al menos 4 caracteres alfanuméricos.');
      return;
    }

    try {
      setIsSubmitting(true);
      setErrorMessage('');
      await modificarIngreso({
        idIngreso: selectedVehicle.id,
        patente: cleanPatente,
        idtipo_ingreso: Number(newIdTipoIngreso)
      });
      setIsModifyTicketOpen(false);
    } catch (err) {
      setErrorMessage(err.message || 'Error al actualizar el ticket en la base de datos.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isModifyTicketOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-modal-in no-print-overlay">
      <div className="bg-white rounded-2xl shadow-modal border border-slate-200 overflow-hidden max-w-lg w-full flex flex-col max-h-[90vh]">
        
        {/* Cabecera del Modal */}
        <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shadow-xs">
              <Edit3 className="w-4 h-4 text-amber-400" />
            </div>
            <div>
              <h3 className="text-sm font-semibold text-slate-900">
                Modificar Ticket de Ingreso
              </h3>
              <p className="text-[11px] text-slate-500">
                Corrección de patente o cambio de servicio en SQLite
              </p>
            </div>
          </div>
          <button
            onClick={() => setIsModifyTicketOpen(false)}
            className="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 transition-colors cursor-pointer"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Contenido del Modal */}
        <div className="p-6 overflow-y-auto space-y-5">

          {/* Alerta de Error si ocurre */}
          {errorMessage && (
            <div className="p-3 rounded-lg bg-rose-50 border border-rose-200 flex items-start gap-2.5 text-xs text-rose-800 animate-modal-in">
              <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
              <span>{errorMessage}</span>
            </div>
          )}

          {/* PASO 1: Búsqueda o Selección de Vehículo */}
          {!selectedVehicle ? (
            <div className="space-y-3">
              <label className="block text-xs font-semibold text-slate-700">
                1. Buscar patente activa dentro del estacionamiento:
              </label>

              {/* Barra de Búsqueda */}
              <div className="relative">
                <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                <input
                  type="text"
                  autoFocus
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Escriba matrícula o servicio..."
                  className="w-full pl-9 pr-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50/50 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white uppercase font-mono tracking-wider transition-all"
                />
              </div>

              {/* Lista de vehículos en recinto */}
              <div className="border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-56 overflow-y-auto">
                {vehiculosFiltrados.length === 0 ? (
                  <div className="p-6 text-center text-xs text-slate-400">
                    No se encontraron vehículos activos con ese criterio.
                  </div>
                ) : (
                  vehiculosFiltrados.map((v) => (
                    <button
                      key={v.id}
                      type="button"
                      onClick={() => selectVehicle(v)}
                      className="w-full p-3 text-left hover:bg-slate-50 transition-colors flex items-center justify-between group cursor-pointer"
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700 font-mono font-bold text-xs group-hover:bg-slate-900 group-hover:text-white transition-colors">
                          <Car className="w-4 h-4" />
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-mono font-bold text-xs text-slate-900 tracking-wider">
                              {v.patente}
                            </span>
                            <span className="text-[10px] text-slate-400 font-mono">
                              #{v.id}
                            </span>
                          </div>
                          <p className="text-[11px] text-slate-500">
                            {v.nombre_servicio} • {v.minutos || 1} min en recinto
                          </p>
                        </div>
                      </div>

                      <div className="flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-slate-900 font-medium">
                        <span>Editar</span>
                        <ArrowRight className="w-3.5 h-3.5" />
                      </div>
                    </button>
                  ))
                )}
              </div>
            </div>
          ) : (
            /* PASO 2: Formulario de Modificación de Datos */
            <form onSubmit={handleSubmit} className="space-y-4">
              
              {/* Card resumen del vehículo actual seleccionado */}
              <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div className="flex items-center gap-2.5">
                  <div className="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-mono font-bold text-xs">
                    {selectedVehicle.patente.slice(0, 2)}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-semibold text-slate-900">
                        Ticket Folio #{selectedVehicle.id}
                      </span>
                      <span className="text-[10px] px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 font-medium">
                        En Recinto
                      </span>
                    </div>
                    <p className="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                      <Clock className="w-3 h-3 text-slate-400" />
                      <span>Ingreso: {selectedVehicle.horaLocal || selectedVehicle.fecha_ingreso}</span>
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setSelectedVehicle(null)}
                  className="text-xs text-slate-500 hover:text-slate-900 underline font-medium cursor-pointer"
                >
                  Cambiar auto
                </button>
              </div>

              {/* Campo 1: Corregir Patente */}
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1.5">
                  Matrícula / Patente <span className="text-slate-400 font-normal">(Corregir tipeo)</span>
                </label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    autoFocus
                    value={newPatente}
                    onChange={handlePatenteChange}
                    placeholder="EJ: ABCD12"
                    className="w-full px-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 font-mono text-sm uppercase font-bold tracking-wider focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white transition-all"
                  />
                  {newPatente.trim().replace(/[^A-Z0-9]/g, '') !== selectedVehicle.patente && newPatente.length >= 4 && (
                    <div className="absolute right-3 top-2.5 text-amber-600 flex items-center gap-1 text-[11px] font-medium">
                      <span>Modificado</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Campo 2: Cambiar Tipo de Servicio */}
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1.5">
                  Tipo de Servicio <span className="text-slate-400 font-normal">(Reasignar catálogo)</span>
                </label>
                <select
                  value={newIdTipoIngreso}
                  onChange={(e) => setNewIdTipoIngreso(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-800 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white transition-all cursor-pointer"
                >
                  {tiposIngreso.map((tipo) => (
                    <option key={tipo.idtipo_ingresos} value={tipo.idtipo_ingresos}>
                      {tipo.nombre_servicio} 
                      {tipo.precio > 0 ? ` (${config.moneda_simbolo}${tipo.precio})` : ''}
                    </option>
                  ))}
                </select>
              </div>

              {/* Previsualización de los cambios */}
              {hasChanges && (
                <div className="p-3 rounded-lg bg-amber-50/80 border border-amber-200/80 text-xs text-amber-900 space-y-1 animate-modal-in">
                  <span className="font-semibold block text-[11px] uppercase tracking-wider text-amber-800">
                    Resumen de Modificaciones:
                  </span>
                  {newPatente.trim().replace(/[^A-Z0-9]/g, '') !== selectedVehicle.patente && (
                    <div className="flex items-center gap-1 text-[11px]">
                      <span className="text-slate-500">Patente:</span>
                      <span className="font-mono line-through text-slate-400">{selectedVehicle.patente}</span>
                      <span>→</span>
                      <span className="font-mono font-bold text-slate-900">{newPatente}</span>
                    </div>
                  )}
                  {Number(newIdTipoIngreso) !== Number(selectedVehicle.idtipo_ingreso) && (
                    <div className="flex items-center gap-1 text-[11px]">
                      <span className="text-slate-500">Servicio:</span>
                      <span className="line-through text-slate-400">{selectedVehicle.nombre_servicio}</span>
                      <span>→</span>
                      <span className="font-bold text-slate-900">
                        {tiposIngreso.find((t) => Number(t.idtipo_ingresos) === Number(newIdTipoIngreso))?.nombre_servicio}
                      </span>
                    </div>
                  )}
                </div>
              )}

              {/* Botones de Acción */}
              <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setSelectedVehicle(null)}
                  disabled={isSubmitting}
                  className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Volver a búsqueda
                </button>

                <button
                  type="submit"
                  disabled={isSubmitting || !hasChanges}
                  className="px-5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition-all shadow-subtle flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer active:scale-98"
                >
                  <Save className="w-3.5 h-3.5" />
                  <span>{isSubmitting ? 'Guardando...' : 'Guardar Cambios'}</span>
                </button>
              </div>

            </form>
          )}

        </div>

        {/* Footer del Modal */}
        <div className="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-[11px] text-slate-400">
          <span>Vehículos activos en recinto: {vehiculos.length}</span>
          <button
            onClick={() => setIsModifyTicketOpen(false)}
            className="hover:text-slate-600 transition-colors cursor-pointer"
          >
            Cerrar [ESC]
          </button>
        </div>

      </div>
    </div>
  );
};
