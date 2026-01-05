import { useState, useEffect } from 'react';
import api from '../api/api';

const ViewCitizen = ({ nationalId, onClose }) => {
  const [citizen, setCitizen] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (nationalId) {
      fetchCitizen(nationalId);
    }
  }, [nationalId]);

  const fetchCitizen = async (id) => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/api/citizens/get.php', {
        params: { nationalId: id },
      });

      console.log('ViewCitizen API Response:', response.data);
      console.log('Response status:', response.status);

      // Handle different possible response formats
      let citizenData = null;

      if (response.data.success) {
        // Standard format: { success: true, citizen: {...} }
        citizenData = response.data.citizen || response.data.data || response.data;
      } else if (response.data.citizen) {
        // Has citizen data even if success is false
        citizenData = response.data.citizen;
      } else if (response.data.data) {
        // Data in data field
        citizenData = response.data.data;
      } else if (response.status === 200 && Object.keys(response.data).length > 0) {
        // Direct citizen data (no wrapper)
        citizenData = response.data;
      }

      if (citizenData && (citizenData.nationalId || citizenData.national_id)) {
        console.log('Citizen data found:', citizenData);
        setCitizen(citizenData);
      } else {
        console.warn('Citizen data not found in response:', response.data);
        setError('Citizen not found');
      }
    } catch (err) {
      console.error('ViewCitizen API Error:', err);
      console.error('Error response:', err.response);
      
      if (err.response?.status === 404) {
        setError('Citizen not found');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError(
          err.message ||
          'An error occurred while fetching citizen details'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="mt-6 bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading citizen details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="mt-6 bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <div className="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
          <div className="flex items-center">
            <svg className="h-5 w-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            <div>
              <p className="text-sm text-red-700 font-medium">{error}</p>
              <p className="text-xs text-red-600 mt-1">National ID: {nationalId}</p>
            </div>
          </div>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-150"
          >
            Close
          </button>
        )}
      </div>
    );
  }

  if (!citizen) {
    return null;
  }

  // Handle both camelCase and snake_case field names
  const getField = (camel, snake) => {
    return citizen[camel] || citizen[snake] || 'N/A';
  };

  const fullName = [
    getField('firstName', 'first_name'),
    getField('middleName', 'middle_name'),
    getField('lastName', 'last_name'),
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <div className="mt-6 bg-white rounded-xl shadow-md p-6 border border-gray-200">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-2xl font-bold text-gray-900">Citizen Details</h3>
        {onClose && (
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition duration-150"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Identity Information */}
        <div className="space-y-4">
          <h4 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Identity Information
          </h4>
          
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">National ID</label>
            <p className="text-base text-gray-900 font-mono">{getField('nationalId', 'national_id')}</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
            <p className="text-base text-gray-900">{fullName || 'N/A'}</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Gender</label>
            <p className="text-base text-gray-900">{getField('gender', 'gender')}</p>
          </div>
        </div>

        {/* Birth Information */}
        <div className="space-y-4">
          <h4 className="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Birth Information
          </h4>
          
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Date of Birth</label>
            <p className="text-base text-gray-900">
              {getField('dateOfBirth', 'date_of_birth') || 'N/A'}
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Place of Birth</label>
            <p className="text-base text-gray-900">
              {getField('placeOfBirth', 'place_of_birth') || 'N/A'}
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Nationality</label>
            <p className="text-base text-gray-900">{getField('nationality', 'nationality') || 'N/A'}</p>
          </div>
        </div>
      </div>

      {/* Status */}
      <div className="mt-6 pt-6 border-t border-gray-200">
        <div className="flex items-center justify-between">
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Status</label>
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
              (getField('status', 'status') || 'Active').toLowerCase() === 'active'
                ? 'bg-green-100 text-green-800'
                : 'bg-gray-100 text-gray-800'
            }`}>
              {getField('status', 'status') || 'Active'}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ViewCitizen;

