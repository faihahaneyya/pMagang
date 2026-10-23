import api from './axios';

/**
 * Memeriksa status kesehatan dan ketersediaan layanan API backend.
 * Endpoint publik: GET /api/health
 */
export const checkHealth = async () => {
  const response = await api.get('/health');
  return response.data;
};
