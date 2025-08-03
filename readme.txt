=== Poxica Image Uploader ===
Contributors: tu-usuario
Tags: woocommerce, google-drive, image-upload, orders, automation
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatiza la gestión de imágenes de pedidos WooCommerce con almacenamiento en Google Drive y limpieza automática de recursos.

== Description ==

Poxica Image Uploader es un plugin completo que automatiza todo el proceso de recolección de imágenes para pedidos de WooCommerce. Perfecto para tiendas que necesitan imágenes de los clientes (impresión personalizada, grabados, etc.).

### 🚀 Características Principales

* **Integración automática con WooCommerce**: Detecta nuevos pedidos y pagos confirmados
* **Gestión inteligente por producto**: Cada unidad comprada tiene su área de subida independiente
* **Almacenamiento seguro en Google Drive**: Integración mediante Service Account
* **Limpieza automática**: Elimina carpetas de pedidos no pagados después de 3 días
* **Interfaz moderna**: Drag & drop con validación en tiempo real
* **Emails personalizables**: Plantillas para clientes y administradores
* **Seguridad avanzada**: Validación de archivos y protección contra malware
* **Autoborrado inmediato**: Elimina carpetas al cancelar pedidos
* **Compatible con HPOS**: Funciona con High-Performance Order Storage

### 🎯 Casos de Uso Ideales

* Tiendas de impresión personalizada
* Servicios de grabado y personalización
* Productos que requieren archivos del cliente
* Cualquier negocio que necesite recopilar imágenes por pedido

### 📁 Organización Automática

El plugin crea automáticamente una estructura organizada:

```
Google Drive/
└── Pedido-12345/
    ├── Producto-A-20x30-horizontal-1/
    ├── Producto-A-20x30-horizontal-2/
    └── Producto-B-40x60-vertical-1/
```

### 🔄 Flujo Automatizado

1. **Cliente realiza pedido** → WooCommerce
2. **Plugin detecta pedido** → Crea registro interno
3. **Email automático** → Cliente recibe enlace único
4. **Pago confirmado** → Se crean carpetas en Google Drive
5. **Cliente sube imágenes** → Interfaz moderna y fácil
6. **Completado** → Admin recibe notificación con enlaces

### 🛡️ Seguridad y Validación

* Verificación de tipos MIME reales
* Escaneo de contenido malicioso
* Tokens únicos con expiración
* Rate limiting para evitar abuso
* Validación de dimensiones de imagen

== Installation ==

### Instalación Básica

1. Sube el plugin a `/wp-content/plugins/poxica-image-uploader/`
2. Activa el plugin desde 'Plugins' en WordPress
3. Ve a 'Poxica Uploader' → 'Configuración' para empezar

### Configuración de Google Drive

**Paso 1: Crear Service Account**

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un proyecto nuevo o selecciona uno existente
3. Habilita la API de Google Drive
4. Crea credenciales → Service Account
5. Descarga el archivo JSON

**Paso 2: Preparar Google Drive**

1. Crea una carpeta en Google Drive para los pedidos
2. Comparte la carpeta con el email del Service Account (permisos de Editor)
3. Copia el ID de la carpeta desde la URL

**Paso 3: Configurar Plugin**

1. Pega el contenido del JSON en 'Credenciales de Google Drive'
2. Introduce el ID de la carpeta
3. Prueba la conexión
4. ¡Listo para usar!

== Frequently Asked Questions ==

= ¿Funciona con cualquier tema de WordPress? =

Sí, el plugin funciona independientemente del tema ya que utiliza su propia página de subida con estilos incluidos.

= ¿Qué pasa si un cliente no paga el pedido? =

Las carpetas de pedidos no pagados se eliminan automáticamente después de 3 días (configurable) para ahorrar espacio.

= ¿Se pueden personalizar los emails? =

Sí, tanto el asunto como el contenido de los emails son completamente personalizables con placeholders dinámicos.

= ¿Qué tipos de archivo se pueden subir? =

Por defecto JPG y PNG. Es configurable desde el panel de administración.

= ¿Hay límite de tamaño de archivo? =

El límite es configurable. Por defecto es 10MB pero puedes ajustarlo según tus necesidades.

= ¿Qué pasa si cancelo un pedido? =

Las carpetas se eliminan inmediatamente de Google Drive cuando se cancela un pedido.

= ¿Necesito HTTPS? =

Sí, HTTPS es obligatorio para el funcionamiento seguro del plugin.

= ¿Funciona con productos variables? =

Sí, detecta automáticamente las variaciones y las incluye en los nombres de carpetas.

== Screenshots ==

1. Panel principal con estadísticas y estado del sistema
2. Configuración de Google Drive con test de conexión
3. Interfaz de subida moderna para clientes
4. Logs de actividad para monitoreo
5. Página de limpieza con estadísticas de cron

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial
* Integración completa con WooCommerce
* Almacenamiento en Google Drive
* Sistema de emails automático
* Limpieza automática de recursos
* Interfaz drag & drop
* Panel de administración completo
* Sistema de logs
* Validaciones de seguridad
* Autoborrado de pedidos cancelados
* Compatibilidad completa con HPOS (High-Performance Order Storage)

== Upgrade Notice ==

= 1.0.0 =
Primera versión del plugin. Instalación limpia recomendada.

== Technical Requirements ==

* WordPress 6.0 o superior
* WooCommerce 8.0 o superior  
* PHP 8.0 o superior
* Extensiones PHP: openssl, fileinfo, curl
* Cuenta de Google Cloud Platform
* HTTPS habilitado
* Compatible con HPOS (High-Performance Order Storage)

== Support ==

Para soporte técnico:

1. Revisa la documentación completa en el README.md
2. Consulta los logs en 'Poxica Uploader' → 'Logs'
3. Verifica la configuración paso a paso
4. Contacta con detalles específicos del error

== Privacy & GDPR ==

Este plugin:
* Almacena información de pedidos en tablas personalizadas
* Sube archivos a Google Drive según la configuración
* Envía emails con datos del pedido
* Registra actividad en logs internos
* No envía datos a terceros fuera de Google Drive
* Respeta las políticas de privacidad de WordPress

Asegúrate de incluir el uso de Google Drive en tu política de privacidad.

== Third Party Services ==

Este plugin utiliza los siguientes servicios externos:

**Google Drive API**
- Propósito: Almacenamiento de archivos subidos
- Política de privacidad: https://policies.google.com/privacy
- Términos de servicio: https://policies.google.com/terms

**Dropzone.js (CDN)**
- Propósito: Interfaz de subida drag & drop
- URL: https://cdnjs.cloudflare.com/ajax/libs/dropzone/
- Solo se carga en páginas de subida

== Contributing ==

El desarrollo de este plugin está abierto a contribuciones:

* Reporta bugs en el repositorio
* Sugiere mejoras y nuevas características  
* Contribuye con traducciones
* Ayuda con documentación

== Credits ==

Desarrollado con ❤️ para la comunidad de WordPress.

Librerías utilizadas:
* Dropzone.js para interfaz de subida
* Google Drive API v3
* WordPress Hooks API
* WooCommerce Action Scheduler