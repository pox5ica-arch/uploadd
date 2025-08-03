import os
import io
from typing import Optional, Tuple
from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.http import MediaIoBaseUpload
from googleapiclient.errors import HttpError
from PIL import Image
import logging
from config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()


class GoogleDriveService:
    def __init__(self):
        self.credentials = None
        self.service = None
        self._initialize_service()

    def _initialize_service(self):
        """Inicializar el servicio de Google Drive"""
        try:
            if os.path.exists(settings.google_credentials_path):
                self.credentials = service_account.Credentials.from_service_account_file(
                    settings.google_credentials_path,
                    scopes=['https://www.googleapis.com/auth/drive']
                )
                self.service = build('drive', 'v3', credentials=self.credentials)
                logger.info("Google Drive service initialized successfully")
            else:
                logger.error(f"Google credentials file not found: {settings.google_credentials_path}")
        except Exception as e:
            logger.error(f"Error initializing Google Drive service: {e}")

    def create_folder(self, folder_name: str, parent_folder_id: Optional[str] = None) -> Optional[str]:
        """Crear una carpeta en Google Drive"""
        try:
            if not self.service:
                logger.error("Google Drive service not initialized")
                return None

            parent_id = parent_folder_id or settings.google_drive_folder_id
            
            folder_metadata = {
                'name': folder_name,
                'mimeType': 'application/vnd.google-apps.folder',
                'parents': [parent_id] if parent_id else []
            }

            folder = self.service.files().create(
                body=folder_metadata,
                fields='id'
            ).execute()

            folder_id = folder.get('id')
            logger.info(f"Created folder '{folder_name}' with ID: {folder_id}")
            return folder_id

        except HttpError as e:
            logger.error(f"Error creating folder '{folder_name}': {e}")
            return None

    def upload_file(self, file_content: bytes, filename: str, folder_id: str, 
                   mime_type: str = 'image/jpeg') -> Optional[Tuple[str, str]]:
        """Subir un archivo a Google Drive"""
        try:
            if not self.service:
                logger.error("Google Drive service not initialized")
                return None

            file_metadata = {
                'name': filename,
                'parents': [folder_id]
            }

            media = MediaIoBaseUpload(
                io.BytesIO(file_content),
                mimetype=mime_type,
                resumable=True
            )

            file = self.service.files().create(
                body=file_metadata,
                media_body=media,
                fields='id,webViewLink'
            ).execute()

            file_id = file.get('id')
            web_view_link = file.get('webViewLink')

            # Hacer el archivo público para visualización
            self._make_file_public(file_id)

            logger.info(f"Uploaded file '{filename}' with ID: {file_id}")
            return file_id, web_view_link

        except HttpError as e:
            logger.error(f"Error uploading file '{filename}': {e}")
            return None

    def _make_file_public(self, file_id: str):
        """Hacer un archivo público para visualización"""
        try:
            permission = {
                'type': 'anyone',
                'role': 'reader'
            }
            self.service.permissions().create(
                fileId=file_id,
                body=permission
            ).execute()
        except HttpError as e:
            logger.warning(f"Could not make file public: {e}")

    def create_order_folder_structure(self, order_id: int) -> Optional[str]:
        """Crear estructura de carpetas para un pedido"""
        folder_name = f"Pedido-{order_id}"
        return self.create_folder(folder_name)

    def create_product_folder(self, product_name: str, order_folder_id: str) -> Optional[str]:
        """Crear carpeta para un producto específico"""
        # Limpiar nombre del producto para usar como nombre de carpeta
        clean_name = "".join(c for c in product_name if c.isalnum() or c in (' ', '-', '_')).rstrip()
        return self.create_folder(clean_name, order_folder_id)

    def optimize_and_upload_image(self, file_content: bytes, filename: str, 
                                 folder_id: str) -> Optional[Tuple[str, str]]:
        """Optimizar imagen y subirla a Google Drive"""
        try:
            # Optimizar imagen
            optimized_content = self._optimize_image(file_content)
            
            # Subir imagen optimizada
            return self.upload_file(optimized_content, filename, folder_id)
            
        except Exception as e:
            logger.error(f"Error optimizing and uploading image '{filename}': {e}")
            # Si falla la optimización, subir imagen original
            return self.upload_file(file_content, filename, folder_id)

    def _optimize_image(self, file_content: bytes, max_size: int = 1920, 
                       quality: int = 85) -> bytes:
        """Optimizar imagen reduciendo tamaño y calidad"""
        try:
            # Abrir imagen
            image = Image.open(io.BytesIO(file_content))
            
            # Convertir a RGB si es necesario (para PNG con transparencia)
            if image.mode in ('RGBA', 'LA', 'P'):
                image = image.convert('RGB')
            
            # Redimensionar si es muy grande
            width, height = image.size
            if width > max_size or height > max_size:
                image.thumbnail((max_size, max_size), Image.Resampling.LANCZOS)
            
            # Guardar imagen optimizada
            output = io.BytesIO()
            image.save(output, format='JPEG', quality=quality, optimize=True)
            output.seek(0)
            
            return output.getvalue()
            
        except Exception as e:
            logger.error(f"Error optimizing image: {e}")
            return file_content

    def get_folder_link(self, folder_id: str) -> str:
        """Obtener enlace público de una carpeta"""
        return f"https://drive.google.com/drive/folders/{folder_id}"

    def health_check(self) -> bool:
        """Verificar que el servicio de Google Drive funciona"""
        try:
            if not self.service:
                return False
            
            # Intentar listar archivos en la carpeta raíz
            self.service.files().list(
                q=f"'{settings.google_drive_folder_id}' in parents",
                pageSize=1
            ).execute()
            
            return True
        except Exception as e:
            logger.error(f"Google Drive health check failed: {e}")
            return False