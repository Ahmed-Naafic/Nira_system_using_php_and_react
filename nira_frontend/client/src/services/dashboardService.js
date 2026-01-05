import api from '../api/api';

/**
 * Dashboard Service
 * 
 * Fetches dashboard statistics from the backend
 */

/**
 * Get dashboard statistics
 * 
 * @returns {Promise<Object>} Dashboard statistics
 */
export const getDashboardStats = async () => {
  try {
    const response = await api.get('/api/dashboard/stats.php');
    return response.data;
  } catch (error) {
    throw error;
  }
};

