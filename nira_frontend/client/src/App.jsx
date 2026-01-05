import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './auth/ProtectedRoute';
import PermissionRoute from './auth/PermissionRoute';
import DashboardLayout from './layout/DashboardLayout';
import LoginPage from './pages/LoginPage';
import Dashboard from './pages/Dashboard';
import CitizenCreate from './citizens/CitizenCreate';
import CitizenSearch from './citizens/CitizenSearch';
import CitizenDetails from './citizens/CitizenDetails';
import Citizens from './citizens/Citizens';
import Users from './users/Users';

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

        {/* Default redirect */}
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
      </Routes>
  );
}

export default App;
