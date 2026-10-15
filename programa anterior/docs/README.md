# 🅿️ Sistema de Estacionamiento Los Ríos

Sistema completo de gestión de estacionamiento y servicios de lavado con integración de pagos TUU e impresión térmica.

---

## 🚀 Características Principales

- ✅ **Gestión de Ingresos y Salidas** - Control de vehículos en tiempo real
- ✅ **Cálculo Automático de Cobros** - Por minutos, franjas horarias
- ✅ **Servicios de Lavado** - Gestión completa de lavadería
- ✅ **Integración TUU** - Pagos con terminal electrónico
- ✅ **Impresión Térmica** - Tickets automáticos (ingreso, salida, lavado, cierre)
- ✅ **Reportes Completos** - Dashboard, reportes diarios, mensuales, cierre de caja
- ✅ **Clientes Mensuales** - Gestión de abonos y membresías
- ✅ **Acceso Remoto** - Visualización desde internet (HTTPS)
- ✅ **Responsive** - Funciona en PC, tablet y smartphone

---

## 📋 Requisitos del Sistema

### **Servidor (Producción):**
- Ubuntu 18.04+ (o Windows Server)
- Apache 2.4+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+

### **Cliente (Oficina):**
- Windows 7/10/11
- XAMPP (para impresión local)
- Impresora térmica Star BSC10 (o compatible)
- Navegador moderno (Chrome, Firefox, Edge)

### **Acceso Remoto:**
- Router con port forwarding
- DuckDNS (dominio gratis) o dominio propio
- Certificado SSL (Let's Encrypt - gratis)

---

## 📚 Documentación

Toda la documentación está organizada en la carpeta `doc/`:

### **[📖 Ver Índice Completo de Documentación →](doc/README.md)**

### **Guías de Inicio Rápido:**

#### 🖨️ **Configurar Impresora (Windows 7):**
1. [Guía de Instalación Windows 7](doc/impresion/GUIA_INSTALACION_WINDOWS7.md)
2. [Resumen de Integración Completa](doc/impresion/RESUMEN_INTEGRACION_COMPLETA.md)

#### 🌐 **Configurar Servidor Ubuntu con Acceso Remoto:**
1. [Implementación DuckDNS + Let's Encrypt](doc/servidor/IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md) ⭐ **PRINCIPAL**
2. [Guía de Migración a Ubuntu](doc/servidor/GUIA_MIGRACION_UBUNTU.md)

#### 💳 **Configurar Pagos TUU:**
1. [Guía TUU vs Manual](doc/pagos/GUIA_PAGOS_TUU_VS_MANUAL.md)
2. [Integración TUU](doc/pagos/INTEGRACION_TUU.md)

#### ⚙️ **Configurar Sistema:**
1. [Configuración de Precios](doc/sistema/GUIA_CONFIGURACION_PRECIOS.md)
2. [Configuración de Zona Horaria](doc/sistema/GUIA_TIMEZONE.md)

---

## 🏗️ Estructura del Proyecto

```
sistemaEstacionamiento/
├── api/                        # APIs backend (PHP)
│   ├── registrar-ingreso.php   # Registro de ingresos
│   ├── registrar-salida.php    # Procesamiento de salidas/cobros
│   ├── api_cierre_caja.php     # Cierre de caja
│   └── ...                     # Otras APIs
├── doc/                        # 📚 Documentación organizada
│   ├── README.md               # Índice de documentación
│   ├── impresion/              # Guías de impresión
│   ├── servidor/               # Guías de servidor/acceso remoto
│   ├── pagos/                  # Guías de pagos TUU
│   └── sistema/                # Configuraciones generales
├── ImpresionTermica/           # Sistema de impresión térmica
│   ├── ticket.php              # Impresión de tickets de ingreso
│   ├── ticketsalida.php        # Impresión de tickets de salida
│   ├── cierre_caja.php         # Impresión de cierre de caja
│   └── ticket/                 # Librería escpos-php
├── JS/                         # JavaScript del frontend
│   ├── ingreso.js              # Lógica de ingresos
│   ├── cobro.js                # Lógica de cobros
│   ├── lavados.js              # Lógica de lavados
│   ├── reporte.js              # Lógica de reportes
│   └── print-service-client-win7.js  # Cliente de impresión
├── print-service-php/          # Servicio de impresión PHP (Win7)
│   └── imprimir.php            # Endpoint de impresión
├── secciones/                  # Páginas del sistema
│   ├── admin.php               # Panel de administración
│   ├── lavados.html            # Gestión de lavados
│   └── reporte.html            # Reportes y cierre
├── scss/                       # Estilos SCSS
├── sql/                        # Scripts de base de datos
├── index.php                   # Dashboard principal
├── login.php                   # Página de login
└── conexion.php                # Configuración de BD
```

---

## 🔧 Instalación

### **1. Clonar/Copiar el Proyecto:**

```bash
# En servidor Ubuntu
git clone [URL_REPOSITORIO]
# O copiar carpeta completa
sudo cp -r sistemaEstacionamiento /var/www/html/
```

### **2. Configurar Base de Datos:**

```bash
# Importar base de datos
mysql -u root -p
CREATE DATABASE estacionamiento;
USE estacionamiento;
SOURCE estacionamiento.sql;
```

### **3. Configurar Conexión:**

```bash
# Copiar ejemplo y editar
cp conexion.php.example conexion.php
nano conexion.php
# Configurar credenciales de BD
```

### **4. Dar Permisos:**

```bash
sudo chown -R www-data:www-data /var/www/html/sistemaEstacionamiento
sudo chmod -R 755 /var/www/html/sistemaEstacionamiento
```

### **5. Acceder al Sistema:**

```
http://localhost/sistemaEstacionamiento/
o
http://tu-servidor/sistemaEstacionamiento/
```

**Usuario por defecto:** admin  
**Contraseña:** (configurar en la BD)

---

## 🖨️ Configurar Impresión

### **Para Windows 7:**
Sigue la guía: [Instalación Windows 7](doc/impresion/GUIA_INSTALACION_WINDOWS7.md)

### **Para Windows 10+:**
Puedes usar Node.js: Ver carpeta `print-service/`

---

## 🌐 Configurar Acceso Remoto

Para permitir acceso desde internet (ej: tu jefe desde casa):

**Sigue la guía completa:** [Implementación DuckDNS + Let's Encrypt](doc/servidor/IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md)

**Resultado:**
- Acceso local: `http://192.168.1.X/sistemaEstacionamiento/`
- Acceso remoto: `https://tu-dominio.duckdns.org/`

---

## 💳 Sistema de Pagos

### **Métodos Soportados:**
- ✅ **Efectivo (Manual)** - Registro manual de pago
- ✅ **TUU - Efectivo** - Con voucher de terminal
- ✅ **TUU - Débito** - Con voucher de terminal
- ✅ **TUU - Crédito** - Con voucher de terminal
- ✅ **Transferencia** - Registro manual

Ver: [Guía de Pagos](doc/pagos/GUIA_PAGOS_TUU_VS_MANUAL.md)

---

## 📊 Reportes Disponibles

- **Dashboard en Tiempo Real** - Ingresos del día, vehículos activos
- **Reporte Diario** - Ingresos y egresos del día
- **Reporte Mensual** - Estadísticas del mes
- **Cierre de Caja** - Desglose por método de pago
- **Historial de Lavados** - Por patente
- **Clientes Mensuales** - Gestión de abonos

---

## 🔐 Seguridad

- ✅ **HTTPS** - Certificado SSL (Let's Encrypt)
- ✅ **Autenticación** - Sistema de login con roles
- ✅ **Firewall** - Configurado en servidor
- ✅ **Sesiones** - Gestión segura de sesiones PHP
- ✅ **SQL** - Preparación de consultas (prevención de inyección)

---

## 🐛 Solución de Problemas

### **Impresora no imprime:**
1. Verificar que esté encendida y conectada
2. Verificar nombre de impresora: `POSESTACIONAMIENTO`
3. Ver logs: Consola del navegador (F12)
4. Consultar: [Guía de Impresión](doc/impresion/GUIA_IMPRESORA_TERMICA_CLIENTE_SERVIDOR.md)

### **No puedo acceder desde internet:**
1. Verificar puerto abierto en router
2. Verificar DuckDNS actualizado
3. Ver logs: `sudo tail -f /var/log/apache2/error.log`
4. Consultar: [Guía de Acceso Remoto](doc/servidor/IMPLEMENTACION_DUCKDNS_LETSENCRYPT.md)

### **TUU no funciona:**
1. Ver: [Sistema de Emergencia TUU](doc/SISTEMA_EMERGENCIA_TUU.md)
2. Usar pago manual como alternativa

---

## 📞 Soporte

**Documentación completa:** [doc/README.md](doc/README.md)

---

## 📝 Versión

**Versión Actual:** 2.0  
**Última Actualización:** 13 de Octubre, 2025

### **Changelog:**
- **v2.0** - Sistema unificado de impresión + Acceso remoto
- **v1.5** - Integración TUU
- **v1.0** - Sistema base

---

## 📄 Licencia

Proyecto propietario - Estacionamiento Los Ríos

---

## 👥 Créditos

Desarrollado para **Estacionamiento Los Ríos**  
Valdivia, Región de Los Ríos, Chile

---

**¿Necesitas ayuda?** Revisa la [documentación completa](doc/README.md) 📚

