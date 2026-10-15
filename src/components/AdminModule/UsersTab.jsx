import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Users,
  ShieldCheck,
  UserCheck,
  User,
  Plus,
  RefreshCw,
  KeyRound,
  Trash2,
  Lock,
  X,
  AlertTriangle,
  Calendar,
  Check,
  ShieldAlert
} from 'lucide-react';

export const UsersTab = () => {
  const { showToast } = useApp();

  const [usuarios, setUsuarios] = useState([]);
  const [loading, setLoading] = useState(true);

  // Modales
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [resetModalOpen, setResetModalOpen] = useState(false);
  const [selectedUserForReset, setSelectedUserForReset] = useState(null);
  const [deleteCandidate, setDeleteCandidate] = useState(null);

  // Formulario Crear Usuario
  const [createForm, setCreateForm] = useState({
    usuario: '',
    password: '',
    confirmPassword: '',
    rol: 'operador'
  });
  const [createErrors, setCreateErrors] = useState({});
  const [submittingCreate, setSubmittingCreate] = useState(false);

  // Formulario Reset Password
  const [resetForm, setResetForm] = useState({
    newPassword: '',
    confirmNewPassword: ''
  });
  const [resetErrors, setResetErrors] = useState({});
  const [submittingReset, setSubmittingReset] = useState(false);

  // Cargar usuarios desde SQLite
  const cargarUsuarios = useCallback(async () => {
    setLoading(true);
    try {
      if (typeof window !== 'undefined' && window.api?.usuarios?.listar) {
        const data = await window.api.usuarios.listar();
        setUsuarios(data || []);
      } else if (typeof window !== 'undefined' && window.api?.getUsuarios) {
        const data = await window.api.getUsuarios();
        setUsuarios(data || []);
      }
    } catch (err) {
      console.error('Error cargando usuarios:', err);
      showToast('Error cargando usuarios: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [showToast]);

  useEffect(() => {
    cargarUsuarios();
  }, [cargarUsuarios]);

  // Manejar apertura de modal crear
  const handleOpenCreate = () => {
    setCreateForm({
      usuario: '',
      password: '',
      confirmPassword: '',
      rol: 'operador'
    });
    setCreateErrors({});
    setCreateModalOpen(true);
  };

  // Enviar creación de usuario
  const handleSubmitCreate = async (e) => {
    e.preventDefault();
    const errors = {};

    const cleanUser = createForm.usuario.trim().toLowerCase();
    if (!cleanUser || cleanUser.length < 3) {
      errors.usuario = 'El nombre de usuario debe tener al menos 3 caracteres.';
    }

    if (!createForm.password || createForm.password.length < 4) {
      errors.password = 'La contraseña debe tener al menos 4 caracteres.';
    }

    if (createForm.password !== createForm.confirmPassword) {
      errors.confirmPassword = 'Las contraseñas no coinciden.';
    }

    if (Object.keys(errors).length > 0) {
      setCreateErrors(errors);
      return;
    }

    setSubmittingCreate(true);
    try {
      if (window.api?.usuarios?.crear) {
        await window.api.usuarios.crear({
          usuario: cleanUser,
          password: createForm.password,
          rol: createForm.rol
        });
      }
      showToast(`Usuario '${cleanUser}' creado exitosamente`, 'success');
      setCreateModalOpen(false);
      await cargarUsuarios();
    } catch (err) {
      console.error('Error creando usuario:', err);
      showToast(err.message || 'Error al crear usuario', 'error');
    } finally {
      setSubmittingCreate(false);
    }
  };

  // Manejar apertura de modal reset password
  const handleOpenReset = (usuario) => {
    setSelectedUserForReset(usuario);
    setResetForm({
      newPassword: '',
      confirmNewPassword: ''
    });
    setResetErrors({});
    setResetModalOpen(true);
  };

  // Enviar reset password
  const handleSubmitReset = async (e) => {
    e.preventDefault();
    const errors = {};

    if (!resetForm.newPassword || resetForm.newPassword.length < 4) {
      errors.newPassword = 'La nueva contraseña debe tener al menos 4 caracteres.';
    }

    if (resetForm.newPassword !== resetForm.confirmNewPassword) {
      errors.confirmNewPassword = 'Las contraseñas no coinciden.';
    }

    if (Object.keys(errors).length > 0) {
      setResetErrors(errors);
      return;
    }

    setSubmittingReset(true);
    try {
      if (window.api?.usuarios?.resetPassword) {
        const res = await window.api.usuarios.resetPassword({
          id: selectedUserForReset.id,
          newPassword: resetForm.newPassword
        });
        showToast(res.mensaje || 'Contraseña actualizada con éxito', 'success');
      }
      setResetModalOpen(false);
    } catch (err) {
      console.error('Error restableciendo contraseña:', err);
      showToast(err.message || 'Error al restablecer contraseña', 'error');
    } finally {
      setSubmittingReset(false);
    }
  };

  // Eliminar usuario
  const handleConfirmDelete = async () => {
    if (!deleteCandidate) return;
    try {
      if (window.api?.usuarios?.eliminar) {
        await window.api.usuarios.eliminar(deleteCandidate.id);
      }
      showToast(`Usuario '${deleteCandidate.usuario}' eliminado`, 'info');
      setDeleteCandidate(null);
      await cargarUsuarios();
    } catch (err) {
      console.error('Error eliminando usuario:', err);
      showToast(err.message || 'No se pudo eliminar el usuario', 'error');
    }
  };

  return (
    <div className="space-y-6">
      {/* Barra de Encabezado */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-4 shadow-card flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <h2 className="text-sm font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Users className="w-4 h-4 text-slate-700" />
            <span>Usuarios y Control de Acceso al Sistema (usuarios)</span>
          </h2>
          <p className="text-xs text-slate-500 mt-0.5">
            Gestión de credenciales con hashing seguro SHA-256 y segmentación de permisos operativos.
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>+ Nuevo Usuario</span>
        </button>
      </div>

      {/* Tarjetas Informativas de Roles */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {/* Rol: Administrador */}
        <div className="p-4 rounded-xl bg-white border border-slate-200/90 shadow-card flex items-start gap-3">
          <div className="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
            <ShieldCheck className="w-4 h-4" />
          </div>
          <div className="space-y-1 text-xs">
            <div className="font-bold text-slate-900 flex items-center gap-2">
              <span>Rol: Administrador</span>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                Acceso Total
              </span>
            </div>
            <p className="text-slate-500 text-[11px] leading-relaxed">
              Gestión integral de precios, tarifas por minuto, metas mensuales, auditoría de arqueos y administración de cuentas.
            </p>
          </div>
        </div>

        {/* Rol: Operador */}
        <div className="p-4 rounded-xl bg-white border border-slate-200/90 shadow-card flex items-start gap-3">
          <div className="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 border border-slate-200">
            <User className="w-4 h-4" />
          </div>
          <div className="space-y-1 text-xs">
            <div className="font-bold text-slate-900 flex items-center gap-2">
              <span>Rol: Operador</span>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                Caseta & Cobros
              </span>
            </div>
            <p className="text-slate-500 text-[11px] leading-relaxed">
              Restringido a la operación en garita: registro de ingresos, cálculo automático de cobros, egresos y corte de caja de turno.
            </p>
          </div>
        </div>
      </div>

      {/* Tabla de Usuarios */}
      <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse text-xs">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                <th className="py-3 px-4 w-20">ID</th>
                <th className="py-3 px-4">Nombre de Usuario</th>
                <th className="py-3 px-4">Rol Asignado</th>
                <th className="py-3 px-4">Fecha de Creación</th>
                <th className="py-3 px-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-slate-400">
                    <RefreshCw className="w-5 h-5 mx-auto animate-spin mb-2 text-slate-400" />
                    <span>Cargando usuarios registrados...</span>
                  </td>
                </tr>
              ) : usuarios.length === 0 ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-slate-400">
                    <Users className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                    <p className="font-medium text-slate-600">No hay usuarios registrados</p>
                  </td>
                </tr>
              ) : (
                usuarios.map((u) => {
                  const isAdmin = u.rol === 'admin' || u.rol === 'administrador';

                  return (
                    <tr key={u.id} className="hover:bg-slate-50/80 transition-colors">
                      {/* ID */}
                      <td className="py-3 px-4 font-mono font-bold text-slate-400">
                        #{u.id}
                      </td>

                      {/* Usuario */}
                      <td className="py-3 px-4 font-bold text-slate-900">
                        <div className="flex items-center gap-2">
                          <span className="font-mono">{u.usuario}</span>
                        </div>
                      </td>

                      {/* Rol */}
                      <td className="py-3 px-4">
                        {isAdmin ? (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-900 text-white shadow-2xs">
                            <ShieldCheck className="w-3 h-3 text-emerald-400" />
                            <span>Administrador</span>
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            <User className="w-3 h-3 text-slate-400" />
                            <span>Operador</span>
                          </span>
                        )}
                      </td>

                      {/* Fecha de Creación */}
                      <td className="py-3 px-4 text-slate-600 font-mono-numbers">
                        <span className="flex items-center gap-1.5 text-xs">
                          <Calendar className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                          <span>{u.fecha_creacion || '—'}</span>
                        </span>
                      </td>

                      {/* Acciones */}
                      <td className="py-3 px-4 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          {/* Botón Restablecer Contraseña */}
                          <button
                            onClick={() => handleOpenReset(u)}
                            title="Restablecer contraseña con hash seguro"
                            className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 font-medium text-[11px] transition-colors cursor-pointer"
                          >
                            <KeyRound className="w-3 h-3" />
                            <span>Cambiar Clave</span>
                          </button>

                          {/* Botón Eliminar */}
                          <button
                            onClick={() => setDeleteCandidate(u)}
                            title="Eliminar usuario"
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

      {/* MODAL CREAR USUARIO */}
      {createModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-md shadow-xl overflow-hidden animate-scaleUp">
            {/* Header */}
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                  <UserCheck className="w-4 h-4 text-slate-700" />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    Registrar Nuevo Usuario
                  </h3>
                  <p className="text-[11px] text-slate-500">
                    Alta de credenciales en base de datos SQLite
                  </p>
                </div>
              </div>
              <button
                onClick={() => setCreateModalOpen(false)}
                className="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmitCreate} className="p-6 space-y-4">
              {/* Nombre de Usuario */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nombre de Usuario (Login) *
                </label>
                <input
                  type="text"
                  value={createForm.usuario}
                  onChange={(e) =>
                    setCreateForm((p) => ({ ...p, usuario: e.target.value.toLowerCase() }))
                  }
                  placeholder="ej: operador1"
                  className={`w-full px-3 py-2 text-xs rounded-lg border font-mono ${
                    createErrors.usuario
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {createErrors.usuario && (
                  <p className="text-[10px] text-rose-600 mt-1">{createErrors.usuario}</p>
                )}
              </div>

              {/* Rol */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Rol y Nivel de Acceso *
                </label>
                <select
                  value={createForm.rol}
                  onChange={(e) =>
                    setCreateForm((p) => ({ ...p, rol: e.target.value }))
                  }
                  className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 font-medium"
                >
                  <option value="operador">Operador (Restringido a caseta/operaciones)</option>
                  <option value="admin">Administrador (Acceso total a configuración y metas)</option>
                </select>
              </div>

              {/* Contraseña Inicial */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Contraseña Inicial *
                </label>
                <input
                  type="password"
                  value={createForm.password}
                  onChange={(e) =>
                    setCreateForm((p) => ({ ...p, password: e.target.value }))
                  }
                  placeholder="Mínimo 4 caracteres"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    createErrors.password
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {createErrors.password && (
                  <p className="text-[10px] text-rose-600 mt-1">{createErrors.password}</p>
                )}
              </div>

              {/* Confirmar Contraseña */}
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Confirmar Contraseña *
                </label>
                <input
                  type="password"
                  value={createForm.confirmPassword}
                  onChange={(e) =>
                    setCreateForm((p) => ({ ...p, confirmPassword: e.target.value }))
                  }
                  placeholder="Repite la contraseña"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    createErrors.confirmPassword
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {createErrors.confirmPassword && (
                  <p className="text-[10px] text-rose-600 mt-1">{createErrors.confirmPassword}</p>
                )}
              </div>

              {/* Acciones */}
              <div className="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setCreateModalOpen(false)}
                  className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={submittingCreate}
                  className="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                >
                  {submittingCreate ? 'Creando...' : 'Crear Usuario'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL RESTABLECER CONTRASEÑA */}
      {resetModalOpen && selectedUserForReset && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-sm shadow-xl overflow-hidden animate-scaleUp">
            {/* Header */}
            <div className="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
              <div className="flex items-center gap-2">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700">
                  <KeyRound className="w-4 h-4 text-slate-700" />
                </div>
                <div>
                  <h3 className="text-sm font-bold text-slate-900">
                    Cambiar Contraseña
                  </h3>
                  <p className="text-[11px] text-slate-500 font-mono">
                    Usuario: {selectedUserForReset.usuario}
                  </p>
                </div>
              </div>
              <button
                onClick={() => setResetModalOpen(false)}
                className="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmitReset} className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Nueva Contraseña *
                </label>
                <input
                  type="password"
                  value={resetForm.newPassword}
                  onChange={(e) =>
                    setResetForm((p) => ({ ...p, newPassword: e.target.value }))
                  }
                  placeholder="Mínimo 4 caracteres"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    resetErrors.newPassword
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {resetErrors.newPassword && (
                  <p className="text-[10px] text-rose-600 mt-1">{resetErrors.newPassword}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Confirmar Nueva Contraseña *
                </label>
                <input
                  type="password"
                  value={resetForm.confirmNewPassword}
                  onChange={(e) =>
                    setResetForm((p) => ({ ...p, confirmNewPassword: e.target.value }))
                  }
                  placeholder="Repite la nueva contraseña"
                  className={`w-full px-3 py-2 text-xs rounded-lg border ${
                    resetErrors.confirmNewPassword
                      ? 'border-rose-400 bg-rose-50/50 text-rose-900'
                      : 'border-slate-200 bg-slate-50 focus:bg-white'
                  } focus:outline-none focus:ring-2 focus:ring-slate-900`}
                />
                {resetErrors.confirmNewPassword && (
                  <p className="text-[10px] text-rose-600 mt-1">{resetErrors.confirmNewPassword}</p>
                )}
              </div>

              {/* Acciones */}
              <div className="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setResetModalOpen(false)}
                  className="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={submittingReset}
                  className="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                >
                  {submittingReset ? 'Actualizando...' : 'Actualizar Clave'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL CONFIRMAR ELIMINACIÓN */}
      {deleteCandidate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
          <div className="bg-white border border-slate-200 rounded-2xl w-full max-w-sm shadow-xl p-6 text-center space-y-4 animate-scaleUp">
            <div className="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6 text-rose-600" />
            </div>

            <div className="space-y-1">
              <h4 className="text-sm font-bold text-slate-900">
                ¿Eliminar usuario del sistema?
              </h4>
              <p className="text-xs text-slate-500">
                Se revocará el acceso al usuario <strong>{deleteCandidate.usuario}</strong> ({deleteCandidate.rol}). Si es el único administrador del sistema, la acción será rechazada.
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

export default UsersTab;
