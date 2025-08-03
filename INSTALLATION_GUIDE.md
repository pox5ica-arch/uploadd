# Guía de Instalación - Poxica Image Uploader

## 🚀 Instalación Rápida

### Paso 1: Instalar el Plugin

1. **Descarga** el archivo `poxica-image-uploader.zip`
2. **Sube a WordPress**:
   - Ve a `WordPress Admin → Plugins → Añadir nuevo → Subir plugin`
   - Selecciona el archivo zip y haz clic en "Instalar ahora"
3. **Activa** el plugin

### Paso 2: Configurar Google Drive API

#### 2.1 Crear Proyecto en Google Cloud

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Haz clic en "Seleccionar proyecto" → "Nuevo proyecto"
3. Introduce un nombre (ej: "Mi Tienda Image Uploader")
4. Haz clic en "Crear"

#### 2.2 Habilitar Google Drive API

1. En el dashboard del proyecto, ve a **APIs y servicios → Biblioteca**
2. Busca "Google Drive API"
3. Haz clic en "Google Drive API" → "Habilitar"

#### 2.3 Crear Service Account

1. Ve a **APIs y servicios → Credenciales**
2. Haz clic en **"Crear credenciales" → "Cuenta de servicio"**
3. Completa los datos:
   - **Nombre**: `poxica-uploader`
   - **ID**: se genera automáticamente
   - **Descripción**: `Service account para Poxica Image Uploader`
4. Haz clic en **"Crear y continuar"**
5. En "Función", selecciona **"Editor"** (opcional, para más seguridad)
6. Haz clic en **"Continuar"** → **"Listo"**

#### 2.4 Generar Clave JSON

1. En la lista de **Cuentas de servicio**, haz clic en el email de la cuenta creada
2. Ve a la pestaña **"Claves"**
3. Haz clic en **"Agregar clave" → "Crear clave nueva"**
4. Selecciona **"JSON"** → **"Crear"**
5. **Guarda el archivo JSON** descargado (lo necesitarás en el paso 3)

### Paso 3: Configurar Carpeta en Google Drive

#### 3.1 Crear Carpeta

1. Ve a [Google Drive](https://drive.google.com/)
2. Haz clic derecho → **"Nueva carpeta"**
3. Nombra la carpeta (ej: "Pedidos Poxica")
4. **Copia el ID de la carpeta** desde la URL:
   ```
   https://drive.google.com/drive/folders/[ESTE_ES_EL_ID]
   ```

#### 3.2 Compartir con Service Account

1. **Haz clic derecho** en la carpeta creada → **"Compartir"**
2. En "Añadir personas y grupos", **pega el email** del Service Account
   - Lo encuentras en el archivo JSON descargado: `"client_email"`
3. Cambia permisos a **"Editor"**
4. **Desactiva** "Notificar a las personas"
5. Haz clic en **"Enviar"**

### Paso 4: Configurar el Plugin

#### 4.1 Configuración de Google Drive

1. Ve a **WordPress Admin → Poxica Uploader → Configuración**
2. En la pestaña **"Google Drive"**:
   - **Credenciales**: Abre el archivo JSON con un editor de texto y **copia todo el contenido**
   - **Carpeta raíz**: Pega el **ID de la carpeta** copiado en el paso 3.1
3. Haz clic en **"Probar Conexión"** para verificar
4. Si es exitoso, haz clic en **"Guardar cambios"**

#### 4.2 Configuración General

1. En la pestaña **"General"**:
   - **Días para limpiar pedidos no pagados**: `3` (recomendado)
   - **Expiración de enlaces**: `7` días (recomendado)
   - **Tamaño máximo de archivo**: `10` MB (ajustar según necesidades)
   - **Tipos de archivo**: `jpg,jpeg,png` (puedes añadir más)
2. **Guardar cambios**

#### 4.3 Configuración de Emails

1. En la pestaña **"Email"**:
   - **Nombre del remitente**: Nombre de tu tienda
   - **Email del remitente**: tu-email@tutienda.com
   - **Emails adicionales**: emails extra para notificaciones de admin
2. **Personaliza las plantillas** si es necesario
3. **Prueba los emails** usando el botón "Enviar email de prueba"
4. **Guardar cambios**

## ✅ Verificación de Instalación

### Prueba Completa

1. **Crear pedido de prueba**:
   - Ve a tu tienda WooCommerce
   - Añade un producto al carrito
   - Completa la compra (puedes usar método de pago de prueba)

2. **Verificar funcionamiento**:
   - Revisa si llegó el email con el enlace de subida
   - Accede al enlace y prueba subir una imagen
   - Verifica en Google Drive que se creó la carpeta
   - Comprueba en **Poxica Uploader → Logs** la actividad

### Panel de Monitoreo

- **Dashboard principal**: `Poxica Uploader`
  - Estadísticas de pedidos y imágenes
  - Estado de configuración
  - Actividad reciente

- **Logs**: `Poxica Uploader → Logs`
  - Historial completo de actividad
  - Útil para diagnosticar problemas

- **Limpieza**: `Poxica Uploader → Limpieza`
  - Estado del cron automático
  - Ejecutar limpieza manual

## 🔧 Configuración Avanzada

### Rewrite Rules (si es necesario)

Si los enlaces de subida no funcionan:

1. Ve a **Ajustes → Enlaces permanentes**
2. Haz clic en **"Guardar cambios"** (recarga las reglas)

### Cron Jobs del Servidor

Para mejor rendimiento, configura cron del servidor:

```bash
# Editar crontab
crontab -e

# Añadir esta línea (ejecuta cada hora)
0 * * * * /usr/bin/php /ruta/a/wordpress/wp-cron.php >/dev/null 2>&1
```

### Optimizaciones PHP

Ajustes recomendados en `php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

## 🐛 Solución de Problemas Comunes

### Error: "Google Drive no configurado"

**Causa**: Credenciales JSON incorrectas o incompletas

**Solución**:
1. Verifica que copiaste **todo** el contenido del archivo JSON
2. Asegúrate de que no hay espacios extra al inicio o final
3. Verifica que el archivo JSON no esté corrupto

### Error: "Carpeta no encontrada"

**Causa**: ID de carpeta incorrecto o no compartida

**Solución**:
1. Verifica el ID de la carpeta (sin espacios extra)
2. Asegúrate de que la carpeta esté compartida con el Service Account
3. Verifica que los permisos sean de "Editor"

### Error: "Token expirado"

**Causa**: Enlaces de subida expirados

**Solución**:
1. Los enlaces expiran según configuración (7 días por defecto)
2. Puedes reenviar el email desde WooCommerce
3. O crear un nuevo enlace desde el admin

### Imágenes no se suben

**Causa**: Problemas de permisos o configuración

**Solución**:
1. Verifica permisos de `wp-content/uploads/`
2. Revisa límites de PHP (tamaño de archivo)
3. Activa SSL/HTTPS en tu sitio
4. Revisa la consola del navegador para errores

### Emails no se envían

**Causa**: Configuración de correo del servidor

**Solución**:
1. Instala un plugin SMTP (ej: WP Mail SMTP)
2. Configura SMTP de tu proveedor de hosting
3. Verifica plantillas de email en la configuración
4. Usa la función "Enviar email de prueba"

## 📞 Soporte

Si necesitas ayuda adicional:

1. **Revisa los logs**: `Poxica Uploader → Logs`
2. **Verifica configuración**: Paso a paso según esta guía
3. **Consulta documentación**: README.md completo
4. **Contacta soporte**: Con detalles específicos del error

## 🎯 Próximos Pasos

Una vez instalado correctamente:

1. **Personaliza emails** con tu marca
2. **Ajusta configuraciones** según tus necesidades
3. **Forma a tu equipo** en el uso del panel admin
4. **Monitorea regularmente** los logs y estadísticas
5. **Realiza backups** de la configuración

¡Tu plugin Poxica Image Uploader está listo para automatizar la gestión de imágenes de pedidos!