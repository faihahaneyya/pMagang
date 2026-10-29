import api from './axios';

/**
 * Mengambil daftar vendor dengan pagination dan pencarian.
 * Endpoint terlindungi: GET /api/vendor
 * @param {Object} params - { search, page, per_page }
 */
export const getVendorList = async (params = {}) => {
  const response = await api.get('/vendor', { params });
  return response.data;
};

/**
 * Mengambil semua vendor tanpa pagination (untuk dropdown pilihan).
 * Endpoint terlindungi: GET /api/vendor/all
 */
export const getVendorAll = async () => {
  const response = await api.get('/vendor/all');
  return response.data;
};

/**
 * Mengambil detail satu vendor beserta daftar kontrak terkait.
 * Endpoint terlindungi: GET /api/vendor/{id}
 * @param {string|number} id
 */
export const getVendorDetail = async (id) => {
  const response = await api.get(`/vendor/${id}`);
  return response.data;
};

/**
 * Menambahkan vendor baru.
 * Endpoint terlindungi: POST /api/vendor
 * @param {Object} data - { name, address, phone, email }
 */
export const createVendor = async (data) => {
  const response = await api.post('/vendor', data);
  return response.data;
};

/**
 * Memperbarui data vendor.
 * Endpoint terlindungi: PUT /api/vendor/{id}
 * @param {string|number} id
 * @param {Object} data
 */
export const updateVendor = async (id, data) => {
  const response = await api.put(`/vendor/${id}`, data);
  return response.data;
};

/**
 * Menghapus vendor. Akan gagal jika vendor masih memiliki kontrak aktif.
 * Endpoint terlindungi (IT only): DELETE /api/vendor/{id}
 * @param {string|number} id
 */
export const deleteVendor = async (id) => {
  const response = await api.delete(`/vendor/${id}`);
  return response.data;
};

