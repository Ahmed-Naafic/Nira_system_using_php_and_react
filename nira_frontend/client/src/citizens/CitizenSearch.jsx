import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api/api';
import { handleViewCitizenDetails } from './utils/citizenNavigation';

const CitizenSearch = () => {
  const [nationalId, setNationalId] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      console.log('Searching for National ID:', nationalId);
      const response = await api.get('/api/citizens/get.php', {
        params: { nationalId: nationalId.trim() },
      });

      console.log('Search response:', response.data);

      if (response.data.success && response.data.data) {
        // Extract nationalId and navigate using shared handler
        const nationalId = response.data.data.nationalId;
        if (nationalId) {
          handleViewCitizenDetails(navigate, nationalId);
        } else {
          setError('Citizen not found');
        }
      } else {
        setError('Citizen not found');
      }
    } catch (err) {
      console.error('Search error:', err);
      console.error('Error response:', err.response);
      
      if (err.response?.status === 404) {
        setError('Citizen not found');
      } else {
        setError(
          err.response?.data?.message ||
            err.message ||
            'An error occurred while searching for the citizen'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto">
      <div className="bg-white shadow rounded-lg p-6">
        <h2 className="text-2xl font-bold text-gray-900 mb-6">
          Search Citizen
        </h2>

        {error && (
          <div className="mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label
              htmlFor="nationalId"
              className="block text-sm font-medium text-gray-700 mb-2"
            >
              National ID <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="nationalId"
              name="nationalId"
              required
              value={nationalId}
              onChange={(e) => setNationalId(e.target.value)}
              placeholder="Enter National ID"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div className="flex space-x-4">
            <button
              type="submit"
              disabled={loading}
              className="flex-1 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {loading ? 'Searching...' : 'Search'}
            </button>
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CitizenSearch;

