import React, { useState, useRef, useEffect } from 'react';
import { useApp } from '../context/AppContext';
import { PlusCircle, Car, Sparkles, Check } from 'lucide-react';

export const QuickEntryForm = () => {
  const { tiposIngreso, registrarIngreso, config } = useApp();
  const [patente, setPatente] = useState('');
  const [idTipoIngreso, setIdTipoIngreso] = useState('');
  const [esLavado, setEsLavado] = useState(false);
  const [motivosExtra, setMotivosExtra] = useState('');
  const [precioExtra, setPrecioExtra] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const inputPatenteRef = useRef(null);

  // Seleccionar automáticamente "Estacionamiento" (18) o el primer servicio
  useEffect(() => {
    if (tiposIngreso.length > 0 && !idTipoIngreso) {
      const defaultServicio = tiposIngreso.find((t) => t.idtipo_ingresos === 18) || tiposIngreso[0];
      if (defaultServicio) {
        setIdTipoIngreso(String(defaultServicio.idtipo_ingresos));
      }
    }
  }, [tiposIngreso, idTipoIngreso]);

  // Autofocus en el input de patente al montar
  useEffect(() => {
    if (inputPatenteRef.current) {
      inputPatenteRef.current.focus();
    }
  }, []);

  // Detectar si el tipo seleccionado es un lavado para mostrar campos extra
  const handleTipoChange = (e) => {
    const val = e.target.value;
    setIdTipoIngreso(val);
    const tipo = tiposIngreso.find((t) => String(t.idtipo_ingresos) === val);
    const esLav = tipo ? tipo.nombre_servicio.toLowerCase().includes('lavad') : false;
    setEsLavado(esLav);
  };

  // Manejar cambio de patente: forzar mayúsculas y quitar espacios extraños
  const handlePatenteChange = (e) => {
    const val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 8);
    setPatente(val);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const cleanPatente = patente.trim().replace(/[^A-Z0-9]/g, '');

    if (cleanPatente.length < 4) {
      alert('Por favor ingrese una patente válida (mínimo 4 caracteres)');
      return;
    }

    try {
      setIsSubmitting(true);
      await registrarIngreso({
        patente: cleanPatente,
        idtipo_ingreso: Number(idTipoIngreso) || 18,
        motivos_extra: esLavado && motivosExtra ? motivosExtra : null,
        precio_extra: esLavado && precioExtra ? Number(precioExtra) : 0
      });

      // Limpiar formulario y reenfocar
      setPatente('');
      setMotivosExtra('');
      setPrecioExtra('');
      if (inputPatenteRef.current) {
        inputPatenteRef.current.focus();
      }
    } catch (err) {
      // El toast global ya muestra el error
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-card">
      <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
        <div className="flex items-center gap-2">
          <Car className="w-4 h-4 text-slate-700" />
          <h2 className="text-sm font-semibold text-slate-900 tracking-tight">
            Registro Rápido de Entrada
          </h2>
        </div>
        <span className="text-[11px] text-slate-400 font-normal">
          Detección automática de abonos mensuales
        </span>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
          
          {/* Campo Patente / Matrícula */}
          <div className="sm:col-span-4">
            <label className="block text-xs font-medium text-slate-600 mb-1.5">
              Matrícula / Patente <span className="text-slate-400 font-normal">(6-8 caracteres)</span>
            </label>
            <div className="relative">
              <input
                ref={inputPatenteRef}
                type="text"
                required
                value={patente}
                onChange={handlePatenteChange}
                placeholder="EJ: AB-1234 / 1234-BBB"
                className="w-full px-3.5 py-2 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 font-mono text-sm uppercase font-semibold tracking-wider focus:outline-none focus:ring-2 focus:ring-slate-900 focus:bg-white transition-all"
              />
              {patente.length >= 6 && (
                <div className="absolute right-3 top-2.5 text-emerald-600">
                  <Check className="w-4 h-4" />
                </div>
              )}
            </div>
          </div>

          {/* Selector de Servicio */}
          <div className="sm:col-span-5">
            <label className="block text-xs font-medium text-slate-600 mb-1.5">
              Servicio de Entrada
            </label>
            <select
              value={idTipoIngreso}
              onChange={handleTipoChange}
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

          {/* Botón de Registro */}
          <div className="sm:col-span-3">
            <button
              type="submit"
              disabled={isSubmitting || !patente.trim()}
              className="w-full py-2 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition-all shadow-subtle flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed active:scale-98"
            >
              <PlusCircle className="w-3.5 h-3.5" />
              <span>{isSubmitting ? 'Registrando...' : 'Ingresar'}</span>
            </button>
          </div>

        </div>

        {/* Campos adicionales condicionales si el servicio es Lavado */}
        {esLavado && (
          <div className="p-3 bg-slate-50 rounded-lg border border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs animate-modal-in">
            <div>
              <label className="block font-medium text-slate-600 mb-1">
                Motivos o detalles adicionales de lavado
              </label>
              <input
                type="text"
                value={motivosExtra}
                onChange={(e) => setMotivosExtra(e.target.value)}
                placeholder="Ej: Desmanchado de tapiz, barro profundo"
                className="w-full px-3 py-1.5 rounded-md border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-1 focus:ring-slate-800"
              />
            </div>
            <div>
              <label className="block font-medium text-slate-600 mb-1">
                Recargo extra ({config.moneda_simbolo})
              </label>
              <input
                type="number"
                value={precioExtra}
                onChange={(e) => setPrecioExtra(e.target.value)}
                placeholder="0"
                className="w-full px-3 py-1.5 rounded-md border border-slate-200 bg-white text-slate-800 font-mono-numbers focus:outline-none focus:ring-1 focus:ring-slate-800"
              />
            </div>
          </div>
        )}
      </form>
    </div>
  );
};
