from sqlalchemy import Column, String, Integer, Decimal, Boolean, DateTime, Text, ForeignKey
from sqlalchemy.dialects.postgresql import UUID, JSONB
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from database import Base
import uuid


class Order(Base):
    __tablename__ = "orders"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    wc_order_id = Column(Integer, unique=True, nullable=False, index=True)
    customer_email = Column(String(255), nullable=False)
    customer_name = Column(String(255), nullable=False)
    status = Column(String(50), default="pending", index=True)
    total_amount = Column(Decimal(10, 2))
    currency = Column(String(3), default="EUR")
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    # Relaciones
    items = relationship("OrderItem", back_populates="order", cascade="all, delete-orphan")
    upload_tokens = relationship("UploadToken", back_populates="order", cascade="all, delete-orphan")
    uploaded_images = relationship("UploadedImage", back_populates="order", cascade="all, delete-orphan")


class OrderItem(Base):
    __tablename__ = "order_items"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    order_id = Column(UUID(as_uuid=True), ForeignKey("orders.id", ondelete="CASCADE"), nullable=False)
    wc_product_id = Column(Integer, nullable=False)
    product_name = Column(String(255), nullable=False)
    quantity = Column(Integer, nullable=False)
    price = Column(Decimal(10, 2))
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relaciones
    order = relationship("Order", back_populates="items")
    uploaded_images = relationship("UploadedImage", back_populates="order_item", cascade="all, delete-orphan")


class UploadToken(Base):
    __tablename__ = "upload_tokens"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    order_id = Column(UUID(as_uuid=True), ForeignKey("orders.id", ondelete="CASCADE"), nullable=False)
    token = Column(String(255), unique=True, nullable=False, index=True)
    expires_at = Column(DateTime(timezone=True), nullable=False, index=True)
    used = Column(Boolean, default=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relaciones
    order = relationship("Order", back_populates="upload_tokens")
    uploaded_images = relationship("UploadedImage", back_populates="upload_token")


class UploadedImage(Base):
    __tablename__ = "uploaded_images"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    order_id = Column(UUID(as_uuid=True), ForeignKey("orders.id", ondelete="CASCADE"), nullable=False, index=True)
    order_item_id = Column(UUID(as_uuid=True), ForeignKey("order_items.id", ondelete="CASCADE"), nullable=False)
    original_filename = Column(String(255), nullable=False)
    file_size = Column(Integer, nullable=False)
    mime_type = Column(String(100), nullable=False)
    google_drive_file_id = Column(String(255))
    google_drive_url = Column(Text)
    upload_token_id = Column(UUID(as_uuid=True), ForeignKey("upload_tokens.id"))
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relaciones
    order = relationship("Order", back_populates="uploaded_images")
    order_item = relationship("OrderItem", back_populates="uploaded_images")
    upload_token = relationship("UploadToken", back_populates="uploaded_images")


class SystemLog(Base):
    __tablename__ = "system_logs"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    level = Column(String(20), nullable=False, index=True)
    message = Column(Text, nullable=False)
    context = Column(JSONB)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)