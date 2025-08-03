import os
from pydantic_settings import BaseSettings
from functools import lru_cache


class Settings(BaseSettings):
    # Database
    database_url: str = os.getenv("DATABASE_URL", "postgresql://user:password@localhost/db")
    
    # Redis
    redis_url: str = os.getenv("REDIS_URL", "redis://localhost:6379")
    
    # Application
    secret_key: str = os.getenv("SECRET_KEY", "your-secret-key-change-in-production")
    algorithm: str = "HS256"
    access_token_expire_hours: int = 24
    upload_token_expire_hours: int = 72
    
    # WooCommerce
    woocommerce_url: str = os.getenv("WOOCOMMERCE_URL", "https://poxica.com")
    woocommerce_key: str = os.getenv("WOOCOMMERCE_KEY", "")
    woocommerce_secret: str = os.getenv("WOOCOMMERCE_SECRET", "")
    
    # Google Drive
    google_credentials_path: str = os.getenv("GOOGLE_CREDENTIALS_PATH", "/app/credentials/google-credentials.json")
    google_drive_folder_id: str = os.getenv("GOOGLE_DRIVE_FOLDER_ID", "")
    
    # Email
    smtp_server: str = os.getenv("SMTP_SERVER", "smtp.gmail.com")
    smtp_port: int = int(os.getenv("SMTP_PORT", "587"))
    smtp_username: str = os.getenv("SMTP_USERNAME", "")
    smtp_password: str = os.getenv("SMTP_PASSWORD", "")
    admin_email: str = os.getenv("ADMIN_EMAIL", "admin@poxica.com")
    
    # Frontend
    frontend_url: str = os.getenv("FRONTEND_URL", "https://upload.poxica.com")
    
    # File upload
    max_file_size: int = 10 * 1024 * 1024  # 10MB
    allowed_extensions: list = [".jpg", ".jpeg", ".png"]
    upload_path: str = "/app/temp"
    
    # CORS
    cors_origins: list = [
        "https://upload.poxica.com",
        "http://localhost:3000",  # Para desarrollo
        "http://127.0.0.1:3000"   # Para desarrollo
    ]

    class Config:
        env_file = ".env"


@lru_cache()
def get_settings():
    return Settings()