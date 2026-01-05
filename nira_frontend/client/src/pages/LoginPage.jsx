import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Login from '../auth/Login';
import { useAuth } from '../context/AuthContext';

const LoginPage = () => {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  // Redirect to dashboard if already logged in
  useEffect(() => {
    if (!loading && user) {
      navigate('/dashboard', { replace: true });
    }
  }, [user, loading, navigate]);

  // Show loading while checking auth
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Checking authentication...</p>
        </div>
      </div>
    );
  }

  // Don't show login form if already authenticated (will redirect)
  if (user) {
    return null;
  }

  return <Login />;
};

export default LoginPage;

