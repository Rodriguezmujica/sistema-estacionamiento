import React, { useState, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { KpiCards } from './KpiCards';
import { ReportFilters } from './ReportFilters';
import { CategoryBreakdown } from './CategoryBreakdown';
import { OperationsHistoryTable } from './OperationsHistoryTable';
import { CashClosingPanel } from './CashClosingPanel';
import { ThermalZTicketModal } from './ThermalZTicketModal';
import {
  BarChart3,
  RefreshCw,
  Calendar,
  Layers,
  Printer
} from 'lucide-react';

export const ReportsModule = () => {
  const { config, formatMoney, showToast, vehiculos } = useApp();

  const hoyStr = new Date().toISOString().split('T')[0];

  // --------------------------------------------------------------------------
  // ESTADOS DEL MÓDULO
  // --------------------------------------------------------------------------
  const [metricas, setMetricas] = useState(null);
  const [reporteData, setReporteData] = useState({
    operaciones: [],
    categorias: [],
    totales: null
  });
  const [cierreZ, setCierreZ] = useState(null);
  const [fechaCierreZ, setFechaCierreZ] = useState(hoyStr);
  const [loading, setLoading] = useState(true);

  // Filtros de búsqueda y consulta
  const [filtros, setFiltros] = useState({
    desde: hoyStr,
    hasta: hoyStr,
    patente: '',
    periodoRapido: 'hoy'
  });

  // Modal de Ticket Térmico Z (Star BSC10)
  const [isThermalModalOpen, setIsThermalModalOpen] = useState(false);
  const [cuadraturaModalData, setCuadraturaModalData] = useState(null);

  // --------------------------------------------------------------------------
  // CARGA DE DATOS DESDE IPC O FALLBACK WEB
  // --------------------------------------------------------------------------
  const cargarDatos = useCallback(async () => {
    setLoading(true);
    const hasElectronApi = typeof window !== 'undefined' && Boolean(window.api);

    try {
      if (hasElectronApi) {
        // Consultas concurrentes optimizadas hacia SQLite
        const [resMetricas, resReporte, resCierre] = await Promise.all([
          window.api.getMetricasReportes
            ? window.api.getMetricasReportes()
            : window.api.reportes?.getMetricas(),
          window.api.getReporteOperaciones
            ? window.api.getReporteOperaciones({
                desde: filtros.desde,
                hasta: filtros.hasta,
                patente: filtros.patente
              })
            : window.api.reportes?.getOperaciones({
                desde: filtros.desde,
                hasta: filtros.hasta,
                patente: filtros.patente
              }),
          window.api.getCierreZ
            ? window.api.getCierreZ(fechaCierreZ)
            : (window.api.getCierreDiario
                ? window.api.getCierreDiario(fechaCierreZ)
                : window.api.caja?.getCierreDiario(fechaCierreZ))
        ]);

        if (resMetricas) setMetricas(resMetricas);
        if (resReporte) setReporteData(resReporte);
        if (resCierre) setCierreZ(resCierre);
      } else {
        // Fallback simulador para desarrollo fuera de Electron
        const totalOpsSimuladas = 6;
        const totalRecaudadoSimulado = 56000;

        setMetricas({
          facturacionDiaria: {
            total: 33000,
            totalFormateado: formatMoney(33000),
            operaciones: 3
          },
          mensualHabiles: {
            total: 145000,
            totalFormateado: formatMoney(145000),
            operaciones: 14
          },
          mensualTotal: {
            total: 182000,
            totalFormateado: formatMoney(182000),
            operaciones: 19
          },
          ocupacionActiva: {
            total: vehiculos?.length || 4
          }
        });

        setReporteData({
          operaciones: [
            {
              folio: 1042,
              fechaSalidaCorta: hoyStr,
              horaSalidaCorta: '18:45',
              patente: 'LAVA88',
              nombre_servicio: 'Lavado Completo (Int/Ext)',
              tiempo_estadia: '65 min',
              metodo_pago: 'EFECTIVO',
              total: 17000,
              totalFormateado: formatMoney(17000)
            },
            {
              folio: 1041,
              fechaSalidaCorta: hoyStr,
              horaSalidaCorta: '17:30',
              patente: 'TEST99',
              nombre_servicio: 'Lavado Completo (Int/Ext)',
              tiempo_estadia: '45 min',
              metodo_pago: 'TARJETA',
              total: 16000,
              totalFormateado: formatMoney(16000)
            },
            {
              folio: 1040,
              fechaSalidaCorta: hoyStr,
              horaSalidaCorta: '16:15',
              patente: 'HXPR22',
              nombre_servicio: 'Estacionamiento por minuto',
              tiempo_estadia: '75 min',
              metodo_pago: 'EFECTIVO',
              total: 2650,
              totalFormateado: formatMoney(2650)
            }
          ],
          categorias: [
            {
              categoria: 'Lavado Completo (Int/Ext)',
              cantidad: 2,
              subtotal: 33000,
              subtotalFormateado: formatMoney(33000),
              porcentaje: 92.5
            },
            {
              categoria: 'Estacionamiento por minuto',
              cantidad: 1,
              subtotal: 2650,
              subtotalFormateado: formatMoney(2650),
              porcentaje: 7.5
            }
          ],
          totales: {
            totalOperaciones: 3,
            totalRecaudado: 35650,
            totalRecaudadoFormateado: formatMoney(35650),
            totalEfectivo: 19650,
            totalEfectivoFormateado: formatMoney(19650),
            totalTarjeta: 16000,
            totalTarjetaFormateado: formatMoney(16000),
            totalTransferencia: 0,
            totalTransferenciaFormateado: formatMoney(0)
          }
        });

        setCierreZ({
          fecha: fechaCierreZ,
          totalTransacciones: 3,
          totalRecaudado: 35650,
          totalRecaudadoFormateado: formatMoney(35650),
          totalEfectivo: 19650,
          totalEfectivoFormateado: formatMoney(19650),
          totalTarjeta: 16000,
          totalTarjetaFormateado: formatMoney(16000),
          totalTransferencia: 0,
          totalTransferenciaFormateado: formatMoney(0),
          desgloseMetodos: [
            { metodo_pago: 'EFECTIVO', cantidad: 2, subtotal: 19650, subtotalFormateado: formatMoney(19650) },
            { metodo_pago: 'TARJETA', cantidad: 1, subtotal: 16000, subtotalFormateado: formatMoney(16000) }
          ],
          desgloseServicios: [
            { nombre_servicio: 'Lavado Completo (Int/Ext)', cantidad: 2, subtotal: 33000, subtotalFormateado: formatMoney(33000) },
            { nombre_servicio: 'Estacionamiento por minuto', cantidad: 1, subtotal: 2650, subtotalFormateado: formatMoney(2650) }
          ],
          vehiculosActivos: vehiculos?.length || 4
        });
      }
    } catch (err) {
      console.error('Error cargando reportes y analíticas:', err);
      showToast('Error cargando datos de reportes: ' + err.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [filtros, fechaCierreZ, formatMoney, showToast, vehiculos]);

  // Recarga al montar y al cambiar filtros
  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

  // Manejo de restablecer filtros
  const handleResetFiltros = () => {
    setFiltros({
      desde: hoyStr,
      hasta: hoyStr,
      patente: '',
      periodoRapido: 'hoy'
    });
  };

  // Apertura de modal de ticket Z con datos de conciliación física
  const handleGenerarTicketZ = (datos) => {
    setCuadraturaModalData(datos);
    setIsThermalModalOpen(true);
  };

  return (
    <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      {/* =================================================================== */}
      {/* 1. HEADER DEL MÓDULO                                                */}
      {/* =================================================================== */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 no-print">
        <div>
          <div className="flex items-center gap-2">
            <span className="p-2 rounded-xl bg-slate-900 text-white shadow-xs">
              <BarChart3 className="w-5 h-5 text-slate-100" />
            </span>
            <h1 className="text-xl font-bold text-slate-900 tracking-tight">
              Reportes & Analíticas de Recaudación
            </h1>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Métricas financieras en tiempo real, histórico de cobros, arqueo de caja Z y exportación de datos.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={cargarDatos}
            disabled={loading}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 shadow-2xs transition-all cursor-pointer disabled:opacity-50"
            title="Sincronizar métricas contables"
          >
            <RefreshCw className={`w-3.5 h-3.5 text-slate-500 ${loading ? 'animate-spin' : ''}`} />
            <span>Actualizar Datos</span>
          </button>
        </div>
      </div>

      {/* =================================================================== */}
      {/* 2. TARJETAS SUPERIORES DE MÉTRICAS FINANCIERAS (KPI CARDS)          */}
      {/* =================================================================== */}
      <div className="no-print">
        <KpiCards
          metricas={metricas}
          loading={loading}
          formatMoney={formatMoney}
        />
      </div>

      {/* =================================================================== */}
      {/* 3. BARRA DE CONTROL DE FILTROS                                      */}
      {/* =================================================================== */}
      <div className="no-print">
        <ReportFilters
          filtros={filtros}
          onFiltrosChange={setFiltros}
          onReset={handleResetFiltros}
          onRefresh={cargarDatos}
          loading={loading}
        />
      </div>

      {/* =================================================================== */}
      {/* 4. DESGLOSE POR CATEGORÍAS & PANEL DE CIERRE Z                      */}
      {/* =================================================================== */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch no-print">
        {/* Desglose agrupado por servicios */}
        <div className="lg:col-span-5 flex flex-col">
          <CategoryBreakdown
            categorias={reporteData.categorias}
            totalRecaudado={reporteData.totales?.totalRecaudado || 0}
            totalOperaciones={reporteData.totales?.totalOperaciones || 0}
            loading={loading}
            formatMoney={formatMoney}
          />
        </div>

        {/* Panel de Arqueo y Cierre Z */}
        <div className="lg:col-span-7 flex flex-col">
          <CashClosingPanel
            cierre={cierreZ}
            fechaSeleccionada={fechaCierreZ}
            onFechaChange={setFechaCierreZ}
            onGenerarTicketZ={handleGenerarTicketZ}
            formatMoney={formatMoney}
            loading={loading}
          />
        </div>
      </div>

      {/* =================================================================== */}
      {/* 5. TABLA DE DETALLE HISTÓRICO DE OPERACIONES                        */}
      {/* =================================================================== */}
      <div className="no-print">
        <OperationsHistoryTable
          operaciones={reporteData.operaciones}
          totales={reporteData.totales}
          loading={loading}
          formatMoney={formatMoney}
          filtros={filtros}
          config={config}
        />
      </div>

      {/* =================================================================== */}
      {/* 6. MODAL DE TICKET TÉRMICO DE CIERRE Z (STAR BSC10 80MM)            */}
      {/* =================================================================== */}
      <ThermalZTicketModal
        isOpen={isThermalModalOpen}
        onClose={() => setIsThermalModalOpen(false)}
        cierre={cierreZ}
        datosCuadratura={cuadraturaModalData}
        formatMoney={formatMoney}
        config={config}
      />

      {/* =================================================================== */}
      {/* 7. VISTA IMPRIMIBLE PARA EXPORTAR REPORTE COMPLETO EN PDF (A4)      */}
      {/* =================================================================== */}
      <div id="printable-full-report" className="hidden print:block font-sans text-slate-900">
        <div className="border-b-2 border-slate-900 pb-4 mb-6">
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-2xl font-bold uppercase tracking-tight">
                ParkFlow Control • Reporte de Operaciones
              </h1>
              <p className="text-xs text-slate-600 mt-1">
                Sistema Integral de Estacionamiento y Centro de Lavados
              </p>
            </div>
            <div className="text-right text-xs space-y-0.5">
              <div><strong>Fecha de Emisión:</strong> {new Date().toLocaleDateString('es-CL')}</div>
              <div><strong>Hora:</strong> {new Date().toLocaleTimeString()}</div>
              <div><strong>Moneda:</strong> {config.moneda_codigo} ({config.moneda_simbolo})</div>
            </div>
          </div>

          <div className="mt-4 p-3 bg-slate-100 rounded text-xs flex justify-between">
            <div>
              <strong>Rango Consultado:</strong> {filtros.desde} hasta {filtros.hasta}
            </div>
            {filtros.patente && (
              <div>
                <strong>Filtro Patente:</strong> {filtros.patente}
              </div>
            )}
            <div>
              <strong>Total Operaciones:</strong> {reporteData.operaciones.length}
            </div>
            <div>
              <strong>Total Recaudado:</strong> {reporteData.totales?.totalRecaudadoFormateado || formatMoney(reporteData.totales?.totalRecaudado || 0)}
            </div>
          </div>
        </div>

        {/* Tabla imprimible de operaciones */}
        <table className="w-full text-xs text-left border-collapse mb-6">
          <thead>
            <tr className="border-b border-slate-400 bg-slate-100 text-[10px] uppercase font-semibold">
              <th className="py-2 px-2">Folio</th>
              <th className="py-2 px-2">Fecha/Hora</th>
              <th className="py-2 px-2">Matrícula</th>
              <th className="py-2 px-2">Servicio</th>
              <th className="py-2 px-2">Método</th>
              <th className="py-2 px-2 text-right">Total</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200">
            {reporteData.operaciones.map((op, idx) => (
              <tr key={idx}>
                <td className="py-1.5 px-2 font-mono">#{String(op.folio || op.id).padStart(5, '0')}</td>
                <td className="py-1.5 px-2">{op.fechaSalidaCorta} {op.horaSalidaCorta}</td>
                <td className="py-1.5 px-2 font-mono font-bold uppercase">{op.patente}</td>
                <td className="py-1.5 px-2">{op.nombre_servicio}</td>
                <td className="py-1.5 px-2">{op.metodo_pago}</td>
                <td className="py-1.5 px-2 text-right font-mono font-bold">
                  {op.totalFormateado || formatMoney(op.total)}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t-2 border-slate-900 font-bold">
              <td colSpan={5} className="py-2 px-2 text-right uppercase">Total Recaudado:</td>
              <td className="py-2 px-2 text-right font-mono text-sm">
                {reporteData.totales?.totalRecaudadoFormateado || formatMoney(reporteData.totales?.totalRecaudado || 0)}
              </td>
            </tr>
          </tfoot>
        </table>

        {/* Resumen de Categorías al pie de impresión */}
        {reporteData.categorias.length > 0 && (
          <div className="pt-4 border-t border-slate-300">
            <h3 className="text-xs font-bold uppercase mb-2">Desglose por Categoría de Servicio:</h3>
            <div className="grid grid-cols-2 gap-2 text-xs">
              {reporteData.categorias.map((c, idx) => (
                <div key={idx} className="flex justify-between border-b border-slate-200 py-1">
                  <span>{c.categoria} ({c.cantidad} ops):</span>
                  <strong className="font-mono">{c.subtotalFormateado}</strong>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

    </div>
  );
};
