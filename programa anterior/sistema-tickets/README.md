# 🖨️ Sistema de Tickets - Estacionamiento/Lavado Los Ríos

## 📋 Descripción
Sistema de impresión de tickets térmicos adaptado del sistema anterior, compatible con impresoras de red y Windows.

## 🔧 Instalación

### 1. Instalar dependencias PHP
```bash
composer install
```

### 2. Configurar impresora

#### Opción A: Impresora de red (Recomendado)
- Configurar IP fija en la impresora (ej: 192.168.1.100)
- Modificar `$ip_impresora` en `print_ticket.php`

#### Opción B: Impresora compartida en Windows
- Compartir la impresora como "POSESTACIONAMIENTO"
- Modificar `$nombre_impresora` en `print_ticket.php`

### 3. Configurar datos de la empresa
Editar en `print_ticket.php`:
```php
$printer->text("LAVADO DE AUTOS LOS RÍOS" . "\n");
$printer->text("Dirección: [TU_DIRECCION]" . "\n");
$printer->text("Teléfono: [TU_TELEFONO]" . "\n");
```

## 🚀 Uso

1. Abrir `formulario_ticket.html` en el navegador
2. Seleccionar tipo de servicio
3. Ingresar datos del vehículo
4. Hacer clic en "Imprimir Ticket"

## 📊 Funcionalidades

### ✅ Implementadas:
- ✅ Impresión de tickets de estacionamiento
- ✅ Impresión de tickets de lavado
- ✅ Cálculo automático de tiempo
- ✅ Conexión por red o Windows
- ✅ Interfaz web moderna
- ✅ Manejo de errores

### 🔄 Adaptadas del sistema anterior:
- ✅ Estructura del ticket
- ✅ Librería Escpos
- ✅ Formato de impresión
- ✅ Configuración de impresora

## 🛠️ Compatibilidad
- **PHP 7.4+**
- **Impresoras térmicas 80mm/58mm**
- **Windows/Linux**
- **Red local**

## 📞 Soporte
Para problemas con la impresora:
1. Verificar conexión de red
2. Comprobar nombre/IP de impresora
3. Revisar permisos de Windows (si aplica)
