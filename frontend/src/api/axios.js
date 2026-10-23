import axios from 'axios';

/**
 * Konfigurasi Axios Client terpusat:
 * - baseURL: Mengarah ke endpoint backend Laravel 12 (default: http://localhost:8000/api)
 * - withCredentials: true -> Mengirim cookie sesi (laravel_session) dan cookie CSRF lintas origin
 * - xsrfCookieName & xsrfHeaderName: Menyelaraskan otomatis header CSRF Laravel (X-XSRF-TOKEN)
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

// Interceptor respons terpusat untuk penanganan error yang konsisten
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response ? error.response.status : 0;
    const data = error.response?.data;
    const message = data?.message || (status === 0 ? 'Tidak dapat terhubung ke server backend (Network Error / Offline).' : 'Terjadi kesalahan pada permintaan.');

    return Promise.reject({
      status,
      message,
      errors: data?.errors || null,
      raw: error,
    });
  }
);

export default api;
