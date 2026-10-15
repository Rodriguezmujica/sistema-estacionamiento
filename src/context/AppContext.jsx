import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

// Contexto global del sistema de estacionamiento
const AppContext = createContext(null);

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp debe ser utilizado dentro de un AppProvider');
  }
  return context;
};

// Fallback seguro en caso de prueba fuera del contenedor Electron
const isElectron = typeof window !== 'undefined' && Boolean(window.api);

export const AppProvider = ({ children }) => {
  const [config, setConfig] = useState({
    moneda_simbolo: '$',
    moneda_codigo: 'CLP',
    decimales: 0,
    activar_redondeo_chile: 1,
    precio_minuto: 35,
    precio_minimo: 500
  });
  const [perfilActivo, setPerfilActivo] = useState('chile');
  const [resumen, setResumen] = useState({
    autosEstacionados: 0,
    lavadosPendientes: 0,
    clientesAbonados: 0,
    recaudadoHoy: 0,
    recaudadoHoyFormateado: '$0'
  });
  const [vehiculos, setVehiculos] = useState([]);
  const [lavados, setLavados] = useState([]);
  const [tiposIngreso, setTiposIngreso] = useState([]);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [appInfo, setAppInfo] = useState(null);
  const [loading, setLoading] = useState(true);

  // Estado de Navegación por Pestañas
  const [activeTab, setActiveTab] = useState('operaciones');

  // Estados de Modales
  const [checkoutVehicle, setCheckoutVehicle] = useState(null);
  const [virtualTicketData, setVirtualTicketData] = useState(null);
  const [isCashClosingOpen, setIsCashClosingOpen] = useState(false);
  const [isModifyTicketOpen, setIsModifyTicketOpen] = useState(false);
  const [isReprintModalOpen, setIsReprintModalOpen] = useState(false);

  // Sistema de notificación Toast sobrio
  const [toast, setToast] = useState(null);

  const showToast = useCallback((message, type = 'info') => {
    setToast({ message, type });
    setTimeout(() => {
      setToast(null);
    }, 4000);
  }, []);

  // Reloj en vivo (1 segundo)
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Carga de datos principales desde Electron IPC
  const refrescarDatos = useCallback(async () => {
    if (!isElectron) {
      console.warn('Ejecutando en entorno Web estándar (sin window.api)');
      setLoading(false);
      return;
    }

    try {
      const [cfg, res, activos, tipos, info, colaLavados] = await Promise.all([
        window.api.getConfig ? window.api.getConfig() : window.api.config.get(),
        window.api.getResumen(),
        window.api.getIngresosActivos(),
        window.api.getTiposIngreso(),
        window.api.getAppInfo ? window.api.getAppInfo() : null,
        window.api.lavados ? window.api.lavados.listar() : (window.api.getLavadosPendientes ? window.api.getLavadosPendientes() : [])
      ]);

      if (cfg) {
        setConfig(cfg);
        setPerfilActivo(cfg.moneda_codigo === 'EUR' ? 'espana' : 'chile');
      }
      if (res) setResumen(res);
      if (activos) setVehiculos(activos);
      if (tipos) setTiposIngreso(tipos);
      if (info) setAppInfo(info);
      if (colaLavados) setLavados(colaLavados);
    } catch (err) {
      console.error('Error al sincronizar datos desde IPC:', err);
      showToast('Error de comunicación con base de datos: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [showToast]);

  // Carga inicial y sondeo periódico suave cada 30 segundos
  useEffect(() => {
    refrescarDatos();
    const interval = setInterval(refrescarDatos, 30000);
    return () => clearInterval(interval);
  }, [refrescarDatos]);

  // Cambiar entre perfil Chile y España
  const cambiarPerfil = async (nuevoPerfil) => {
    if (!isElectron) {
      setPerfilActivo(nuevoPerfil);
      return;
    }
    try {
      setLoading(true);
      const res = window.api.setConfigProfile 
        ? await window.api.setConfigProfile(nuevoPerfil)
        : await window.api.config.setProfile(nuevoPerfil);

      if (res && res.config) {
        setConfig(res.config);
        setPerfilActivo(nuevoPerfil);
        await refrescarDatos();
        showToast(`Perfil cambiado a ${nuevoPerfil === 'chile' ? 'Chile ($ CLP)' : 'España (€ EUR)'}`, 'success');
      }
    } catch (err) {
      console.error('Error al cambiar perfil regional:', err);
      showToast('No se pudo cambiar el perfil regional', 'error');
    } finally {
      setLoading(false);
    }
  };

  // Cargar datos de demostración
  const cargarDemo = async () => {
    if (!isElectron) {
      showToast('Simulador demo (modo web)', 'info');
      return;
    }
    try {
      setLoading(true);
      const res = await window.api.seedDemoData();
      await refrescarDatos();
      showToast(res.mensaje || 'Datos demo sembrados con éxito', 'success');
    } catch (err) {
      console.error('Error al sembrar datos demo:', err);
      showToast('Error al cargar datos demo: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  // Registrar nuevo ingreso
  const registrarIngreso = async (datos) => {
    if (!isElectron) return;
    try {
      const res = window.api.registrarIngreso 
        ? await window.api.registrarIngreso(datos) 
        : await window.api.parking.registrarIngreso(datos);
      await refrescarDatos();
      showToast(`Vehículo ${res.patente} registrado exitosamente`, 'success');
      return res;
    } catch (err) {
      showToast(err.message || 'Error al registrar ingreso', 'error');
      throw err;
    }
  };

  // Calcular cobro para vista previa de salida
  const calcularCobro = async (criterio, metodoPago = 'EFECTIVO') => {
    if (!isElectron) return null;
    try {
      return window.api.calcularCobro
        ? await window.api.calcularCobro(criterio, metodoPago)
        : await window.api.parking.calcularCobro(criterio, metodoPago);
    } catch (err) {
      showToast(err.message || 'Error al calcular cobro', 'error');
      throw err;
    }
  };

  // Registrar salida definitiva y emitir ticket
  const registrarSalida = async (datos) => {
    if (!isElectron) return;
    try {
      const resultado = window.api.registrarSalida
        ? await window.api.registrarSalida(datos)
        : await window.api.parking.registrarSalida(datos);

      await refrescarDatos();
      // Cerramos checkout y abrimos el ticket virtual
      setCheckoutVehicle(null);
      setVirtualTicketData(resultado);
      showToast(`Cobro completado para ${resultado.patente}: ${resultado.totalFormateado}`, 'success');
      return resultado;
    } catch (err) {
      showToast(err.message || 'Error al procesar cobro', 'error');
      throw err;
    }
  };

  // Modificar ticket de ingreso activo
  const modificarIngreso = async (datos) => {
    if (!isElectron) {
      setVehiculos(prev => prev.map(v => {
        if (v.id === datos.idIngreso) {
          const servicio = tiposIngreso.find(t => t.idtipo_ingresos === datos.idtipo_ingreso);
          return {
            ...v,
            patente: datos.patente,
            idtipo_ingreso: datos.idtipo_ingreso,
            nombre_servicio: servicio ? servicio.nombre_servicio : v.nombre_servicio
          };
        }
        return v;
      }));
      showToast(`Ticket #${datos.idIngreso} actualizado: ${datos.patente}`, 'success');
      setIsModifyTicketOpen(false);
      return { success: true };
    }

    try {
      const res = window.api.modificarIngreso
        ? await window.api.modificarIngreso(datos)
        : await window.api.parking.modificarIngreso(datos);
      await refrescarDatos();
      showToast(res.mensaje || `Ticket #${datos.idIngreso} modificado con éxito`, 'success');
      setIsModifyTicketOpen(false);
      return res;
    } catch (err) {
      showToast(err.message || 'Error al modificar el ticket', 'error');
      throw err;
    }
  };

  // Reimprimir último ingreso registrado
  const reimprimirUltimoIngreso = async () => {
    if (!isElectron) {
      if (vehiculos.length > 0) {
        const v = vehiculos[0];
        setVirtualTicketData({
          tipoTicket: 'ingreso',
          idIngreso: v.id,
          id: v.id,
          patente: v.patente,
          nombre_servicio: v.nombre_servicio,
          fechaHoraIngreso: `${v.fechaLocal || ''} ${v.horaLocal || ''}`.trim() || new Date().toLocaleString(),
          precio_base_servicio: v.precio_base_servicio,
          configRegional: config
        });
        setIsReprintModalOpen(false);
        showToast(`Ticket de entrada para ${v.patente} preparado`, 'info');
      } else {
        showToast('No hay vehículos activos para reimprimir entrada', 'error');
      }
      return;
    }

    try {
      const ticket = window.api.getUltimoIngreso
        ? await window.api.getUltimoIngreso()
        : await window.api.parking.getUltimoIngreso();
      setVirtualTicketData(ticket);
      setIsReprintModalOpen(false);
      showToast(`Ticket de entrada #${ticket.idIngreso} (${ticket.patente}) listo para imprimir`, 'info');
      return ticket;
    } catch (err) {
      showToast(err.message || 'No se pudo obtener el último ingreso', 'error');
      throw err;
    }
  };

  // Reimprimir última salida cobrada
  const reimprimirUltimaSalida = async () => {
    if (!isElectron) {
      setVirtualTicketData({
        tipoTicket: 'salida',
        idSalida: 1042,
        id: 1042,
        patente: 'DEMO01',
        servicio: 'Estacionamiento por minuto',
        tiempo_estadia: '45 min',
        total: 1575,
        totalFormateado: formatMoney(1575),
        metodo_pago: 'EFECTIVO',
        montoRecibido: 2000,
        vuelto: 425,
        configRegional: config
      });
      setIsReprintModalOpen(false);
      showToast('Comprobante de pago demo listo para imprimir', 'info');
      return;
    }

    try {
      const ticket = window.api.getUltimaSalida
        ? await window.api.getUltimaSalida()
        : await window.api.parking.getUltimaSalida();
      setVirtualTicketData(ticket);
      setIsReprintModalOpen(false);
      showToast(`Comprobante de salida #${ticket.idSalida} (${ticket.patente}) listo para imprimir`, 'info');
      return ticket;
    } catch (err) {
      showToast(err.message || 'No se pudo obtener la última salida', 'error');
      throw err;
    }
  };

  // Operaciones del Módulo de Lavados (Fase 3.2)
  const cambiarEstadoLavado = async (idLavado, nuevoEstado) => {
    if (!isElectron) {
      setLavados(prev => prev.map(l => {
        if (l.id_lavado === idLavado || l.id === idLavado) {
          return {
            ...l,
            estado: nuevoEstado,
            timestamp_proceso: nuevoEstado === 'proceso' ? Date.now() : l.timestamp_proceso,
            timestamp_listo: nuevoEstado === 'listo' ? Date.now() : l.timestamp_listo
          };
        }
        return l;
      }));
      showToast(`Estado de bahía cambiado a '${nuevoEstado}'`, 'info');
      return { success: true };
    }

    try {
      const res = window.api.lavados?.cambiarEstado
        ? await window.api.lavados.cambiarEstado(idLavado, nuevoEstado)
        : await window.api.cambiarEstadoLavado(idLavado, nuevoEstado);
      await refrescarDatos();
      showToast(`Estado de bahía actualizado a '${nuevoEstado}'`, 'success');
      return res;
    } catch (err) {
      console.error('Error al cambiar estado de lavado:', err);
      showToast(err.message || 'Error al cambiar estado de bahía', 'error');
      throw err;
    }
  };

  const registrarLavado = async (datos) => {
    if (!isElectron) {
      const nuevoLavado = {
        id_lavado: Date.now(),
        id_ingreso: Date.now(),
        id: Date.now(),
        patente: datos.patente.toUpperCase(),
        motivos_extra_list: datos.motivos_extra || [],
        precio_extra: Number(datos.precio_extra) || 0,
        estado: 'espera',
        observaciones: datos.observaciones || '',
        vinculado_estadia: datos.vincularEstadia ? 1 : 0,
        nombre_servicio: datos.nombre_servicio || 'Lavado Estándar',
        precio_base_servicio: Number(datos.precio_base) || 12000,
        timestamp_ingreso: Date.now(),
        horaIngresoLocal: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        totalEstimado: (Number(datos.precio_base) || 12000) + (Number(datos.precio_extra) || 0),
        totalEstimadoFormateado: formatMoney((Number(datos.precio_base) || 12000) + (Number(datos.precio_extra) || 0))
      };
      setLavados(prev => [nuevoLavado, ...prev]);
      showToast(`Ingreso a lavado registrado: ${datos.patente}`, 'success');
      return nuevoLavado;
    }

    try {
      const res = window.api.lavados?.registrar
        ? await window.api.lavados.registrar(datos)
        : await window.api.registrarLavado(datos);
      await refrescarDatos();
      showToast(res.mensaje || `Lavado registrado para ${res.patente}`, 'success');
      return res;
    } catch (err) {
      console.error('Error al registrar orden de lavado:', err);
      showToast(err.message || 'Error al registrar orden de lavado', 'error');
      throw err;
    }
  };

  const getHistorialVehiculo = async (patente) => {
    if (!patente) return [];
    if (!isElectron) {
      return [
        {
          idIngreso: 101,
          patente: patente.toUpperCase(),
          servicio: 'Lavado Completo (Int/Ext)',
          fechaIngresoLocal: 'Ayer, 16:30',
          fechaSalidaLocal: 'Ayer, 17:45',
          tiempoEstadia: '75 min',
          totalFormateado: formatMoney(12000),
          metodoPago: 'EFECTIVO',
          observaciones: 'Sin novedades. Rayón leve en parachoques.',
          motivos_extra_list: ['Interior sucio']
        }
      ];
    }

    try {
      return window.api.lavados?.getHistorial
        ? await window.api.lavados.getHistorial(patente)
        : await window.api.getHistorialVehiculo(patente);
    } catch (err) {
      console.error('Error consultando historial de vehículo:', err);
      return [];
    }
  };

  // Formateador de moneda utilitario
  const formatMoney = (amount) => {
    const num = Number(amount) || 0;
    if (config.moneda_codigo === 'EUR') {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(num);
    }
    // Chile CLP
    return new Intl.NumberFormat('es-CL', {
      style: 'currency',
      currency: 'CLP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(num);
  };

  const value = {
    config,
    perfilActivo,
    resumen,
    vehiculos,
    lavados,
    tiposIngreso,
    currentTime,
    appInfo,
    loading,
    toast,
    showToast,
    cambiarPerfil,
    cargarDemo,
    refrescarDatos,
    registrarIngreso,
    calcularCobro,
    registrarSalida,
    formatMoney,
    // Operaciones de Lavados
    cambiarEstadoLavado,
    registrarLavado,
    getHistorialVehiculo,
    // Navegación por pestañas
    activeTab,
    setActiveTab,
    // Modificar y Reimprimir Tickets
    modificarIngreso,
    reimprimirUltimoIngreso,
    reimprimirUltimaSalida,
    // Modales
    checkoutVehicle,
    setCheckoutVehicle,
    virtualTicketData,
    setVirtualTicketData,
    isCashClosingOpen,
    setIsCashClosingOpen,
    isModifyTicketOpen,
    setIsModifyTicketOpen,
    isReprintModalOpen,
    setIsReprintModalOpen
  };

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>;
};
