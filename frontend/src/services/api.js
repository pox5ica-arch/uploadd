import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000, // 30 segundos para uploads grandes
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para requests
api.interceptors.request.use(
  (config) => {
    // Agregar timestamp para evitar cache
    if (config.method === 'get') {
      config.params = {
        ...config.params,
        _t: Date.now(),
      };
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para responses
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Manejo global de errores
    if (error.response) {
      // Error del servidor
      console.error('API Error:', error.response.status, error.response.data);
    } else if (error.request) {
      // Error de red
      console.error('Network Error:', error.request);
    } else {
      // Error de configuración
      console.error('Request Error:', error.message);
    }
    
    return Promise.reject(error);
  }
);

// Funciones específicas de la API
export const uploadAPI = {
  getOrderData: (token) => api.get(`/upload/${token}`),
  
  uploadImages: (token, itemId, files, onProgress) => {
    const formData = new FormData();
    formData.append('item_id', itemId);
    
    files.forEach(file => {
      formData.append('files', file);
    });

    return api.post(`/upload/${token}/images`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: onProgress,
    });
  },
  
  healthCheck: () => api.get('/health'),
};

export default api;