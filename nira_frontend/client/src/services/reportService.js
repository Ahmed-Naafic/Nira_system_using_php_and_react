import api from '../api/api';

/**
 * Report Service
 * Handles all report-related API calls
 */

/**
 * Get comprehensive summary report
 * @returns {Promise} Summary report data
 */
export const getSummaryReport = async () => {
  const response = await api.get('/api/reports/summary.php');
  return response.data;
};

/**
 * Get citizen report
 * @returns {Promise} Citizen statistics
 */
export const getCitizenReport = async () => {
  const response = await api.get('/api/reports/citizens.php');
  return response.data;
};

/**
 * Get registration report
 * @param {string} period - 'day', 'month', or 'year' (default: 'month')
 * @returns {Promise} Registration statistics
 */
export const getRegistrationReport = async (period = 'month') => {
  const response = await api.get(`/api/reports/registrations.php?period=${period}`);
  return response.data;
};

/**
 * Get user report
 * @returns {Promise} User statistics
 */
export const getUserReport = async () => {
  const response = await api.get('/api/reports/users.php');
  return response.data;
};

