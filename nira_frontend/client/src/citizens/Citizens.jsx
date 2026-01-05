import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import api from '../api/api';

const Citizens = () => {
  const { hasPermission } = useAuth();
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState('');
  const [allCitizens, setAllCitizens] = useState([]);
  const [filteredCitizens, setFilteredCitizens] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Load all citizens on component mount
  useEffect(() => {
    fetchAllCitizens();
  }, []);

  // Filter citizens when search query changes
  useEffect(() => {
    if (!searchQuery.trim()) {
      // Show all citizens when search is empty
      setFilteredCitizens(allCitizens);
      setError('');
    } else {
      // Filter citizens based on search query
      filterCitizens(searchQuery.trim());
    }
  }, [searchQuery, allCitizens]);

  const fetchAllCitizens = async () => {
    setLoading(true);
    setError('');
    
    try {
      // Try multiple approaches to get all citizens
      let citizens = [];
      
      // Approach 1: Try search with wildcard or empty query
      try {
        const response = await api.get('/api/citizens/search.php', {
          params: { q: '%' }, // Try wildcard
        });
        
        if (response.data.success) {
          citizens = response.data.citizens || response.data.data || [];
        }
      } catch (searchErr) {
        console.log('Wildcard search failed, trying empty query:', searchErr);
        
        // Approach 2: Try with empty query
        try {
          const response = await api.get('/api/citizens/search.php', {
            params: { q: '' },
          });
          
          if (response.data.success) {
            citizens = response.data.citizens || response.data.data || [];
          }
        } catch (emptyErr) {
          console.log('Empty query failed, trying all endpoint:', emptyErr);
          
          // Approach 3: Try dedicated all endpoint
          try {
            const response = await api.get('/api/citizens/all.php');
            if (response.data.success) {
              citizens = response.data.citizens || response.data.data || [];
            }
          } catch (allErr) {
            console.log('All endpoint failed:', allErr);
            // If all endpoints fail, we'll just start with empty array
            // Users can still search
            citizens = [];
          }
        }
      }

      setAllCitizens(citizens);
      setFilteredCitizens(citizens);
      
      if (citizens.length === 0) {
        // Don't show error if we just have no citizens - that's valid
        // Only show error if there was an actual API error
      }
    } catch (err) {
      console.error('Error fetching all citizens:', err);
      setAllCitizens([]);
      setFilteredCitizens([]);
      // Don't show error message - let users search instead
    } finally {
      setLoading(false);
    }
  };

  const filterCitizens = (query) => {
    if (!query) {
      setFilteredCitizens(allCitizens);
      return;
    }

    const lowerQuery = query.toLowerCase();
    const filtered = allCitizens.filter((citizen) => {
      const nationalId = String(citizen.nationalId || citizen.national_id || '').toLowerCase();
      const firstName = String(citizen.firstName || citizen.first_name || '').toLowerCase();
      const middleName = String(citizen.middleName || citizen.middle_name || '').toLowerCase();
      const lastName = String(citizen.lastName || citizen.last_name || '').toLowerCase();
      const fullName = `${firstName} ${middleName} ${lastName}`.trim().toLowerCase();

      return (
        nationalId.includes(lowerQuery) ||
        firstName.includes(lowerQuery) ||
        lastName.includes(lowerQuery) ||
        fullName.includes(lowerQuery)
      );
    });

    setFilteredCitizens(filtered);
    
    if (filtered.length === 0) {
      setError('No citizens found matching your search');
    } else {
      setError('');
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    // Filtering is handled by useEffect, but we can trigger a server search if needed
    if (searchQuery.trim() && allCitizens.length === 0) {
      // If we don't have all citizens loaded, do a server search
      performServerSearch(searchQuery.trim());
    }
  };

  const performServerSearch = async (query) => {
    setLoading(true);
    setError('');

    try {
      const isLikelyNationalId = /^[0-9A-Za-z]+$/.test(query) && query.length >= 6;
      
      let response;
      let results = [];

      if (isLikelyNationalId) {
        // Try direct lookup by National ID first
        try {
          response = await api.get('/api/citizens/get.php', {
            params: { nationalId: query },
          });

          if (response.data.success && response.data.citizen) {
            results = [response.data.citizen];
          } else {
            throw new Error('Citizen not found');
          }
        } catch (getErr) {
          // If direct lookup fails, try search endpoint
          response = await api.get('/api/citizens/search.php', {
            params: { q: query },
          });

          if (response.data.success) {
            results = response.data.citizens || response.data.data || [];
          }
        }
      } else {
        // Try search endpoint for name searches
        response = await api.get('/api/citizens/search.php', {
          params: { q: query },
        });

        if (response.data.success) {
          results = response.data.citizens || response.data.data || [];
        }
      }

      if (results.length > 0) {
        setFilteredCitizens(results);
        setError('');
      } else {
        setError('No citizens found');
        setFilteredCitizens([]);
      }
    } catch (err) {
      if (err.response?.status === 404) {
        setError('No citizens found');
        setFilteredCitizens([]);
      } else {
        setError(
          err.response?.data?.message ||
            err.message ||
            'An error occurred while searching'
        );
        setFilteredCitizens([]);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleViewDetails = (citizen) => {
    navigate(`/citizens/details?nationalId=${citizen.nationalId || citizen.national_id}`);
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Citizens</h1>
          <p className="text-gray-600">National citizen registry</p>
        </div>
        {hasPermission('CREATE_CITIZEN') && (
          <Link
            to="/citizens/create"
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 shadow-sm hover:shadow-md flex items-center"
          >
            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Register New Citizen
          </Link>
        )}
      </div>

      {/* Search Bar */}
      <div className="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <form onSubmit={handleSearch} className="flex gap-4">
          <div className="flex-1">
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search by National ID or name..."
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150"
              disabled={loading}
            />
          </div>
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 shadow-sm hover:shadow-md flex items-center"
          >
            {loading ? (
              <>
                <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Searching...
              </>
            ) : (
              <>
                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Search
              </>
            )}
          </button>
        </form>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4">
          <div className="flex items-center">
            <svg className="h-5 w-5 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
            <p className="text-yellow-800 font-medium">{error}</p>
          </div>
        </div>
      )}

      {/* Citizens Table */}
      {!loading && (
        <div className="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
          {filteredCitizens.length > 0 ? (
            <>
              <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 className="text-lg font-semibold text-gray-900">
                  {searchQuery.trim() ? `Search Results (${filteredCitizens.length})` : `All Citizens (${filteredCitizens.length})`}
                </h2>
              </div>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        National ID
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Full Name
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Gender
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date of Birth
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {filteredCitizens.map((citizen, index) => (
                      <tr key={citizen.nationalId || citizen.national_id || index} className="hover:bg-gray-50 transition-colors">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="text-sm font-mono text-gray-900">
                            {citizen.nationalId || citizen.national_id || 'N/A'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="text-sm text-gray-900">
                            {[
                              citizen.firstName || citizen.first_name,
                              citizen.middleName || citizen.middle_name,
                              citizen.lastName || citizen.last_name,
                            ]
                              .filter(Boolean)
                              .join(' ')}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="text-sm text-gray-900">
                            {citizen.gender || 'N/A'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="text-sm text-gray-900">
                            {citizen.dateOfBirth ||
                              citizen.date_of_birth ||
                              citizen.dob ||
                              'N/A'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            {citizen.status || 'Active'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <button
                            onClick={() => handleViewDetails(citizen)}
                            className="text-blue-600 hover:text-blue-900 transition-colors"
                          >
                            View
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </>
          ) : (
            <div className="text-center py-12">
              <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p className="text-gray-500 text-lg font-medium">No citizens found</p>
              <p className="text-gray-400 text-sm mt-2">Try a different search term</p>
            </div>
          )}
        </div>
      )}

      {/* Empty State */}
      {!loading && filteredCitizens.length === 0 && !error && (
        <div className="bg-white rounded-xl shadow-md p-12 border border-gray-200 text-center">
          <svg className="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            {searchQuery.trim() ? 'No citizens found' : allCitizens.length === 0 ? 'No citizens loaded' : 'No citizens registered'}
          </h3>
          <p className="text-gray-500">
            {searchQuery.trim() 
              ? 'Try a different search term or clear the search to see all citizens'
              : allCitizens.length === 0
              ? 'Use the search box above to find citizens, or register a new citizen to get started'
              : 'Register a new citizen to get started'}
          </p>
        </div>
      )}
    </div>
  );
};

export default Citizens;

