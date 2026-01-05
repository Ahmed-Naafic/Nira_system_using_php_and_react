import { createContext, useContext, useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/api';

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
  const navigate = useNavigate();

  // Fetch user info, permissions, and menus on mount
  useEffect(() => {
    fetchUserInfo();
  }, []);

  const fetchUserInfo = async () => {
    try {
      setLoading(true);
      const response = await api.get('/api/auth/me.php');

      if (response.data.success) {
        setUser(response.data.user);
        setPermissions(response.data.permissions || []);
        setMenus(response.data.menus || []);
      } else {
        // Not authenticated
        setUser(null);
        setPermissions([]);
        setMenus([]);
        navigate('/login');
      }
    } catch (error) {
      // 401 means not authenticated
      if (error.response?.status === 401) {
        setUser(null);
        setPermissions([]);
        setMenus([]);
        navigate('/login');
      } else if (error.response?.status === 403) {
        // 403 means user has no role assigned
        console.error('User has no role assigned:', error.response.data);
        setUser(null);
        setPermissions([]);
        setMenus([]);
        // Show error message and redirect to login
        alert('Error: ' + (error.response.data?.message || 'User does not have a role assigned. Please contact administrator.'));
        navigate('/login');
      } else {
        console.error('Error fetching user info:', error);
        // On network errors, still try to continue (might be temporary)
        setUser(null);
        setPermissions([]);
        setMenus([]);
      }
    } finally {
      setLoading(false);
    }
  };

  const hasPermission = (permissionCode) => {
    return permissions.includes(permissionCode);
  };

  const logout = async () => {
    try {
      await api.post('/api/auth/logout.php');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setPermissions([]);
      setMenus([]);
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
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

