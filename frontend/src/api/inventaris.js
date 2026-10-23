import api from './axios';

/**
 * Mengambil daftar data inventaris dengan opsi filter, pencarian, dan pagination.
 * Endpoint terlindungi: GET /api/inventaris
 * @param {Object} params - { search, status_aset, status_kepemilikan, per_page, page }
 */
export const getInventarisList = async (params = {}) => {
  const response = await api.get('/inventaris', { params });
  return response.data;
};

/**
 * Mengambil detail lengkap suatu aset inventaris beserta relasi foto dan kontrak.
 * Endpoint terlindungi: GET /api/inventaris/{id}
 * @param {string|number} id
 */
export const getInventarisDetail = async (id) => {
  const response = await api.get(`/inventaris/${id}`);
  return response.data;
};

/**
 * Menambahkan aset inventaris baru.
 * Endpoint terlindungi: POST /api/inventaris
 * @param {Object|FormData} data
 */
export const createInventaris = async (data) => {
  const headers = data instanceof FormData ? { 'Content-Type': 'multipart/form-data' } : {};
  const response = await api.post('/inventaris', data, { headers });
  return response.data;
};

/**
 * Memperbarui data aset inventaris.
 * Endpoint terlindungi: PUT/PATCH /api/inventaris/{id}
 * @param {string|number} id
 * @param {Object} data
 */
export const updateInventaris = async (id, data) => {
  if (data instanceof FormData) {
    // Gunakan POST dengan parameter _method=PUT agar PHP/Laravel dapat memproses unggahan file multipart pada operasi update
    data.append('_method', 'PUT');
    const response = await api.post(`/inventaris/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  }
  const response = await api.put(`/inventaris/${id}`, data);
  return response.data;
};

/**
 * Menghapus data aset inventaris.
 * Endpoint terlindungi: DELETE /api/inventaris/{id}
 * @param {string|number} id
 */
export const deleteInventaris = async (id) => {
  const response = await api.delete(`/inventaris/${id}`);
  return response.data;
};
