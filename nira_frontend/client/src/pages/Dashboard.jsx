import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { getDashboardStats } from '../services/dashboardService';

const Dashboard = () => {
  const { user, hasPermission } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getDashboardStats();
      
      // Debug: Log the response to see what we're getting
      console.log('Dashboard stats response:', data);
      
      if (data.success) {
        // Handle different response formats
        const statsData = data.stats || data.data || data;
        console.log('Setting stats:', statsData);
        setStats(statsData);
      } else {
        setError(data.message || 'Failed to load dashboard statistics');
      }
    } catch (err) {
      console.error('Error fetching dashboard stats:', err);
      console.error('Error response:', err.response);
      
      if (err.response?.status === 401) {
        // Unauthorized - redirect to login
        navigate('/login');
      } else if (err.response?.status === 403) {
        // Forbidden - user doesn't have permission
        setError('You do not have access to dashboard statistics');
      } else if (err.response?.status === 404) {
        // Endpoint not found
        setError('Dashboard statistics endpoint not found. Please check backend configuration.');
      } else {
        setError(
          err.response?.data?.message ||
          err.message ||
          'An error occurred while loading dashboard statistics'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ title, value, icon, color = 'blue' }) => {
    const colorConfig = {
      blue: {
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        text: 'text-blue-900',
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        accent: 'from-blue-500 to-blue-600',
      },
      green: {
        bg: 'bg-green-50',
        border: 'border-green-200',
        text: 'text-green-900',
        iconBg: 'bg-green-100',
        iconColor: 'text-green-600',
        accent: 'from-green-500 to-green-600',
      },
      purple: {
        bg: 'bg-purple-50',
        border: 'border-purple-200',
        text: 'text-purple-900',
        iconBg: 'bg-purple-100',
        iconColor: 'text-purple-600',
        accent: 'from-purple-500 to-purple-600',
      },
      orange: {
        bg: 'bg-orange-50',
        border: 'border-orange-200',
        text: 'text-orange-900',
        iconBg: 'bg-orange-100',
        iconColor: 'text-orange-600',
        accent: 'from-orange-500 to-orange-600',
      },
    };

    const config = colorConfig[color];

    return (
      <div className={`bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 p-6 border ${config.border}`}>
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <p className="text-sm font-medium text-gray-600 mb-3">{title}</p>
            <p className={`text-3xl font-bold ${config.text}`}>{value}</p>
          </div>
          <div className={`w-12 h-12 ${config.iconBg} rounded-lg flex items-center justify-center ml-4`}>
            <span className={`text-2xl ${config.iconColor}`}>{icon}</span>
          </div>
        </div>
        <div className={`mt-4 h-1 bg-gradient-to-r ${config.accent} rounded-full`}></div>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Dashboard</h1>
        <p className="text-gray-600">System overview & statistics</p>
      </div>

      {/* Welcome Banner */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 mb-6 text-white">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h2 className="text-xl font-semibold mb-1">
                Welcome back, {user?.username || 'User'}
              </h2>
              <p className="text-blue-100 text-sm">
                {user?.role?.name || 'No Role'} • {user?.role?.description || 'No description'}
              </p>
            </div>
          </div>
          <div className="text-right hidden sm:block">
            <p className="text-blue-100 text-sm">Last login</p>
            <p className="text-white font-medium">
              {new Date().toLocaleDateString('en-US', { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
              })}
            </p>
          </div>
        </div>
      </div>

      {/* Statistics Cards */}
      {loading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="bg-white rounded-xl shadow-md p-6 border border-gray-200 animate-pulse">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
                  <div className="h-8 bg-gray-200 rounded w-1/3"></div>
                </div>
                <div className="w-12 h-12 bg-gray-200 rounded-lg ml-4"></div>
              </div>
              <div className="mt-4 h-1 bg-gray-200 rounded-full"></div>
            </div>
          ))}
        </div>
      ) : error ? (
        <div className="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-6">
          <div className="flex items-center">
            <svg className="h-5 w-5 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
            <p className="text-yellow-800 font-medium">Unable to load dashboard statistics</p>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          {hasPermission('VIEW_DASHBOARD') && (
            <>
              <StatCard
                title="Total Citizens"
                value={stats?.totalCitizens ?? stats?.citizens ?? stats?.total_citizens ?? '0'}
                icon="👥"
                color="blue"
              />
              <StatCard
                title="Total Users"
                value={stats?.totalUsers ?? stats?.users ?? stats?.total_users ?? '0'}
                icon="👤"
                color="green"
              />
              <StatCard
                title="Total Roles"
                value={stats?.totalRoles ?? stats?.roles ?? stats?.total_roles ?? '0'}
                icon="🔐"
                color="purple"
              />
              <StatCard
                title="Total Reports"
                value={stats?.totalReports ?? stats?.reports ?? stats?.total_reports ?? '0'}
                icon="📊"
                color="orange"
              />
            </>
          )}
        </div>
      )}

      {/* Secondary Sections Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Recent Activity */}
        <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div className="text-center py-8">
            <svg className="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p className="text-gray-500 text-sm">No recent activity</p>
          </div>
        </div>

        {/* System Notices */}
        <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">System Notices</h3>
            <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <div className="text-center py-8">
            <svg className="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p className="text-gray-500 text-sm">No new notices</p>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {hasPermission('VIEW_CITIZEN') && (
            <Link
              to="/citizens"
              className="block p-5 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:shadow-md transition-all duration-200 group"
            >
              <div className="flex items-center mb-2">
                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200 transition-colors">
                  <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <h4 className="font-semibold text-blue-900">Search Citizen</h4>
              </div>
              <p className="text-sm text-blue-700 ml-13">
                Find citizen by National ID or name
              </p>
            </Link>
          )}
          
          {hasPermission('CREATE_CITIZEN') && (
            <Link
              to="/citizens/create"
              className="block p-5 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 hover:shadow-md transition-all duration-200 group"
            >
              <div className="flex items-center mb-2">
                <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200 transition-colors">
                  <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                </div>
                <h4 className="font-semibold text-green-900">Register Citizen</h4>
              </div>
              <p className="text-sm text-green-700 ml-13">
                Register new citizen and generate National ID
              </p>
            </Link>
          )}

          {hasPermission('VIEW_REPORTS') && (
            <Link
              to="/reports"
              className="block p-5 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 hover:shadow-md transition-all duration-200 group"
            >
              <div className="flex items-center mb-2">
                <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200 transition-colors">
                  <svg className="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <h4 className="font-semibold text-purple-900">View Reports</h4>
              </div>
              <p className="text-sm text-purple-700 ml-13">
                Access system reports and analytics
              </p>
            </Link>
          )}

          {hasPermission('MANAGE_USERS') && (
            <Link
              to="/users"
              className="block p-5 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 hover:shadow-md transition-all duration-200 group"
            >
              <div className="flex items-center mb-2">
                <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-orange-200 transition-colors">
                  <svg className="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                </div>
                <h4 className="font-semibold text-orange-900">Manage Users</h4>
              </div>
              <p className="text-sm text-orange-700 ml-13">
                Add, edit, or remove system users
              </p>
            </Link>
          )}

          {hasPermission('MANAGE_ROLES') && (
            <Link
              to="/roles"
              className="block p-5 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 hover:shadow-md transition-all duration-200 group"
            >
              <div className="flex items-center mb-2">
                <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-indigo-200 transition-colors">
                  <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
                <h4 className="font-semibold text-indigo-900">Manage Roles</h4>
              </div>
              <p className="text-sm text-indigo-700 ml-13">
                Configure roles and permissions
              </p>
            </Link>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

