# 🖼️ Guía de Debug: Problema del Logo en Linux Mint

## 📋 Resumen

Esta guía te ayuda a diagnosticar y solucionar el problema del logo que no aparece en los tickets impresos en Linux Mint.

---

## 🔍 Paso 1: Diagnóstico

Ejecuta el script de diagnóstico completo:

```bash
cd /var/www/html/sistemaEstacionamiento
php tests/test_gd_logo.php
```

**Este script te dirá:**
- ✅ Si GD está instalado
- ✅ Si PNG/JPEG Support está habilitado
- ✅ Si los archivos de logo existen
- ✅ Si los archivos tienen permisos correctos
- ✅ Qué librerías del sistema faltan
- ❌ **Exactamente qué está fallando**

---

## 🔧 Paso 2: Solucionar el Problema

### Opción A: Instalación Automática (Recomendada)

```bash
sudo bash scripts/instalar_gd_linux.sh
```

Este script:
1. Instala todas las dependencias necesarias
2. Reinstala PHP GD correctamente
3. Reinicia Apache
4. Verifica que todo funcionó

### Opción B: Instalación Manual

```bash
sudo apt-get update
sudo apt-get install php-gd libpng-dev libjpeg-dev libgd-dev
sudo systemctl restart apache2
```

---

## 🧪 Paso 3: Probar el Logo

Después de instalar GD, prueba que el logo se carga:

```bash
php tests/test_imprimir_logo.php
```

Este script:
- ✅ Prueba cargar el logo con EscposImage
- ✅ Genera un archivo de impresión de prueba
- ✅ Te dice si todo funciona

---

## 📊 Paso 4: Ver Logs en Tiempo Real

Durante una impresión, mira los logs para ver qué pasa:

```bash
sudo tail -f /var/log/apache2/error.log
```

Luego intenta imprimir un ticket desde el sistema. Verás mensajes como:

- ✅ `Logo cargado: image.jpg` → Todo bien
- ❌ `Error cargando logo: ...` → Hay un problema
- ⚠️ `Logo no encontrado` → El archivo no existe

---

## 📁 Archivos de Debug Creados

### 1. **tests/test_gd_logo.php**
   - Diagnóstico completo del sistema
   - Verifica GD, formatos, archivos, permisos
   - **Úsalo primero para saber qué falta**

### 2. **tests/test_imprimir_logo.php**
   - Prueba directa de carga e impresión del logo
   - Genera archivo de prueba
   - **Úsalo después de instalar GD para verificar**

### 3. **tests/crear_logo_simple.php**
   - Genera un logo simple en blanco y negro
   - Útil si el logo original tiene problemas
   - **Úsalo como alternativa**

### 4. **scripts/instalar_gd_linux.sh**
   - Script de instalación automática
   - Instala todo lo necesario
   - **Ejecútalo para solucionar el problema**

---

## 🔍 Problemas Comunes

### Problema 1: "GD no está instalado"
```bash
sudo apt-get install php-gd
sudo systemctl restart apache2
```

### Problema 2: "PNG Support deshabilitado"
```bash
sudo apt-get install libpng-dev
sudo apt-get install --reinstall php-gd
sudo systemctl restart apache2
```

### Problema 3: "Archivo no encontrado"
```bash
# Verificar que existen
ls -la imagenes/image.jpg
ls -la ImpresionTermica/geek.png

# Si no existen, copiar desde otra ubicación o crear simples
php tests/crear_logo_simple.php
```

### Problema 4: "Permisos denegados"
```bash
chmod 644 imagenes/image.jpg
chmod 644 ImpresionTermica/geek.png
```

---

## 📝 Checklist de Verificación

- [ ] Ejecutado `php tests/test_gd_logo.php`
- [ ] GD instalado: `php -m | grep gd`
- [ ] PNG Support habilitado: `php -r "echo gd_info()['PNG Support'];"`
- [ ] Archivos de logo existen
- [ ] Archivos tienen permisos de lectura
- [ ] Apache reiniciado después de instalar GD
- [ ] Logs revisados durante impresión
- [ ] Prueba de logo exitosa: `php tests/test_imprimir_logo.php`

---

## 💡 Consejos

1. **Siempre reinicia Apache** después de instalar módulos PHP:
   ```bash
   sudo systemctl restart apache2
   ```

2. **Revisa los logs** mientras imprimes un ticket para ver errores en tiempo real:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

3. **El sistema funciona sin logo** - Si GD no se puede instalar por alguna razón, los tickets se imprimen sin logo pero todo lo demás funciona.

4. **El código ahora registra mejor los errores** - Revisa los logs para ver mensajes claros sobre qué está fallando.

---

## 🎯 Resultado Esperado

Después de seguir estos pasos:

1. ✅ `test_gd_logo.php` muestra "TODO ESTÁ CORRECTO"
2. ✅ `test_imprimir_logo.php` carga el logo exitosamente
3. ✅ Los logs muestran "✅ Logo cargado: image.jpg"
4. ✅ El logo aparece en los tickets impresos

---

## 📚 Más Información

- **SOLUCION_LOGO_LINUX.txt** - Guía rápida de referencia
- Ejecuta los scripts de prueba para diagnóstico detallado

---

**¿Listo para empezar?** 

```bash
php tests/test_gd_logo.php
```

Este comando te dirá exactamente qué hacer. 🚀

