import React from 'react';
import { WashModule } from './WashModule';

/**
 * Pestaña de Lavados del Sistema ParkFlow (Paso 3.2).
 * Delega en el componente modular <WashModule /> manteniendo la separación de responsabilidades.
 */
export const LavadosView = () => {
  return <WashModule />;
};

export default LavadosView;
