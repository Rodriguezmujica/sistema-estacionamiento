import React, { useState, useEffect } from 'react';
import { Clock } from 'lucide-react';

/**
 * Componente de temporizador en vivo para las tarjetas de bahías de lavado.
 * Actualiza cada segundo de forma eficiente y muestra el tiempo transcurrido
 * según la fase activa del proceso (Espera, En Proceso, Listo).
 */
export const ElapsedTimer = ({
  timestampIngreso,
  timestampProceso,
  timestampListo,
  estado = 'espera'
}) => {
  const [elapsedSeconds, setElapsedSeconds] = useState(0);

  useEffect(() => {
    // Determinar la marca de tiempo de referencia según la fase
    let refTimestamp = timestampIngreso;
    if (estado === 'proceso' && timestampProceso) {
      refTimestamp = timestampProceso;
    } else if (estado === 'listo' && timestampListo) {
      refTimestamp = timestampListo;
    }

    const calcElapsed = () => {
      const now = Date.now();
      const start = Number(refTimestamp) || now;
      const diffSec = Math.max(0, Math.floor((now - start) / 1000));
      setElapsedSeconds(diffSec);
    };

    calcElapsed();
    const timer = setInterval(calcElapsed, 1000);

    return () => clearInterval(timer);
  }, [timestampIngreso, timestampProceso, timestampListo, estado]);

  // Formateo de tiempo: HH:MM:SS o MM:SS
  const formatTime = (totalSec) => {
    const hours = Math.floor(totalSec / 3600);
    const minutes = Math.floor((totalSec % 3600) / 60);
    const seconds = totalSec % 60;

    const pad = (n) => String(n).padStart(2, '0');

    if (hours > 0) {
      return `${hours}h ${pad(minutes)}m ${pad(seconds)}s`;
    }
    return `${pad(minutes)}:${pad(seconds)}`;
  };

  const getLabel = () => {
    switch (estado) {
      case 'espera':
        return 'En espera';
      case 'proceso':
        return 'Lavando';
      case 'listo':
        return 'Listo hace';
      default:
        return 'Tiempo';
    }
  };

  const getStyles = () => {
    switch (estado) {
      case 'espera':
        return 'bg-amber-50/80 text-amber-700 border-amber-200/80';
      case 'proceso':
        return 'bg-blue-50/80 text-blue-700 border-blue-200/80';
      case 'listo':
        return 'bg-emerald-50/80 text-emerald-700 border-emerald-200/80';
      default:
        return 'bg-slate-50 text-slate-700 border-slate-200';
    }
  };

  return (
    <div
      className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium border font-mono-numbers transition-colors ${getStyles()}`}
      title={`${getLabel()} (${elapsedSeconds} segundos)`}
    >
      <Clock className="w-3 h-3 shrink-0" />
      <span className="font-medium text-[10px] uppercase tracking-wider opacity-80">
        {getLabel()}:
      </span>
      <span className="font-semibold tracking-tight">{formatTime(elapsedSeconds)}</span>
    </div>
  );
};
