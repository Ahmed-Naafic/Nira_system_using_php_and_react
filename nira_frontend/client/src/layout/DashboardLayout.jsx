import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import Sidebar from '../components/Sidebar';

const DashboardLayout = ({ children }) => {
  const [loggingOut, setLoggingOut] = useState(false);
  const { user, logout } = useAuth();

  const handleLogout = async () => {
    setLoggingOut(true);
    await logout();
    setLoggingOut(false);
  };

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <Sidebar />

      {/* Main Content Area */}
      <div className="flex-1 ml-64">
        {/* Header */}
        <header className="bg-white shadow sticky top-0 z-10">
          <div className="px-4 sm:px-6 lg:px-8">
            <div className="flex justify-between items-center py-4">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">NIRA</h1>
                <p className="text-sm text-gray-600">
                  National Identification Registration Authority
                </p>
              </div>
              <div className="flex items-center space-x-4">
                {user && (
                  <div className="flex items-center space-x-3">
                    {user.profilePictureUrl ? (
                      <img
                        src={user.profilePictureUrl}
                        alt={user.username}
                        className="h-10 w-10 rounded-full object-cover border border-gray-300"
                        onError={(e) => {
                          e.target.style.display = 'none';
                          e.target.nextElementSibling.style.display = 'flex';
                        }}
                      />
                    ) : null}
                    <div className={`h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold text-sm ${user.profilePictureUrl ? 'hidden' : ''}`}
                      style={{
                        backgroundColor: user.profilePictureUrl ? 'transparent' : `hsl(${user.id * 137.508 % 360}, 70%, 50%)`
                      }}
                    >
                      {user.username.charAt(0).toUpperCase()}
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-medium text-gray-900">
                        {user.username}
                      </p>
                      <p className="text-xs text-gray-500">
                        {user.role?.name || 'No Role'}
                        {user.phoneNumber && ` • ${user.phoneNumber}`}
                      </p>
                    </div>
                  </div>
                )}
                <button
                  onClick={handleLogout}
                  disabled={loggingOut}
                  className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                >
                  {loggingOut ? 'Logging out...' : 'Logout'}
                </button>
              </div>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="py-6 px-4 sm:px-6 lg:px-8">
          {children}
        </main>
      </div>
    </div>
  );
};

export default DashboardLayout;

