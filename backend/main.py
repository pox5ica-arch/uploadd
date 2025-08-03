import os
import logging
import secrets
import uuid
from datetime import datetime, timedelta
from typing import List, Optional
from fastapi import FastAPI, HTTPException, Depends, File, UploadFile, Form, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from sqlalchemy import desc
import redis
import aiofiles

from config import get_settings
from database import get_db, engine
from models import Base, Order, OrderItem, UploadToken, UploadedImage, SystemLog
from schemas import (
    WooCommerceWebhook, UploadResponse, FileUploadResponse, 
    OrderSummary, HealthCheck, SystemLogCreate
)
from services.woocommerce import WooCommerceService
from services.google_drive import GoogleDriveService
from services.email import EmailService

# Configurar logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Configuración
settings = get_settings()

# Crear tablas
Base.metadata.create_all(bind=engine)

# Inicializar aplicación
app = FastAPI(
    title="Poxica Upload Service",
    description="Servicio para subir imágenes después de compras en WooCommerce",
    version="1.0.0"
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Seguridad
security = HTTPBearer()

# Servicios
woocommerce_service = WooCommerceService()
google_drive_service = GoogleDriveService()
email_service = EmailService()

# Redis para cache
redis_client = redis.from_url(settings.redis_url)


# Funciones de utilidad
def log_system_event(db: Session, level: str, message: str, context: dict = None):
    """Registrar evento del sistema"""
    try:
        log_entry = SystemLog(
            level=level,
            message=message,
            context=context
        )
        db.add(log_entry)
        db.commit()
    except Exception as e:
        logger.error(f"Error logging system event: {e}")


def generate_upload_token() -> str:
    """Generar token único para subida"""
    return secrets.token_urlsafe(32)


def verify_upload_token(token: str, db: Session) -> Optional[UploadToken]:
    """Verificar token de subida"""
    upload_token = db.query(UploadToken).filter(
        UploadToken.token == token,
        UploadToken.expires_at > datetime.utcnow(),
        UploadToken.used == False
    ).first()
    
    return upload_token


def is_allowed_file(filename: str) -> bool:
    """Verificar si el archivo tiene una extensión permitida"""
    return any(filename.lower().endswith(ext) for ext in settings.allowed_extensions)


async def save_uploaded_file(file: UploadFile) -> str:
    """Guardar archivo subido temporalmente"""
    file_id = str(uuid.uuid4())
    file_extension = os.path.splitext(file.filename)[1]
    temp_filename = f"{file_id}{file_extension}"
    temp_path = os.path.join(settings.upload_path, temp_filename)
    
    async with aiofiles.open(temp_path, 'wb') as f:
        content = await file.read()
        await f.write(content)
    
    return temp_path


# Endpoints

@app.get("/", response_model=dict)
async def root():
    """Endpoint raíz"""
    return {
        "message": "Poxica Upload Service",
        "version": "1.0.0",
        "status": "active"
    }


@app.get("/health", response_model=HealthCheck)
async def health_check():
    """Check de salud del sistema"""
    services = {
        "database": "healthy",
        "redis": "healthy",
        "woocommerce": "healthy" if woocommerce_service.health_check() else "unhealthy",
        "google_drive": "healthy" if google_drive_service.health_check() else "unhealthy",
        "email": "healthy" if email_service.health_check() else "unhealthy"
    }
    
    # Verificar Redis
    try:
        redis_client.ping()
    except:
        services["redis"] = "unhealthy"
    
    overall_status = "healthy" if all(status == "healthy" for status in services.values()) else "degraded"
    
    return HealthCheck(
        status=overall_status,
        timestamp=datetime.utcnow(),
        services=services
    )


@app.post("/webhook/woocommerce")
async def woocommerce_webhook(
    webhook_data: WooCommerceWebhook,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Recibir webhook de WooCommerce cuando se paga un pedido"""
    try:
        # Verificar que el pedido esté pagado
        if webhook_data.status not in ['processing', 'completed']:
            log_system_event(db, "INFO", f"Order {webhook_data.id} not in paid status: {webhook_data.status}")
            return {"status": "ignored", "reason": "order not paid"}

        # Verificar si ya procesamos este pedido
        existing_order = db.query(Order).filter(Order.wc_order_id == webhook_data.id).first()
        if existing_order:
            log_system_event(db, "INFO", f"Order {webhook_data.id} already processed")
            return {"status": "already_processed"}

        # Obtener información del cliente
        customer_info = woocommerce_service.get_customer_info(webhook_data.id)
        if not customer_info:
            raise HTTPException(status_code=400, detail="Could not get customer information")

        # Crear orden en nuestra DB
        order = Order(
            wc_order_id=webhook_data.id,
            customer_email=customer_info['email'],
            customer_name=customer_info['full_name'],
            total_amount=float(webhook_data.total),
            currency=webhook_data.currency,
            status='pending'
        )
        db.add(order)
        db.flush()

        # Crear items del pedido
        for item_data in webhook_data.line_items:
            order_item = OrderItem(
                order_id=order.id,
                wc_product_id=item_data['product_id'],
                product_name=item_data['name'],
                quantity=item_data['quantity'],
                price=float(item_data.get('price', 0))
            )
            db.add(order_item)

        # Generar token de subida
        token = generate_upload_token()
        expires_at = datetime.utcnow() + timedelta(hours=settings.upload_token_expire_hours)
        
        upload_token = UploadToken(
            order_id=order.id,
            token=token,
            expires_at=expires_at
        )
        db.add(upload_token)
        
        db.commit()

        # Crear estructura de carpetas en Google Drive en background
        background_tasks.add_task(create_google_drive_folders, order.wc_order_id, order.id)

        # Log del evento
        log_system_event(
            db, "INFO", 
            f"Order {webhook_data.id} processed successfully",
            {"order_id": str(order.id), "token": token[:8] + "..."}
        )

        # Enviar enlace de subida al cliente (esto normalmente se haría por email)
        upload_url = f"{settings.frontend_url}/upload/{token}"
        
        return {
            "status": "success",
            "message": "Order processed successfully",
            "upload_token": token,
            "upload_url": upload_url
        }

    except Exception as e:
        db.rollback()
        log_system_event(db, "ERROR", f"Error processing webhook: {str(e)}")
        logger.error(f"Error processing webhook: {e}")
        raise HTTPException(status_code=500, detail="Internal server error")


@app.get("/upload/{token}", response_model=OrderSummary)
async def get_upload_info(token: str, db: Session = Depends(get_db)):
    """Obtener información del pedido para subida de imágenes"""
    upload_token = verify_upload_token(token, db)
    if not upload_token:
        raise HTTPException(status_code=404, detail="Invalid or expired token")

    order = db.query(Order).filter(Order.id == upload_token.order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    # Contar imágenes ya subidas
    uploaded_images_count = db.query(UploadedImage).filter(
        UploadedImage.order_id == order.id
    ).count()

    total_items_count = sum(item.quantity for item in order.items)

    return OrderSummary(
        order_id=order.id,
        wc_order_id=order.wc_order_id,
        customer_name=order.customer_name,
        customer_email=order.customer_email,
        status=order.status,
        items=order.items,
        uploaded_images_count=uploaded_images_count,
        total_items_count=total_items_count
    )


@app.post("/upload/{token}/images", response_model=FileUploadResponse)
async def upload_images(
    token: str,
    item_id: str = Form(...),
    files: List[UploadFile] = File(...),
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db)
):
    """Subir imágenes para un producto específico"""
    # Verificar token
    upload_token = verify_upload_token(token, db)
    if not upload_token:
        raise HTTPException(status_code=404, detail="Invalid or expired token")

    # Verificar item del pedido
    order_item = db.query(OrderItem).filter(
        OrderItem.id == item_id,
        OrderItem.order_id == upload_token.order_id
    ).first()
    
    if not order_item:
        raise HTTPException(status_code=404, detail="Order item not found")

    try:
        uploaded_files = []
        
        for file in files:
            # Validar archivo
            if not is_allowed_file(file.filename):
                raise HTTPException(
                    status_code=400, 
                    detail=f"File type not allowed: {file.filename}"
                )
            
            if file.size > settings.max_file_size:
                raise HTTPException(
                    status_code=400,
                    detail=f"File too large: {file.filename}"
                )

            # Leer contenido del archivo
            file_content = await file.read()
            
            # Guardar información en DB primero
            uploaded_image = UploadedImage(
                order_id=upload_token.order_id,
                order_item_id=order_item.id,
                original_filename=file.filename,
                file_size=len(file_content),
                mime_type=file.content_type,
                upload_token_id=upload_token.id
            )
            db.add(uploaded_image)
            db.flush()

            # Subir a Google Drive en background
            background_tasks.add_task(
                upload_to_google_drive,
                file_content,
                file.filename,
                order_item.order.wc_order_id,
                order_item.product_name,
                str(uploaded_image.id),
                db
            )

            uploaded_files.append({
                "filename": file.filename,
                "size": len(file_content),
                "id": str(uploaded_image.id)
            })

        db.commit()

        # Marcar token como usado si es la primera subida
        if not upload_token.used:
            upload_token.used = True
            db.commit()

        # Enviar notificaciones en background
        background_tasks.add_task(
            send_upload_notifications,
            upload_token.order_id,
            len(uploaded_files)
        )

        return FileUploadResponse(
            success=True,
            message=f"Successfully uploaded {len(uploaded_files)} file(s)",
            file_id=uploaded_files[0]["id"] if uploaded_files else None
        )

    except Exception as e:
        db.rollback()
        logger.error(f"Error uploading files: {e}")
        raise HTTPException(status_code=500, detail="Error uploading files")


# Tareas en background

async def create_google_drive_folders(wc_order_id: int, order_id: str):
    """Crear estructura de carpetas en Google Drive"""
    try:
        # Crear carpeta del pedido
        order_folder_id = google_drive_service.create_order_folder_structure(wc_order_id)
        if order_folder_id:
            logger.info(f"Created Google Drive folder for order {wc_order_id}")
        else:
            logger.error(f"Failed to create Google Drive folder for order {wc_order_id}")
    except Exception as e:
        logger.error(f"Error creating Google Drive folders: {e}")


async def upload_to_google_drive(file_content: bytes, filename: str, 
                               wc_order_id: int, product_name: str, 
                               uploaded_image_id: str, db: Session):
    """Subir archivo a Google Drive"""
    try:
        # Obtener o crear carpeta del pedido
        order_folder_id = google_drive_service.create_order_folder_structure(wc_order_id)
        if not order_folder_id:
            logger.error(f"Could not create order folder for order {wc_order_id}")
            return

        # Obtener o crear carpeta del producto
        product_folder_id = google_drive_service.create_product_folder(product_name, order_folder_id)
        if not product_folder_id:
            logger.error(f"Could not create product folder for {product_name}")
            return

        # Subir archivo
        result = google_drive_service.optimize_and_upload_image(
            file_content, filename, product_folder_id
        )

        if result:
            file_id, web_view_link = result
            
            # Actualizar registro en DB
            uploaded_image = db.query(UploadedImage).filter(
                UploadedImage.id == uploaded_image_id
            ).first()
            
            if uploaded_image:
                uploaded_image.google_drive_file_id = file_id
                uploaded_image.google_drive_url = web_view_link
                db.commit()
                
            logger.info(f"Successfully uploaded {filename} to Google Drive")
        else:
            logger.error(f"Failed to upload {filename} to Google Drive")

    except Exception as e:
        logger.error(f"Error uploading to Google Drive: {e}")


async def send_upload_notifications(order_id: str, images_count: int):
    """Enviar notificaciones por email"""
    try:
        # Obtener información del pedido
        db = next(get_db())
        order = db.query(Order).filter(Order.id == order_id).first()
        
        if not order:
            return

        # Preparar datos para emails
        items_data = [
            {
                "product_name": item.product_name,
                "quantity": item.quantity,
                "price": float(item.price) if item.price else 0
            }
            for item in order.items
        ]

        # Enviar confirmación al cliente
        email_service.send_upload_confirmation(
            order.customer_email,
            order.customer_name,
            order.wc_order_id,
            items_data,
            images_count
        )

        # Obtener enlace de la carpeta de Google Drive
        order_folder_id = google_drive_service.create_order_folder_structure(order.wc_order_id)
        google_drive_url = google_drive_service.get_folder_link(order_folder_id) if order_folder_id else ""

        # Enviar notificación al administrador
        email_service.send_admin_notification(
            order.customer_name,
            order.customer_email,
            order.wc_order_id,
            items_data,
            images_count,
            google_drive_url
        )

        # Actualizar estado del pedido
        order.status = 'images_uploaded'
        db.commit()

        # Añadir nota al pedido en WooCommerce
        woocommerce_service.update_order_notes(
            order.wc_order_id,
            f"Imágenes subidas exitosamente. Total: {images_count} archivos. Ver en Google Drive: {google_drive_url}"
        )

        db.close()

    except Exception as e:
        logger.error(f"Error sending notifications: {e}")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)