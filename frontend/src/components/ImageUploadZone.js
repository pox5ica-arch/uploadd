import React, { useCallback } from 'react';
import { useDropzone } from 'react-dropzone';
import styled from 'styled-components';
import { motion } from 'framer-motion';
import { FiUpload, FiImage, FiX } from 'react-icons/fi';

const DropzoneContainer = styled(motion.div)`
  border: 2px dashed ${props => 
    props.isDragActive ? '#667eea' : 
    props.disabled ? '#6c757d' : 
    'rgba(255, 255, 255, 0.3)'
  };
  border-radius: 12px;
  padding: 40px 20px;
  text-align: center;
  cursor: ${props => props.disabled ? 'not-allowed' : 'pointer'};
  transition: all 0.3s ease;
  background: ${props => 
    props.isDragActive ? 'rgba(102, 126, 234, 0.1)' : 
    'rgba(255, 255, 255, 0.05)'
  };
  opacity: ${props => props.disabled ? 0.6 : 1};

  &:hover {
    border-color: ${props => props.disabled ? '#6c757d' : '#667eea'};
    background: ${props => props.disabled ? 'rgba(255, 255, 255, 0.05)' : 'rgba(102, 126, 234, 0.05)'};
  }
`;

const UploadIcon = styled.div`
  font-size: 3rem;
  color: ${props => props.isDragActive ? '#667eea' : 'rgba(255, 255, 255, 0.7)'};
  margin-bottom: 15px;
  transition: color 0.3s ease;
`;

const UploadText = styled.div`
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  font-weight: 500;
  margin-bottom: 8px;
`;

const UploadSubtext = styled.div`
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  line-height: 1.4;
`;

const FileList = styled.div`
  margin-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
`;

const FileItem = styled(motion.div)`
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 10px 15px;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.9);
`;

const FileInfo = styled.div`
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
`;

const FileName = styled.span`
  font-weight: 500;
  word-break: break-all;
`;

const FileSize = styled.span`
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.8rem;
`;

const RemoveButton = styled.button`
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.2s ease;

  &:hover {
    background: rgba(220, 53, 69, 0.1);
  }
`;

const ErrorMessage = styled.div`
  color: #dc3545;
  font-size: 0.9rem;
  margin-top: 10px;
  padding: 10px;
  background: rgba(220, 53, 69, 0.1);
  border-radius: 6px;
  border: 1px solid rgba(220, 53, 69, 0.3);
`;

function ImageUploadZone({ 
  onFilesSelected, 
  disabled = false, 
  accept = {
    'image/jpeg': ['.jpg', '.jpeg'],
    'image/png': ['.png']
  },
  maxSize = 10 * 1024 * 1024, // 10MB
  multiple = true,
  selectedFiles = [],
  onRemoveFile
}) {
  
  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const validateFile = (file) => {
    const errors = [];
    
    // Validar tipo de archivo
    const acceptedTypes = Object.keys(accept).concat(
      Object.values(accept).flat()
    );
    
    const isValidType = acceptedTypes.some(type => {
      if (type.startsWith('.')) {
        return file.name.toLowerCase().endsWith(type.toLowerCase());
      } else {
        return file.type === type;
      }
    });
    
    if (!isValidType) {
      errors.push(`Tipo de archivo no válido. Solo se aceptan: ${Object.values(accept).flat().join(', ')}`);
    }
    
    // Validar tamaño
    if (file.size > maxSize) {
      errors.push(`El archivo es demasiado grande. Máximo: ${formatFileSize(maxSize)}`);
    }
    
    return errors;
  };

  const onDrop = useCallback((acceptedFiles, rejectedFiles) => {
    if (disabled) return;

    let validFiles = [];
    let errors = [];

    // Procesar archivos aceptados
    acceptedFiles.forEach(file => {
      const fileErrors = validateFile(file);
      if (fileErrors.length === 0) {
        validFiles.push(file);
      } else {
        errors.push(`${file.name}: ${fileErrors.join(', ')}`);
      }
    });

    // Procesar archivos rechazados
    rejectedFiles.forEach(({ file, errors: dropzoneErrors }) => {
      const errorMessages = dropzoneErrors.map(error => {
        switch (error.code) {
          case 'file-too-large':
            return `Archivo demasiado grande (máximo ${formatFileSize(maxSize)})`;
          case 'file-invalid-type':
            return 'Tipo de archivo no válido';
          default:
            return error.message;
        }
      });
      errors.push(`${file.name}: ${errorMessages.join(', ')}`);
    });

    if (validFiles.length > 0) {
      onFilesSelected(validFiles);
    }

    if (errors.length > 0) {
      console.error('File validation errors:', errors);
      // Aquí podrías mostrar los errores al usuario
    }
  }, [disabled, accept, maxSize, onFilesSelected]);

  const {
    getRootProps,
    getInputProps,
    isDragActive,
    isDragReject
  } = useDropzone({
    onDrop,
    accept,
    maxSize,
    multiple,
    disabled
  });

  return (
    <div>
      <DropzoneContainer
        {...getRootProps()}
        isDragActive={isDragActive}
        disabled={disabled}
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.3 }}
      >
        <input {...getInputProps()} />
        
        <UploadIcon isDragActive={isDragActive}>
          {isDragActive ? <FiUpload /> : <FiImage />}
        </UploadIcon>
        
        <UploadText>
          {isDragActive ? 
            'Suelta las imágenes aquí' : 
            disabled ? 
              'Subida deshabilitada' :
              'Arrastra imágenes aquí o haz clic para seleccionar'
          }
        </UploadText>
        
        <UploadSubtext>
          {!disabled && (
            <>
              Acepta archivos JPG y PNG de hasta {formatFileSize(maxSize)}
              {multiple && <br />}
              {multiple && 'Puedes seleccionar múltiples archivos'}
            </>
          )}
        </UploadSubtext>

        {isDragReject && (
          <ErrorMessage>
            Algunos archivos no son válidos
          </ErrorMessage>
        )}
      </DropzoneContainer>

      {selectedFiles && selectedFiles.length > 0 && (
        <FileList>
          {selectedFiles.map((file, index) => (
            <FileItem
              key={`${file.name}-${index}`}
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: 20 }}
              transition={{ duration: 0.3 }}
            >
              <FileInfo>
                <FiImage />
                <div>
                  <FileName>{file.name}</FileName>
                  <FileSize> - {formatFileSize(file.size)}</FileSize>
                </div>
              </FileInfo>
              {onRemoveFile && (
                <RemoveButton onClick={() => onRemoveFile(index)}>
                  <FiX />
                </RemoveButton>
              )}
            </FileItem>
          ))}
        </FileList>
      )}
    </div>
  );
}

export default ImageUploadZone;