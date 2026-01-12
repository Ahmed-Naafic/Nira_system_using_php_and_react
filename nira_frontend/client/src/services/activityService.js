import api from '../api/api';

/**
 * Activity Service
 * Handles all activity-related API calls
 */

/**
 * Get recent activities
 * @param {number} limit - Maximum number of activities (default: 20)
 * @returns {Promise} List of recent activities
 */
export const getRecentActivities = async (limit = 20) => {
  const response = await api.get(`/api/activities/recent.php?limit=${limit}`);
  return response.data;
};

