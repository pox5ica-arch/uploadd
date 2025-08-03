import logging
from typing import Optional, Dict, Any
from woocommerce import API
from config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()


class WooCommerceService:
    def __init__(self):
        self.api = None
        self._initialize_api()

    def _initialize_api(self):
        """Inicializar la API de WooCommerce"""
        try:
            if settings.woocommerce_key and settings.woocommerce_secret:
                self.api = API(
                    url=settings.woocommerce_url,
                    consumer_key=settings.woocommerce_key,
                    consumer_secret=settings.woocommerce_secret,
                    wp_api=True,
                    version="wc/v3",
                    timeout=30
                )
                logger.info("WooCommerce API initialized successfully")
            else:
                logger.error("WooCommerce credentials not provided")
        except Exception as e:
            logger.error(f"Error initializing WooCommerce API: {e}")

    def get_order(self, order_id: int) -> Optional[Dict[str, Any]]:
        """Obtener información de un pedido"""
        try:
            if not self.api:
                logger.error("WooCommerce API not initialized")
                return None

            response = self.api.get(f"orders/{order_id}")
            
            if response.status_code == 200:
                return response.json()
            else:
                logger.error(f"Error getting order {order_id}: {response.status_code}")
                return None

        except Exception as e:
            logger.error(f"Error fetching order {order_id}: {e}")
            return None

    def verify_order_paid(self, order_id: int) -> bool:
        """Verificar si un pedido está pagado"""
        try:
            order = self.get_order(order_id)
            if not order:
                return False

            # Estados que consideramos como "pagado"
            paid_statuses = ['processing', 'completed']
            return order.get('status') in paid_statuses

        except Exception as e:
            logger.error(f"Error verifying payment for order {order_id}: {e}")
            return False

    def get_order_items(self, order_id: int) -> list:
        """Obtener los productos de un pedido"""
        try:
            order = self.get_order(order_id)
            if not order:
                return []

            items = []
            for item in order.get('line_items', []):
                items.append({
                    'product_id': item.get('product_id'),
                    'name': item.get('name'),
                    'quantity': item.get('quantity'),
                    'price': float(item.get('price', 0))
                })

            return items

        except Exception as e:
            logger.error(f"Error getting items for order {order_id}: {e}")
            return []

    def get_customer_info(self, order_id: int) -> Optional[Dict[str, str]]:
        """Obtener información del cliente"""
        try:
            order = self.get_order(order_id)
            if not order:
                return None

            billing = order.get('billing', {})
            return {
                'email': billing.get('email', ''),
                'first_name': billing.get('first_name', ''),
                'last_name': billing.get('last_name', ''),
                'full_name': f"{billing.get('first_name', '')} {billing.get('last_name', '')}".strip()
            }

        except Exception as e:
            logger.error(f"Error getting customer info for order {order_id}: {e}")
            return None

    def update_order_notes(self, order_id: int, note: str) -> bool:
        """Añadir una nota al pedido"""
        try:
            if not self.api:
                logger.error("WooCommerce API not initialized")
                return False

            data = {
                "note": note,
                "customer_note": False
            }

            response = self.api.post(f"orders/{order_id}/notes", data)
            
            if response.status_code == 201:
                logger.info(f"Note added to order {order_id}")
                return True
            else:
                logger.error(f"Error adding note to order {order_id}: {response.status_code}")
                return False

        except Exception as e:
            logger.error(f"Error adding note to order {order_id}: {e}")
            return False

    def health_check(self) -> bool:
        """Verificar que la conexión con WooCommerce funciona"""
        try:
            if not self.api:
                return False

            # Intentar obtener información de la tienda
            response = self.api.get("system_status")
            return response.status_code == 200

        except Exception as e:
            logger.error(f"WooCommerce health check failed: {e}")
            return False

    def validate_webhook_signature(self, payload: str, signature: str, secret: str) -> bool:
        """Validar la firma del webhook de WooCommerce"""
        import hmac
        import hashlib
        import base64

        try:
            # Calcular la firma esperada
            expected_signature = base64.b64encode(
                hmac.new(
                    secret.encode('utf-8'),
                    payload.encode('utf-8'),
                    hashlib.sha256
                ).digest()
            ).decode('utf-8')

            # Comparar con la firma recibida
            return hmac.compare_digest(signature, expected_signature)

        except Exception as e:
            logger.error(f"Error validating webhook signature: {e}")
            return False