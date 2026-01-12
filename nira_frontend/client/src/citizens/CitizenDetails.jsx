import { useLocation, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import api from '../api/api';
import { useAuth } from '../context/AuthContext';

const CitizenDetails = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { hasPermission, permissions, user } = useAuth();
  const [citizen, setCitizen] = useState(location.state?.citizen || null);
  const [loading, setLoading] = useState(!citizen);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState(location.state?.success || '');


  // Handle success message from navigation
  useEffect(() => {
    if (location.state?.success) {
      setSuccessMessage(location.state.success);
      // Clear success message after 5 seconds
      const timer = setTimeout(() => {
        setSuccessMessage('');
      }, 5000);
      // Clear location.state to prevent showing message again on refresh
      window.history.replaceState({}, document.title);
      return () => clearTimeout(timer);
    }
  }, [location.state]);

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


  const handleDelete = async () => {
    if (!citizen?.nationalId) return;
    
    if (!window.confirm(`Are you sure you want to delete citizen ${citizen.nationalId}? This will move them to trash.`)) {
      return;
    }
    
    try {
      const response = await api.post('/api/citizens/delete.php', {
        nationalId: citizen.nationalId,
      });
      
      if (response.data.success) {
        navigate('/citizens/trash', {
          state: { success: 'Citizen moved to trash successfully' },
        });
      } else {
        setError(response.data.message || 'Failed to delete citizen');
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'Failed to delete citizen'
      );
    }
  };

  const fetchCitizen = async (nationalId) => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/api/citizens/get.php', {
        params: { nationalId },
      });

      console.log('CitizenDetails API Response:', response.data);

      // Backend returns normalized structure in data field
      if (response.data.success && response.data.data) {
        setCitizen(response.data.data);
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
              onClick={() => {
                // Navigate based on user permissions
                if (hasPermission('VIEW_CITIZEN')) {
                  navigate('/citizens/search');
                } else {
                  navigate('/dashboard');
                }
              }}
              className="mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              {hasPermission('VIEW_CITIZEN') ? 'Search Again' : 'Back to Dashboard'}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Success Message */}
      {successMessage && (
        <div className="bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-green-800">{successMessage}</p>
              </div>
            </div>
            <button
              onClick={() => setSuccessMessage('')}
              className="text-green-400 hover:text-green-600"
            >
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Citizen Details</h1>
          <p className="text-gray-600">View citizen information</p>
        </div>
        <div className="flex gap-3">
          {/* Edit button - requires UPDATE_CITIZEN permission */}
          {citizen?.nationalId && hasPermission('UPDATE_CITIZEN') && (
            <button
              onClick={() => navigate(`/citizens/edit/${citizen.nationalId}`)}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 flex items-center gap-2 shadow-sm hover:shadow-md"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Edit Citizen
            </button>
          )}
          {/* Delete button - requires DELETE_CITIZEN permission */}
          {citizen?.nationalId && hasPermission('DELETE_CITIZEN') && (
            <button
              onClick={handleDelete}
              className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 flex items-center gap-2 shadow-sm hover:shadow-md"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Delete
            </button>
          )}
          {/* Back button - navigates to appropriate page based on permission */}
          <button
            onClick={() => {
              // Navigate to citizens list if user has permission, otherwise dashboard
              if (hasPermission('VIEW_CITIZEN')) {
                navigate('/citizens');
              } else {
                navigate('/dashboard');
              }
            }}
            className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150"
          >
            {hasPermission('VIEW_CITIZEN') ? 'Back to List' : 'Back to Dashboard'}
          </button>
        </div>
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
              {citizen.nationalId || 'N/A'}
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-500 mb-2">
              Full Name
            </label>
            <p className="text-lg text-gray-900">
              {[
                citizen.firstName,
                citizen.middleName,
                citizen.lastName,
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
              {citizen.dateOfBirth || 'N/A'}
            </p>
          </div>
          {citizen.placeOfBirth && (
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-2">
                Place of Birth
              </label>
              <p className="text-lg text-gray-900">
                {citizen.placeOfBirth}
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

      {/* Files Section */}
      {(citizen.imageUrl || citizen.documentUrl) && (
        <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
            Attached Files
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Image Display */}
            {citizen.imageUrl && (
              <div>
                <label className="block text-sm font-medium text-gray-500 mb-3">
                  Citizen Photo
                </label>
                <div className="border border-gray-300 rounded-lg p-4 bg-gray-50">
                  <img
                    src={citizen.imageUrl}
                    alt={`${citizen.firstName} ${citizen.lastName}`}
                    className="w-full h-auto max-h-96 object-contain rounded-lg"
                    onError={(e) => {
                      e.target.style.display = 'none';
                      e.target.nextSibling.style.display = 'block';
                    }}
                  />
                  <div style={{ display: 'none' }} className="text-center text-gray-500 py-8">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p className="mt-2">Image not available</p>
                  </div>
                  <a
                    href={citizen.imageUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
                  >
                    <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Open in new tab
                  </a>
                </div>
              </div>
            )}

            {/* Document Display */}
            {citizen.documentUrl && (
              <div>
                <label className="block text-sm font-medium text-gray-500 mb-3">
                  Supporting Document
                </label>
                <div className="border border-gray-300 rounded-lg p-4 bg-gray-50">
                  <div className="flex flex-col items-center justify-center py-8">
                    <svg className="h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p className="text-gray-600 mb-4">Document attached</p>
                    <a
                      href={citizen.documentUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150"
                    >
                      <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      View Document
                    </a>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

    </div>
  );
};

export default CitizenDetails;

