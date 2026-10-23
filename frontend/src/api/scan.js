import api from './axios';

/**
 * Mengambil data publik barang inventaris berdasarkan kode scan barcode (Code 128).
 * Endpoint publik: GET /api/scan/{kode}
 * @param {string} kode - Kode unik inventaris (misal: INV-IT-001)
 */
export const scanInventaris = async (kode) => {
  const response = await api.get(`/scan/${encodeURIComponent(kode)}`);
  return response.data;
};
