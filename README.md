# 🚀 Poxica Upload Service

Una solución completa y lista para producción para la subida de imágenes después de pagos en WooCommerce, con integración a Google Drive y notificaciones por email.

## 📋 Tabla de Contenidos

- [Características](#características)
- [Arquitectura](#arquitectura)
- [Requisitos Previos](#requisitos-previos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Despliegue](#despliegue)
- [Uso](#uso)
- [API Documentation](#api-documentation)
- [Monitoreo](#monitoreo)
- [Troubleshooting](#troubleshooting)
- [Contribuir](#contribuir)

## 🌟 Características

### Funcionalidades Principales
- ✅ **Integración WooCommerce**: Detecta pedidos pagados mediante webhooks
- ✅ **Subida de Imágenes**: Interface moderna con drag & drop
- ✅ **Google Drive**: Almacenamiento automático organizado por pedidos
- ✅ **Notificaciones**: Emails automáticos a clientes y administradores
- ✅ **Seguridad**: Tokens únicos con expiración y HTTPS obligatorio
- ✅ **Responsive**: Diseño adaptable a todos los dispositivos

### Tecnologías
- **Backend**: FastAPI (Python 3.11)
- **Frontend**: React 18 con styled-components
- **Base de Datos**: PostgreSQL 15
- **Cache**: Redis 7
- **Proxy**: Nginx con SSL/TLS
- **Contenedores**: Docker & Docker Compose
- **SSL**: Let's Encrypt automático

## 🏗️ Arquitectura

```
                    ┌─────────────────┐
                    │   WooCommerce   │
                    │   (poxica.com)  │
                    └─────────┬───────┘
                              │ Webhook
                              ▼
┌─────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Cliente   │◄──►│      Nginx      │◄──►│    Backend      │
│  (Browser)  │    │  (SSL Proxy)    │    │   (FastAPI)     │
└─────────────┘    └─────────────────┘    └─────────┬───────┘
                              │                      │
                   ┌─────────────────┐              │
                   │    Frontend     │              │
                   │    (React)      │              │
                   └─────────────────┘              │
                                                    ▼
  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
  │  Google Drive   │  │   PostgreSQL    │  │      Redis      │
  │  (Archivos)     │  │  (Datos)        │  │    (Cache)      │
  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

## 📋 Requisitos Previos

### Servidor
- **OS**: Ubuntu 22.04 LTS (recomendado)
- **RAM**: Mínimo 2GB, recomendado 4GB
- **Almacenamiento**: Mínimo 20GB SSD
- **CPU**: 2 cores mínimo

### Software
- Docker 24.0+
- Docker Compose 2.0+
- Git
- Dominio configurado (upload.poxica.com)

### Servicios Externos
- Cuenta de Google Cloud con Google Drive API
- Servidor SMTP (Gmail, etc.)
- WooCommerce con API REST activa

## 🔧 Instalación

### 1. Clonar el Repositorio

```bash
cd /opt
sudo git clone https://github.com/tu-usuario/poxica-upload-service.git
cd poxica-upload-service
sudo chown -R $USER:$USER .
```

### 2. Ejecutar Script de Instalación

```bash
chmod +x scripts/install.sh
sudo ./scripts/install.sh
```

Este script:
- Instala Docker y Docker Compose
- Configura firewall
- Crea directorios necesarios
- Configura permisos

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
nano .env
```

Configurar todas las variables (ver sección [Configuración](#configuración)).

## ⚙️ Configuración

### Variables de Entorno (.env)

```bash
# Base de Datos
DB_PASSWORD=tu_password_super_seguro_aqui

# Aplicación
SECRET_KEY=tu_secret_key_aqui_32_caracteres

# WooCommerce
WOOCOMMERCE_URL=https://poxica.com
WOOCOMMERCE_KEY=ck_tu_consumer_key_aqui
WOOCOMMERCE_SECRET=cs_tu_consumer_secret_aqui

# Google Drive
GOOGLE_DRIVE_FOLDER_ID=tu_folder_id_de_google_drive

# Email
SMTP_SERVER=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu_email@gmail.com
SMTP_PASSWORD=tu_app_password_aqui
ADMIN_EMAIL=admin@poxica.com

# Dominio
DOMAIN=upload.poxica.com
```

### Generar SECRET_KEY

```bash
openssl rand -hex 32
```

### Configurar Google Drive API

1. **Crear Proyecto en Google Cloud Console**:
   - Ve a [Google Cloud Console](https://console.cloud.google.com/)
   - Crea un nuevo proyecto o selecciona uno existente

2. **Habilitar Google Drive API**:
   ```bash
   # En la consola, habilita Google Drive API
   ```

3. **Crear Service Account**:
   - Ve a "IAM & Admin" > "Service Accounts"
   - Crea una nueva service account
   - Descarga el archivo JSON de credenciales

4. **Configurar Permisos**:
   - Crea una carpeta en Google Drive para los uploads
   - Comparte la carpeta con el email de la service account
   - Copia el ID de la carpeta para `GOOGLE_DRIVE_FOLDER_ID`

5. **Instalar Credenciales**:
   ```bash
   mkdir -p credentials
   cp tu-archivo-credenciales.json credentials/google-credentials.json
   ```

### Configurar WooCommerce

1. **Habilitar API REST**:
   - WooCommerce > Settings > Advanced > REST API
   - Crear nueva API Key con permisos de lectura/escritura

2. **Configurar Webhook**:
   - WooCommerce > Settings > Advanced > Webhooks
   - Crear nuevo webhook:
     - **Delivery URL**: `https://upload.poxica.com/api/webhook/woocommerce`
     - **Topic**: Order updated
     - **Status**: Active

### Configurar Email (Gmail)

1. **Habilitar 2FA** en tu cuenta de Gmail
2. **Generar App Password**:
   - Google Account > Security > App passwords
   - Generar password para "Mail"
3. **Usar App Password** en `SMTP_PASSWORD`

## 🚀 Despliegue

### 1. Configurar DNS

Apunta `upload.poxica.com` a la IP de tu servidor:

```bash
# Ejemplo en tu proveedor DNS
upload.poxica.com.  A  tu.ip.del.servidor
```

### 2. Obtener Certificado SSL

```bash
# Ejecutar script de SSL
chmod +x scripts/setup-ssl.sh
sudo ./scripts/setup-ssl.sh
```

### 3. Iniciar Servicios

```bash
# Desarrollo
docker-compose up -d

# Producción
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 4. Verificar Instalación

```bash
# Ver logs
docker-compose logs -f

# Verificar health
curl https://upload.poxica.com/health

# Verificar servicios
docker-compose ps
```

## 📖 Uso

### Flujo de Trabajo

1. **Cliente paga en WooCommerce** (poxica.com)
2. **WooCommerce envía webhook** al backend
3. **Backend genera token único** y estructura de carpetas
4. **Cliente recibe email** con enlace de subida
5. **Cliente sube imágenes** por producto
6. **Imágenes se guardan** en Google Drive organizadas
7. **Notificaciones automáticas** a cliente y admin

### Enlaces de Subida

Los enlaces tienen el formato:
```
https://upload.poxica.com/upload/TOKEN_UNICO_AQUI
```

- **Expiración**: 72 horas por defecto
- **Un solo uso**: El token se marca como usado
- **Seguro**: Token criptográficamente seguro

## 📚 API Documentation

### Endpoints Principales

#### GET /health
**Descripción**: Estado del sistema
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

#### POST /webhook/woocommerce
**Descripción**: Webhook de WooCommerce
**Body**: Datos del pedido de WooCommerce
**Response**: 
```json
{
  "status": "success",
  "upload_token": "token_generado",
  "upload_url": "https://upload.poxica.com/upload/token"
}
```

#### GET /upload/{token}
**Descripción**: Información del pedido para subida
**Response**:
```json
{
  "order_id": "uuid",
  "wc_order_id": 12345,
  "customer_name": "Nombre Cliente",
  "customer_email": "cliente@email.com",
  "status": "pending",
  "items": [...],
  "uploaded_images_count": 0,
  "total_items_count": 3
}
```

#### POST /upload/{token}/images
**Descripción**: Subir imágenes
**Form Data**:
- `item_id`: ID del producto
- `files`: Archivos de imagen (JPG/PNG, max 10MB)

## 📊 Monitoreo

### Logs

```bash
# Logs de aplicación
docker-compose logs -f backend

# Logs de nginx
docker-compose logs -f nginx

# Logs del sistema
sudo journalctl -u docker -f
```

### Métricas

```bash
# Uso de recursos
docker stats

# Estado de contenedores
docker-compose ps

# Espacio en disco
df -h
```

### Health Checks

```bash
# Check automático cada minuto
*/1 * * * * curl -f https://upload.poxica.com/health || echo "Service down" | mail -s "Alert" admin@poxica.com
```

## 🔧 Troubleshooting

### Problemas Comunes

#### 1. Error de Conexión a Google Drive
```bash
# Verificar credenciales
docker-compose exec backend python -c "
from services.google_drive import GoogleDriveService
service = GoogleDriveService()
print('OK' if service.health_check() else 'ERROR')
"
```

#### 2. Webhook no Funciona
```bash
# Verificar logs de WooCommerce
# Verificar firewall
sudo ufw status

# Verificar endpoint
curl -X POST https://upload.poxica.com/api/webhook/woocommerce \
  -H "Content-Type: application/json" \
  -d '{"id":123,"status":"processing",...}'
```

#### 3. Emails no se Envían
```bash
# Verificar configuración SMTP
docker-compose exec backend python -c "
from services.email import EmailService
service = EmailService()
print('OK' if service.health_check() else 'ERROR')
"
```

#### 4. SSL no Funciona
```bash
# Renovar certificado
sudo docker-compose run --rm certbot renew

# Verificar certificado
openssl s_client -connect upload.poxica.com:443
```

### Comandos Útiles

```bash
# Reiniciar servicios
docker-compose restart

# Ver logs en tiempo real
docker-compose logs -f

# Limpiar sistema
docker system prune -f

# Backup de base de datos
docker-compose exec db pg_dump -U poxica_user poxica_upload > backup.sql

# Restaurar base de datos
docker-compose exec -T db psql -U poxica_user poxica_upload < backup.sql
```

## 🔒 Seguridad

### Medidas Implementadas

- **HTTPS Obligatorio** con certificados Let's Encrypt
- **Rate Limiting** en endpoints críticos
- **Tokens seguros** con expiración
- **Headers de seguridad** (HSTS, CSP, etc.)
- **Validación de archivos** (tipo, tamaño)
- **Logs de auditoría** completos

### Recomendaciones Adicionales

```bash
# Firewall básico
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Actualizaciones automáticas
sudo apt install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades

# Fail2ban para SSH
sudo apt install fail2ban
sudo systemctl enable fail2ban
```

## 📈 Escalabilidad

### Para Mayor Volumen

1. **Load Balancer**:
   ```yaml
   # docker-compose.scale.yml
   backend:
     deploy:
       replicas: 3
   ```

2. **Base de Datos Externa**:
   - PostgreSQL administrado (AWS RDS, etc.)
   - Redis administrado

3. **CDN**:
   - CloudFlare para assets estáticos
   - Optimización de imágenes

## 🛠️ Desarrollo

### Entorno Local

```bash
# Variables para desarrollo
cp .env.example .env.dev

# Iniciar en modo desarrollo
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up

# Acceso directo a servicios
# Frontend: http://localhost:3000
# Backend: http://localhost:8000
# Base de datos: localhost:5432
```

### Estructura del Proyecto

```
poxica-upload-service/
├── backend/                 # API FastAPI
│   ├── services/           # Servicios (Google Drive, Email, etc.)
│   ├── models.py          # Modelos de base de datos
│   ├── schemas.py         # Schemas de validación
│   └── main.py           # Aplicación principal
├── frontend/              # React App
│   ├── src/
│   │   ├── components/   # Componentes React
│   │   ├── pages/       # Páginas
│   │   └── services/    # API client
├── nginx/                # Configuración proxy
├── database/            # Scripts SQL
├── scripts/            # Scripts de instalación
└── docker-compose.yml  # Orquestación
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver archivo [LICENSE](LICENSE) para detalles.

## 📞 Soporte

- **Issues**: [GitHub Issues](https://github.com/tu-usuario/poxica-upload-service/issues)
- **Email**: admin@poxica.com
- **Documentación**: [Wiki del proyecto](https://github.com/tu-usuario/poxica-upload-service/wiki)

---

**Hecho con ❤️ para Poxica**
