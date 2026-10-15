import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Users,
  UserCheck,
  UserX,
  TrendingUp,
  Search,
  Plus,
  RefreshCw,
  Edit2,
  Trash2,
  Calendar,
  Car,
  Phone,
  AlertTriangle,
  X,
  Check,
  CheckCircle2,
  Clock
} from 'lucide-react';

export const SubscribersTab = () => {
  const { formatMoney, showToast, refrescarDatos } = useApp();

  const [clientes, setClientes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('todos'); // 'todos' | 'activos' | 'vencidos'

  // Estados de modales
  const [modalOpen, setModalOpen] = useState(false);
  const [editingCliente, setEditingCliente] = useState(null);
  const [deleteCandidate, setDeleteCandidate] = useState(null);
  const [renewingId, setRenewingId] = useState(null);

  // Formulario de creación/edición
  const [formData, setFormData] = useState({
    patente: '',
    nombres: '',
    telefono: '',
    vehiculo: '',
    dia_pago: 5,
    fecha_vencimiento: '',
    monto_plan: 45000
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Cargar clientes desde SQLite
  const cargarClientes = useCallback(async () => {
    setLoading(true);
    try {
      if (typeof window !== 'undefined' && window.api?.clientes?.listar) {
        const lista = await window.api.clientes.listar();
        setClientes(lista || []);
      } else if (typeof window !== 'undefined' && window.api?.getClientes) {
        const lista = await window.api.getClientes();
        setClientes(lista || []);
      }
    } catch (err) {
      console.error('Error cargando abonados:', err);
      showToast('Error al cargar abonados: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [showToast]);

  useEffect(() => {
    cargarClientes();
  }, [cargarClientes]);

  // Cálculos de KPIs
  const kpis = useMemo(() => {
    const total = clientes.length;
    let activos = 0;
    let vencidos = 0;
    let proyectado = 0;

    clientes.forEach((c) => {
      if (c.esActivo || c.estado === 'ACTIVO') {
        activos++;
      } else {
        vencidos++;
      }
      proyectado += Number(c.monto_plan) || 0;
    });

    return {
      total,
      activos,
      vencidos,
      proyectado,
      proyectadoFormateado: formatMoney(proyectado)
    };
  }, [clientes, formatMoney]);

  // Filtrado de clientes
  const clientesFiltrados = useMemo(() => {
    const query = searchTerm.trim().toLowerCase();
    return clientes.filter((c) => {
      const matchSearch =
        !query ||
        c.patente?.toLowerCase().includes(query) ||
        c.nombres?.toLowerCase().includes(query) ||
        c.vehiculo?.toLowerCase().includes(query) ||
        c.telefono?.toLowerCase().includes(query);

      const isActivo = c.esActivo || c.estado === 'ACTIVO';
      const matchStatus =
        statusFilter === 'todos' ||
        (statusFilter === 'activos' && isActivo) ||
        (statusFilter === 'vencidos' && !isActivo);

      return matchSearch && matchStatus;
    });
  }, [clientes, searchTerm, statusFilter]);

  // Abrir modal para crear
  const handleOpenCreate = () => {
    const hoy = new Date();
    hoy.setMonth(hoy.getMonth() + 1);
    const vencimientoDefault = hoy.toISOString().slice(0, 10);

    setEditingCliente(null);
    setFormData({
      patente: '',
      nombres: '',
      telefono: '',
      vehiculo: '',
      dia_pago: 5,
      fecha_vencimiento: vencimientoDefault,
      monto_plan: 45000
    });
    setFormErrors({});
    setModalOpen(true);
  };

  // Abrir modal para editar
  const handleOpenEdit = (cliente) => {
    setEditingCliente(cliente);
    setFormData({
      patente: cliente.patente || '',
      nombres: cliente.nombres || '',
      telefono: cliente.telefono || '',
      vehiculo: cliente.vehiculo || '',
      dia_pago: cliente.dia_pago || 5,
      fecha_vencimiento: cliente.fecha_vencimiento ? cliente.fecha_vencimiento.slice(0, 10) : '',
      monto_plan: cliente.monto_plan || 0
    });
    setFormErrors({});
    setModalOpen(true);
  };

  // Validar y guardar abonado
  const handleSubmitForm = async (e) => {
    e.preventDefault();
    const errors = {};

    const cleanPatente = formData.patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (!cleanPatente || cleanPatente.length < 6 || cleanPatente.length > 8) {
      errors.patente = 'La matrícula debe tener entre 6 y 8 caracteres alfanuméricos.';
    }

    if (!formData.nombres.trim()) {
      errors.nombres = 'El nombre completo es obligatorio.';
    }

    const diaNum = parseInt(formData.dia_pago, 10);
    if (isNaN(diaNum) || diaNum < 1 || diaNum > 31) {
      errors.dia_pago = 'El día de cobro debe estar entre 1 y 31.';
    }

    if (!formData.fecha_vencimiento) {
      errors.fecha_vencimiento = 'La fecha de vencimiento es obligatoria.';
    }

    const montoNum = parseFloat(formData.monto_plan);
    if (isNaN(montoNum) || montoNum < 0) {
      errors.monto_plan = 'El monto del plan debe ser un número mayor o igual a 0.';
    }

    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        patente: cleanPatente,
        nombres: formData.nombres.trim(),
        telefono: formData.telefono.trim(),
        vehiculo: formData.vehiculo.trim(),
        dia_pago: diaNum,
        fecha_vencimiento: formData.fecha_vencimiento,
        monto_plan: montoNum
      };

      if (editingCliente) {
        if (window.api?.clientes?.actualizar) {
          await window.api.clientes.actualizar(editingCliente.id, payload);
        }
        showToast(`Abonado ${cleanPatente} actualizado con éxito`, 'success');
      } else {
        if (window.api?.clientes?.crear) {
          await window.api.clientes.crear(payload);
        }
        showToast(`Abonado ${cleanPatente} registrado correctamente`, 'success');
      }

      setModalOpen(false);
      await cargarClientes();
      await refrescarDatos();
    } catch (err) {
      console.error('Error guardando abonado:', err);
      showToast(err.message || 'Error al guardar abonado', 'error');
    } finally {
      setSubmitting(false);
    }
  };

  // Renovar mes (adelanta fecha y registra cobro)
  const handleRenovarMes = async (cliente) => {
    try {
      setRenewingId(cliente.id);
      if (window.api?.clientes?.renovar) {
        const res = await window.api.clientes.renovar(cliente.id, 1, 'EFECTIVO');
        showToast(
          res.mensaje || `Abono de ${cliente.nombres} renovado por 1 mes y cobro registrado`,
          'success'
        );
      }
      await cargarClientes();
      await refrescarDatos();
    } catch (err) {
      console.error('Error renovando abono:', err);
      showToast(err.message || 'Error al renovar abono', 'error');
    } finally {
      setRenewingId(null);
    }
  };

  // Confirmar eliminación
  const handleConfirmDelete = async () => {
    if (!deleteCandidate) return;
    try {
      if (window.api?.clientes?.eliminar) {
        await window.api.clientes.eliminar(deleteCandidate.id);
      }
      showToast(`Cliente ${deleteCandidate.patente} eliminado del sistema`, 'info');
      setDeleteCandidate(null);
      await cargarClientes();
      await refrescarDatos();
    } catch (err) {
      console.error('Error eliminando cliente:', err);
      showToast(err.message || 'Error al eliminar cliente', 'error');
    }
  };

  return (
    <div className="space-y-6">
      {/* 1. FILA DE 4 TARJETAS KPI EN GRIS NEUTRO */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* KPI 1: Planes Activos con indicador sutil en verde tenue */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Planes Activos
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <UserCheck className="w-4 h-4 text-slate-700" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight flex items-center gap-2">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-100 shrink-0" />
              <span>{loading ? '...' : kpis.activos}</span>
            </div>
            <div className="text-xs text-slate-500">
              Contratos al día con acceso habilitado
            </div>
          </div>
        </div>

        {/* KPI 2: Planes Vencidos con indicador sutil en rojo/ámbar tenue */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Planes Vencidos
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <UserX className="w-4 h-4 text-slate-700" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight flex items-center gap-2">
              <span className="w-2.5 h-2.5 rounded-full bg-rose-500 ring-4 ring-rose-100 shrink-0" />
              <span>{loading ? '...' : kpis.vencidos}</span>
            </div>
            <div className="text-xs text-slate-500">
              Requieren renovación o regularización
            </div>
          </div>
        </div>

        {/* KPI 3: Total de Abonados registrados */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Total Abonados
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <Users className="w-4 h-4 text-slate-700" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {loading ? '...' : kpis.total}
            </div>
            <div className="text-xs text-slate-500">
              Clientes mensuales en base de datos
            </div>
          </div>
        </div>

        {/* KPI 4: Ingresos Mensuales Proyectados */}
        <div className="bg-white border border-slate-200/90 rounded-xl p-4.5 shadow-card hover:border-slate-300 transition-colors">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              Ingresos Proyectados
            </span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
              <TrendingUp className="w-4 h-4 text-slate-700" />
            </div>
          </div>
          <div className="space-y-1">
            <div className="text-2xl sm:text-3xl font-bold font-mono-numbers text-slate-900 tracking-tight">
              {loading ? '...' : kpis.proyectadoFormateado}
            </div>
            <div className="text-xs text-slate-500">
              Facturación estimada recurrente
            </div>
          </div>
        </div>
      </div>

      {/* 2. BARRA DE HERRAMIENTAS: BÚSQUEDA, FILTRO POR ESTADO Y BOTÓN NUEVO */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto flex-1">
          {/* Buscador */}
          <div className="relative w-full sm:w-72">
            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Buscar por matrícula o nombre..."
              className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all"
            />
          </div>

          {/* Filtro por estado */}
          <div className="flex items-center gap-1 p-1 bg-slate-100 rounded-lg w-full sm:w-auto">
            <button
              onClick={() => setStatusFilter('todos')}
              className={`flex-1 sm:flex-initial px-3 py-1.5 rounded-md text-xs font-medium transition-colors cursor-pointer ${
                statusFilter === 'todos'
                  ? 'bg-white text-slate-900 shadow-2xs font-semibold'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Todos ({kpis.total})
            </button>
            <button
              onClick={() => setStatusFilter('activos')}
              className={`flex-1 sm:flex-initial px-3 py-1.5 rounded-md text-xs font-medium transition-colors cursor-pointer ${
                statusFilter === 'activos'
                  ? 'bg-white text-emerald-800 shadow-2xs font-semibold'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Al día ({kpis.activos})
            </button>
            <button
              onClick={() => setStatusFilter('vencidos')}
              className={`flex-1 sm:flex-initial px-3 py-1.5 rounded-md text-xs font-medium transition-colors cursor-pointer ${
                statusFilter === 'vencidos'
                  ? 'bg-white text-rose-800 shadow-2xs font-semibold'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              Vencidos ({kpis.vencidos})
            </button>
          </div>
        </div>

        {/* Botón "+ Nuevo Abonado" */}
        <button
          onClick={handleOpenCreate}
          className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>+ Nuevo Abonado</span>
        </button>
      </div>

      {/* 3. TABLA DE CLIENTES ABONADOS */}
      <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                <th className="py-3 px-4">Matrícula</th>
                <th className="py-3 px-4">Nombre Completo</th>
                <th className="py-3 px-4">Teléfono</th>
                <th className="py-3 px-4">Vehículo</th>
                <th className="py-3 px-4 text-center">Día Cobro</th>
                <th className="py-3 px-4">Próximo Vencimiento</th>
                <th className="py-3 px-4 text-right">Monto Plan</th>
                <th className="py-3 px-4 text-center">Estado</th>
                <th className="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan="9" className="py-12 text-center text-slate-400">
                    <RefreshCw className="w-5 h-5 mx-auto animate-spin mb-2 text-slate-400" />
                    <span>Cargando cartera de clientes abonados...</span>
                  </td>
                </tr>
              ) : clientesFiltrados.length === 0 ? (
                <tr>
                  <td colSpan="9" className="py-12 text-center text-slate-400">
                    <Users className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                    <p className="font-medium text-slate-600">No se encontraron clientes abonados</p>
                    <p className="text-[11px] text-slate-400 mt-1">
                      {searchTerm
                        ? 'Intenta con otro término de búsqueda o cambia el filtro.'
                        : 'Utiliza el botón "+ Nuevo Abonado" para registrar el primer cliente.'}
                    </p>
                  </td>
                </tr>
              ) : (
                clientesFiltrados.map((cliente) => {
                  const isActivo = cliente.esActivo || cliente.estado === 'ACTIVO';
                  const isRenewing = renewingId === cliente.id;

                  return (
                    <tr
                      key={cliente.id}
                      className="hover:bg-slate-50/80 transition-colors"
                    >
                      {/* Matrícula */}
                      <td className="py-3 px-4 font-mono font-bold text-slate-900 tracking-wider">
                        <span className="inline-block px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-800 text-[11px]">
                          {cliente.patente}
                        </span>
                      </td>

                      {/* Nombre completo */}
                      <td className="py-3 px-4 font-medium text-slate-900">
                        {cliente.nombres}
                      </td>

                      {/* Teléfono */}
                      <td className="py-3 px-4 text-slate-600 font-mono text-[11px]">
                        {cliente.telefono ? (
                          <span className="flex items-center gap-1">
                            <Phone className="w-3 h-3 text-slate-400 shrink-0" />
                            <span>{cliente.telefono}</span>
                          </span>
                        ) : (
                          <span className="text-slate-300">—</span>
                        )}
                      </td>

                      {/* Vehículo */}
                      <td className="py-3 px-4 text-slate-600">
                        {cliente.vehiculo ? (
                          <span className="flex items-center gap-1.5">
                            <Car className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                            <span className="truncate max-w-[140px]">{cliente.vehiculo}</span>
                          </span>
                        ) : (
                          <span className="text-slate-400 italic">No especificado</span>
                        )}
                      </td>

                      {/* Día de cobro (1 al 31) */}
                      <td className="py-3 px-4 text-center font-mono-numbers text-slate-700">
                        <span className="inline-block px-2 py-0.5 rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">
                          Día {cliente.dia_pago || 5}
                        </span>
                      </td>

                      {/* Próximo Vencimiento */}
                      <td className="py-3 px-4 text-slate-700 font-mono-numbers">
                        <span className="flex items-center gap-1.5 text-xs">
                          <Calendar className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                          <span>{cliente.fecha_vencimiento || '—'}</span>
                        </span>
                      </td>

                      {/* Monto Plan */}
                      <td className="py-3 px-4 text-right font-bold text-slate-900 font-mono-numbers">
                        {cliente.monto_plan_formateado || formatMoney(cliente.monto_plan)}
                      </td>

                      {/* Estado: Al día o Vencido */}
                      <td className="py-3 px-4 text-center">
                        {isActivo ? (
                          <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0" />
                            <span>Al día</span>
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200/80">
                            <span className="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0" />
                            <span>Vencido</span>
                          </span>
                        )}
                      </td>

                      {/* Acciones: Renovar Mes, Editar, Eliminar */}
                      <td className="py-3 px-4 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          {/* Botón Renovar Mes */}
                          <button
                            onClick={() => handleRenovarMes(cliente)}
                            disabled={isRenewing}
                            title="Adelanta fecha de vencimiento 1 mes y registra el cobro"
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 font-medium text-[11px] transition-colors cursor-pointer disabled:opacity-50"
                          >
                            <RefreshCw className={`w-3 h-3 ${isRenewing ? 'animate-spin' : ''}`} />
                            <span>Renovar Mes</span>
                          </button>

                          {/* Botón Editar */}
                          <button
                            onClick={() => handleOpenEdit(cliente)}
                            title="Editar datos del abonado"
                            className="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors cursor-pointer"
                          >
                            <Edit2 className="w-3.5 h-3.5" />
                          </button>

                          {/* Botón Eliminar */}
                          <button
                            onClick={() => setDeleteCandidate(cliente)}
                            title="Eliminar cliente"
                            className="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* 4. MODAL CREAR / EDITAR ABONADO */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-lg shadow-xl overflow-hidden animate-scaleUp">
            {/* Modal Header */}
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                  <Users className="w-4 h-4 text-slate-700" />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    {editingCliente ? 'Editar Cliente Abonado' : 'Registrar Nuevo Cliente Abonado'}
                  </h3>
                  <p className="text-[11px] text-slate-500">
                    Vigencia y mensualidad en base de datos SQLite
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
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Patente */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Matrícula / Patente *
                  </label>
                  <input
                    type="text"
                    value={formData.patente}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, patente: e.target.value.toUpperCase() }))
                    }
                    placeholder="Ej: AA1001 o 1234BBB"
                    maxLength={8}
                    className={`w-full px-3 py-2 text-xs rounded-lg border font-mono font-bold tracking-wider uppercase ${
                      formErrors.patente
                        ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                        : 'border-slate-200 bg-slate-50 focus:bg-white'
                    } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                  />
                  {formErrors.patente && (
                    <p className="text-[10px] text-rose-600 mt-1">{formErrors.patente}</p>
                  )}
                </div>

                {/* Día de cobro */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Día de Cobro Mensual (1-31) *
                  </label>
                  <input
                    type="number"
                    min="1"
                    max="31"
                    value={formData.dia_pago}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, dia_pago: e.target.value }))
                    }
                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers"
                  />
                  {formErrors.dia_pago && (
                    <p className="text-[10px] text-rose-600 mt-1">{formErrors.dia_pago}</p>
                  )}
                </div>
              </div>

              {/* Nombre Completo */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nombre Completo del Titular *
                </label>
                <input
                  type="text"
                  value={formData.nombres}
                  onChange={(e) =>
                    setFormData((p) => ({ ...p, nombres: e.target.value }))
                  }
                  placeholder="Ej: Juan Carlos Pérez"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    formErrors.nombres
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {formErrors.nombres && (
                  <p className="text-[10px] text-rose-600 mt-1">{formErrors.nombres}</p>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Teléfono */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Teléfono de Contacto
                  </label>
                  <input
                    type="text"
                    value={formData.telefono}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, telefono: e.target.value }))
                    }
                    placeholder="Ej: +56 9 1234 5678"
                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono text-[11px]"
                  />
                </div>

                {/* Vehículo */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Vehículo (Marca / Modelo)
                  </label>
                  <input
                    type="text"
                    value={formData.vehiculo}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, vehiculo: e.target.value }))
                    }
                    placeholder="Ej: Hyundai Tucson 2022"
                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Fecha de Vencimiento */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Fecha Próximo Vencimiento *
                  </label>
                  <input
                    type="date"
                    value={formData.fecha_vencimiento}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, fecha_vencimiento: e.target.value }))
                    }
                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers"
                  />
                  {formErrors.fecha_vencimiento && (
                    <p className="text-[10px] text-rose-600 mt-1">{formErrors.fecha_vencimiento}</p>
                  )}
                </div>

                {/* Monto del Plan Mensual */}
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Monto Plan Mensual *
                  </label>
                  <input
                    type="number"
                    step="any"
                    value={formData.monto_plan}
                    onChange={(e) =>
                      setFormData((p) => ({ ...p, monto_plan: e.target.value }))
                    }
                    placeholder="45000"
                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-mono-numbers font-semibold"
                  />
                  {formErrors.monto_plan && (
                    <p className="text-[10px] text-rose-600 mt-1">{formErrors.monto_plan}</p>
                  )}
                </div>
              </div>

              {/* Botones de acción del Modal */}
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
                  {submitting ? 'Guardando...' : editingCliente ? 'Guardar Cambios' : 'Crear Abonado'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 5. MODAL DE CONFIRMACIÓN DE ELIMINACIÓN */}
      {deleteCandidate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-sm shadow-xl p-6 text-center space-y-4 animate-scaleUp">
            <div className="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6 text-rose-600" />
            </div>

            <div className="space-y-1">
              <h4 className="text-sm font-bold text-slate-900">
                ¿Eliminar cliente abonado?
              </h4>
              <p className="text-xs text-slate-500">
                Se removerá a <strong>{deleteCandidate.nombres}</strong> ({deleteCandidate.patente}) de la base de datos de abonados. Esta acción no se puede deshacer.
              </p>
            </div>

            <div className="flex items-center justify-center gap-2 pt-2">
              <button
                onClick={() => setDeleteCandidate(null)}
                className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
              >
                Cancelar
              </button>
              <button
                onClick={handleConfirmDelete}
                className="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 shadow-xs transition-colors cursor-pointer"
              >
                Sí, eliminar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SubscribersTab;
