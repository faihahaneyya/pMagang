import React, { useState, useEffect } from 'react';
import LoginPage from './components/LoginPage';
import Dashboard from './components/Dashboard';
import PublicScanPage from './components/PublicScanPage';
import BarcodeScannerModal from './components/BarcodeScannerModal';
import { getProfile, logout } from './api';
import { RefreshCw } from 'lucide-react';

function getPublicScanKode() {
  const pathname = window.location.pathname;
  const match = pathname.match(/^\/(?:aset|scan)\/([^/?#]+)/i);
  return match ? decodeURIComponent(match[1]) : null;
}

function isScannerRoute() {
  const pathname = window.location.pathname.toLowerCase().replace(/\/+$/, '');
  return pathname === '/pindai' || pathname === '/scan';
}

function getInitialMenu() {
  const pathname = window.location.pathname.toLowerCase();
  if (pathname.startsWith('/inventaris')) return 'inventaris';
  if (pathname.startsWith('/kontrak')) return 'kontrak';
  if (pathname.startsWith('/laporan')) return 'laporan';
  return 'dashboard';
}

export default function App() {
  const [publicScanKode, setPublicScanKode] = useState(() => getPublicScanKode());
  const [showScanner, setShowScanner] = useState(() => isScannerRoute());
  const [activeMenu, setActiveMenu] = useState(() => getInitialMenu());
  const [currentUser, setCurrentUser] = useState(null);
  const [checkingSession, setCheckingSession] = useState(!getPublicScanKode() && !isScannerRoute());

  useEffect(() => {
    const handlePopState = () => {
      const kode = getPublicScanKode();
      setPublicScanKode(kode);
      setShowScanner(isScannerRoute());

      if (!kode && !isScannerRoute()) {
        setActiveMenu(getInitialMenu());
        if (!currentUser) {
          verifySession();
        }
      }
    };

    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, [currentUser]);

  useEffect(() => {
    // Hanya verifikasi sesi jika tidak sedang membuka halaman scan publik / scanner
    if (!publicScanKode && !isScannerRoute()) {
      verifySession();
    }
  }, [publicScanKode]);

  /**
   * Selalu verifikasi sesi aktif melalui endpoint resmi GET /api/me ke Laravel backend.
   * Tidak mengandalkan state React murni atau token lokal.
   */
  const verifySession = async () => {
    setCheckingSession(true);
    try {
      const profile = await getProfile();
      if (profile?.user) {
        setCurrentUser(profile.user);
      } else {
        setCurrentUser(null);
      }
    } catch {
      // Respons 401 atau kegagalan koneksi memastikan status unauthenticated
      setCurrentUser(null);
    } finally {
      setCheckingSession(false);
    }
  };

  /**
   * Logout aman: Mengakhiri sesi Laravel di backend via POST /api/logout,
   * lalu membersihkan state autentikasi React dan mengembalikan tampilan ke login.
   */
  const handleLogout = async () => {
    try {
      await logout();
    } catch (err) {
      console.warn('Logout notice:', err.message);
    } finally {
      setCurrentUser(null);
    }
  };

  /**
   * Buka scanner kamera
   */
  const handleOpenScanner = () => {
    setShowScanner(true);
  };

  /**
   * Tutup scanner kamera
   */
  const handleCloseScanner = () => {
    setShowScanner(false);
    if (isScannerRoute()) {
      window.history.pushState({}, '', '/');
    }
  };

  /**
   * Setelah kode barcode berhasil terbaca & terverifikasi oleh scanner
   */
  const handleAsetScanned = (kode) => {
    setShowScanner(false);
    window.history.pushState({}, '', `/aset/${encodeURIComponent(kode)}`);
    setPublicScanKode(kode);
  };

  // 1. Rute Publik: /aset/:kode atau /scan/:kode dirender langsung tanpa autentikasi
  if (publicScanKode) {
    return (
      <>
        <PublicScanPage
          kode={publicScanKode}
          onBackToHome={() => {
            window.history.pushState({}, '', '/');
            setPublicScanKode(null);
          }}
          onOpenScanner={handleOpenScanner}
        />
        <BarcodeScannerModal
          isOpen={showScanner}
          onClose={handleCloseScanner}
          onSelectAset={handleAsetScanned}
        />
      </>
    );
  }

  // 2. Loading State saat inisialisasi / verifikasi sesi awal
  if (checkingSession) {
    return (
      <div className="min-h-screen bg-[#F3F4F6] flex flex-col items-center justify-center p-4">
        <div className="bg-white rounded-3xl p-8 max-w-sm w-full shadow-lg border border-gray-200 text-center space-y-4 animate-fadeIn">
          <div className="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#F05D24] to-[#D84A15] flex items-center justify-center text-white font-extrabold text-xl mx-auto shadow-md">
            BK
          </div>
          <div>
            <h2 className="text-base font-extrabold text-gray-900 tracking-tight">
              PT BANYAN KOALINDO LESTARI
            </h2>
            <p className="text-xs text-[#6B7280] mt-0.5">
              Sistem Informasi Inventaris Sewa Beli
            </p>
          </div>
          <div className="pt-2 flex items-center justify-center space-x-2 text-xs font-semibold text-[#F05D24]">
            <RefreshCw className="w-4 h-4 animate-spin text-[#F05D24]" />
            <span>Memuat Sistem...</span>
          </div>
        </div>
      </div>
    );
  }

  // 3. Proteksi Ketat: Dashboard HANYA ditampilkan jika currentUser diverifikasi melalui GET /api/me
  if (currentUser) {
    return (
      <>
        <Dashboard
          user={currentUser}
          activeMenu={activeMenu}
          onMenuChange={(menu) => setActiveMenu(menu)}
          onLogout={handleLogout}
          onSessionExpired={handleLogout}
          onOpenScanner={handleOpenScanner}
        />
        <BarcodeScannerModal
          isOpen={showScanner}
          onClose={handleCloseScanner}
          onSelectAset={handleAsetScanned}
        />
      </>
    );
  }

  // 4. Default: Jika unauthenticated / 401, tampilkan halaman Login
  return (
    <>
      <LoginPage
        onLoginSuccess={verifySession}
        onOpenScanner={handleOpenScanner}
      />
      <BarcodeScannerModal
        isOpen={showScanner}
        onClose={handleCloseScanner}
        onSelectAset={handleAsetScanned}
      />
    </>
  );
}
