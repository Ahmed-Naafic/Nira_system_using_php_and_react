import { useLocation, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import api from '../api/api';

const CitizenDetails = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [citizen, setCitizen] = useState(location.state?.citizen || null);
  const [loading, setLoading] = useState(!citizen);
  const [error, setError] = useState('');

  useEffect(() => {
    // Get nationalId from URL query parameter
    const params = new URLSearchParams(location.search);
    const nationalId = params.get('nationalId');
    
    // If we have nationalId in URL, fetch citizen (ignore location.state)
    // This ensures URL is the source of truth
    if (nationalId) {
      // Reset state before fetching
      setCitizen(null);
      setError('');
      fetchCitizen(nationalId);
    } else if (location.state?.citizen) {
      // Fallback to location.state if no URL param
      setCitizen(location.state.citizen);
      setLoading(false);
    } else {
      // No nationalId and no state
      setError('No citizen data provided');
      setLoading(false);
    }
  }, [location.search]);


  const fetchCitizen = async (nationalId) => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/api/citizens/get.php', {
        params: { nationalId },
      });

      console.log('CitizenDetails API Response:', response.data);

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
        setCitizen(citizenData);
      } else {
        setError('Citizen not found');
      }
    } catch (err) {
      // Handle specific error cases
      if (err.response) {
        // Backend responded with error status
        if (err.response.status === 404) {
          setError('Citizen not found');
        } else if (err.response.status === 400) {
          // Bad request - show backend message
          setError(
            err.response.data?.message ||
            err.response.data?.error ||
            'Invalid request'
          );
        } else {
          // Other HTTP errors
          setError(
            err.response.data?.message ||
            err.response.data?.error ||
            'An error occurred while fetching citizen details'
          );
        }
      } else if (err.request) {
        // Network error - no response received
        setError('Network error. Please check your connection.');
      } else {
        // Something else happened
        setError('An error occurred while fetching citizen details');
      }
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto">
        <div className="bg-white shadow rounded-lg p-6">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading citizen details...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error || !citizen) {
    return (
      <div className="max-w-2xl mx-auto">
        <div className="bg-white shadow rounded-lg p-6">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
              <svg
                className="h-6 w-6 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <h2 className="mt-4 text-xl font-bold text-gray-900">
              {error || 'Citizen not found'}
            </h2>
            <button
              onClick={() => navigate('/citizens/search')}
              className="mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Search Again
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Citizen Details</h1>
          <p className="text-gray-600">View citizen information</p>
        </div>
        <button
          onClick={() => navigate('/citizens')}
          className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150"
        >
          Back to List
        </button>
      </div>

      {/* Identity Section */}
      <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
          Identity Information
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-2">
              National ID
            </label>
            <p className="text-lg font-mono font-semibold text-gray-900">
              {citizen.nationalId || citizen.national_id || 'N/A'}
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-2">
              Full Name
            </label>
            <p className="text-lg text-gray-900">
              {[
                citizen.firstName || citizen.first_name,
                citizen.middleName || citizen.middle_name,
                citizen.lastName || citizen.last_name,
              ]
                .filter(Boolean)
                .join(' ') || 'N/A'}
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-2">
              Gender
            </label>
            <p className="text-lg text-gray-900">
              {citizen.gender || 'N/A'}
            </p>
          </div>
          {citizen.status && (
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-2">
                Status
              </label>
              <span className="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                {citizen.status}
              </span>
            </div>
          )}
        </div>
      </div>

      {/* Birth Information Section */}
      <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
          Birth Information
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-2">
              Date of Birth
            </label>
            <p className="text-lg text-gray-900">
              {citizen.dateOfBirth ||
                citizen.date_of_birth ||
                citizen.dob ||
                'N/A'}
            </p>
          </div>
          {(citizen.placeOfBirth || citizen.place_of_birth) && (
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-2">
                Place of Birth
              </label>
              <p className="text-lg text-gray-900">
                {citizen.placeOfBirth || citizen.place_of_birth}
              </p>
            </div>
          )}
          {citizen.nationality && (
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-2">
                Nationality
              </label>
              <p className="text-lg text-gray-900">{citizen.nationality}</p>
            </div>
          )}
        </div>
      </div>

      {/* Future: Edit Button Placeholder */}
      {/* {hasPermission('UPDATE_CITIZEN') && (
        <div className="flex justify-end">
          <button className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Edit Citizen
          </button>
        </div>
      )} */}
    </div>
  );
};

export default CitizenDetails;

