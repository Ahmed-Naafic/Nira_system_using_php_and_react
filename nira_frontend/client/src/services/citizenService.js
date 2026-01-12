/**
 * Citizen Service
 * Centralized API calls for citizen operations
 * All methods expect and return normalized citizen data structure:
 * { id, nationalId, firstName, middleName, lastName, gender, 
 *   dateOfBirth, placeOfBirth, nationality, status, createdAt }
 */

import api from '../api/api';

/**
 * Get citizen by national ID
 * @param {string} nationalId - National ID
 * @returns {Promise<Object>} Normalized citizen object
 */
export const getCitizen = async (nationalId) => {
  const response = await api.get('/api/citizens/get.php', {
    params: { nationalId },
  });
  
  if (response.data.success && response.data.data) {
    return response.data.data;
  }
  
  throw new Error(response.data.message || 'Failed to fetch citizen');
};

/**
 * Update citizen by national ID
 * @param {string} nationalId - National ID (immutable)
 * @param {Object} data - Update data (camelCase fields)
 * @returns {Promise<Object>} Updated normalized citizen object
 */
export const updateCitizen = async (nationalId, data) => {
  const response = await api.post('/api/citizens/update.php', {
    nationalId,
    ...data,
  });
  
  if (response.data.success && response.data.citizen) {
    return response.data.citizen;
  }
  
  throw new Error(response.data.message || 'Failed to update citizen');
};

/**
 * Search citizens
 * @param {string} query - Search query
 * @param {number} limit - Maximum results
 * @param {number} offset - Offset for pagination
 * @returns {Promise<Array>} Array of normalized citizen objects
 */
export const searchCitizens = async (query, limit = 50, offset = 0) => {
  const response = await api.get('/api/citizens/search.php', {
    params: { q: query, limit, offset },
  });
  
  if (response.data.success && Array.isArray(response.data.data)) {
    return response.data.data;
  }
  
  throw new Error(response.data.message || 'Failed to search citizens');
};

/**
 * List citizens with pagination
 * @param {number} limit - Maximum results
 * @param {number} offset - Offset for pagination
 * @returns {Promise<Array>} Array of normalized citizen objects
 */
export const listCitizens = async (limit = 50, offset = 0) => {
  const response = await api.get('/api/citizens/list.php', {
    params: { limit, offset },
  });
  
  if (response.data.success && Array.isArray(response.data.data)) {
    return response.data.data;
  }
  
  throw new Error(response.data.message || 'Failed to list citizens');
};

/**
 * Create new citizen
 * @param {Object} data - Citizen data (camelCase fields)
 * @returns {Promise<Object>} Created normalized citizen object
 */
export const createCitizen = async (data) => {
  const response = await api.post('/api/citizens/create.php', data);
  
  if (response.data.success && response.data.data) {
    return response.data.data;
  }
  
  throw new Error(response.data.message || 'Failed to create citizen');
};

