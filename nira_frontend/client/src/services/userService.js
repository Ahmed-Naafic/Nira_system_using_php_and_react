import api from '../api/api';

/**
 * User Management Service
 * All endpoints require MANAGE_USERS permission
 */

export const getUsers = async () => {
  const response = await api.get('/api/users/list.php');
  return response.data;
};

export const getUserById = async (id) => {
  const response = await api.get(`/api/users/get.php?id=${id}`);
  return response.data;
};

export const createUser = async (userData) => {
  const response = await api.post('/api/users/create.php', userData);
  return response.data;
};

export const updateUser = async (id, userData) => {
  const response = await api.put('/api/users/update.php', {
    id,
    ...userData,
  });
  return response.data;
};

export const changeUserStatus = async (id, status) => {
  const response = await api.post('/api/users/status.php', {
    id,
    status,
  });
  return response.data;
};

export const resetUserPassword = async (id, newPassword) => {
  const response = await api.post('/api/users/reset-password.php', {
    id,
    password: newPassword,
  });
  return response.data;
};

export const deleteUser = async (id) => {
  const response = await api.post('/api/users/delete.php', {
    id,
  });
  return response.data;
};

export const getRoles = async () => {
  // Try to fetch roles from backend endpoint
  try {
    const response = await api.get('/api/roles/list.php');
    if (response.data.success) {
      return response.data.data || response.data.roles || [];
    }
  } catch (apiError) {
    console.log('Roles endpoint not available, using default roles');
  }
  
  // Fallback to default roles if endpoint doesn't exist
  return [
    { id: 1, name: 'ADMIN', description: 'System Administrator' },
    { id: 2, name: 'OFFICER', description: 'Officer' },
    { id: 3, name: 'VIEWER', description: 'Viewer' },
  ];
};

