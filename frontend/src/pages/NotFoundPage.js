import React from 'react';
import styled from 'styled-components';
import { motion } from 'framer-motion';
import { FiHome, FiMail } from 'react-icons/fi';

const PageContainer = styled.div`
  max-width: 600px;
  margin: 0 auto;
  padding: 60px 20px;
  text-align: center;
`;

const ErrorCard = styled(motion.div)`
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 16px;
  padding: 50px 30px;
  color: white;
`;

const ErrorCode = styled(motion.h1)`
  font-size: 6rem;
  font-weight: 700;
  margin: 0 0 20px 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  
  @media (max-width: 768px) {
    font-size: 4rem;
  }
`;

const ErrorTitle = styled(motion.h2)`
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 15px;
  
  @media (max-width: 768px) {
    font-size: 1.5rem;
  }
`;

const ErrorMessage = styled(motion.p)`
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin-bottom: 40px;
  
  @media (max-width: 768px) {
    font-size: 1rem;
  }
`;

const ActionButtons = styled(motion.div)`
  display: flex;
  gap: 15px;
  justify-content: center;
  flex-wrap: wrap;
`;

const ActionButton = styled.a`
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: ${props => 
    props.primary ? 
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 
    'rgba(255, 255, 255, 0.1)'
  };
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid ${props => 
    props.primary ? 
    'transparent' : 
    'rgba(255, 255, 255, 0.3)'
  };

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    background: ${props => 
      props.primary ? 
      'linear-gradient(135deg, #5a67d8 0%, #667eea 100%)' : 
      'rgba(255, 255, 255, 0.2)'
    };
  }
`;

function NotFoundPage() {
  return (
    <PageContainer>
      <ErrorCard
        initial={{ opacity: 0, y: 30 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6 }}
      >
        <ErrorCode
          initial={{ opacity: 0, scale: 0.8 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.6, delay: 0.1 }}
        >
          404
        </ErrorCode>
        
        <ErrorTitle
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.2 }}
        >
          Página no encontrada
        </ErrorTitle>
        
        <ErrorMessage
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.3 }}
        >
          Lo sentimos, la página que estás buscando no existe o ha sido movida.
          Verifica que el enlace sea correcto o regresa a la página principal.
        </ErrorMessage>
        
        <ActionButtons
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.4 }}
        >
          <ActionButton 
            href="https://poxica.com" 
            primary
            target="_blank" 
            rel="noopener noreferrer"
          >
            <FiHome />
            Ir a Poxica.com
          </ActionButton>
          
          <ActionButton 
            href="https://poxica.com/contacto" 
            target="_blank" 
            rel="noopener noreferrer"
          >
            <FiMail />
            Contactar soporte
          </ActionButton>
        </ActionButtons>
      </ErrorCard>
    </PageContainer>
  );
}

export default NotFoundPage;