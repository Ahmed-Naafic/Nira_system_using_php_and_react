import { useState, useEffect } from 'react';
import { createUser, getRoles } from '../services/userService';

const UserCreate = ({ onClose, onSuccess }) => {
  const [formData, setFormData] = useState({
    username: '',
    password: '',
    role_id: '',
    status: 'ACTIVE',
  });
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [loadingRoles, setLoadingRoles] = useState(true);

  useEffect(() => {
    fetchRoles();
  }, []);

  const fetchRoles = async () => {
    try {
      setLoadingRoles(true);
      const rolesData = await getRoles();
      console.log('Fetched roles:', rolesData); // Debug log
      
      if (rolesData && rolesData.length > 0) {
        setRoles(rolesData);
      } else {
        // Use default roles if API returns empty or fails
        console.warn('No roles returned from API, using defaults');
        // setRoles([
        //   { id: 1, name: 'ADMIN', description: 'System Administrator' },
        //   { id: 2, name: 'OFFICER', description: 'Officer' },
        //   { id: 3, name: 'VIEWER', description: 'Viewer' },
        // ]);
      }
    } catch (err) {
      console.error('Error fetching roles:', err);
      // Use default roles if API fails
      // setRoles([
      //   { id: 1, name: 'ADMIN', description: 'System Administrator' },
      //   { id: 2, name: 'OFFICER', description: 'Officer' },
      //   { id: 3, name: 'VIEWER', description: 'Viewer' },
      // ]);
    } finally {
      setLoadingRoles(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    // Validation
    if (!formData.username.trim()) {
      setError('Username is required');
      return;
    }

    if (!formData.password.trim()) {
      setError('Password is required');
      return;
    }

    if (formData.password.length < 6) {
      setError('Password must be at least 6 characters long');
      return;
    }

    if (!formData.role_id || formData.role_id === '') {
      setError('Role is required');
      return;
    }
    
    // Validate role_id is a valid number
    const roleIdNum = parseInt(formData.role_id, 10);
    if (isNaN(roleIdNum)) {
      setError('Please select a valid role');
      return;
    }

    try {
      setLoading(true);
      
      // Ensure role_id is a number, not a string
      const submitData = {
        ...formData,
        role_id: formData.role_id ? parseInt(formData.role_id, 10) : null,
      };
      
      console.log('Submitting user data:', submitData); // Debug log
      
      const response = await createUser(submitData);
      
      console.log('Create user response:', response); // Debug log

      if (response.success) {
        onSuccess();
      } else {
        setError(response.message || 'Failed to create user');
      }
    } catch (err) {
      console.error('Error creating user:', err);
      console.error('Error response:', err.response?.data); // Debug log
      setError(
        err.response?.data?.message ||
        err.message ||
        'An error occurred while creating the user'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900">Create New User</h2>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Error Message */}
          {error && (
            <div className="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
              <div className="flex items-center">
                <svg className="h-5 w-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
                <p className="text-sm text-red-700">{error}</p>
              </div>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="username" className="block text-sm font-medium text-gray-700 mb-2">
                Username <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="username"
                name="username"
                required
                value={formData.username}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                placeholder="Enter username"
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                Password <span className="text-red-500">*</span>
              </label>
              <input
                type="password"
                id="password"
                name="password"
                required
                value={formData.password}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                placeholder="Enter password (min. 6 characters)"
                minLength={6}
              />
            </div>

            <div>
              <label htmlFor="role_id" className="block text-sm font-medium text-gray-700 mb-2">
                Role <span className="text-red-500">*</span>
              </label>
              <select
                id="role_id"
                name="role_id"
                required
                value={formData.role_id}
                onChange={handleChange}
                disabled={loadingRoles}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="">Select Role</option>
                {roles.length === 0 && !loadingRoles ? (
                  <option value="" disabled>No roles available</option>
                ) : (
                  roles.map((role) => (
                    <option key={role.id} value={String(role.id)}>
                      {role.name} - {role.description || ''}
                    </option>
                  ))
                )}
              </select>
              {loadingRoles && (
                <p className="mt-1 text-xs text-gray-500">Loading roles...</p>
              )}
              {roles.length === 0 && !loadingRoles && (
                <p className="mt-1 text-xs text-red-500">No roles available. Please check backend configuration.</p>
              )}
            </div>

            <div>
              <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                Status
              </label>
              <select
                id="status"
                name="status"
                value={formData.status}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="ACTIVE">Active</option>
                <option value="DISABLED">Disabled</option>
              </select>
            </div>

            {/* Actions */}
            <div className="flex space-x-4 pt-4">
              <button
                type="submit"
                disabled={loading || loadingRoles}
                className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-sm hover:shadow-md flex items-center justify-center"
              >
                {loading ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                  </>
                ) : (
                  'Create User'
                )}
              </button>
              <button
                type="button"
                onClick={onClose}
                disabled={loading}
                className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default UserCreate;

