# 🔧 Solución de Permisos para Impresora en Linux Mint

## 📊 Situación Detectada

- **Dispositivo**: `/dev/usb/lp0`
- **Permisos actuales**: `crw-rw---- 1 root lp`
- **ID Impresora**: `0519:000b (Star BSC10)`
- **Usuario PHP**: `www-data` (probablemente)

---

## ⚠️ Problema

El usuario `www-data` (que ejecuta PHP/Apache) **no tiene permisos** para escribir en `/dev/usb/lp0` porque:
- Solo el usuario `root` y el grupo `lp` tienen permisos
- `www-data` no pertenece al grupo `lp`

---

## ✅ Solución 1: Agregar www-data al grupo lp (RECOMENDADA)

```bash
# Agregar usuario www-data al grupo lp
sudo usermod -a -G lp www-data

# Verificar
groups www-data
# Debe mostrar: www-data : www-data lp

# Reiniciar Apache para aplicar cambios
sudo systemctl restart apache2
# O si usas otro servidor:
# sudo service apache2 restart
```

**Esta es la solución más segura y permanente.**

---

## ✅ Solución 2: Cambiar permisos del dispositivo (Alternativa)

```bash
# Hacer que todos puedan leer y escribir
sudo chmod 666 /dev/usb/lp0

# Verificar
ls -l /dev/usb/lp0
# Debe mostrar: crw-rw-rw- 1 root lp
```

**Problema**: Estos permisos se pierden al reiniciar. Necesitas hacerlo permanente con una regla udev.

### Hacer permanente con udev:

```bash
# Crear regla udev
sudo nano /etc/udev/rules.d/99-impresora-estacionamiento.rules
```

Agregar esta línea:

```
KERNEL=="usblp0", MODE="0666", GROUP="lp"
```

Guardar, salir y ejecutar:

```bash
# Recargar reglas
sudo udevadm control --reload-rules
sudo udevadm trigger

# Verificar
ls -l /dev/usb/lp0
```

---

## 🧪 Paso 3: Probar que Funciona

Después de aplicar los permisos, prueba:

```bash
# Verificar que www-data puede escribir
sudo -u www-data test -w /dev/usb/lp0 && echo "✅ www-data puede escribir" || echo "❌ Sin permisos"

# Probar impresión
curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \
  -H "Content-Type: application/json" \
  -d '{
    "tipo": "test",
    "datos": {"mensaje": "Prueba de permisos"},
    "impresora": "POSESTACIONAMIENTO"
  }'
```

---

## 📝 Resumen de Comandos (Todo junto)

```bash
# 1. Agregar usuario al grupo lp
sudo usermod -a -G lp www-data

# 2. Reiniciar Apache
sudo systemctl restart apache2

# 3. Verificar permisos
sudo -u www-data test -w /dev/usb/lp0 && echo "✅ OK" || echo "❌ ERROR"

# 4. Probar impresión
curl -X POST http://localhost/sistemaEstacionamiento/print-service-php/imprimir.php \
  -H "Content-Type: application/json" \
  -d '{"tipo":"test","datos":{"mensaje":"Test"},"impresora":"POSESTACIONAMIENTO"}'
```

---

## 🐛 Si No Funciona

Verifica los logs de PHP:

```bash
# Ver logs de Apache
tail -f /var/log/apache2/error.log

# Ver permisos actuales
ls -l /dev/usb/lp0

# Ver usuarios del grupo lp
getent group lp

# Ver grupos del usuario www-data
groups www-data
```

