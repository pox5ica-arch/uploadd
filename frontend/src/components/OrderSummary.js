import React from 'react';
import styled from 'styled-components';
import { motion } from 'framer-motion';
import { FiShoppingCart, FiUser, FiMail, FiImage, FiCheck } from 'react-icons/fi';

const SummaryCard = styled(motion.div)`
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 25px;
  color: white;
  position: sticky;
  top: 100px;
`;

const SummaryHeader = styled.div`
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
`;

const HeaderIcon = styled.div`
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
`;

const HeaderText = styled.div`
  flex: 1;
`;

const HeaderTitle = styled.h3`
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 2px;
`;

const HeaderSubtitle = styled.p`
  font-size: 0.8rem;
  opacity: 0.7;
  margin: 0;
`;

const InfoSection = styled.div`
  margin-bottom: 20px;
`;

const InfoTitle = styled.h4`
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 10px;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.5px;
`;

const InfoItem = styled.div`
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 0.9rem;
`;

const InfoIcon = styled.div`
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
`;

const InfoText = styled.span`
  color: rgba(255, 255, 255, 0.9);
  word-break: break-word;
`;

const ProductList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 10px;
`;

const ProductItem = styled.div`
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
`;

const ProductName = styled.div`
  font-weight: 500;
  font-size: 0.9rem;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.95);
`;

const ProductDetails = styled.div`
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.7);
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const ProgressSection = styled.div`
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
`;

const ProgressItem = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
`;

const ProgressLabel = styled.span`
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.9);
`;

const ProgressValue = styled.span`
  font-size: 0.9rem;
  font-weight: 600;
  color: ${props => props.completed ? '#28a745' : '#ffc107'};
  display: flex;
  align-items: center;
  gap: 5px;
`;

const StatusBadge = styled.div`
  background: ${props => 
    props.status === 'completed' ? 'rgba(40, 167, 69, 0.2)' :
    props.status === 'in_progress' ? 'rgba(255, 193, 7, 0.2)' :
    'rgba(108, 117, 125, 0.2)'
  };
  color: ${props => 
    props.status === 'completed' ? '#28a745' :
    props.status === 'in_progress' ? '#ffc107' :
    '#6c757d'
  };
  border: 1px solid ${props => 
    props.status === 'completed' ? 'rgba(40, 167, 69, 0.3)' :
    props.status === 'in_progress' ? 'rgba(255, 193, 7, 0.3)' :
    'rgba(108, 117, 125, 0.3)'
  };
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: center;
  margin-top: 15px;
`;

function OrderSummary({ orderData }) {
  if (!orderData) return null;

  const getStatusText = (status) => {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'images_uploaded':
        return 'Imágenes subidas';
      case 'processing':
        return 'En proceso';
      case 'completed':
        return 'Completado';
      default:
        return 'Desconocido';
    }
  };

  const getStatusType = (status) => {
    switch (status) {
      case 'images_uploaded':
      case 'completed':
        return 'completed';
      case 'processing':
        return 'in_progress';
      default:
        return 'pending';
    }
  };

  const totalItems = orderData.items?.reduce((sum, item) => sum + item.quantity, 0) || 0;
  const uploadedImagesCount = orderData.uploaded_images_count || 0;

  return (
    <SummaryCard
      initial={{ opacity: 0, x: 20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ duration: 0.5 }}
    >
      <SummaryHeader>
        <HeaderIcon>
          <FiShoppingCart />
        </HeaderIcon>
        <HeaderText>
          <HeaderTitle>Pedido #{orderData.wc_order_id}</HeaderTitle>
          <HeaderSubtitle>Resumen de tu pedido</HeaderSubtitle>
        </HeaderText>
      </SummaryHeader>

      <InfoSection>
        <InfoTitle>Información del cliente</InfoTitle>
        <InfoItem>
          <InfoIcon><FiUser /></InfoIcon>
          <InfoText>{orderData.customer_name}</InfoText>
        </InfoItem>
        <InfoItem>
          <InfoIcon><FiMail /></InfoIcon>
          <InfoText>{orderData.customer_email}</InfoText>
        </InfoItem>
      </InfoSection>

      <InfoSection>
        <InfoTitle>Productos</InfoTitle>
        <ProductList>
          {orderData.items?.map((item, index) => (
            <ProductItem key={index}>
              <ProductName>{item.product_name}</ProductName>
              <ProductDetails>
                <span>Cantidad: {item.quantity}</span>
                {item.price && <span>€{parseFloat(item.price).toFixed(2)}</span>}
              </ProductDetails>
            </ProductItem>
          ))}
        </ProductList>
      </InfoSection>

      <ProgressSection>
        <InfoTitle>Progreso de subida</InfoTitle>
        <ProgressItem>
          <ProgressLabel>Total de productos:</ProgressLabel>
          <ProgressValue>{totalItems}</ProgressValue>
        </ProgressItem>
        <ProgressItem>
          <ProgressLabel>Imágenes subidas:</ProgressLabel>
          <ProgressValue completed={uploadedImagesCount > 0}>
            {uploadedImagesCount > 0 && <FiCheck />}
            {uploadedImagesCount}
          </ProgressValue>
        </ProgressItem>
      </ProgressSection>

      <StatusBadge status={getStatusType(orderData.status)}>
        {getStatusText(orderData.status)}
      </StatusBadge>
    </SummaryCard>
  );
}

export default OrderSummary;