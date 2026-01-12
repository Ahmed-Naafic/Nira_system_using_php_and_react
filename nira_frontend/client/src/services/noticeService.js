import api from '../api/api';

/**
 * Notice Service
 * Handles all notice-related API calls
 */

/**
 * Get active notices
 * @returns {Promise} List of active notices
 */
export const getNotices = async () => {
  const response = await api.get('/api/notices/list.php');
  return response.data;
};

/**
 * Create a new notice
 * @param {Object} noticeData - Notice data (title, message, type, expiresAt)
 * @returns {Promise} Created notice
 */
export const createNotice = async (noticeData) => {
  const response = await api.post('/api/notices/create.php', noticeData);
  return response.data;
};

/**
 * Delete a notice (soft delete)
 * @param {number} noticeId - Notice ID
 * @returns {Promise} Success response
 */
export const deleteNotice = async (noticeId) => {
  const response = await api.post('/api/notices/delete.php', { id: noticeId });
  return response.data;
};

