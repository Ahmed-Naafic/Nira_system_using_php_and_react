import { useState, useEffect } from 'react';
import { updateUser, getRoles } from '../services/userService';
import api from '../api/api';

const UserEdit = ({ user, onClose, onSuccess }) => {
  const [formData, setFormData] = useState({
    role_id: user.role?.id || user.role_id || '',
    phoneNumber: user.phoneNumber || '',
    status: user.status || 'ACTIVE',
  });
  const [existingProfilePicture, setExistingProfilePicture] = useState(user.profilePictureUrl || null);
  const [profilePictureFile, setProfilePictureFile] = useState(null);
  const [profilePicturePreview, setProfilePicturePreview] = useState(null);
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
      setRoles(rolesData);
    } catch (err) {
      console.error('Error fetching roles:', err);
      setRoles([
        { id: 1, name: 'ADMIN', description: 'System Administrator' },
        { id: 2, name: 'OFFICER', description: 'Officer' },
        { id: 3, name: 'VIEWER', description: 'Viewer' },
      ]);
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

  const handleProfilePictureChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      if (!file.type.startsWith('image/')) {
        setError('Please select a valid image file');
        return;
      }
      // Validate file size (5MB)
      if (file.size > 5 * 1024 * 1024) {
        setError('Image file size must be less than 5MB');
        return;
      }
      setProfilePictureFile(file);
      setError('');
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setProfilePicturePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!formData.role_id) {
      setError('Role is required');
      return;
    }

    try {
      setLoading(true);
      
      // Validate phone number format if provided
      if (formData.phoneNumber && !/^[+]?[0-9]{8,15}$/.test(formData.phoneNumber.trim())) {
        setError('Invalid phone number format. Use 8-15 digits with optional country code (e.g., +1234567890)');
        setLoading(false);
        return;
      }
      
      // Check if we have profile picture to upload (use FormData) or just data (use JSON)
      const hasProfilePicture = profilePictureFile;
      
      if (hasProfilePicture) {
        // Use FormData for file upload
        const formDataToSend = new FormData();
        formDataToSend.append('id', user.id);
        if (formData.role_id) {
          formDataToSend.append('role_id', parseInt(formData.role_id, 10));
        }
        if (formData.status) {
          formDataToSend.append('status', formData.status);
        }
        if (formData.phoneNumber.trim()) {
          formDataToSend.append('phoneNumber', formData.phoneNumber.trim());
        }
        
        if (profilePictureFile) {
          formDataToSend.append('profilePicture', profilePictureFile);
        }
        
        const response = await api.post('/api/users/update.php', formDataToSend, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        
        if (response.data.success) {
          onSuccess();
        } else {
          setError(response.data.message || 'Failed to update user');
        }
      } else {
        // Use JSON for regular updates
        const updateData = {
          role_id: formData.role_id ? parseInt(formData.role_id, 10) : null,
          status: formData.status,
          phoneNumber: formData.phoneNumber.trim() || null,
        };
        
        // Remove null values
        Object.keys(updateData).forEach(key => {
          if (updateData[key] === null) {
            delete updateData[key];
          }
        });
        
        const response = await updateUser(user.id, updateData);

        if (response.success) {
          onSuccess();
        } else {
          setError(response.message || 'Failed to update user');
        }
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'An error occurred while updating the user'
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
            <div>
              <h2 className="text-2xl font-bold text-gray-900">Edit User</h2>
              <p className="text-sm text-gray-600 mt-1">Username: {user.username}</p>
            </div>
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
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                  {roles.map((role) => (
                    <option key={role.id} value={role.id}>
                      {role.name} - {role.description}
                    </option>
                  ))}
                </select>
              </div>

            <div>
              <label htmlFor="phoneNumber" className="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
              </label>
              <input
                type="tel"
                id="phoneNumber"
                name="phoneNumber"
                value={formData.phoneNumber}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
                placeholder="+1234567890 (optional)"
                pattern="[+]?[0-9]{8,15}"
              />
              <p className="text-xs text-gray-500 mt-1">8-15 digits with optional country code</p>
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

            <div className="col-span-2">
              <label htmlFor="profilePicture" className="block text-sm font-medium text-gray-700 mb-2">
                Profile Picture
              </label>
              <div className="space-y-3">
                {existingProfilePicture && !profilePicturePreview && (
                  <div className="mb-3">
                    <p className="text-sm text-gray-600 mb-2">Current Profile Picture:</p>
                    <img
                      src={existingProfilePicture}
                      alt="Current profile"
                      className="h-32 w-32 object-cover border border-gray-300 rounded-full"
                    />
                  </div>
                )}
                <input
                  type="file"
                  id="profilePicture"
                  name="profilePicture"
                  accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                  onChange={handleProfilePictureChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 text-sm"
                />
                <p className="text-xs text-gray-500">
                  Accepted formats: JPG, PNG, GIF, WEBP (Max 5MB). Leave empty to keep current picture.
                </p>
                {profilePicturePreview && (
                  <div className="mt-3">
                    <p className="text-sm text-gray-600 mb-2">New Profile Picture Preview:</p>
                    <img
                      src={profilePicturePreview}
                      alt="Preview"
                      className="h-32 w-32 object-cover border border-gray-300 rounded-full"
                    />
                  </div>
                )}
              </div>
            </div>
            </div>

            <div className="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
              <p className="text-sm text-blue-700">
                <strong>Note:</strong> Password cannot be changed here. Use the "Reset Password" action to change the user's password.
              </p>
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
                    Updating...
                  </>
                ) : (
                  'Update User'
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

export default UserEdit;

