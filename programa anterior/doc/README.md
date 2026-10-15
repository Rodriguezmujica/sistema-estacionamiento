# 📚 Documentación - Sistema de Estacionamiento Los Ríos

**Índice general de toda la documentación del sistema.**

---

## 🖨️ Impresión Térmica

Documentación sobre configuración e integración de impresoras térmicas.

### Archivos:
- **[GUIA_IMPRESORA_TERMICA_CLIENTE_SERVIDOR.md](impresion/GUIA_IMPRESORA_TERMICA_CLIENTE_SERVIDOR.md)**
  - Explicación de arquitectura cliente-servidor para impresoras
  - Soluciones disponibles (Node.js, PHP, red Windows)
  - Comparación de opciones

- **[GUIA_INSTALACION_WINDOWS7.md](impresion/GUIA_INSTALACION_WINDOWS7.md)**
  - Guía específica para Windows 7
  - Instalación del servicio PHP de impresión
  - Configuración de impresora Star BSC10

- **[RESUMEN_INTEGRACION_COMPLETA.md](impresion/RESUMEN_INTEGRACION_COMPLETA.md)**
  - Estado de integración completa del sistema
  - Todas las funciones de impresión implementadas
  - Checklist de verificación

- **[INSTRUCCIONES_PRUEBA_SEGURA.txt](impresion/INSTRUCCIONES_PRUEBA_SEGURA.txt)**
  - Cómo probar sin afectar producción
  - Opciones de prueba en carpeta separada

### Archivos Relacionados:
- `../print-service-php/` - Servicio PHP de impresión
- `../JS/print-service-client-win7.js` - Cliente JavaScript
- `../test-imprimir-win7.html` - Página de pruebas
- `../ejemplos-integracion.html` - Ejemplos de integración

---

## 🌐 Servidor y Acceso Remoto

Documentación sobre configuración del servidor Ubuntu y acceso desde internet.

### Archivos:
- **[IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md](servidor/IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md)** ⭐ **PRINCIPAL**
  - Guía completa paso a paso
  - Configuración de DuckDNS (dominio gratis)
  - Instalación de certificado SSL (HTTPS gratis)
  - Configuración de router y firewall
  - Acceso local + remoto

- **[GUIA_ACCESO_REMOTO_UBUNTU.md](servidor/GUIA_ACCESO_REMOTO_UBUNTU.md)**
  - Opciones de acceso remoto
  - Comparación de soluciones (web directo, VPN, etc.)
  - Arquitectura del sistema

- **[RESUMEN_SERVIDOR_Y_UBUNTU.md](servidor/RESUMEN_SERVIDOR_Y_UBUNTU.md)**
  - Cómo funciona el servidor
  - Compatibilidad con Ubuntu
  - Flujo de impresión local vs remoto

- **[GUIA_MIGRACION_UBUNTU.md](servidor/GUIA_MIGRACION_UBUNTU.md)**
  - Migración desde Windows a Ubuntu
  - Instalación de Apache, PHP, MySQL
  - Configuraciones necesarias

---

## 💳 Pagos y TUU

Documentación sobre sistema de pagos y terminal TUU.

### Archivos:
- **[GUIA_PAGOS_TUU_VS_MANUAL.md](pagos/GUIA_PAGOS_TUU_VS_MANUAL.md)**
  - Diferencias entre pago TUU y manual
  - Flujos de cobro
  - Cuándo usar cada uno

- **[INTEGRACION_TUU.md](pagos/INTEGRACION_TUU.md)**
  - Integración con terminal TUU
  - Configuración y API
  - Manejo de transacciones

- **[IMPLEMENTACION_FRONTEND_PAGOS.md](pagos/IMPLEMENTACION_FRONTEND_PAGOS.md)**
  - Interfaz de usuario para pagos
  - Botones y flujos

- **[README_IMPLEMENTACION_PAGOS.md](pagos/README_IMPLEMENTACION_PAGOS.md)**
  - Resumen de implementación de pagos
  - Archivos modificados

### Archivos de Emergencia:
- **[SISTEMA_EMERGENCIA_TUU.md](SISTEMA_EMERGENCIA_TUU.md)**
  - Qué hacer si TUU falla
  - Procedimientos de emergencia

- **[GUIA_RAPIDA_EMERGENCIA_TUU.md](GUIA_RAPIDA_EMERGENCIA_TUU.md)**
  - Guía rápida de emergencia

- **[CHECKLIST_PRUEBA_TUU_MANANA.md](CHECKLIST_PRUEBA_TUU_MANANA.md)**
  - Checklist para pruebas

---

## ⚙️ Sistema y Configuración

Documentación sobre configuraciones generales del sistema.

### Archivos:
- **[GUIA_CONFIGURACION_PRECIOS.md](sistema/GUIA_CONFIGURACION_PRECIOS.md)**
  - Cómo configurar precios
  - Tipos de servicios
  - Precios por minuto, franjas horarias, etc.

- **[GUIA_TIMEZONE.md](sistema/GUIA_TIMEZONE.md)**
  - Configuración de zona horaria
  - Solución de problemas de fechas/horas

- **[plan-estacionamiento-lavado.md](sistema/plan-estacionamiento-lavado.md)**
  - Plan general del sistema
  - Funcionalidades

### Archivos de Desarrollo:
- **[todo.md](todo.md)**
  - Lista de tareas pendientes

- **[FIX_MYSQL57_FECHAS_CERO.md](FIX_MYSQL57_FECHAS_CERO.md)**
  - Solución para problemas con MySQL 5.7

---

## 📦 Otros Recursos

### Carpetas del Proyecto:
- `../print-service/` - Servicio Node.js (alternativa para Windows 10+)
- `../print-service-php/` - Servicio PHP (para Windows 7)
- `../sistema-tickets/` - Sistema de tickets (antiguo)
- `../sql/` - Scripts de base de datos
- `../ImpresionTermica/` - Archivos de impresión térmica

### Archivos Útiles:
- `../ideas.txt` - Ideas y notas generales
- `../ARCHIVOS_IGNORADOS_POR_GIT.txt` - Archivos que no se suben a Git
- `../conexion.php.example` - Ejemplo de configuración de BD

---

## 🚀 Guías de Inicio Rápido

### Para Instalar Impresora (Windows 7):
1. Lee: [GUIA_INSTALACION_WINDOWS7.md](impresion/GUIA_INSTALACION_WINDOWS7.md)
2. Sigue: [RESUMEN_INTEGRACION_COMPLETA.md](impresion/RESUMEN_INTEGRACION_COMPLETA.md)

### Para Configurar Servidor Ubuntu:
1. Lee: [IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md](servidor/IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md)
2. Sigue paso a paso

### Para Configurar Pagos:
1. Lee: [GUIA_PAGOS_TUU_VS_MANUAL.md](pagos/GUIA_PAGOS_TUU_VS_MANUAL.md)
2. Revisa: [INTEGRACION_TUU.md](pagos/INTEGRACION_TUU.md)

---

## 📞 Soporte

Si tienes problemas:
1. Busca en esta documentación la guía correspondiente
2. Revisa los archivos de emergencia (si aplica)
3. Verifica los logs del sistema

---

## 📝 Mantenimiento de Documentación

### Estructura de Carpetas:
```
doc/
├── README.md                    (Este archivo - Índice principal)
├── impresion/                   (Todo sobre impresoras)
│   ├── GUIA_IMPRESORA_TERMICA_CLIENTE_SERVIDOR.md
│   ├── GUIA_INSTALACION_WINDOWS7.md
│   ├── RESUMEN_INTEGRACION_COMPLETA.md
│   └── INSTRUCCIONES_PRUEBA_SEGURA.txt
├── servidor/                    (Ubuntu, acceso remoto, migración)
│   ├── IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md
│   ├── GUIA_ACCESO_REMOTO_UBUNTU.md
│   ├── RESUMEN_SERVIDOR_Y_UBUNTU.md
│   └── GUIA_MIGRACION_UBUNTU.md
├── pagos/                       (TUU, pagos, transacciones)
│   ├── GUIA_PAGOS_TUU_VS_MANUAL.md
│   ├── INTEGRACION_TUU.md
│   ├── IMPLEMENTACION_FRONTEND_PAGOS.md
│   └── README_IMPLEMENTACION_PAGOS.md
└── sistema/                     (Configuraciones generales)
    ├── GUIA_CONFIGURACION_PRECIOS.md
    ├── GUIA_TIMEZONE.md
    └── plan-estacionamiento-lavado.md
```

### Agregar Nueva Documentación:
1. Determinar categoría (impresion, servidor, pagos, sistema)
2. Crear archivo en carpeta correspondiente
3. Actualizar este README.md con el nuevo archivo

---

**Última actualización:** 13 de Octubre, 2025  
**Versión del Sistema:** 2.0 - Sistema Unificado de Impresión + Acceso Remoto

