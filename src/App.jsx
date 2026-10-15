import React from 'react';
import { AppProvider, useApp } from './context/AppContext';
import { Navbar } from './components/Navbar';
import { NavigationTabs } from './components/NavigationTabs';
import { Dashboard } from './components/Dashboard';
import { LavadosView } from './components/LavadosView';
import { ReportesView } from './components/ReportesView';
import { AdminView } from './components/AdminView';
import { CheckoutModal } from './components/CheckoutModal';
import { VirtualTicketModal } from './components/VirtualTicketModal';
import { CashClosingModal } from './components/CashClosingModal';
import { ModifyTicketModal } from './components/ModifyTicketModal';
import { ReprintModal } from './components/ReprintModal';
import { Database, Shield } from 'lucide-react';

const AppContent = () => {
  const { appInfo, config, activeTab } = useApp();

  return (
    <div className="min-h-screen flex flex-col bg-slate-50 text-slate-800">
      {/* Barra de Navegación Superior */}
      <Navbar />

      {/* Barra de Navegación por Pestañas (Tabs) */}
      <NavigationTabs />

      {/* Contenido Principal según Pestaña Activa */}
      <main className="flex-1 flex flex-col">
        {activeTab === 'operaciones' && <Dashboard />}
        {activeTab === 'lavados' && <LavadosView />}
        {activeTab === 'reportes' && <ReportesView />}
        {activeTab === 'admin' && <AdminView />}
      </main>

      {/* Modales del Sistema */}
      <CheckoutModal />
      <VirtualTicketModal />
      <CashClosingModal />
      <ModifyTicketModal />
      <ReprintModal />

      {/* Footer Minimalista Profesional */}
      <footer className="border-t border-slate-200 bg-white py-3 px-4 sm:px-8 text-xs text-slate-400 no-print">
        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
          <div className="flex items-center gap-3">
            <span className="flex items-center gap-1">
              <Shield className="w-3.5 h-3.5 text-slate-400" />
              <span>Fase 3 • React UI + Electron IPC</span>
            </span>
            <span>•</span>
            <span className="flex items-center gap-1 font-mono text-[11px] text-slate-500 truncate max-w-sm">
              <Database className="w-3 h-3 text-slate-400 shrink-0" />
              <span className="truncate">{appInfo?.dbPath || 'SQLite: estacionamiento.db'}</span>
            </span>
          </div>

          <div className="flex items-center gap-3 text-[11px]">
            <span>Perfil: <strong>{config.moneda_codigo}</strong></span>
            <span>•</span>
            <span>Redondeo Efectivo: {config.activar_redondeo_chile ? 'Activo' : 'Inactivo'}</span>
          </div>
        </div>
      </footer>
    </div>
  );
};

export default function App() {
  return (
    <AppProvider>
      <AppContent />
    </AppProvider>
  );
}
