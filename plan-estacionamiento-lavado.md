# 🚗 Plan Sistema Estacionamiento/Lavado de Autos

## 📋 Ideas y Requerimientos

### Base de datos actual:
- ✅ Sistema de estacionamiento por minutos funcionando
- ✅ Servicios de lavado configurados
- ✅ Gestión de clientes con planes
- ✅ Registro de ingresos/salidas

### Nueva funcionalidad propuesta:
**Sistema de Historial de Precios por Patente**
- Registro de precios cobrados anteriormente
- Niveles de suciedad (Limpio, Normal, Sucio, Muy Sucio)
- Sugerencias inteligentes basadas en historial
- Observaciones por servicio

### Estructura propuesta para nueva tabla:
```sql
CREATE TABLE `historial_lavados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `patente` VARCHAR(6) NOT NULL,
  `fecha_servicio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `tipo_servicio` VARCHAR(100) NOT NULL,
  `precio_base` INT(11) NOT NULL,
  `precio_cobrado` INT(11) NOT NULL,
  `nivel_suciedad` ENUM('Limpio', 'Normal', 'Sucio', 'Muy Sucio') DEFAULT 'Normal',
  `observaciones` TEXT DEFAULT NULL,
  `id_cliente` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_patente` (`patente`),
  INDEX `idx_fecha` (`fecha_servicio`)
);
```

### Interfaz propuesta:
- Formulario con búsqueda de patente
- Historial reciente visible
- Selector de nivel de suciedad
- Cálculo automático de precio final
- Observaciones personalizables

## 🎯 Próximos pasos:
1. Adaptar frontend de pastelería
2. Crear backend API
3. Integrar con base de datos existente
4. Implementar sistema de historial (futuro)

---
*Chat guardado el: $(Get-Date)*
