# Poxica Image Uploader

Un plugin completo de WordPress para WooCommerce que automatiza la gestión de imágenes de pedidos con almacenamiento en Google Drive y limpieza automática de recursos.

## 🚀 Características Principales

- **Integración completa con WooCommerce**: Detecta automáticamente pedidos creados y pagos confirmados
- **Gestión inteligente de imágenes por producto**: Cada unidad comprada tiene su propia subcarpeta
- **Almacenamiento en Google Drive**: Integración segura mediante Service Account
- **Limpieza automática**: Elimina carpetas de pedidos no pagados después de 3 días
- **Interfaz moderna de subida**: Drag & drop con Dropzone.js
- **Notificaciones por email**: Plantillas personalizables para clientes y administradores
- **Cron jobs automáticos**: Tareas de mantenimiento programadas
- **Seguridad avanzada**: Validación de archivos y protección contra código malicioso

## 📋 Requisitos

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+
- Extensiones PHP: `openssl`, `fileinfo`, `curl`
- Cuenta de Google Cloud Platform con API de Google Drive habilitada
- HTTPS obligatorio para producción

## 🛠️ Instalación

### 1. Instalación del Plugin

1. Descarga el archivo `poxica-image-uploader.zip`
2. Ve a **WordPress Admin → Plugins → Añadir nuevo → Subir plugin**
3. Selecciona el archivo zip y haz clic en "Instalar ahora"
4. Activa el plugin

### 2. Configuración de Google Drive

#### Crear Service Account

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la **Google Drive API**:
   - Ve a **APIs & Services → Library**
   - Busca "Google Drive API" y habilítala

4. Crear Service Account:
   - Ve a **APIs & Services → Credentials**
   - Haz clic en "Create Credentials" → "Service Account"
   - Completa el nombre y descripción
   - Haz clic en "Create and Continue"

5. Generar clave JSON:
   - En la lista de Service Accounts, haz clic en el email del account creado
   - Ve a la pestaña "Keys"
   - Haz clic en "Add Key" → "Create new key"
   - Selecciona "JSON" y descarga el archivo

#### Configurar carpeta en Google Drive

1. Crea una carpeta en Google Drive para almacenar los pedidos
2. Comparte la carpeta con el email del Service Account (con permisos de Editor)
3. Copia el ID de la carpeta desde la URL:
   ```
   https://drive.google.com/drive/folders/[FOLDER_ID]
   ```

### 3. Configuración del Plugin

1. Ve a **WordPress Admin → Poxica Uploader → Configuración**
2. En la pestaña **Google Drive**:
   - Pega el contenido completo del archivo JSON en "Credenciales de Google Drive"
   - Introduce el Folder ID de la carpeta raíz
   - Haz clic en "Probar Conexión" para verificar

3. En la pestaña **General**:
   - Configura los días para limpiar pedidos no pagados (por defecto: 3)
   - Establece la expiración de enlaces de subida (por defecto: 7 días)
   - Ajusta el tamaño máximo de archivo
   - Define los tipos de archivo permitidos

4. En la pestaña **Email**:
   - Configura el remitente de los emails
   - Personaliza las plantillas de email
   - Añade emails adicionales para notificaciones de administrador

## 📚 Uso del Plugin

### Para Administradores

#### Panel Principal
- Ve a **Poxica Uploader** para ver estadísticas y estado del sistema
- Monitorea pedidos pendientes y completados
- Revisa la actividad reciente

#### Gestión de Logs
- **Poxica Uploader → Logs**: Ver todos los eventos del sistema
- Filtrar por fecha, acción o pedido específico

#### Limpieza Manual
- **Poxica Uploader → Limpieza**: Ejecutar limpieza manual
- Ver estadísticas de elementos pendientes de limpieza
- Verificar estado del cron automático

### Para Clientes

1. **Realizar pedido**: Compra productos en la tienda WooCommerce
2. **Recibir email**: Automáticamente recibe email con enlace de subida
3. **Subir imágenes**: 
   - Accede al enlace único
   - Arrastra y suelta imágenes en las áreas correspondientes
   - Una imagen por unidad de producto comprada
4. **Confirmación**: Recibe confirmación cuando todas las imágenes están subidas

## 🔧 Estructura de Archivos

```
poxica-image-uploader/
├── poxica-image-uploader.php          # Archivo principal del plugin
├── includes/                          # Clases PHP del plugin
│   ├── class-poxica-core.php         # Clase principal
│   ├── class-poxica-database.php     # Operaciones de base de datos
│   ├── class-poxica-google-drive.php # Integración con Google Drive
│   ├── class-poxica-order-handler.php # Gestión de pedidos WooCommerce
│   ├── class-poxica-upload-handler.php # Procesamiento de subidas
│   ├── class-poxica-email-notifications.php # Sistema de emails
│   ├── class-poxica-cron.php         # Tareas programadas
│   ├── class-poxica-admin.php        # Interfaz de administración
│   └── class-poxica-security.php     # Validaciones de seguridad
├── admin/                             # Vistas del panel de administración
│   └── views/
│       ├── main-page.php             # Panel principal
│       ├── settings-page.php         # Página de configuración
│       ├── logs-page.php             # Página de logs
│       └── cleanup-page.php          # Página de limpieza
├── templates/                         # Plantillas frontend
│   └── upload-page.php               # Página de subida de imágenes
├── assets/                           # Recursos estáticos
│   ├── js/
│   │   ├── upload.js                 # JavaScript para subida de imágenes
│   │   └── admin.js                  # JavaScript para admin
│   └── css/
│       ├── upload.css                # Estilos para página de subida
│       └── admin.css                 # Estilos para admin
├── languages/                        # Archivos de traducción
├── readme.txt                       # WordPress readme
└── README.md                        # Este archivo
```

## 🗃️ Base de Datos

El plugin crea 4 tablas personalizadas:

### `wp_poxica_orders`
Almacena información de pedidos para el sistema de subida de imágenes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único |
| `order_id` | INT | ID del pedido WooCommerce |
| `upload_token` | VARCHAR | Token único para subida |
| `drive_folder_id` | VARCHAR | ID de carpeta en Google Drive |
| `status` | ENUM | Estado: pending, uploading, completed, cancelled, expired |
| `token_expires` | DATETIME | Fecha de expiración del token |

### `wp_poxica_order_products`
Productos de cada pedido con sus cantidades.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único |
| `poxica_order_id` | INT | Referencia a poxica_orders |
| `product_id` | INT | ID del producto WooCommerce |
| `variation_id` | INT | ID de variación (si aplica) |
| `product_name` | VARCHAR | Nombre del producto |
| `variation_details` | TEXT | Detalles de la variación |
| `quantity` | INT | Cantidad comprada |
| `drive_folder_id` | VARCHAR | ID de carpeta del producto |

### `wp_poxica_uploaded_images`
Registro de imágenes subidas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único |
| `poxica_product_id` | INT | Referencia a poxica_order_products |
| `unit_number` | INT | Número de unidad (1, 2, 3...) |
| `original_filename` | VARCHAR | Nombre original del archivo |
| `drive_file_id` | VARCHAR | ID del archivo en Google Drive |
| `file_size` | INT | Tamaño del archivo en bytes |
| `mime_type` | VARCHAR | Tipo MIME del archivo |

### `wp_poxica_logs`
Registro de actividad del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único |
| `order_id` | INT | ID del pedido (opcional) |
| `action` | VARCHAR | Tipo de acción |
| `message` | TEXT | Mensaje descriptivo |
| `data` | LONGTEXT | Datos adicionales en JSON |
| `created_at` | DATETIME | Fecha y hora |

## 🔐 Seguridad

### Validaciones de Archivos
- Verificación de tipo MIME real vs declarado
- Escaneo de contenido malicioso (PHP tags, scripts)
- Validación de dimensiones de imagen
- Límites de tamaño configurables

### Autenticación y Autorización
- Tokens únicos con expiración para cada pedido
- Verificación de nonces en todas las operaciones AJAX
- Acceso restringido al panel de administración
- Rate limiting para subidas

### Comunicación Segura
- HTTPS obligatorio en producción
- Autenticación JWT con Google Drive
- Sanitización de todas las entradas de usuario

## 🎨 Personalización

### Plantillas de Email
Las plantillas soportan los siguientes placeholders:

**Para ambas plantillas:**
- `{order_number}` - Número de pedido
- `{customer_name}` - Nombre del cliente
- `{site_name}` - Nombre del sitio
- `{site_url}` - URL del sitio
- `{order_date}` - Fecha del pedido
- `{order_total}` - Total del pedido

**Email de enlace de subida:**
- `{upload_url}` - Enlace único de subida

**Email de notificación de completado:**
- `{order_products}` - Lista de productos y estado de subida
- `{drive_links}` - Enlaces a carpetas de Google Drive

### Hooks y Filtros

El plugin proporciona varios hooks para personalización:

```php
// Filtrar la expiración del token
add_filter('poxica_token_expiry_days', function($days) {
    return 14; // Cambiar a 14 días
});

// Acción después de subir imagen
add_action('poxica_image_uploaded', function($order_id, $product_id, $file_id) {
    // Tu código personalizado
}, 10, 3);

// Filtrar tipos de archivo permitidos
add_filter('poxica_allowed_file_types', function($types) {
    return ['jpg', 'jpeg', 'png', 'gif']; // Añadir GIF
});
```

## 🔄 Cron Jobs

### Limpieza Diaria
Ejecuta automáticamente cada día:
- Elimina carpetas de pedidos no pagados (>3 días por defecto)
- Limpia pedidos cancelados
- Marca tokens expirados
- Elimina archivos temporales
- Limpia logs antiguos (mantiene últimos 1000)

### Configuración Manual
Si el cron de WordPress no funciona correctamente:

```bash
# Añadir a crontab del servidor
0 2 * * * /usr/bin/php /path/to/wordpress/wp-cron.php
```

## 🐛 Solución de Problemas

### Google Drive no se conecta
1. Verifica que las credenciales JSON sean válidas
2. Comprueba que la API de Google Drive esté habilitada
3. Asegúrate de que la carpeta esté compartida con el Service Account
4. Revisa los logs en **Poxica Uploader → Logs**

### Las imágenes no se suben
1. Verifica permisos de archivos en `/wp-content/uploads/`
2. Comprueba límites de PHP: `upload_max_filesize`, `post_max_size`
3. Asegúrate de que HTTPS esté habilitado
4. Revisa la consola del navegador para errores JavaScript

### Los emails no se envían
1. Configura un plugin de SMTP (WP Mail SMTP recomendado)
2. Verifica las plantillas de email en la configuración
3. Comprueba los logs del servidor de correo
4. Usa la función "Enviar email de prueba" en la configuración

### El cron no funciona
1. Verifica que WP-Cron esté habilitado
2. Instala un plugin de cron viewer para diagnosticar
3. Configura cron del sistema como alternativa
4. Revisa **Poxica Uploader → Limpieza** para el estado

## 📊 Logs y Monitoreo

### Tipos de Acciones Registradas
- `order_created` - Nuevo pedido procesado
- `folder_created` - Carpeta creada en Google Drive
- `image_uploaded` - Imagen subida exitosamente
- `email_sent` - Email enviado al cliente
- `admin_notification_sent` - Notificación enviada al admin
- `cleanup_completed` - Limpieza automática ejecutada
- `error` - Errores del sistema

### Monitoreo Recomendado
- Revisar logs diariamente para errores
- Monitorear espacio en Google Drive
- Verificar que los emails se envíen correctamente
- Comprobar estadísticas en el panel principal

## 🔧 Desarrollo

### Entorno de Desarrollo
```bash
# Clonar repositorio
git clone [repository-url]
cd poxica-image-uploader

# Configurar entorno WordPress local
# Copiar plugin a wp-content/plugins/

# Activar modo debug en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Estructura de Clases
- **Poxica_Core**: Inicialización y coordinación
- **Poxica_Database**: Operaciones de base de datos
- **Poxica_Google_Drive**: API de Google Drive
- **Poxica_Order_Handler**: Eventos de WooCommerce
- **Poxica_Upload_Handler**: Procesamiento de archivos
- **Poxica_Email_Notifications**: Sistema de emails
- **Poxica_Cron**: Tareas programadas
- **Poxica_Admin**: Panel de administración
- **Poxica_Security**: Validaciones de seguridad

## 📝 Licencia

GPL v2 or later

## 🤝 Soporte

Para soporte técnico:
1. Revisa esta documentación
2. Consulta los logs del plugin
3. Verifica la configuración paso a paso
4. Contacta al desarrollador con detalles específicos del error

## 🚀 Roadmap

### Próximas Versiones
- [ ] Soporte para más tipos de archivo (PDF, videos)
- [ ] Integración con otros servicios de almacenamiento (Dropbox, AWS S3)
- [ ] Sistema de watermarks automático
- [ ] Compresión automática de imágenes
- [ ] API REST para integraciones externas
- [ ] Dashboard de analytics avanzado

### Mejoras Planificadas
- [ ] Subida múltiple simultánea
- [ ] Preview de imágenes antes de subir
- [ ] Sistema de comentarios para revisiones
- [ ] Notificaciones push
- [ ] Modo offline con sincronización
- [ ] Múltiples idiomas (i18n completo)

---

**Versión**: 1.0.0  
**Última actualización**: 2024  
**Desarrollado para**: WordPress 6.x + WooCommerce 8.x
