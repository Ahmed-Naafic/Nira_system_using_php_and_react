/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/api';
import SessionExpiredModal from '../components/SessionExpiredModal';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [permissions, setPermissions] = useState([]);
  const [menus, setMenus] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sessionExpired, setSessionExpired] = useState(false);
  const [sessionExpiresAt, setSessionExpiresAt] = useState(null);
  const sessionCheckInterval = useRef(null);
  const navigate = useNavigate();

  // Fetch user info, permissions, and menus on mount
  useEffect(() => {
    fetchUserInfo();
    
    // Listen for session expired event from API interceptor
    const handleSessionExpired = () => {
      setSessionExpired(true);
      setUser(null);
      setPermissions([]);
      setMenus([]);
      stopSessionCheck();
    };
    
    window.addEventListener('sessionExpired', handleSessionExpired);
    
    return () => {
      window.removeEventListener('sessionExpired', handleSessionExpired);
    };
  }, []);

  const fetchUserInfo = async () => {
    try {
      setLoading(true);
      const response = await api.get('/api/auth/me.php');

      if (response.data.success) {
        setUser(response.data.user);
        setPermissions(response.data.permissions || []);
        setMenus(response.data.menus || []);
        
        // Set session expiration time if provided
        if (response.data.sessionExpiresAt) {
          setSessionExpiresAt(response.data.sessionExpiresAt);
          startSessionCheck(response.data.sessionExpiresAt);
        }
      } else {
        // Not authenticated
        setUser(null);
        setPermissions([]);
        setMenus([]);
        setSessionExpiresAt(null);
        stopSessionCheck();
        // Don't navigate here - let the routes handle it
        // The root redirect or ProtectedRoute will handle navigation
      }
    } catch (error) {
      // 401 means not authenticated or session expired
      if (error.response?.status === 401) {
        // Check if session expired or just not authenticated
        if (error.response?.data?.expired) {
          // Session expired
          setSessionExpired(true);
        }
        setUser(null);
        setPermissions([]);
        setMenus([]);
        setSessionExpiresAt(null);
        stopSessionCheck();
        // Don't navigate here - let the routes handle it
        // The root redirect or ProtectedRoute will handle navigation
      } else if (error.response?.status === 403) {
        // 403 means user has no role assigned
        console.error('User has no role assigned:', error.response.data);
        setUser(null);
        setPermissions([]);
        setMenus([]);
        // Show error message
        alert('Error: ' + (error.response.data?.message || 'User does not have a role assigned. Please contact administrator.'));
        setUser(null);
        setPermissions([]);
        setMenus([]);
        // Don't navigate here - let the routes handle it
      } else {
        console.error('Error fetching user info:', error);
        // On network errors or other errors, set user to null
        // This allows the login page to show
        setUser(null);
        setPermissions([]);
        setMenus([]);
        setSessionExpiresAt(null);
        stopSessionCheck();
        // Don't navigate here - let the routes handle it
      }
    } finally {
      setLoading(false);
    }
  };

  // Start checking session expiration
  const startSessionCheck = (expiresAt) => {
    stopSessionCheck(); // Clear any existing interval
    
    if (!expiresAt) return;
    
    // Check every 30 seconds
    sessionCheckInterval.current = setInterval(() => {
      const now = Math.floor(Date.now() / 1000);
      const expires = parseInt(expiresAt);
      
      if (now >= expires) {
        // Session expired
        setSessionExpired(true);
        setUser(null);
        setPermissions([]);
        setMenus([]);
        stopSessionCheck();
      }
    }, 30000); // Check every 30 seconds
  };

  // Stop checking session expiration
  const stopSessionCheck = () => {
    if (sessionCheckInterval.current) {
      clearInterval(sessionCheckInterval.current);
      sessionCheckInterval.current = null;
    }
  };

  // Handle session expiration modal close
  const handleSessionExpiredClose = () => {
    setSessionExpired(false);
    navigate('/login', {
      state: {
        message: 'Your session has expired. Please login again.'
      }
    });
  };

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      stopSessionCheck();
    };
  }, []);

  const hasPermission = (permissionCode) => {
    return permissions.includes(permissionCode);
  };

  const logout = async () => {
    try {
      stopSessionCheck();
      await api.post('/api/auth/logout.php');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setPermissions([]);
      setMenus([]);
      setSessionExpiresAt(null);
      setSessionExpired(false);
      navigate('/login');
    }
  };

  const value = {
    user,
    permissions,
    menus,
    loading,
    hasPermission,
    logout,
    refreshUserInfo: fetchUserInfo,
    sessionExpiresAt,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
      {sessionExpired && (
        <SessionExpiredModal onLogin={handleSessionExpiredClose} />
      )}
    </AuthContext.Provider>
  );
};

