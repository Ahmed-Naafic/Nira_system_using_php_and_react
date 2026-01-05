import { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import api from '../api/api';

const ProtectedRoute = ({ children }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Verify session by making a request to check authentication
    const checkAuth = async () => {
      try {
        // Try to call auth check endpoint
        // If it returns 200, we're authenticated
        // If it returns 401, we're not authenticated
        // If endpoint doesn't exist (404), we'll allow access and let actual API calls handle auth
        const response = await api.get('/api/auth/check.php');
        
        // If we get a response (even if check endpoint doesn't validate properly)
        // and it's not 401, assume authenticated
        if (response.status === 200) {
          setIsAuthenticated(true);
        } else {
          setIsAuthenticated(false);
        }
      } catch (error) {
        // 401 means definitely not authenticated
        if (error.response && error.response.status === 401) {
          setIsAuthenticated(false);
        } else if (error.response && error.response.status === 404) {
          // Check endpoint doesn't exist - allow access, let actual API calls handle auth
          // This is a fallback for backends that don't have a check endpoint
          setIsAuthenticated(true);
        } else {
          // Network error or other issue - be conservative and require login
          setIsAuthenticated(false);
        }
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Verifying session...</p>
        </div>
      </div>
    );
  }

  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

export default ProtectedRoute;

