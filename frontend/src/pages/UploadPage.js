import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import styled from 'styled-components';
import { motion, AnimatePresence } from 'framer-motion';
import toast from 'react-hot-toast';
import { FiUpload, FiCheck, FiX, FiImage } from 'react-icons/fi';
import api from '../services/api';
import LoadingSpinner from '../components/LoadingSpinner';
import ImageUploadZone from '../components/ImageUploadZone';
import OrderSummary from '../components/OrderSummary';
import ProgressBar from '../components/ProgressBar';

const PageContainer = styled.div`
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
`;

const PageTitle = styled(motion.h1)`
  color: white;
  text-align: center;
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 10px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const PageSubtitle = styled(motion.p)`
  color: rgba(255, 255, 255, 0.9);
  text-align: center;
  font-size: 1.1rem;
  margin-bottom: 40px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;

  @media (max-width: 768px) {
    font-size: 1rem;
    margin-bottom: 30px;
  }
`;

const ContentGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 30px;
  align-items: start;

  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
    gap: 20px;
  }
`;

const MainContent = styled.div`
  min-height: 400px;
`;

const Sidebar = styled.div`
  @media (max-width: 1024px) {
    order: -1;
  }
`;

const ErrorCard = styled(motion.div)`
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  border-radius: 12px;
  padding: 30px;
  text-align: center;
  color: white;
  backdrop-filter: blur(10px);
`;

const ErrorIcon = styled.div`
  font-size: 3rem;
  margin-bottom: 20px;
  color: #dc3545;
`;

const ErrorTitle = styled.h2`
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: #dc3545;
`;

const ErrorMessage = styled.p`
  font-size: 1rem;
  opacity: 0.9;
  line-height: 1.6;
`;

const ProductList = styled.div`
  display: flex;
  flex-direction: column;
  gap: 20px;
`;

const ProductCard = styled(motion.div)`
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 25px;
  color: white;
`;

const ProductHeader = styled.div`
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
`;

const ProductInfo = styled.div`
  flex: 1;
`;

const ProductName = styled.h3`
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 5px;
`;

const ProductDetails = styled.p`
  font-size: 0.9rem;
  opacity: 0.8;
`;

const UploadStatus = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  color: ${props => props.completed ? '#28a745' : '#ffc107'};
`;

function UploadPage() {
  const { token } = useParams();
  const [loading, setLoading] = useState(true);
  const [orderData, setOrderData] = useState(null);
  const [error, setError] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState({});
  const [completedUploads, setCompletedUploads] = useState({});

  useEffect(() => {
    loadOrderData();
  }, [token]);

  const loadOrderData = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/upload/${token}`);
      setOrderData(response.data);
      setError(null);
    } catch (err) {
      console.error('Error loading order data:', err);
      if (err.response?.status === 404) {
        setError({
          title: 'Enlace no válido',
          message: 'El enlace de subida no es válido o ha expirado. Por favor, contacta con el servicio de atención al cliente.'
        });
      } else {
        setError({
          title: 'Error de conexión',
          message: 'No se pudo cargar la información del pedido. Por favor, inténtalo de nuevo más tarde.'
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const handleFileUpload = async (files, itemId) => {
    if (!files || files.length === 0) return;

    try {
      setUploading(true);
      setUploadProgress(prev => ({ ...prev, [itemId]: 0 }));

      const formData = new FormData();
      formData.append('item_id', itemId);
      
      files.forEach(file => {
        formData.append('files', file);
      });

      const response = await api.post(
        `/upload/${token}/images`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
          onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
            setUploadProgress(prev => ({ ...prev, [itemId]: percentCompleted }));
          },
        }
      );

      if (response.data.success) {
        setCompletedUploads(prev => ({ 
          ...prev, 
          [itemId]: (prev[itemId] || 0) + files.length 
        }));
        
        toast.success(
          `¡${files.length} imagen${files.length > 1 ? 'es' : ''} subida${files.length > 1 ? 's' : ''} correctamente!`
        );
        
        // Recargar datos del pedido para actualizar contadores
        await loadOrderData();
      } else {
        throw new Error(response.data.message || 'Error en la subida');
      }
    } catch (err) {
      console.error('Upload error:', err);
      toast.error(
        err.response?.data?.detail || 
        'Error al subir las imágenes. Por favor, inténtalo de nuevo.'
      );
    } finally {
      setUploading(false);
      setUploadProgress(prev => ({ ...prev, [itemId]: 100 }));
      
      // Limpiar progreso después de un delay
      setTimeout(() => {
        setUploadProgress(prev => {
          const newProgress = { ...prev };
          delete newProgress[itemId];
          return newProgress;
        });
      }, 2000);
    }
  };

  if (loading) {
    return (
      <PageContainer>
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '400px' }}>
          <LoadingSpinner size="large" />
        </div>
      </PageContainer>
    );
  }

  if (error) {
    return (
      <PageContainer>
        <ErrorCard
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5 }}
        >
          <ErrorIcon>
            <FiX />
          </ErrorIcon>
          <ErrorTitle>{error.title}</ErrorTitle>
          <ErrorMessage>{error.message}</ErrorMessage>
        </ErrorCard>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageTitle
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6 }}
      >
        Sube tus imágenes
      </PageTitle>
      
      <PageSubtitle
        initial={{ opacity: 0, y: -10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, delay: 0.1 }}
      >
        Arrastra y suelta tus imágenes o haz clic para seleccionarlas. 
        Acepta archivos JPG y PNG de hasta 10MB cada uno.
      </PageSubtitle>

      <ContentGrid>
        <MainContent>
          <ProductList>
            <AnimatePresence>
              {orderData?.items?.map((item, index) => (
                <ProductCard
                  key={item.id}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                >
                  <ProductHeader>
                    <ProductInfo>
                      <ProductName>{item.product_name}</ProductName>
                      <ProductDetails>
                        Cantidad: {item.quantity} unidad{item.quantity > 1 ? 'es' : ''}
                      </ProductDetails>
                    </ProductInfo>
                    <UploadStatus completed={completedUploads[item.id] > 0}>
                      {completedUploads[item.id] > 0 ? (
                        <>
                          <FiCheck />
                          {completedUploads[item.id]} imagen{completedUploads[item.id] > 1 ? 'es' : ''}
                        </>
                      ) : (
                        <>
                          <FiImage />
                          Sin imágenes
                        </>
                      )}
                    </UploadStatus>
                  </ProductHeader>

                  {uploadProgress[item.id] !== undefined && (
                    <ProgressBar 
                      progress={uploadProgress[item.id]} 
                      style={{ marginBottom: '20px' }}
                    />
                  )}

                  <ImageUploadZone
                    onFilesSelected={(files) => handleFileUpload(files, item.id)}
                    disabled={uploading}
                    accept={{
                      'image/jpeg': ['.jpg', '.jpeg'],
                      'image/png': ['.png']
                    }}
                    maxSize={10 * 1024 * 1024} // 10MB
                    multiple={true}
                  />
                </ProductCard>
              ))}
            </AnimatePresence>
          </ProductList>
        </MainContent>

        <Sidebar>
          <OrderSummary orderData={orderData} />
        </Sidebar>
      </ContentGrid>
    </PageContainer>
  );
}

export default UploadPage;