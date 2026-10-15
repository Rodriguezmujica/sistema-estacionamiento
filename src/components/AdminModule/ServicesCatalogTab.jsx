import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Layers,
  Plus,
  RefreshCw,
  Edit2,
  Lock,
  Tag,
  DollarSign,
  Check,
  X,
  AlertCircle,
  ShieldCheck,
  Info
} from 'lucide-react';

export const ServicesCatalogTab = () => {
  const { formatMoney, showToast, refrescarDatos } = useApp();

  const [servicios, setServicios] = useState([]);
  const [loading, setLoading] = useState(true);

  // Modales y formularios
  const [modalOpen, setModalOpen] = useState(false);
  const [editingServicio, setEditingServicio] = useState(null);
  const [formData, setFormData] = useState({
    nombre_servicio: '',
    precio: 0,
    es_plan: 0
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [togglingId, setTogglingId] = useState(null);

  // Cargar catálogo completo desde SQLite
  const cargarServicios = useCallback(async () => {
    setLoading(true);
    try {
      if (typeof window !== 'undefined' && window.api?.servicios?.listar) {
        const data = await window.api.servicios.listar();
        setServicios(data || []);
      } else if (typeof window !== 'undefined' && window.api?.getTodosServicios) {
        const data = await window.api.getTodosServicios();
        setServicios(data || []);
      } else if (typeof window !== 'undefined' && window.api?.getTiposIngreso) {
        const data = await window.api.getTiposIngreso();
        setServicios(data || []);
      }
    } catch (err) {
      console.error('Error cargando catálogo de servicios:', err);
      showToast('Error al cargar catálogo de servicios: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [showToast]);

  useEffect(() => {
    cargarServicios();
  }, [cargarServicios]);

  // Abrir modal para crear
  const handleOpenCreate = () => {
    setEditingServicio(null);
    setFormData({
      nombre_servicio: '',
      precio: 10000,
      es_plan: 0
    });
    setFormErrors({});
    setModalOpen(true);
  };

  // Abrir modal para editar
  const handleOpenEdit = (servicio) => {
    setEditingServicio(servicio);
    setFormData({
      nombre_servicio: servicio.nombre_servicio || '',
      precio: servicio.precio || 0,
      es_plan: servicio.es_plan || 0
    });
    setFormErrors({});
    setModalOpen(true);
  };

  // Guardar creación o edición
  const handleSubmitForm = async (e) => {
    e.preventDefault();
    const errors = {};

    if (!formData.nombre_servicio.trim()) {
      errors.nombre_servicio = 'El nombre del servicio es obligatorio.';
    }

    const p = parseFloat(formData.precio);
    if (isNaN(p) || p < 0) {
      errors.precio = 'El precio base debe ser mayor o igual a 0.';
    }

    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    setSubmitting(true);
    try {
      if (editingServicio) {
        if (window.api?.servicios?.actualizar) {
          await window.api.servicios.actualizar(editingServicio.idtipo_ingresos, {
            nombre_servicio: formData.nombre_servicio.trim(),
            precio: p
          });
        }
        showToast(`Servicio '${formData.nombre_servicio}' actualizado`, 'success');
      } else {
        if (window.api?.servicios?.crear) {
          await window.api.servicios.crear({
            nombre_servicio: formData.nombre_servicio.trim(),
            precio: p,
            es_plan: formData.es_plan ? 1 : 0
          });
        }
        showToast(`Servicio '${formData.nombre_servicio}' agregado al catálogo`, 'success');
      }

      setModalOpen(false);
      await cargarServicios();
      await refrescarDatos();
    } catch (err) {
      console.error('Error guardando servicio:', err);
      showToast(err.message || 'Error al guardar servicio', 'error');
    } finally {
      setSubmitting(false);
    }
  };

  // Alternar estado activo / inactivo
  const handleToggleActivo = async (servicio) => {
    if ([17, 18, 19].includes(servicio.idtipo_ingresos)) {
      showToast('Los servicios reservados del sistema no pueden ser desactivados', 'info');
      return;
    }

    try {
      setTogglingId(servicio.idtipo_ingresos);
      if (window.api?.servicios?.toggleActivo) {
        await window.api.servicios.toggleActivo(servicio.idtipo_ingresos);
      }
      showToast(
        `Servicio '${servicio.nombre_servicio}' ${servicio.activo ? 'desactivado' : 'activado'}`,
        'success'
      );
      await cargarServicios();
      await refrescarDatos();
    } catch (err) {
      console.error('Error cambiando estado de servicio:', err);
      showToast(err.message || 'Error al alternar estado', 'error');
    } finally {
      setTogglingId(null);
    }
  };

  return (
    <div className="space-y-6">
      {/* Barra de Encabezado & Botón Agregar Servicio */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <h2 className="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Layers className="w-4 h-4 text-slate-700" />
            <span>Catálogo Maestro de Servicios (tipo_ingreso)</span>
          </h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Tarifario para estacionamiento, planes mensuales y servicios de lavado y estética vehicular.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>+ Agregar Servicio</span>
        </button>
      </div>

      {/* Nota sobre IDs Reservados */}
      <div className="p-3.5 rounded-xl bg-slate-100/70 border border-slate-200/80 flex items-start gap-2.5 text-xs text-slate-600">
        <Info className="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
        <div className="leading-relaxed text-[11px]">
          <strong>Identificadores Reservados del Sistema:</strong> El sistema ParkFlow reserva de manera estricta el <strong>ID 17 (Plan Mensual)</strong>, <strong>ID 18 (Estacionamiento x min)</strong> y el <strong>ID 19 (Error de ingreso)</strong> para garantizar el funcionamiento automático de la caseta y no pueden ser desactivados.
        </div>
      </div>

      {/* Tabla del Catálogo de Servicios */}
      <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                <th className="py-3 px-4 w-28">ID</th>
                <th className="py-3 px-4">Nombre del Servicio</th>
                <th className="py-3 px-4">Tipo / Clasificación</th>
                <th className="py-3 px-4 text-right">Precio Base</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-slate-400">
                    <RefreshCw className="w-5 h-5 mx-auto animate-spin mb-2 text-slate-400" />
                    <span>Cargando catálogo de servicios...</span>
                  </td>
                </tr>
              ) : servicios.length === 0 ? (
                <tr>
                  <td colSpan="6" className="py-12 text-center text-slate-400">
                    <Layers className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                    <p className="font-medium text-slate-600">No hay servicios registrados en la base de datos</p>
                  </td>
                </tr>
              ) : (
                servicios.map((s) => {
                  const isReserved = [17, 18, 19].includes(s.idtipo_ingresos);
                  const isToggling = togglingId === s.idtipo_ingresos;

                  return (
                    <tr
                      key={s.idtipo_ingresos}
                      className="hover:bg-slate-50/80 transition-colors"
                    >
                      {/* Identificador con distinción de IDs reservados */}
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-1.5 font-mono">
                          <span className="font-bold text-slate-900">
                            #{s.idtipo_ingresos}
                          </span>
                          {isReserved && (
                            <span
                              title="Identificador reservado del sistema"
                              className="p-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200"
                            >
                              <Lock className="w-3 h-3" />
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Nombre del servicio */}
                      <td className="py-3 px-4 font-semibold text-slate-900">
                        <div className="flex items-center gap-2">
                          <span>{s.nombre_servicio}</span>
                          {isReserved && (
                            <span className="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200/80">
                              Sistema
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Tipo / Clasificación */}
                      <td className="py-3 px-4 text-slate-600">
                        {s.idtipo_ingresos === 17 ? (
                          <span className="text-slate-700">Abono Recurrente</span>
                        ) : s.idtipo_ingresos === 18 ? (
                          <span className="text-slate-700">Estadía x Minuto</span>
                        ) : s.idtipo_ingresos === 19 ? (
                          <span className="text-slate-700">Sanción / Ajuste</span>
                        ) : (
                          <span className="text-slate-700">Lavado / Estética</span>
                        )}
                      </td>

                      {/* Precio Base */}
                      <td className="py-3 px-4 text-right font-mono-numbers font-bold text-slate-900">
                        {s.idtipo_ingresos === 18 ? (
                          <span className="text-xs text-slate-500 font-normal italic">
                            Tarifa dinámica x min
                          </span>
                        ) : s.idtipo_ingresos === 17 ? (
                          <span className="text-xs text-slate-500 font-normal italic">
                            Según plan abonado
                          </span>
                        ) : (
                          formatMoney(s.precio)
                        )}
                      </td>

                      {/* Estado: Activo / Inactivo con Toggle Switch */}
                      <td className="py-3 px-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            type="button"
                            onClick={() => handleToggleActivo(s)}
                            disabled={isReserved || isToggling}
                            className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                              isReserved
                                ? 'opacity-50 cursor-not-allowed bg-slate-800'
                                : s.activo
                                ? 'bg-slate-900'
                                : 'bg-slate-300'
                            }`}
                          >
                            <span
                              className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out ${
                                s.activo ? 'translate-x-4' : 'translate-x-0'
                              }`}
                            />
                          </button>
                          <span
                            className={`text-[11px] font-medium ${
                              s.activo ? 'text-slate-800' : 'text-slate-400'
                            }`}
                          >
                            {s.activo ? 'Activo' : 'Inactivo'}
                          </span>
                        </div>
                      </td>

                      {/* Acciones */}
                      <td className="py-3 px-4 text-right">
                        <button
                          onClick={() => handleOpenEdit(s)}
                          disabled={s.idtipo_ingresos === 18}
                          title={
                            s.idtipo_ingresos === 18
                              ? 'La tarifa por minuto se configura en la sub-pestaña "Configuración y Metas"'
                              : 'Modificar precio y nombre'
                          }
                          className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 font-medium text-[11px] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          <Edit2 className="w-3 h-3" />
                          <span>Editar</span>
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

      {/* MODAL CREAR / EDITAR SERVICIO */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-md shadow-xl overflow-hidden animate-scaleUp">
            {/* Modal Header */}
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                  <Tag className="w-4 h-4 text-slate-700" />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    {editingServicio ? `Editar Servicio #${editingServicio.idtipo_ingresos}` : 'Nuevo Servicio del Catálogo'}
                  </h3>
                  <p className="text-[11px] text-slate-500">
                    Configuración de precios en SQLite (tipo_ingreso)
                  </p>
                </div>
              </div>
              <button
                onClick={() => setModalOpen(false)}
                className="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Modal Form */}
            <form onSubmit={handleSubmitForm} className="p-6 space-y-4">
              {/* Nombre del Servicio */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nombre del Servicio *
                </label>
                <input
                  type="text"
                  value={formData.nombre_servicio}
                  onChange={(e) =>
                    setFormData((p) => ({ ...p, nombre_servicio: e.target.value }))
                  }
                  placeholder="Ej: Lavado de Chasis & Motor"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    formErrors.nombre_servicio
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {formErrors.nombre_servicio && (
                  <p className="text-[10px] text-rose-600 mt-1">{formErrors.nombre_servicio}</p>
                )}
              </div>

              {/* Precio Base */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Precio Base *
                </label>
                <div className="relative">
                  <DollarSign className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="number"
                    step="any"
                    value={formData.precio}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, precio: e.target.value }))
                    }
                    placeholder="0"
                    className={`w-full pl-9 pr-3 py-2 text-xs rounded-lg border font-mono-numbers font-semibold ${
                      formErrors.precio
                        ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                        : 'border-slate-200 bg-slate-50 focus:bg-white'
                    } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                  />
                </div>
                {formErrors.precio && (
                  <p className="text-[10px] text-rose-600 mt-1">{formErrors.precio}</p>
                )}
              </div>

              {/* Acciones */}
              <div className="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                >
                  {submitting ? 'Guardando...' : editingServicio ? 'Guardar Cambios' : 'Crear Servicio'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ServicesCatalogTab;
