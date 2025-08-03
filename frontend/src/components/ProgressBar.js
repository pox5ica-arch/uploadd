import React from 'react';
import styled from 'styled-components';
import { motion } from 'framer-motion';

const ProgressContainer = styled.div`
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  overflow: hidden;
  position: relative;
`;

const ProgressFill = styled(motion.div)`
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px;
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(255, 255, 255, 0.4),
      transparent
    );
    transform: translateX(-100%);
    animation: shimmer 2s infinite;
  }
  
  @keyframes shimmer {
    100% {
      transform: translateX(100%);
    }
  }
`;

const ProgressText = styled.div`
  text-align: center;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.8rem;
  font-weight: 500;
  margin-top: 5px;
`;

function ProgressBar({ 
  progress = 0, 
  showText = true, 
  text,
  animate = true,
  className,
  style 
}) {
  const clampedProgress = Math.min(Math.max(progress, 0), 100);
  
  return (
    <div className={className} style={style}>
      <ProgressContainer>
        <ProgressFill
          initial={animate ? { width: 0 } : false}
          animate={{ width: `${clampedProgress}%` }}
          transition={animate ? { duration: 0.5, ease: "easeInOut" } : false}
        />
      </ProgressContainer>
      
      {showText && (
        <ProgressText>
          {text || `${Math.round(clampedProgress)}%`}
        </ProgressText>
      )}
    </div>
  );
}

export default ProgressBar;