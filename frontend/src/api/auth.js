import api from './axios';

/**
 * Inisiasi sesi dan cookie CSRF (XSRF-TOKEN) sebelum request mutasi.
 * Endpoint: GET /api/csrf-cookie
 */
export const getCsrfCookie = async () => {
  const response = await api.get('/csrf-cookie');
  if (response.data?.csrf_token) {
    // Set fallback header X-CSRF-TOKEN untuk fleksibilitas ganda (Cookie + Header)
    api.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token;
  }
  return response.data;
};

/**
 * Autentikasi pengguna admin menggunakan session & cookie bawaan Laravel.
 * Endpoint: POST /api/login
 * @param {Object} credentials - { email, password, remember }
 */
export const login = async (credentials) => {
  // 1. Pastikan cookie CSRF telah diinisiasi sebelum melakukan POST login
  await getCsrfCookie();
  
  // 2. Eksekusi login ke backend Laravel
  const response = await api.post('/login', credentials);
  return response.data;
};

/**
 * Mendapatkan profil pengguna yang sedang memiliki sesi login aktif.
 * Endpoint terlindungi: GET /api/me
 */
export const getProfile = async () => {
  const response = await api.get('/me');
  return response.data;
};

/**
 * Mengakhiri sesi pengguna aktif dan menghapus cookie autentikasi.
 * Endpoint terlindungi: POST /api/logout
 */
export const logout = async () => {
  const response = await api.post('/logout');
  delete api.defaults.headers.common['X-CSRF-TOKEN'];
  return response.data;
};
