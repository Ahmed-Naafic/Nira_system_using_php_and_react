import axios from 'axios';

// Create Axios instance with withCredentials for cookie-based authentication
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/nira_system', // Update with your NIRA backend URL
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

// Response interceptor to handle 401 errors globally
// Note: We don't redirect here to avoid conflicts with ProtectedRoute
// ProtectedRoute will handle redirects for route protection
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Let the error propagate - ProtectedRoute and components will handle 401s
    // Only redirect if we're not already on login page
    if (error.response && error.response.status === 401) {
      const currentPath = window.location.pathname;
      if (currentPath !== '/login' && !currentPath.startsWith('/login')) {
        // Only redirect if not already on login page
        // This prevents redirect loops
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;

