import axios from 'axios';

// Create Axios instance with withCredentials for cookie-based authentication
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/niraSystem/nira_backend', // Update with your NIRA backend URL
  withCredentials: true, // Essential for session cookies
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor (optional - for debugging)
api.interceptors.request.use(
  (config) => {
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle 401 errors globally (session expiration)
api.interceptors.response.use(
  (response) => {
    // Update session expiration time if provided in response
    if (response.data?.sessionExpiresAt) {
      // Store in localStorage for cross-component access
      localStorage.setItem('sessionExpiresAt', response.data.sessionExpiresAt.toString());
    }
    return response;
  },
  (error) => {
    // Handle 401 errors (session expired or not authenticated)
    if (error.response?.status === 401) {
      // Clear session expiration time
      localStorage.removeItem('sessionExpiresAt');
      
      // Check if it's a session expiration (not just missing auth)
      if (error.response?.data?.expired) {
        // Trigger session expired event for AuthContext
        window.dispatchEvent(new CustomEvent('sessionExpired'));
      }
    }
    
    // Let the error propagate - AuthContext and ProtectedRoute will handle 401s
    // We don't redirect here to prevent redirect loops
    return Promise.reject(error);
  }
);

export default api;

