import React, { useState } from 'react';
import {
  FileSpreadsheet,
  FileText,
  Clock,
  ArrowUpDown,
  Car,
  CreditCard,
  Banknote,
  ArrowRightLeft,
  ChevronLeft,
  ChevronRight
} from 'lucide-react';

export const OperationsHistoryTable = ({
  operaciones = [],
  totales,
  loading = false,
  formatMoney,
  filtros = {},
  config = {}
}) => {
  // Paginación interna ligera (15 filas por página)
  const [paginaActual, setPaginaActual] = useState(1);
  const filasPorPagina = 12;

  const totalPaginas = Math.ceil(operaciones.length / filasPorPagina) || 1;
  const inicio = (paginaActual - 1) * filasPorPagina;
  const operacionesPaginadas = operaciones.slice(inicio, inicio + filasPorPagina);

  // --------------------------------------------------------------------------
  // 1. EXPORTACIÓN A CSV CON COMPATIBILIDAD EXCEL (BOM UTF-8)
  // --------------------------------------------------------------------------
  const exportarCSV = () => {
    if (!operaciones || operaciones.length === 0) return;

    const encabezados = [
      'Folio',
      'Fecha Salida',
      'Hora Salida',
      'Matrícula',
      'Servicio',
      'Método de Pago',
      'Tiempo Estadía',
      'Importe Final Cobrado'
    ];

    const filas = operaciones.map((op) => [
      `"${op.folio || op.id || ''}"`,
      `"${op.fechaSalidaCorta || ''}"`,
      `"${op.horaSalidaCorta || ''}"`,
      `"${op.patente || ''}"`,
      `"${(op.nombre_servicio || '').replace(/"/g, '""')}"`,
      `"${op.metodo_pago || ''}"`,
      `"${op.tiempo_estadia || ''}"`,
      `"${op.total || 0}"`
    ]);

    // Prepend UTF-8 BOM (\uFEFF) para que Excel interprete correctamente los caracteres en español
    const contenidoCSV =
      '\uFEFF' +
      encabezados.join(';') +
      '\n' +
      filas.map((f) => f.join(';')).join('\n');

    const blob = new Blob([contenidoCSV], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    const nombreArchivo = `reporte_operaciones_${filtros.desde || 'inicio'}_al_${filtros.hasta || 'hoy'}.csv`;
    link.setAttribute('href', url);
    link.setAttribute('download', nombreArchivo);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // --------------------------------------------------------------------------
  // 2. EXPORTACIÓN A PDF (IMPRESIÓN VÍA SISTEMA)
  // --------------------------------------------------------------------------
  const exportarPDF = () => {
    window.print();
  };

  // Helper de badge para método de pago
  const renderMetodoBadge = (metodo) => {
    const m = (metodo || '').toUpperCase();
    if (m === 'EFECTIVO') {
      return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200/60">
          <Banknote className="w-3 h-3 text-emerald-600" />
          <span>Efectivo</span>
        </span>
      );
    }
    if (m === 'TARJETA' || m === 'POS' || m === 'TUU') {
      return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 border border-blue-200/60">
          <CreditCard className="w-3 h-3 text-blue-600" />
          <span>Tarjeta/POS</span>
        </span>
      );
    }
    if (m === 'TRANSFERENCIA') {
      return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-800 border border-purple-200/60">
          <ArrowRightLeft className="w-3 h-3 text-purple-600" />
          <span>Transferencia</span>
        </span>
      );
    }
    return (
      <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
        {metodo}
      </span>
    );
  };

  return (
    <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden">
      {/* Cabecera de la Tabla */}
      <div className="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
        <div>
          <h3 className="text-sm font-semibold text-slate-900 tracking-tight">
            Detalle Histórico de Operaciones
          </h3>
          <p className="text-xs text-slate-500 mt-0.5">
            Registro secuencial de cobros finalizados para el rango de fechas seleccionado
          </p>
        </div>

        {/* Botones Discretos de Exportación */}
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={exportarCSV}
            disabled={operaciones.length === 0}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors shadow-2xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            title="Exportar archivo CSV compatible con Excel"
          >
            <FileSpreadsheet className="w-3.5 h-3.5 text-emerald-600" />
            <span>Exportar CSV</span>
          </button>

          <button
            type="button"
            onClick={exportarPDF}
            disabled={operaciones.length === 0}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 transition-colors shadow-2xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            title="Imprimir o guardar reporte en PDF"
          >
            <FileText className="w-3.5 h-3.5 text-red-600" />
            <span>Exportar PDF</span>
          </button>
        </div>
      </div>

      {/* Contenido de la Tabla */}
      <div className="overflow-x-auto">
        <table className="w-full text-xs text-left">
          <thead className="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-200">
            <tr>
              <th className="py-3 px-4">Folio</th>
              <th className="py-3 px-4">Fecha y Hora Salida</th>
              <th className="py-3 px-4">Matrícula</th>
              <th className="py-3 px-4">Servicio Contratado</th>
              <th className="py-3 px-4">Método de Pago</th>
              <th className="py-3 px-4 text-right">Importe Cobrado</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading ? (
              <tr>
                <td colSpan={6} className="py-12 text-center text-slate-400">
                  <div className="flex items-center justify-center gap-2">
                    <div className="w-4 h-4 border-2 border-slate-300 border-t-slate-700 rounded-full animate-spin" />
                    <span>Cargando transacciones históricas...</span>
                  </div>
                </td>
              </tr>
            ) : operaciones.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-12 text-center text-slate-400">
                  <Car className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                  <p className="font-medium text-slate-600">No se encontraron operaciones registradas.</p>
                  <p className="text-[11px] mt-1 text-slate-400">
                    Ajuste los filtros de fecha o el término de búsqueda de matrícula.
                  </p>
                </td>
              </tr>
            ) : (
              operacionesPaginadas.map((op, idx) => (
                <tr
                  key={op.folio || idx}
                  className="hover:bg-slate-50/80 transition-colors even:bg-slate-50/30"
                >
                  {/* Folio */}
                  <td className="py-3 px-4 font-mono-numbers text-slate-500">
                    <span className="px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200/70 font-semibold">
                      #{String(op.folio || op.id).padStart(5, '0')}
                    </span>
                  </td>

                  {/* Fecha y Hora de Salida */}
                  <td className="py-3 px-4 text-slate-700 whitespace-nowrap">
                    <div className="flex items-center gap-1.5">
                      <Clock className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                      <span>{op.fechaSalidaCorta}</span>
                      <span className="text-slate-400 font-mono-numbers">{op.horaSalidaCorta}</span>
                    </div>
                  </td>

                  {/* Matrícula */}
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="font-mono font-bold tracking-wider uppercase text-slate-900 px-2 py-1 bg-white border border-slate-300 rounded shadow-2xs">
                      {op.patente}
                    </span>
                  </td>

                  {/* Servicio */}
                  <td className="py-3 px-4 text-slate-800 font-medium">
                    <div className="flex items-center gap-1.5">
                      <span>{op.nombre_servicio}</span>
                      {op.tiempo_estadia && (
                        <span className="text-[10px] text-slate-400 font-mono-numbers">
                          ({op.tiempo_estadia})
                        </span>
                      )}
                    </div>
                  </td>

                  {/* Método de Pago */}
                  <td className="py-3 px-4 whitespace-nowrap">
                    {renderMetodoBadge(op.metodo_pago)}
                  </td>

                  {/* Importe Final */}
                  <td className="py-3 px-4 text-right font-mono-numbers font-bold text-slate-900 text-sm whitespace-nowrap">
                    {op.totalFormateado || (formatMoney ? formatMoney(op.total) : `$${op.total}`)}
                  </td>
                </tr>
              ))
            )}
          </tbody>

          {/* Pie de Tabla con Totales */}
          {operaciones.length > 0 && totales && (
            <tfoot className="bg-slate-50/80 border-t-2 border-slate-200 font-semibold text-slate-800 text-xs">
              <tr>
                <td colSpan={3} className="py-3 px-4">
                  <span className="text-slate-500 uppercase tracking-wider text-[11px]">
                    Total Operaciones:
                  </span>{' '}
                  <strong className="font-mono-numbers text-slate-900">{operaciones.length}</strong>
                </td>
                <td colSpan={2} className="py-3 px-4 text-right text-slate-500 uppercase tracking-wider text-[11px]">
                  Total Facturado:
                </td>
                <td className="py-3 px-4 text-right font-mono-numbers font-bold text-slate-900 text-base">
                  {totales.totalRecaudadoFormateado || (formatMoney ? formatMoney(totales.totalRecaudado) : `$${totales.totalRecaudado}`)}
                </td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>

      {/* Paginación */}
      {operaciones.length > filasPorPagina && (
        <div className="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-white">
          <div>
            Mostrando <strong>{inicio + 1}</strong> a{' '}
            <strong>{Math.min(inicio + filasPorPagina, operaciones.length)}</strong> de{' '}
            <strong>{operaciones.length}</strong> operaciones
          </div>

          <div className="flex items-center gap-1.5">
            <button
              type="button"
              onClick={() => setPaginaActual((p) => Math.max(p - 1, 1))}
              disabled={paginaActual === 1}
              className="p-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <span className="font-mono-numbers px-2">
              Página {paginaActual} de {totalPaginas}
            </span>
            <button
              type="button"
              onClick={() => setPaginaActual((p) => Math.min(p + 1, totalPaginas))}
              disabled={paginaActual === totalPaginas}
              className="p-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
