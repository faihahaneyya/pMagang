import React, { useState, useEffect } from 'react';
import { 
  Lock, 
  Mail, 
  Eye, 
  EyeOff, 
  ShieldCheck, 
  AlertCircle, 
  RefreshCw, 
  Info,
  Camera
} from 'lucide-react';
import { login, getProfile, checkHealth } from '../api';

export default function LoginPage({ onLoginSuccess, onOpenScanner }) {
  // State Input Form
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  // State Status
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [backendOnline, setBackendOnline] = useState(null);

  // Periksa konektivitas backend saat halaman login dimuat
  useEffect(() => {
    checkBackend();
  }, []);

  const checkBackend = async () => {
    try {
      await checkHealth();
      setBackendOnline(true);
    } catch (err) {
      if (err.status === 0) {
        setBackendOnline(false);
        setErrorMessage('Tidak dapat terhubung ke server. Silakan pastikan koneksi jaringan aktif atau hubungi administrator.');
      } else {
        setBackendOnline(true);
      }
    }
  };

  const validateForm = () => {
    const errors = {};
    if (!email.trim()) {
      errors.email = 'Alamat email wajib diisi.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      errors.email = 'Format alamat email tidak valid.';
    }

    if (!password) {
      errors.password = 'Kata sandi wajib diisi.';
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setErrorMessage('');

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      await login({
        email: email.trim(),
        password,
        remember,
      });

      const verifiedProfile = await getProfile();

      if (verifiedProfile?.user) {
        setPassword('');
        setFieldErrors({});
        setErrorMessage('');
        if (onLoginSuccess) {
          onLoginSuccess(verifiedProfile.user);
        }
      } else {
        setErrorMessage('Verifikasi sesi gagal. Silakan coba masuk kembali.');
      }
    } catch (err) {
      setPassword('');

      if (err.status === 401) {
        setErrorMessage('Email atau kata sandi tidak valid. Silakan periksa kembali.');
      } else if (err.status === 422) {
        setErrorMessage(err.message || 'Data yang dimasukkan tidak memenuhi kriteria validasi.');
        if (err.errors) {
          setFieldErrors({
            email: err.errors.email ? err.errors.email[0] : undefined,
            password: err.errors.password ? err.errors.password[0] : undefined,
          });
        }
      } else if (err.status === 419) {
        setErrorMessage('Sesi Anda telah kedaluwarsa. Silakan coba masuk kembali.');
      } else if (err.status === 0) {
        setErrorMessage('Koneksi terputus: Server sistem tidak merespons. Silakan coba sesaat lagi.');
      } else {
        setErrorMessage(err.message || 'Terjadi kesalahan sistem saat memproses login.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#F3F4F6] text-[#1F2937] flex flex-col justify-between font-sans">
      {/* Top Header Branding */}
      <header className="bg-white border-b border-gray-200/80 shadow-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#176B7A] to-[#0f4e5a] flex items-center justify-center text-white font-extrabold text-base shadow-xs flex-shrink-0">
              BKL
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <span className="font-extrabold text-gray-900 tracking-tight text-sm sm:text-base">
                  PT BANYAN KOALINDO LESTARI
                </span>
                <span className="hidden sm:inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#176B7A]/10 text-[#176B7A]">
                  Portal Internal
                </span>
              </div>
              <p className="text-xs text-[#6B7280]">
                Sistem Informasi Inventaris Sewa Beli
              </p>
            </div>
          </div>
        </div>
      </header>

      {/* Main Container */}
      <main className="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div className="w-full max-w-md">
          {/* Formulir Login Admin */}
          <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-200/80 space-y-6">
            {/* Header Form */}
            <div className="text-center space-y-1.5">
              <div className="w-12 h-12 rounded-2xl bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center mx-auto mb-3 shadow-2xs">
                <Lock className="w-6 h-6" />
              </div>
              <h2 className="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">
                Masuk Administrator
              </h2>
              <p className="text-xs text-[#6B7280]">
                Gunakan kredensial resmi PT Banyan Koalindo Lestari
              </p>
            </div>

            {/* Alert Error Box */}
            {errorMessage && (
              <div 
                id="login-error-alert"
                className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-950 flex items-start space-x-2.5 animate-fadeIn"
              >
                <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" />
                <p className="leading-relaxed font-medium">{errorMessage}</p>
              </div>
            )}

            {/* Form Input */}
            <form onSubmit={handleLogin} className="space-y-4" noValidate>
              {/* Field Email */}
              <div className="space-y-1">
                <label 
                  htmlFor="email" 
                  className="block text-xs font-bold text-gray-700"
                >
                  Alamat Email
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <Mail className="w-4 h-4" />
                  </div>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    value={email}
                    onChange={(e) => {
                      setEmail(e.target.value);
                      if (fieldErrors.email) {
                        setFieldErrors((prev) => ({ ...prev, email: undefined }));
                      }
                    }}
                    placeholder="nama@perusahaan.co.id"
                    disabled={loading}
                    className={`w-full pl-10 pr-4 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 ${
                      fieldErrors.email
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20 text-rose-950'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20 text-gray-900 bg-white'
                    }`}
                  />
                </div>
                {fieldErrors.email && (
                  <p className="text-[11px] text-rose-600 font-medium pl-1">{fieldErrors.email}</p>
                )}
              </div>

              {/* Field Password */}
              <div className="space-y-1">
                <div className="flex justify-between items-center">
                  <label 
                    htmlFor="password" 
                    className="block text-xs font-bold text-gray-700"
                  >
                    Kata Sandi
                  </label>
                </div>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <Lock className="w-4 h-4" />
                  </div>
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    value={password}
                    onChange={(e) => {
                      setPassword(e.target.value);
                      if (fieldErrors.password) {
                        setFieldErrors((prev) => ({ ...prev, password: undefined }));
                      }
                    }}
                    placeholder="••••••••"
                    disabled={loading}
                    className={`w-full pl-10 pr-11 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 ${
                      fieldErrors.password
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20 text-rose-950'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20 text-gray-900 bg-white'
                    }`}
                  />
                  <button
                    type="button"
                    id="btn-toggle-password"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                    tabIndex={-1}
                    title={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                  >
                    {showPassword ? (
                      <EyeOff className="w-4 h-4" />
                    ) : (
                      <Eye className="w-4 h-4" />
                    )}
                  </button>
                </div>
                {fieldErrors.password && (
                  <p className="text-[11px] text-rose-600 font-medium pl-1">{fieldErrors.password}</p>
                )}
              </div>

              {/* Remember Me */}
              <div className="flex items-center justify-between pt-1">
                <label className="flex items-center space-x-2 cursor-pointer select-none">
                  <input
                    id="remember-me"
                    type="checkbox"
                    checked={remember}
                    onChange={(e) => setRemember(e.target.checked)}
                    disabled={loading}
                    className="w-4 h-4 rounded text-[#176B7A] border-gray-300 focus:ring-[#176B7A]/30 cursor-pointer"
                  />
                  <span className="text-xs text-[#6B7280]">Ingat saya di perangkat ini</span>
                </label>
              </div>

              {/* Tombol Submit */}
              <button
                id="btn-submit-login"
                type="submit"
                disabled={loading}
                className="w-full mt-2 py-3 px-4 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-sm shadow-xs transition-all duration-200 flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {loading ? (
                  <>
                    <RefreshCw className="w-4 h-4 animate-spin text-white" />
                    <span>Memverifikasi Kredensial...</span>
                  </>
                ) : (
                  <span>Masuk ke Sistem</span>
                )}
              </button>
            </form>

            {/* Tombol Akses Cepat Pindai Barcode (Kamera HP) */}
            <div className="pt-2">
              <div className="relative flex py-2 items-center">
                <div className="flex-grow border-t border-gray-200"></div>
                <span className="flex-shrink mx-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                  Atau Verifikasi Label Aset
                </span>
                <div className="flex-grow border-t border-gray-200"></div>
              </div>

              <button
                type="button"
                id="btn-login-pindai-barcode"
                onClick={onOpenScanner}
                className="w-full py-2.5 px-4 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-2xs transition-all duration-200 flex items-center justify-center space-x-2"
                title="Pindai label QR Code inventaris menggunakan kamera"
              >
                <Camera className="w-4 h-4 text-[#176B7A]" />
                <span>Pindai QR Code (Kamera HP)</span>
              </button>
            </div>

            {/* Informasi Akses Resmi */}
            <div className="pt-4 border-t border-gray-100">
              <div className="flex items-center justify-center space-x-2 text-[11px] text-[#6B7280]">
                <ShieldCheck className="w-3.5 h-3.5 text-[#176B7A]" />
                <span>Akses Terbatas Khusus Pengguna Berwenang</span>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-white border-t border-gray-200/80 py-4 text-center text-xs text-[#6B7280]">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
          <span>&copy; 2026 PT Banyan Koalindo Lestari. Hak Cipta Dilindungi.</span>
          <div className="flex items-center space-x-3 text-xs">
            <span className="text-[#176B7A] font-medium">Sistem Informasi Inventaris Sewa Beli</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
