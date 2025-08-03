import React from 'react';
import styled, { keyframes } from 'styled-components';

const spin = keyframes`
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
`;

const SpinnerContainer = styled.div`
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
`;

const Spinner = styled.div`
  width: ${props => 
    props.size === 'small' ? '16px' :
    props.size === 'large' ? '40px' : '24px'
  };
  height: ${props => 
    props.size === 'small' ? '16px' :
    props.size === 'large' ? '40px' : '24px'
  };
  border: ${props => 
    props.size === 'small' ? '2px' :
    props.size === 'large' ? '4px' : '3px'
  } solid rgba(255, 255, 255, 0.3);
  border-top: ${props => 
    props.size === 'small' ? '2px' :
    props.size === 'large' ? '4px' : '3px'
  } solid ${props => props.color || '#667eea'};
  border-radius: 50%;
  animation: ${spin} 1s linear infinite;
`;

const LoadingText = styled.span`
  color: ${props => props.textColor || 'rgba(255, 255, 255, 0.9)'};
  font-size: ${props => 
    props.size === 'small' ? '0.8rem' :
    props.size === 'large' ? '1.1rem' : '0.9rem'
  };
  font-weight: 500;
`;

function LoadingSpinner({ 
  size = 'medium', 
  color, 
  text, 
  textColor,
  className 
}) {
  return (
    <SpinnerContainer className={className}>
      <Spinner size={size} color={color} />
      {text && (
        <LoadingText size={size} textColor={textColor}>
          {text}
        </LoadingText>
      )}
    </SpinnerContainer>
  );
}

export default LoadingSpinner;