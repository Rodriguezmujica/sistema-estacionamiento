import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { SubscribersTab } from './SubscribersTab';
import { ServicesCatalogTab } from './ServicesCatalogTab';
import { ConfigGoalsTab } from './ConfigGoalsTab';
import { UsersTab } from './UsersTab';
import {
  Settings,
  Users,
  Layers,
  Sliders,
  ShieldCheck,
  Clock,
  Sparkles
} from 'lucide-react';

/**
 * ============================================================================
 * COMPONENTE PRINCIPAL DEL MÓDULO DE ADMINISTRACIÓN (<AdminModule />) - PASO 3.4
 * ============================================================================
 * Gestión centralizada conectada directamente a SQLite:
 * - Sub-pestaña 1: Abonados / Clientes Mensuales (tabla clientes)
 * - Sub-pestaña 2: Catálogo de Servicios (tabla tipo_ingreso)
 * - Sub-pestaña 3: Configuración y Metas (tablas configuracion_regional, precios, metas_mensuales)
 * - Sub-pestaña 4: Usuarios del Sistema (tabla usuarios con hash SHA-256)
 * ============================================================================
 */
export const AdminModule = () => {
  const { config, perfilActivo } = useApp();
  const [activeSubTab, setActiveSubTab] = useState('subscribers'); // 'subscribers' | 'services' | 'config' | 'users'

  const subTabs = [
    {
      id: 'subscribers',
      label: 'Abonados / Clientes Mensuales',
      shortLabel: 'Abonados',
      icon: Users
    },
    {
      id: 'services',
      label: 'Catálogo de Servicios',
      shortLabel: 'Catálogo',
      icon: Layers
    },
    {
      id: 'config',
      label: 'Configuración y Metas',
      shortLabel: 'Tarifas & Metas',
      icon: Sliders
    },
    {
      id: 'users',
      label: 'Usuarios del Sistema',
      shortLabel: 'Usuarios',
      icon: ShieldCheck
    }
  ];

  return (
    <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      {/* 1. HEADER PRINCIPAL DEL MÓDULO DE ADMINISTRACIÓN */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <div className="flex items-center gap-2.5">
            <span className="p-2 rounded-lg bg-slate-900 text-white shadow-xs">
              <Settings className="w-5 h-5 text-white" />
            </span>
            <div>
              <h1 className="text-xl font-bold text-slate-900 tracking-tight">
                Administración & Configuración del Sistema
              </h1>
              <p className="text-xs text-slate-500 mt-0.5">
                Control de contratos mensuales, tarifario, perfiles regionales y cuentas operativas en SQLite.
              </p>
            </div>
          </div>
        </div>

        {/* Indicadores de Entorno Activo */}
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
            <span className="w-2 h-2 rounded-full bg-emerald-500 mr-1.5" />
            SQLite Directo
          </span>
          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900 text-white shadow-2xs">
            {perfilActivo === 'chile' ? 'CLP ($)' : 'EUR (€)'}
          </span>
        </div>
      </div>

      {/* 2. SUB-NAVEGACIÓN POR PESTAÑAS SOBRIAS (Slate / Zinc) */}
      <div className="bg-white border border-slate-200/90 rounded-xl p-1.5 shadow-card overflow-x-auto scrollbar-none">
        <div className="flex items-center space-x-1 sm:space-x-2 min-w-max">
          {subTabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeSubTab === tab.id;

            return (
              <button
                key={tab.id}
                onClick={() => setActiveSubTab(tab.id)}
                className={`flex items-center gap-2 py-2 px-3.5 rounded-lg text-xs font-medium transition-all cursor-pointer whitespace-nowrap ${
                  isActive
                    ? 'bg-slate-900 text-white shadow-xs font-semibold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                }`}
              >
                <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                <span className="hidden md:inline">{tab.label}</span>
                <span className="inline md:hidden">{tab.shortLabel}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* 3. CONTENIDO DINÁMICO SEGÚN SUB-PESTAÑA SELECCIONADA */}
      <div className="transition-all duration-150">
        {activeSubTab === 'subscribers' && <SubscribersTab />}
        {activeSubTab === 'services' && <ServicesCatalogTab />}
        {activeSubTab === 'config' && <ConfigGoalsTab />}
        {activeSubTab === 'users' && <UsersTab />}
      </div>
    </div>
  );
};

export default AdminModule;
