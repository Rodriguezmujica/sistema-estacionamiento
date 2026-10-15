# 📦 Instrucciones: Copiar Archivos al USB y Usar en Linux Mint

## 📋 Archivos Necesarios

Copia estos archivos manteniendo la estructura de carpetas:

```
USB:/sistemaEstacionamiento/
├── tests/
│   ├── test_gd_logo.php              ⭐ Diagnóstico principal
│   ├── fix_logo_problema.php          ⭐ Solución del logo
│   ├── test_imprimir_logo.php         ⭐ Prueba final
│   └── crear_logo_simple.php          ⚠️ Alternativa si falla
└── scripts/
    └── instalar_gd_linux.sh          ⚠️ Solo si falta GD
```

---

## 🚀 Paso 1: Copiar Archivos al USB (Desde Windows)

### Ubicación de los archivos en tu sistema:

```
C:\xampp\htdocs\sistemaEstacionamiento\
├── tests\
│   ├── test_gd_logo.php
│   ├── fix_logo_problema.php
│   ├── test_imprimir_logo.php
│   └── crear_logo_simple.php
└── scripts\
    └── instalar_gd_linux.sh
```

### En el USB crea esta estructura:

1. Crea carpeta: `sistemaEstacionamiento` en el USB
2. Dentro crea: `tests` y `scripts`
3. Copia los archivos a sus carpetas correspondientes

---

## 🔧 Paso 2: Copiar del USB al Servidor Linux Mint

### Opción A: Copiar con USB Montado

```bash
# 1. Montar USB (si no está automático)
sudo mkdir -p /mnt/usb
sudo mount /dev/sdb1 /mnt/usb  # Ajusta sdb1 según tu USB

# 2. Copiar archivos
sudo cp -r /mnt/usb/sistemaEstacionamiento/tests \
          /var/www/html/sistemaEstacionamiento/

sudo cp -r /mnt/usb/sistemaEstacionamiento/scripts \
          /var/www/html/sistemaEstacionamiento/

# 3. Dar permisos
sudo chmod +x /var/www/html/sistemaEstacionamiento/scripts/instalar_gd_linux.sh
sudo chmod 755 /var/www/html/sistemaEstacionamiento/tests/*.php

# 4. Desmontar USB
sudo umount /mnt/usb
```

### Opción B: Copiar Directamente (si USB está automático)

```bash
# Encontrar dónde se montó el USB
lsblk | grep sdb

# Copiar (ajusta la ruta según donde se montó)
sudo cp -r /media/[usuario]/[nombre-usb]/sistemaEstacionamiento/tests \
          /var/www/html/sistemaEstacionamiento/

sudo cp -r /media/[usuario]/[nombre-usb]/sistemaEstacionamiento/scripts \
          /var/www/html/sistemaEstacionamiento/

# Dar permisos
sudo chmod +x /var/www/html/sistemaEstacionamiento/scripts/instalar_gd_linux.sh
```

---

## 🔍 Paso 3: Ejecutar Diagnóstico

```bash
cd /var/www/html/sistemaEstacionamiento
php tests/test_gd_logo.php
```

**Esto te mostrará:**
- ✅ Si GD está instalado
- ✅ Si los formatos están soportados
- ✅ Qué archivos de logo existen
- ❌ **Qué falta exactamente**

---

## 🔧 Paso 4: Solucionar el Logo (El Paso Más Importante)

```bash
php tests/fix_logo_problema.php
```

Este script:
1. Analiza el logo actual
2. Lo convierte a formato compatible
3. Crea versiones arregladas: `image_fixed.jpg` y `geek_fixed.png`
4. Verifica que funcionan

---

## 📝 Paso 5: Reemplazar el Logo Arreglado

```bash
# Hacer backup del original (IMPORTANTE)
cp imagenes/image.jpg imagenes/image.jpg.backup

# Usar el logo arreglado
cp imagenes/image_fixed.jpg imagenes/image.jpg

# Si prefieres usar PNG
cp ImpresionTermica/geek_fixed.png ImpresionTermica/geek.png
```

---

## ✅ Paso 6: Verificar que Funciona

```bash
php tests/test_imprimir_logo.php
```

**Debe mostrar:**
- ✅ Logo cargado exitosamente
- ✅ Ancho: 250 px (o similar, **NO 0x0**)
- ✅ Alto: 150 px (o similar, **NO 0x0**)
- ✅ Archivo de impresión generado exitosamente

---

## ⚠️ Paso 7 (Opcional): Si Falta GD

Si el diagnóstico en Paso 3 muestra que falta GD:

```bash
sudo bash scripts/instalar_gd_linux.sh
```

Esto instalará:
- PHP GD
- Librerías necesarias (libpng, libjpeg, etc.)
- Reiniciará Apache

---

## 🎯 Resumen: Orden de Ejecución

```bash
# 1. Ir al directorio
cd /var/www/html/sistemaEstacionamiento

# 2. Ver qué falta
php tests/test_gd_logo.php

# 3. Arreglar logo (LO MÁS IMPORTANTE)
php tests/fix_logo_problema.php

# 4. Reemplazar logo
cp imagenes/image.jpg imagenes/image.jpg.backup
cp imagenes/image_fixed.jpg imagenes/image.jpg

# 5. Probar
php tests/test_imprimir_logo.php

# 6. Si falta GD
sudo bash scripts/instalar_gd_linux.sh
```

---

## 📁 Verificación Final

Después de todo, verifica que los archivos están:

```bash
ls -la /var/www/html/sistemaEstacionamiento/tests/
ls -la /var/www/html/sistemaEstacionamiento/scripts/
ls -la /var/www/html/sistemaEstacionamiento/imagenes/image*.jpg
```

---

## 💡 Consejos

1. **Haz backup del logo original** antes de reemplazarlo
2. **Ejecuta los scripts en orden** - primero diagnóstico, luego solución
3. **Revisa los logs** si hay problemas: `sudo tail -f /var/log/apache2/error.log`
4. **El logo arreglado es binario** (blanco y negro puro) - siempre funciona

---

## 🆘 Si Algo Sale Mal

1. **Verifica permisos:**
   ```bash
   ls -la imagenes/image.jpg
   chmod 644 imagenes/image.jpg
   ```

2. **Vuelve al logo original:**
   ```bash
   cp imagenes/image.jpg.backup imagenes/image.jpg
   ```

3. **Prueba logo simple:**
   ```bash
   php tests/crear_logo_simple.php
   cp ImpresionTermica/geek_logo_simple.png ImpresionTermica/geek.png
   ```

---

**¡Listo!** Después de estos pasos, el logo debería funcionar en los tickets. 🎉

