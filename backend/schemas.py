from pydantic import BaseModel, EmailStr, field_validator
from typing import List, Optional, Dict, Any
from datetime import datetime
from decimal import Decimal
import uuid


# Schemas base
class OrderItemBase(BaseModel):
    wc_product_id: int
    product_name: str
    quantity: int
    price: Optional[Decimal] = None


class OrderItemCreate(OrderItemBase):
    pass


class OrderItem(OrderItemBase):
    id: uuid.UUID
    order_id: uuid.UUID
    created_at: datetime

    class Config:
        from_attributes = True


class OrderBase(BaseModel):
    wc_order_id: int
    customer_email: EmailStr
    customer_name: str
    total_amount: Optional[Decimal] = None
    currency: str = "EUR"


class OrderCreate(OrderBase):
    items: List[OrderItemCreate]


class Order(OrderBase):
    id: uuid.UUID
    status: str
    created_at: datetime
    updated_at: datetime
    items: List[OrderItem] = []

    class Config:
        from_attributes = True


class UploadTokenBase(BaseModel):
    token: str
    expires_at: datetime


class UploadTokenCreate(UploadTokenBase):
    order_id: uuid.UUID


class UploadToken(UploadTokenBase):
    id: uuid.UUID
    order_id: uuid.UUID
    used: bool
    created_at: datetime

    class Config:
        from_attributes = True


class UploadedImageBase(BaseModel):
    original_filename: str
    file_size: int
    mime_type: str


class UploadedImageCreate(UploadedImageBase):
    order_id: uuid.UUID
    order_item_id: uuid.UUID
    google_drive_file_id: Optional[str] = None
    google_drive_url: Optional[str] = None
    upload_token_id: Optional[uuid.UUID] = None


class UploadedImage(UploadedImageBase):
    id: uuid.UUID
    order_id: uuid.UUID
    order_item_id: uuid.UUID
    google_drive_file_id: Optional[str] = None
    google_drive_url: Optional[str] = None
    created_at: datetime

    class Config:
        from_attributes = True


# Schemas para API
class WooCommerceWebhook(BaseModel):
    id: int
    status: str
    billing: Dict[str, Any]
    line_items: List[Dict[str, Any]]
    total: str
    currency: str

    @field_validator('line_items')
    @classmethod
    def validate_line_items(cls, v):
        if not v:
            raise ValueError('Order must have at least one item')
        return v


class UploadResponse(BaseModel):
    success: bool
    message: str
    upload_token: Optional[str] = None
    upload_url: Optional[str] = None


class FileUploadResponse(BaseModel):
    success: bool
    message: str
    file_id: Optional[str] = None
    google_drive_url: Optional[str] = None


class OrderSummary(BaseModel):
    order_id: uuid.UUID
    wc_order_id: int
    customer_name: str
    customer_email: str
    status: str
    items: List[OrderItem]
    uploaded_images_count: int
    total_items_count: int

    class Config:
        from_attributes = True


class SystemLogCreate(BaseModel):
    level: str
    message: str
    context: Optional[Dict[str, Any]] = None


class HealthCheck(BaseModel):
    status: str
    timestamp: datetime
    services: Dict[str, str]