import React from 'react';
import { AdminModule } from './AdminModule';

/**
 * ============================================================================
 * VISTA PRINCIPAL DE ADMINISTRACIÓN (Paso 3.4)
 * ============================================================================
 * Enlaza directamente con el componente modular <AdminModule />
 * conectado a las tablas SQLite (clientes, tipo_ingreso, precios, metas_mensuales, usuarios).
 * ============================================================================
 */
export const AdminView = () => {
  return <AdminModule />;
};

export { AdminModule };
export default AdminView;
