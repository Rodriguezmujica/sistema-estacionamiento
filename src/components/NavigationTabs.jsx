import React from 'react';
import { useApp } from '../context/AppContext';
import { Car, Sparkles, BarChart3, Settings } from 'lucide-react';

export const NavigationTabs = () => {
  const { activeTab, setActiveTab, resumen } = useApp();

  const tabs = [
    {
      id: 'operaciones',
      label: 'Operaciones',
      icon: Car,
      badge: resumen.autosEstacionados,
      badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200'
    },
    {
      id: 'lavados',
      label: 'Lavados',
      icon: Sparkles,
      badge: resumen.lavadosPendientes > 0 ? resumen.lavadosPendientes : null,
      badgeColor: 'bg-amber-50 text-amber-700 border-amber-200'
    },
    {
      id: 'reportes',
      label: 'Reportes',
      icon: BarChart3,
      badge: null
    },
    {
      id: 'admin',
      label: 'Administración',
      icon: Settings,
      badge: null
    }
  ];

  return (
    <nav className="bg-white border-b border-slate-200/90 shadow-2xs no-print">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center space-x-1 sm:space-x-2 overflow-x-auto py-1 scrollbar-none">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;

            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 py-2.5 px-3.5 rounded-lg text-xs font-medium transition-all cursor-pointer whitespace-nowrap ${
                  isActive
                    ? 'bg-slate-900 text-white shadow-xs font-semibold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70'
                }`}
              >
                <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                <span>{tab.label}</span>
                {tab.badge !== null && tab.badge !== undefined && (
                  <span
                    className={`ml-1 text-[10px] font-mono-numbers px-1.5 py-0.5 rounded-full border transition-all ${
                      isActive
                        ? 'bg-slate-800 text-slate-200 border-slate-700'
                        : tab.badgeColor || 'bg-slate-100 text-slate-600 border-slate-200'
                    }`}
                  >
                    {tab.badge}
                  </span>
                )}
              </button>
            );
          })}
        </div>
      </div>
    </nav>
  );
};
