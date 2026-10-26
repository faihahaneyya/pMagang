import api from './axios';

/**
 * Mengambil daftar seluruh kontrak sewa beli dengan opsi pencarian.
 * Endpoint terlindungi: GET /api/kontrak
 * @param {Object} params - { search, status }
 */
export const getKontrakList = async (params = {}) => {
  const response = await api.get('/kontrak', { params });
  return response.data;
};

/**
 * Mengambil detail kontrak beserta data inventaris terkait dan data finansial.
 * Endpoint terlindungi: GET /api/kontrak/{id}
 * @param {string|number} id
 */
export const getKontrakDetail = async (id) => {
  const response = await api.get(`/kontrak/${id}`);
  return response.data;
};

/**
 * Menambahkan kontrak baru.
 * Endpoint terlindungi: POST /api/kontrak
 * @param {Object} data
 */
export const createKontrak = async (data) => {
  const response = await api.post('/kontrak', data);
  return response.data;
};

/**
 * Memperbarui data kontrak.
 * Endpoint terlindungi: PUT/PATCH /api/kontrak/{id}
 * @param {string|number} id
 * @param {Object} data
 */
export const updateKontrak = async (id, data) => {
  const response = await api.put(`/kontrak/${id}`, data);
  return response.data;
};

/**
 * Menghapus data kontrak.
 * Endpoint terlindungi: DELETE /api/kontrak/{id}
 * @param {string|number} id
 */
export const deleteKontrak = async (id) => {
  const response = await api.delete(`/kontrak/${id}`);
  return response.data;
};

/**
 * Mengambil ringkasan statistik Dashboard dari backend.
 * Endpoint terlindungi: GET /api/dashboard/stats
 * @param {Object} params - { filter }
 */
export const getDashboardStats = async (params = {}) => {
  const response = await api.get('/dashboard/stats', { params });
  return response.data;
};

