# 🔧 Solución: Logo muestra 0x0 px y falla al imprimir

## 📋 Problema Detectado

El logo se carga con EscposImage pero muestra:
- ✅ Logo cargado exitosamente
- ⚠️ Ancho: 0 px
- ⚠️ Alto: 0 px
- ❌ Error al imprimir: "Failed to load image"

Esto significa que el logo original tiene un formato que EscposImage no puede procesar correctamente.

---

## 🎯 Solución: Convertir Logo a Formato Compatible

### Paso 1: Ejecutar el script de conversión

```bash
cd /var/www/html/sistemaEstacionamiento
php tests/fix_logo_problema.php
```

Este script:
1. ✅ Analiza el logo original
2. ✅ Redimensiona a tamaño óptimo para impresoras térmicas
3. ✅ Convierte a binario (blanco y negro puro)
4. ✅ Elimina transparencias y problemas de formato
5. ✅ Genera versiones compatibles (JPG y PNG)
6. ✅ Verifica que funcionan correctamente

---

### Paso 2: Reemplazar el logo

```bash
# Hacer backup del original
cp imagenes/image.jpg imagenes/image.jpg.backup

# Usar el logo arreglado
cp imagenes/image_fixed.jpg imagenes/image.jpg

# O usar el PNG si prefieres
cp ImpresionTermica/geek_fixed.png ImpresionTermica/geek.png
```

---

### Paso 3: Verificar que funciona

```bash
php tests/test_imprimir_logo.php
```

Ahora deberías ver:
- ✅ Logo cargado exitosamente
- ✅ Ancho: 250 px (o similar, pero > 0)
- ✅ Alto: 150 px (o similar, pero > 0)
- ✅ Archivo de impresión generado exitosamente

---

## 🔍 Por Qué Falla el Logo Original

El logo original probablemente tiene:
1. **Dimensiones problemáticas** - Muy grandes o formato extraño
2. **Transparencias** - Aunque sea JPG, puede tener problemas de canal alpha
3. **Compresión avanzada** - Formatos de JPG que GD no procesa bien
4. **Metadatos corruptos** - Información que confunde a EscposImage

---

## 💡 Solución Alternativa: Logo Simple

Si el script de conversión no funciona, crea un logo simple desde cero:

```bash
php tests/crear_logo_simple.php
```

Esto genera un logo básico en blanco y negro que **siempre funciona**.

---

## ✅ Verificación Final

Después de reemplazar el logo:

1. ✅ Verifica dimensiones:
   ```bash
   php -r "require 'ImpresionTermica/ticket/autoload.php'; 
            use Mike42\Escpos\EscposImage; 
            \$logo = EscposImage::load('imagenes/image.jpg', false); 
            echo \$logo->getWidth() . ' x ' . \$logo->getHeight();"
   ```
   **Debe mostrar dimensiones > 0**

2. ✅ Prueba impresión:
   ```bash
   php tests/test_imprimir_logo.php
   ```

3. ✅ Prueba en el sistema:
   - Imprime un ticket desde el sistema web
   - Verifica que el logo aparece

---

## 🐛 Si Todavía No Funciona

1. **Verifica logs de Apache:**
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. **Verifica que el archivo existe:**
   ```bash
   ls -la imagenes/image.jpg
   ```

3. **Prueba con un logo de prueba simple:**
   ```bash
   php tests/crear_logo_simple.php
   cp ImpresionTermica/geek_logo_simple.png ImpresionTermica/geek.png
   ```

---

## 📝 Archivos Generados

Después de ejecutar `fix_logo_problema.php`:

- `imagenes/image_fixed.jpg` - Logo JPG arreglado
- `ImpresionTermica/geek_fixed.png` - Logo PNG arreglado

Estos archivos están optimizados para impresoras térmicas y deberían funcionar sin problemas.

---

**¿Listo?** Ejecuta el script de solución:

```bash
php tests/fix_logo_problema.php
```

El logo debería funcionar ahora. 🎉

