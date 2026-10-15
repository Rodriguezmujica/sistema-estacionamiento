import React from 'react';
import { Layers, PieChart } from 'lucide-react';

export const CategoryBreakdown = ({
  categorias = [],
  totalRecaudado = 0,
  totalOperaciones = 0,
  loading = false,
  formatMoney
}) => {
  return (
    <div className="bg-white border border-slate-200/90 rounded-xl shadow-card overflow-hidden flex flex-col h-full">
      {/* Cabecera del Panel */}
      <div className="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
        <div className="flex items-center gap-2">
          <div className="p-1.5 rounded-lg bg-slate-900 text-white">
            <Layers className="w-3.5 h-3.5 text-slate-100" />
          </div>
          <div>
            <h3 className="text-xs font-semibold text-slate-900">
              Desglose por Categoría de Servicio
            </h3>
            <p className="text-[11px] text-slate-500">
              Volumen y recaudación según servicio en el período
            </p>
          </div>
        </div>

        <span className="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
          {categorias.length} {categorias.length === 1 ? 'servicio' : 'servicios'}
        </span>
      </div>

      {/* Contenido / Tabla Compacta */}
      <div className="p-4 flex-1 flex flex-col justify-between">
        {loading ? (
          <div className="py-8 space-y-3">
            {[1, 2, 3].map((n) => (
              <div key={n} className="h-9 bg-slate-50 rounded-lg animate-pulse" />
            ))}
          </div>
        ) : categorias.length === 0 ? (
          <div className="py-12 text-center text-slate-400 text-xs">
            <PieChart className="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <p>No se registran operaciones en el período seleccionado.</p>
          </div>
        ) : (
          <div className="space-y-3">
            <div className="border border-slate-200/80 rounded-lg overflow-hidden">
              <table className="w-full text-xs text-left">
                <thead className="bg-slate-50/80 text-[10px] text-slate-500 uppercase tracking-wider border-b border-slate-200">
                  <tr>
                    <th className="py-2.5 px-3 font-semibold">Categoría</th>
                    <th className="py-2.5 px-3 text-center font-semibold">Operaciones</th>
                    <th className="py-2.5 px-3 text-right font-semibold">Subtotal</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {categorias.map((cat, idx) => (
                    <tr key={idx} className="hover:bg-slate-50/60 transition-colors">
                      <td className="py-2.5 px-3">
                        <div className="space-y-1">
                          <span className="font-medium text-slate-800">
                            {cat.categoria}
                          </span>
                          {/* Barra de progreso de participación */}
                          <div className="flex items-center gap-2">
                            <div className="w-24 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                              <div
                                className="bg-slate-800 h-full rounded-full transition-all duration-300"
                                style={{ width: `${Math.min(100, Math.max(4, cat.porcentaje || 0))}%` }}
                              />
                            </div>
                            <span className="text-[10px] text-slate-400 font-mono-numbers">
                              {cat.porcentaje || 0}%
                            </span>
                          </div>
                        </div>
                      </td>
                      <td className="py-2.5 px-3 text-center font-mono-numbers font-medium text-slate-600">
                        {cat.cantidad}
                      </td>
                      <td className="py-2.5 px-3 text-right font-mono-numbers font-bold text-slate-900">
                        {cat.subtotalFormateado || formatMoney(cat.subtotal)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Resumen Total al Pie del Panel */}
        {categorias.length > 0 && (
          <div className="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span className="font-semibold text-slate-600 uppercase text-[11px] tracking-wider">
              Total Período:
            </span>
            <div className="flex items-center gap-4">
              <span className="font-mono-numbers text-slate-500">
                <strong>{totalOperaciones}</strong> ops
              </span>
              <span className="font-mono-numbers font-bold text-slate-900 text-sm">
                {formatMoney ? formatMoney(totalRecaudado) : `$${totalRecaudado}`}
              </span>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
