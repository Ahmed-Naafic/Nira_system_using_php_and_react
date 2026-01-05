import { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/api';
import ViewCitizen from './ViewCitizen';

const CitizenCreate = () => {
  const navigate = useNavigate();
  const nationalIdInputRef = useRef(null);
  const [showViewCitizen, setShowViewCitizen] = useState(false);
  const [formData, setFormData] = useState({
    firstName: '',
    middleName: '',
    lastName: '',
    gender: '',
    dateOfBirth: '',
    placeOfBirth: '',
    nationality: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(null);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await api.post('/api/citizens/create.php', formData);


      if (response.data.success) {
        // Handle different possible field names for national ID
        const nationalId = response.data.nationalId || 
                          response.data.national_id || 
                          response.data.data?.nationalId ||
                          response.data.data?.national_id ||
                          response.data.citizen?.nationalId ||
                          response.data.citizen?.national_id ||
                          null;

        if (!nationalId) {
          setError('Citizen created but National ID not returned. Please check the citizen details.');
          return;
        }

        setSuccess({
          nationalId: nationalId,
          message: response.data.message || 'Citizen registered successfully',
        });
      } else {
        setError(response.data.message || 'Failed to register citizen');
      }
    } catch (err) {
      setError(
        err.response?.data?.message ||
          err.message ||
          'An error occurred while registering the citizen'
      );
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = async (text, event) => {
    try {
      await navigator.clipboard.writeText(text);
      // Show a temporary success message
      if (event?.target) {
        const button = event.target.closest('button');
        if (button) {
          const originalText = button.innerHTML;
          button.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Copied!';
          button.classList.add('bg-green-600', 'hover:bg-green-700');
          button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
          setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
          }, 2000);
        }
      }
    } catch (err) {
      console.error('Failed to copy:', err);
      alert('Failed to copy to clipboard');
    }
  };

  if (success) {
    return (
      <div className="space-y-6">
        <div className="bg-white rounded-xl shadow-md p-6 sm:p-8 border border-gray-200">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
              <svg
                className="h-6 w-6 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <h2 className="mt-4 text-2xl font-bold text-gray-900">
              Citizen Registered Successfully
            </h2>
            <p className="mt-2 text-gray-600">{success.message}</p>

            <div className="mt-6 p-4 bg-gray-50 rounded-lg">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                National ID
              </label>
              <div className="flex items-center space-x-2">
                <input
                  ref={nationalIdInputRef}
                  type="text"
                  readOnly
                  value={success.nationalId || 'Not available'}
                  className="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-white font-mono text-lg focus:outline-none"
                />
                {success.nationalId && (
                  <button
                    onClick={(e) => copyToClipboard(success.nationalId, e)}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 flex items-center"
                  >
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Copy
                  </button>
                )}
              </div>
            </div>

            <div className="mt-6 flex space-x-4 justify-center">
              {success.nationalId && (
                <button
                  onClick={() => {
                    // Read National ID from the input field
                    const nationalIdFromInput = nationalIdInputRef.current?.value?.trim() || success.nationalId?.trim();
                    if (nationalIdFromInput && nationalIdFromInput !== 'Not available') {
                      setShowViewCitizen(true);
                    }
                  }}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150"
                >
                  View Citizen Details
                </button>
              )}
              <button
                onClick={() => {
                  setSuccess(null);
                  setShowViewCitizen(false);
                  setFormData({
                    firstName: '',
                    middleName: '',
                    lastName: '',
                    gender: '',
                    dateOfBirth: '',
                    placeOfBirth: '',
                    nationality: '',
                  });
                }}
                className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150"
              >
                Register Another
              </button>
            </div>
          </div>
        </div>

        {/* View Citizen Component */}
        {showViewCitizen && (
          <ViewCitizen
            nationalId={nationalIdInputRef.current?.value?.trim() || success.nationalId?.trim()}
            onClose={() => setShowViewCitizen(false)}
          />
        )}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Register New Citizen</h1>
        <p className="text-gray-600">Add a new citizen to the national registry</p>
      </div>

      <div className="bg-white rounded-xl shadow-md p-6 sm:p-8 border border-gray-200">
        {error && (
          <div className="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm text-red-700">{error}</p>
              </div>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label
                htmlFor="firstName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                First Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="firstName"
                name="firstName"
                required
                value={formData.firstName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="middleName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Middle Name
              </label>
              <input
                type="text"
                id="middleName"
                name="middleName"
                value={formData.middleName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="lastName"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Last Name <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="lastName"
                name="lastName"
                required
                value={formData.lastName}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="gender"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Gender <span className="text-red-500">*</span>
              </label>
              <select
                id="gender"
                name="gender"
                required
                value={formData.gender}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              >
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label
                htmlFor="dateOfBirth"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Date of Birth <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                id="dateOfBirth"
                name="dateOfBirth"
                required
                value={formData.dateOfBirth}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="placeOfBirth"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Place of Birth <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                id="placeOfBirth"
                name="placeOfBirth"
                required
                value={formData.placeOfBirth}
                onChange={handleChange}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>

            <div>
              <label
                htmlFor="nationality"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Nationality
              </label>
              <input
                type="text"
                id="nationality"
                name="nationality"
                value={formData.nationality}
                onChange={handleChange}
                placeholder="e.g., Somali"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              />
            </div>
          </div>

          <div className="flex space-x-4 pt-4">
            <button
              type="submit"
              disabled={loading}
              className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-sm hover:shadow-md flex items-center justify-center"
            >
              {loading ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Registering...
                </>
              ) : (
                'Register Citizen'
              )}
            </button>
            <button
              type="button"
              onClick={() => navigate('/citizens')}
              className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CitizenCreate;

