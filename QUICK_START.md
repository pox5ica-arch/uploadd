# 🚀 Quick Start Guide - Poxica Upload Service

Esta guía te permitirá tener el sistema funcionando en menos de 30 minutos.

## ⚡ Instalación Rápida

### 1. Preparar el Servidor

```bash
# Conectar al servidor VPS
ssh root@tu-servidor-ip

# Clonar el repositorio
cd /opt
git clone https://github.com/tu-usuario/poxica-upload-service.git
cd poxica-upload-service

# Ejecutar instalación automática
chmod +x scripts/install.sh
sudo ./scripts/install.sh
```

### 2. Configurar Variables de Entorno

```bash
# Copiar y editar configuración
cp .env.example .env
nano .env
```

**Variables críticas que DEBES configurar:**

```bash
# Genera con: openssl rand -hex 32
SECRET_KEY=tu_secret_key_de_32_caracteres

# Credenciales de WooCommerce
WOOCOMMERCE_URL=https://poxica.com
WOOCOMMERCE_KEY=ck_tu_consumer_key
WOOCOMMERCE_SECRET=cs_tu_consumer_secret

# ID de la carpeta de Google Drive
GOOGLE_DRIVE_FOLDER_ID=1ABC234def567890

# Email (usar App Password de Gmail)
SMTP_USERNAME=tu_email@gmail.com
SMTP_PASSWORD=tu_app_password_de_16_caracteres
ADMIN_EMAIL=admin@poxica.com

# Base de datos (cambiar por una segura)
DB_PASSWORD=tu_password_super_seguro
```

### 3. Configurar Google Drive

#### Crear Service Account:
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto
3. Habilita "Google Drive API"
4. Ve a "IAM & Admin" > "Service Accounts"
5. Crea nueva service account
6. Descarga el archivo JSON

#### Configurar permisos:
```bash
# Subir credenciales al servidor
scp google-credentials.json root@tu-servidor:/opt/poxica-upload-service/credentials/

# En el servidor:
chmod 600 credentials/google-credentials.json
```

#### En Google Drive:
1. Crea una carpeta para los uploads
2. Comparte la carpeta con el email de la service account
3. Copia el ID de la carpeta (desde la URL)

### 4. Configurar DNS

Apunta tu dominio al servidor:

```bash
# En tu proveedor DNS (Cloudflare, etc.)
upload.poxica.com.  A  [IP_DE_TU_SERVIDOR]
```

### 5. Obtener SSL

```bash
chmod +x scripts/setup-ssl.sh
sudo ./scripts/setup-ssl.sh
```

### 6. Iniciar Servicios

```bash
# Iniciar todo
docker-compose up -d

# Verificar que funciona
curl https://upload.poxica.com/health
```

## 🔧 Configurar WooCommerce

### 1. Habilitar API REST

1. Ve a **WooCommerce** > **Settings** > **Advanced** > **REST API**
2. Clic en **Add Key**
3. Configura:
   - **Description**: Poxica Upload Service
   - **User**: Administrador
   - **Permissions**: Read/Write
4. Guarda las credenciales en `.env`

### 2. Crear Webhook

1. Ve a **WooCommerce** > **Settings** > **Advanced** > **Webhooks**
2. Clic en **Add webhook**
3. Configura:
   - **Name**: Poxica Upload
   - **Status**: Active
   - **Topic**: Order updated
   - **Delivery URL**: `https://upload.poxica.com/api/webhook/woocommerce`
   - **API Version**: WP REST API Integration v3

## ✅ Verificar Instalación

```bash
# Ejecutar todas las pruebas
make test

# O manualmente:
curl https://upload.poxica.com/health | python3 -m json.tool
```

**Respuesta esperada:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01T00:00:00Z",
  "services": {
    "database": "healthy",
    "redis": "healthy",
    "woocommerce": "healthy",
    "google_drive": "healthy", 
    "email": "healthy"
  }
}
```

## 🎯 Probar el Flujo Completo

### 1. Simular Pedido (Para Testing)

```bash
# Crear pedido de prueba en WooCommerce
curl -X POST https://upload.poxica.com/api/webhook/woocommerce \
  -H "Content-Type: application/json" \
  -d '{
    "id": 123,
    "status": "processing",
    "billing": {
      "email": "cliente@test.com",
      "first_name": "Juan",
      "last_name": "Pérez"
    },
    "line_items": [
      {
        "product_id": 1,
        "name": "Producto Test",
        "quantity": 1,
        "price": "25.00"
      }
    ],
    "total": "25.00",
    "currency": "EUR"
  }'
```

### 2. Obtener Token de Subida

La respuesta incluirá un token y URL:
```json
{
  "status": "success",
  "upload_token": "abc123...",
  "upload_url": "https://upload.poxica.com/upload/abc123..."
}
```

### 3. Probar Subida

Visita la URL en el navegador y prueba subir imágenes.

## 🛠️ Comandos Útiles

```bash
# Ver logs en tiempo real
make logs

# Ver estado de servicios
make status

# Crear backup
make backup

# Reiniciar servicios
make restart

# Limpiar sistema
make clean

# Ver logs específicos
make logs SERVICE=backend
```

## 🚨 Troubleshooting Rápido

### Error: "Google Drive API not working"
```bash
# Verificar credenciales
ls -la credentials/
python3 -c "import json; print(json.load(open('credentials/google-credentials.json'))['client_email'])"
```

### Error: "WooCommerce webhook not working"
```bash
# Verificar firewall
sudo ufw status
curl -X POST https://upload.poxica.com/api/webhook/woocommerce
```

### Error: "SSL certificate failed"
```bash
# Verificar DNS
nslookup upload.poxica.com
# Reintentar SSL
sudo ./scripts/setup-ssl.sh
```

### Error: "Email not sending"
```bash
# Verificar credenciales SMTP
docker-compose exec backend python -c "
from services.email import EmailService
service = EmailService()
print('OK' if service.health_check() else 'ERROR')
"
```

## 📚 Próximos Pasos

1. **Monitoreo**: Configura alertas automáticas
2. **Backups**: Programa backups diarios con `crontab`
3. **Escalabilidad**: Considera un load balancer para mayor volumen
4. **Seguridad**: Configura fail2ban y actualizaciones automáticas

## 🆘 Soporte

- **GitHub Issues**: [Reportar problemas](https://github.com/tu-usuario/poxica-upload-service/issues)
- **Documentación completa**: Ver `README.md`
- **Email**: admin@poxica.com

---

¡Tu sistema debería estar funcionando perfectamente! 🎉