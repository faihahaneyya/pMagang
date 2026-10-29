import api from './axios';

/**
 * Mengambil daftar semua user (IT only).
 * Endpoint terlindungi: GET /api/users
 * @param {Object} params - { search, role, page, per_page }
 */
export const getUserList = async (params = {}) => {
  const response = await api.get('/users', { params });
  return response.data;
};

/**
 * Mengambil detail satu user.
 * Endpoint terlindungi: GET /api/users/{id}
 * @param {string|number} id
 */
export const getUserDetail = async (id) => {
  const response = await api.get(`/users/${id}`);
  return response.data;
};

/**
 * Membuat user baru.
 * Endpoint terlindungi (IT only): POST /api/users
 * @param {Object} data - { name, email, password, role }
 */
export const createUser = async (data) => {
  const response = await api.post('/users', data);
  return response.data;
};

/**
 * Memperbarui data user (termasuk role dan password opsional).
 * Endpoint terlindungi (IT only): PUT /api/users/{id}
 * @param {string|number} id
 * @param {Object} data
 */
export const updateUser = async (id, data) => {
  const response = await api.put(`/users/${id}`, data);
  return response.data;
};

/**
 * Menghapus user. User tidak dapat menghapus akunnya sendiri.
 * Endpoint terlindungi (IT only): DELETE /api/users/{id}
 * @param {string|number} id
 */
export const deleteUser = async (id) => {
  const response = await api.delete(`/users/${id}`);
  return response.data;
};

