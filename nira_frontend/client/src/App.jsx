import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './auth/ProtectedRoute';
import PermissionRoute from './auth/PermissionRoute';
import DashboardLayout from './layout/DashboardLayout';
import LoginPage from './pages/LoginPage';
import Dashboard from './pages/Dashboard';
import CitizenCreate from './citizens/CitizenCreate';
import CitizenSearch from './citizens/CitizenSearch';
import CitizenDetails from './citizens/CitizenDetails';
import CitizenEdit from './citizens/CitizenEdit';
import Citizens from './citizens/Citizens';
import CitizensTrash from './citizens/CitizensTrash';
import Users from './users/Users';
import UsersTrash from './users/UsersTrash';
import ReportsDashboard from './reports/ReportsDashboard';
import { useAuth } from './context/AuthContext';

// Root route component that redirects based on auth status
function RootRedirect() {
  const { user, loading } = useAuth();
  
  // Show loading while checking auth
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }
  
  // Redirect to dashboard if authenticated, otherwise to login
  return <Navigate to={user ? '/dashboard' : '/login'} replace />;
}

function App() {
  return (
    <Routes>
        {/* Public route */}
        <Route path="/login" element={<LoginPage />} />

        {/* Protected routes with permissions */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_DASHBOARD">
                <DashboardLayout>
                  <Dashboard />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_CITIZEN">
                <DashboardLayout>
                  <Citizens />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens/create"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="CREATE_CITIZEN">
                <DashboardLayout>
                  <CitizenCreate />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens/search"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_CITIZEN">
                <DashboardLayout>
                  <CitizenSearch />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens/details"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_CITIZEN">
                <DashboardLayout>
                  <CitizenDetails />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens/edit/:nationalId"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="UPDATE_CITIZEN">
                <DashboardLayout>
                  <CitizenEdit />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/citizens/trash"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_CITIZEN">
                <DashboardLayout>
                  <CitizensTrash />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/users"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="MANAGE_USERS">
                <DashboardLayout>
                  <Users />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/users/trash"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="MANAGE_USERS">
                <DashboardLayout>
                  <UsersTrash />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />
        <Route
          path="/reports"
          element={
            <ProtectedRoute>
              <PermissionRoute requiredPermission="VIEW_REPORTS">
                <DashboardLayout>
                  <ReportsDashboard />
                </DashboardLayout>
              </PermissionRoute>
            </ProtectedRoute>
          }
        />

        {/* Default redirect - checks auth and redirects appropriately */}
        <Route path="/" element={<RootRedirect />} />
      </Routes>
  );
}

export default App;
