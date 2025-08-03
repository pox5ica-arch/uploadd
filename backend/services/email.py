import smtplib
import logging
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from typing import List, Optional
from jinja2 import Environment, DictLoader
from config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()


class EmailService:
    def __init__(self):
        self.smtp_server = settings.smtp_server
        self.smtp_port = settings.smtp_port
        self.username = settings.smtp_username
        self.password = settings.smtp_password
        self.admin_email = settings.admin_email
        
        # Templates de email
        self.templates = {
            'upload_confirmation': '''
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; }
        .button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .success { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Imágenes Recibidas!</h1>
        </div>
        <div class="content">
            <p>Hola {{ customer_name }},</p>
            
            <p class="success">Hemos recibido exitosamente las imágenes para tu pedido #{{ order_id }}.</p>
            
            <h3>Detalles del pedido:</h3>
            <ul>
                {% for item in items %}
                <li>{{ item.product_name }} ({{ item.quantity }} unidad{% if item.quantity > 1 %}es{% endif %})</li>
                {% endfor %}
            </ul>
            
            <p><strong>Total de imágenes recibidas:</strong> {{ images_count }}</p>
            
            <p>Nuestro equipo revisará las imágenes y procederá con la producción de tu pedido.</p>
            
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            
            <p>¡Gracias por tu confianza!</p>
        </div>
        <div class="footer">
            <p>© {{ current_year }} Poxica - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
            ''',
            
            'admin_notification': '''
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; }
        .info-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; }
        .button { background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Nuevas Imágenes Subidas</h1>
        </div>
        <div class="content">
            <p>Se han subido nuevas imágenes para el pedido #{{ order_id }}.</p>
            
            <div class="info-box">
                <h3>Información del Cliente:</h3>
                <p><strong>Nombre:</strong> {{ customer_name }}</p>
                <p><strong>Email:</strong> {{ customer_email }}</p>
            </div>
            
            <div class="info-box">
                <h3>Productos del Pedido:</h3>
                <ul>
                    {% for item in items %}
                    <li>{{ item.product_name }} ({{ item.quantity }} unidad{% if item.quantity > 1 %}es{% endif %}) - €{{ item.price }}</li>
                    {% endfor %}
                </ul>
            </div>
            
            <div class="info-box">
                <h3>Imágenes Subidas:</h3>
                <p><strong>Total:</strong> {{ images_count }} imagen{% if images_count > 1 %}es{% endif %}</p>
                {% if google_drive_links %}
                <p><strong>Enlaces de Google Drive:</strong></p>
                <ul>
                    {% for link in google_drive_links %}
                    <li><a href="{{ link.url }}" target="_blank">{{ link.name }}</a></li>
                    {% endfor %}
                </ul>
                {% endif %}
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ google_drive_folder_url }}" class="button" target="_blank">Ver Carpeta en Google Drive</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema de Subida de Imágenes - Poxica</p>
        </div>
    </div>
</body>
</html>
            '''
        }
        
        self.jinja_env = Environment(loader=DictLoader(self.templates))

    def send_email(self, to_email: str, subject: str, html_content: str, 
                   from_email: Optional[str] = None) -> bool:
        """Enviar email"""
        try:
            if not self.username or not self.password:
                logger.error("SMTP credentials not configured")
                return False

            from_email = from_email or self.username

            # Crear mensaje
            message = MIMEMultipart("alternative")
            message["Subject"] = subject
            message["From"] = from_email
            message["To"] = to_email

            # Añadir contenido HTML
            html_part = MIMEText(html_content, "html", "utf-8")
            message.attach(html_part)

            # Enviar email
            with smtplib.SMTP(self.smtp_server, self.smtp_port) as server:
                server.starttls()
                server.login(self.username, self.password)
                server.send_message(message)

            logger.info(f"Email sent successfully to {to_email}")
            return True

        except Exception as e:
            logger.error(f"Error sending email to {to_email}: {e}")
            return False

    def send_upload_confirmation(self, customer_email: str, customer_name: str,
                               order_id: int, items: List[dict], images_count: int) -> bool:
        """Enviar confirmación de subida al cliente"""
        try:
            template = self.jinja_env.get_template('upload_confirmation')
            
            html_content = template.render(
                customer_name=customer_name,
                order_id=order_id,
                items=items,
                images_count=images_count,
                current_year=2024
            )

            subject = f"Imágenes recibidas para tu pedido #{order_id} - Poxica"
            
            return self.send_email(customer_email, subject, html_content)

        except Exception as e:
            logger.error(f"Error sending upload confirmation: {e}")
            return False

    def send_admin_notification(self, customer_name: str, customer_email: str,
                              order_id: int, items: List[dict], images_count: int,
                              google_drive_folder_url: str, google_drive_links: List[dict] = None) -> bool:
        """Enviar notificación al administrador"""
        try:
            template = self.jinja_env.get_template('admin_notification')
            
            html_content = template.render(
                customer_name=customer_name,
                customer_email=customer_email,
                order_id=order_id,
                items=items,
                images_count=images_count,
                google_drive_folder_url=google_drive_folder_url,
                google_drive_links=google_drive_links or []
            )

            subject = f"🚨 Nuevas imágenes subidas - Pedido #{order_id}"
            
            return self.send_email(self.admin_email, subject, html_content)

        except Exception as e:
            logger.error(f"Error sending admin notification: {e}")
            return False

    def send_error_notification(self, error_message: str, context: dict = None) -> bool:
        """Enviar notificación de error al administrador"""
        try:
            html_content = f"""
            <html>
            <body style="font-family: Arial, sans-serif;">
                <h2 style="color: #dc3545;">Error en Sistema de Subida</h2>
                <p><strong>Mensaje de error:</strong></p>
                <p style="background-color: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545;">{error_message}</p>
                
                {f'<p><strong>Contexto:</strong></p><pre>{context}</pre>' if context else ''}
                
                <p>Revisa los logs del sistema para más detalles.</p>
            </body>
            </html>
            """

            subject = "🚨 Error en Sistema de Subida de Imágenes"
            
            return self.send_email(self.admin_email, subject, html_content)

        except Exception as e:
            logger.error(f"Error sending error notification: {e}")
            return False

    def health_check(self) -> bool:
        """Verificar que el servicio de email funciona"""
        try:
            if not self.username or not self.password:
                return False

            # Intentar conectar al servidor SMTP
            with smtplib.SMTP(self.smtp_server, self.smtp_port) as server:
                server.starttls()
                server.login(self.username, self.password)

            return True

        except Exception as e:
            logger.error(f"Email service health check failed: {e}")
            return False