import React, { useState, useEffect, useMemo } from 'react';
import {
  LayoutDashboard,
  Boxes,
  FileText,
  LogOut,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  Building2,
  Menu,
  X,
  AlertTriangle,
  Info,
  ChevronRight,
  FileCheck2,
  ArrowRight,
  Download,
  Clock,
  Camera,
  DollarSign,
  FileSpreadsheet
} from 'lucide-react';
import { getInventarisList, getKontrakList, getDashboardStats, checkHealth } from '../api';
import InventarisPage from './InventarisPage';
import KontrakPage from './KontrakPage';
import LaporanPage from './LaporanPage';
import ErrorBoundary from './ErrorBoundary';

export default function Dashboard({ user, onLogout, onSessionExpired, activeMenu: propActiveMenu, onMenuChange, onOpenScanner }) {
  // Navigation State
  const [activeMenu, setActiveMenu] = useState(() => propActiveMenu || 'dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [noticeModal, setNoticeModal] = useState(null);

  // Card Filter State: 'semua' | 'sewa_aktif' | 'akan_berakhir' | 'sewa_berakhir'
  const [selectedCardFilter, setSelectedCardFilter] = useState('semua');
  const [initialInventarisFilter, setInitialInventarisFilter] = useState('');

  useEffect(() => {
    if (propActiveMenu && propActiveMenu !== activeMenu) {
      setActiveMenu(propActiveMenu);
    }
  }, [propActiveMenu]);

  // Data State
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [backendOnline, setBackendOnline] = useState(null);
  const [inventarisData, setInventarisData] = useState([]);
  const [kontrakData, setKontrakData] = useState([]);
  const [inventarisMeta, setInventarisMeta] = useState({ total: 0 });
  const [kontrakMeta, setKontrakMeta] = useState({ total: 0 });
  const [dashboardStats, setDashboardStats] = useState(null);

  // Error State
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    setErrorMessage('');

    try {
      // 1. Periksa konektivitas backend port 8000
      try {
        await checkHealth();
        setBackendOnline(true);
      } catch (healthErr) {
        if (healthErr.status === 0) {
          setBackendOnline(false);
        }
      }

      // 2. Ambil data asli dari API Laravel (GET /api/inventaris, GET /api/kontrak, & GET /api/dashboard/stats)
      // Seluruh request ini dilindungi oleh middleware 'auth' Laravel di backend
      const [invRes, konRes, statsRes] = await Promise.all([
        getInventarisList({ per_page: 100 }),
        getKontrakList({ per_page: 100 }),
        getDashboardStats().catch(() => null)
      ]);

      if (invRes?.data) {
        setInventarisData(invRes.data);
        setInventarisMeta(invRes.meta || { total: invRes.data.length });
      }

      if (konRes?.data) {
        setKontrakData(konRes.data);
        setKontrakMeta(konRes.meta || { total: konRes.data.length });
      }

      if (statsRes?.data) {
        setDashboardStats(statsRes.data);
      }
    } catch (err) {
      if (err.status === 401) {
        // KEAMANAN KETAT: Jika server mengembalikan HTTP 401 (sesi kedaluwarsa atau tidak sah),
        // pengguna TIDAK boleh dipertahankan di Dashboard. Segera alihkan kembali ke halaman Login!
        if (onSessionExpired) {
          onSessionExpired();
        } else if (onLogout) {
          onLogout();
        }
        return;
      } else if (err.status === 0) {
        setBackendOnline(false);
        setErrorMessage('Tidak dapat terhubung ke server. Silakan pastikan koneksi jaringan aktif.');
      } else {
        setErrorMessage(err.message || 'Gagal memuat data ringkasan dari server.');
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleManualRefresh = () => {
    setRefreshing(true);
    fetchDashboardData();
  };

  const handleMenuClick = (menuId, menuName) => {
    setActiveMenu(menuId);
    setSidebarOpen(false);
    if (menuId === 'dashboard') {
      fetchDashboardData();
    }
    if (onMenuChange) {
      onMenuChange(menuId);
    }
    const targetPath = menuId === 'dashboard' ? '/' : `/${menuId}`;
    if (window.location.pathname !== targetPath) {
      window.history.pushState({}, '', targetPath);
    }
  };

  const handleExecuteLogout = () => {
    if (onLogout) {
      onLogout();
    }
  };

  // Kalkulasi statistik dari data backend MySQL
  const stats = useMemo(() => {
    const totalInv = inventarisMeta.total || inventarisData.length;

    // Nilai agregasi dokumen kontrak (diprioritaskan dari backend GET /api/dashboard/stats)
    const totalKon = dashboardStats?.total_dokumen ?? (kontrakMeta.total || kontrakData.length);

    const kontrakAktif = dashboardStats?.kontrak_aktif ?? kontrakData.filter((k) => {
      const sk = String(k.status_kontrak || '').toUpperCase();
      return sk === 'AKTIF' || sk === 'SEDANG BERJALAN';
    }).length;

    const kontrakAkanBerakhir = dashboardStats?.kontrak_akan_berakhir ?? kontrakData.filter((k) => {
      const sk = String(k.status_kontrak || '').toUpperCase();
      return sk === 'AKAN BERAKHIR';
    }).length;

    const kontrakBerakhir = dashboardStats?.kontrak_berakhir ?? kontrakData.filter((k) => {
      const sk = String(k.status_kontrak || '').toUpperCase();
      return sk === 'BERAKHIR' || sk === 'HABIS';
    }).length;

    const totalBiaya = dashboardStats?.akumulasi_komitmen ?? kontrakData.reduce((acc, curr) => acc + (Number(curr.total_biaya) || 0), 0);

    // Filter unit inventaris berdasarkan status kontrak sebenarnya:
    // 1. Sewa Aktif: Unit inventaris dengan kontrak aktif (sisa kontrak > 30 hari)
    const sewaAktif = inventarisData.filter((item) => {
      if (!item.kontrak_id) return false;
      const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
      return sk === 'AKTIF' || sk === 'SEDANG BERJALAN';
    }).length;

    // 2. Akan Berakhir: Unit inventaris dengan sisa kontrak 1–30 hari (maksimal 30 hari)
    const akanBerakhir = inventarisData.filter((item) => {
      if (!item.kontrak_id) return false;
      const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
      return sk === 'AKAN BERAKHIR';
    }).length;

    // 3. Sewa Berakhir: Unit inventaris dengan kontrak yang tanggal akhirnya sudah lewat (beralih menjadi Milik Perusahaan)
    const sewaBerakhir = inventarisData.filter((item) => {
      if (!item.kontrak_id) return false;
      const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
      return sk === 'BERAKHIR' || sk === 'HABIS';
    }).length;

    // 4. Milik Mandiri: Unit inventaris milik mandiri perusahaan (tanpa kontrak sewa sejak awal)
    const milikMandiri = inventarisData.filter((item) => !item.kontrak_id).length;

    // Filter aset sewa beli vs aset milik perusahaan keseluruhan
    const asetSewa = inventarisData.filter(
      (item) => item.kontrak_id !== null || (item.status_kepemilikan && item.status_kepemilikan.toLowerCase().includes('sewa'))
    ).length;
    const asetPerusahaan = totalInv - asetSewa;

    // Filter status fisik aset
    const statusAda = inventarisData.filter((i) => i.status_aset === 'Ada').length;
    const statusRusak = inventarisData.filter((i) => i.status_aset === 'Rusak').length;
    const statusHilang = inventarisData.filter((i) => i.status_aset === 'Hilang' || i.status_aset === 'Tidak Ditemukan').length;

    return {
      totalInv,
      totalKon,
      kontrakAktif,
      kontrakAkanBerakhir,
      kontrakBerakhir,
      sewaAktif,
      akanBerakhir,
      sewaBerakhir,
      milikMandiri,
      asetSewa,
      asetPerusahaan,
      statusAda,
      statusRusak,
      statusHilang,
      totalBiaya
    };
  }, [inventarisData, inventarisMeta, kontrakData, kontrakMeta, dashboardStats]);

  // Daftar inventaris terfilter berdasarkan kartu yang diklik pengguna
  const filteredInventarisList = useMemo(() => {
    let list = [...inventarisData];
    if (selectedCardFilter === 'sewa_aktif') {
      list = list.filter((item) => {
        if (!item.kontrak_id) return false;
        const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
        return sk === 'AKTIF' || sk === 'SEDANG BERJALAN';
      });
    } else if (selectedCardFilter === 'akan_berakhir') {
      list = list.filter((item) => {
        if (!item.kontrak_id) return false;
        const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
        return sk === 'AKAN BERAKHIR';
      });
      // Aturan Target 4: Urutkan inventaris berdasarkan sisa hari kontrak dari yang paling sedikit ke yang paling banyak
      list.sort((a, b) => {
        const daysA = a.kontrak?.sisa_hari !== undefined && a.kontrak?.sisa_hari !== null ? Number(a.kontrak.sisa_hari) : 9999;
        const daysB = b.kontrak?.sisa_hari !== undefined && b.kontrak?.sisa_hari !== null ? Number(b.kontrak.sisa_hari) : 9999;
        return daysA - daysB;
      });
    } else if (selectedCardFilter === 'sewa_berakhir') {
      list = list.filter((item) => {
        if (!item.kontrak_id) return false;
        const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
        return sk === 'BERAKHIR' || sk === 'HABIS';
      });
    }
    return list;
  }, [inventarisData, selectedCardFilter]);

  // Handler Klik Kartu Statistik Dashboard
  const handleCardClick = (category) => {
    if (selectedCardFilter === category && category !== 'semua') {
      setSelectedCardFilter('semua');
    } else {
      setSelectedCardFilter(category);
    }
  };

  // Handler Buka Kelola Inventaris dengan filter yang bersangkutan
  const handleOpenInventarisWithFilter = (category) => {
    let statusFilter = '';
    if (category === 'sewa_aktif') statusFilter = 'AKTIF';
    else if (category === 'akan_berakhir') statusFilter = 'AKAN BERAKHIR';
    else if (category === 'sewa_berakhir') statusFilter = 'BERAKHIR';

    setInitialInventarisFilter(statusFilter);
    handleMenuClick('inventaris', 'Inventaris');
  };

  // Format Helper
  const formatRupiah = (val) => {
    if (!val || isNaN(val)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(val);
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
      const d = new Date(dateStr);
      return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      }).format(d);
    } catch {
      return dateStr;
    }
  };

  return (
    <div className="min-h-screen bg-[#F3F4F6] text-[#1F2937] flex flex-col font-sans antialiased">
      {/* Modal Pemberitahuan Batasan Langkah */}
      {noticeModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fadeIn">
          <div className="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
            <div className="w-12 h-12 rounded-xl bg-[#F05D24]/10 text-[#F05D24] flex items-center justify-center">
              <Info className="w-6 h-6" />
            </div>
            <div>
              <h3 className="text-lg font-bold text-gray-900">{noticeModal.title}</h3>
              <p className="text-sm text-[#6B7280] mt-1 leading-relaxed">{noticeModal.message}</p>
            </div>
            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={() => setNoticeModal(null)}
                className="px-4 py-2 rounded-xl bg-[#F05D24] hover:bg-[#D84A15] text-white font-semibold text-sm transition-colors"
              >
                Saya Mengerti
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Main Container Layout */}
      <div className="flex-1 flex overflow-hidden">
        {/* SIDEBAR DESKTOP & MOBILE DRAWER */}
        {sidebarOpen && (
          <div
            className="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}

        <aside
          id="admin-sidebar"
          className={`fixed lg:static top-0 bottom-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col justify-between transition-transform duration-300 ease-in-out ${
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
          }`}
        >
          {/* Sidebar Top: Logo & Company */}
          <div>
            <div className="h-16 px-5 border-b border-gray-200/80 flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#176B7A] to-[#F15A24] flex items-center justify-center text-white font-extrabold text-sm shadow-xs flex-shrink-0">
                  BKL
                </div>
                <div>
                  <h1 className="font-extrabold text-gray-900 text-xs sm:text-[13px] tracking-tight leading-none">
                    PT BANYAN KOALINDO LESTARI
                  </h1>
                  <span className="text-[11px] font-semibold text-[#176B7A]">
                    Inventaris Sewa Beli
                  </span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setSidebarOpen(false)}
                className="lg:hidden p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Sidebar Navigation Menu */}
            <div className="px-3 py-4 space-y-1">
              <div className="px-3 pb-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase">
                Menu Utama
              </div>

              {/* Menu 1: Dashboard */}
              <button
                type="button"
                id="menu-dashboard"
                onClick={() => handleMenuClick('dashboard', 'Dashboard')}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all ${
                  activeMenu === 'dashboard'
                    ? 'bg-[#176B7A] text-white shadow-xs'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center space-x-3">
                  <LayoutDashboard className="w-4 h-4 flex-shrink-0" />
                  <span>Dashboard</span>
                </div>
                {activeMenu === 'dashboard' && (
                  <span className="w-1.5 h-1.5 rounded-full bg-white" />
                )}
              </button>

              {/* Menu 2: Data Inventaris */}
              <button
                type="button"
                id="menu-inventaris"
                onClick={() => handleMenuClick('inventaris', 'Inventaris')}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all ${
                  activeMenu === 'inventaris'
                    ? 'bg-[#176B7A] text-white shadow-xs'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center space-x-3">
                  <Boxes className="w-4 h-4 flex-shrink-0" />
                  <span>Data Inventaris</span>
                </div>
                {activeMenu === 'inventaris' && (
                  <span className="w-1.5 h-1.5 rounded-full bg-white" />
                )}
              </button>

              {/* Menu 3: Data Kontrak */}
              <button
                type="button"
                id="menu-kontrak"
                onClick={() => handleMenuClick('kontrak', 'Kontrak')}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all ${
                  activeMenu === 'kontrak'
                    ? 'bg-[#176B7A] text-white shadow-xs'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center space-x-3">
                  <FileText className="w-4 h-4 flex-shrink-0" />
                  <span>Data Kontrak</span>
                </div>
                {activeMenu === 'kontrak' && (
                  <span className="w-1.5 h-1.5 rounded-full bg-white" />
                )}
              </button>

              {/* Menu 4: Laporan & Ekspor */}
              <button
                type="button"
                id="menu-laporan"
                onClick={() => handleMenuClick('laporan', 'Laporan')}
                className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all ${
                  activeMenu === 'laporan'
                    ? 'bg-[#176B7A] text-white shadow-xs'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                }`}
              >
                <div className="flex items-center space-x-3">
                  <FileSpreadsheet className="w-4 h-4 flex-shrink-0" />
                  <span>Laporan & Ekspor</span>
                </div>
                {activeMenu === 'laporan' && (
                  <span className="w-1.5 h-1.5 rounded-full bg-white" />
                )}
              </button>

              {/* Tombol Pindai Barcode di Sidebar */}
              <div className="pt-2">
                <button
                  type="button"
                  id="sidebar-btn-pindai-barcode"
                  onClick={() => {
                    setSidebarOpen(false);
                    if (onOpenScanner) onOpenScanner();
                  }}
                  className="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs text-gray-700 hover:bg-[#F15A24]/10 hover:text-[#F15A24] transition-all border border-gray-200/80 bg-white shadow-2xs"
                  title="Pindai label QR Code inventaris"
                >
                  <div className="flex items-center space-x-3">
                    <Camera className="w-4 h-4 text-[#F15A24] flex-shrink-0" />
                    <span>Pindai QR Code</span>
                  </div>
                  <span className="text-[10px] font-bold text-[#F15A24] bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200">
                    Kamera
                  </span>
                </button>
              </div>
            </div>
          </div>

          {/* Sidebar Bottom: User & Logout */}
          <div className="p-3 border-t border-gray-200 space-y-2 bg-gray-50/50">
            {/* User Profile Card */}
            <div className="p-2.5 rounded-xl bg-white border border-gray-200 shadow-sm flex items-center space-x-3">
              <div className="w-9 h-9 rounded-lg bg-[#176B7A]/10 border border-[#176B7A]/20 flex items-center justify-center text-[#176B7A] font-bold text-xs flex-shrink-0">
                {user?.role ? (user.role === 'IT' ? 'IT' : 'FN') : (user?.name ? user.name.slice(0, 2).toUpperCase() : 'AD')}
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center space-x-1.5">
                  <p className="text-xs font-bold text-gray-900 truncate">
                    {user?.name || 'Administrator'}
                  </p>
                  {user?.role && (
                    <span className="px-1.5 py-0.5 text-[9px] font-bold rounded bg-[#176B7A]/10 text-[#176B7A]">
                      {user.role}
                    </span>
                  )}
                </div>
                <p className="text-[11px] text-[#6B7280] truncate">
                  {user?.email || 'it@banyankoalindo.co.id'}
                </p>
              </div>
            </div>

            {/* Logout Button */}
            <button
              type="button"
              id="sidebar-btn-logout"
              onClick={handleExecuteLogout}
              className="w-full flex items-center justify-center space-x-2 px-3 py-2 rounded-xl text-xs font-semibold text-[#F05D24] hover:bg-orange-50 border border-orange-200/80 transition-colors"
            >
              <LogOut className="w-4 h-4 text-[#F05D24]" />
              <span>Keluar (Logout)</span>
            </button>
          </div>
        </aside>

        {/* MAIN CONTENT AREA */}
        <div className="flex-1 flex flex-col min-w-0 overflow-y-auto">
          {/* TOP HEADER */}
          <header className="h-16 bg-white border-b border-gray-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            {/* Header Left: Toggle & Title */}
            <div className="flex items-center space-x-3">
              <button
                type="button"
                onClick={() => setSidebarOpen(true)}
                className="lg:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100"
                aria-label="Buka Menu"
              >
                <Menu className="w-5 h-5" />
              </button>
              <div>
                <h2 className="text-sm sm:text-base font-extrabold text-gray-900 tracking-tight leading-tight">
                  {activeMenu === 'inventaris'
                    ? 'Manajemen Data Inventaris'
                    : activeMenu === 'kontrak'
                    ? 'Manajemen Kontrak Sewa Beli'
                    : activeMenu === 'laporan'
                    ? 'Laporan & Ekspor Data'
                    : 'Dashboard Ringkasan Inventaris & Kontrak'}
                </h2>
                <p className="text-[11px] text-[#6B7280] hidden sm:block">
                  Sistem Informasi Inventaris Sewa Beli — PT Banyan Koalindo Lestari
                </p>
              </div>
            </div>

            {/* Header Right: Controls & User Info */}
            <div className="flex items-center space-x-2 sm:space-x-3">
              {/* Tombol Pindai QR Code (Kamera) */}
              <button
                type="button"
                id="header-btn-pindai-barcode"
                onClick={onOpenScanner}
                className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#F15A24] hover:bg-[#D84A15] text-white font-bold text-xs shadow-xs transition-all"
                title="Pindai label QR Code inventaris menggunakan kamera"
              >
                <Camera className="w-3.5 h-3.5 text-white" />
                <span className="hidden sm:inline">Pindai QR Code</span>
              </button>

              {/* Actions khusus Dashboard view */}
              {activeMenu === 'dashboard' && (
                <>
                  {/* Refresh Button */}
                  <button
                    type="button"
                    id="btn-refresh-dashboard"
                    onClick={handleManualRefresh}
                    disabled={loading || refreshing}
                    className="p-2 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-200 transition-colors disabled:opacity-50"
                    title="Muat ulang data dari API"
                  >
                    <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
                  </button>

                  {/* Export Dashboard PDF */}
                  <a
                    href="/api/dashboard/export/pdf"
                    id="btn-dashboard-export-pdf"
                    className="hidden sm:inline-flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-teal-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-xs transition-colors"
                    title="Unduh Laporan Ringkasan Dashboard (PDF)"
                  >
                    <Download className="w-3.5 h-3.5 text-[#176B7A]" />
                    <span className="hidden xl:inline">PDF</span>
                  </a>

                  {/* Export Dashboard Excel */}
                  <a
                    href="/api/dashboard/export/excel"
                    id="btn-dashboard-export-excel"
                    className="hidden sm:inline-flex items-center space-x-1.5 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-teal-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-xs transition-colors"
                    title="Unduh Laporan Ringkasan Dashboard (Excel XLSX)"
                  >
                    <Download className="w-3.5 h-3.5 text-[#176B7A]" />
                    <span className="hidden xl:inline">Excel</span>
                  </a>
                </>
              )}

              {/* User Chip Header */}
              <div className="flex items-center space-x-2 pl-2 border-l border-gray-200">
                <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-[#176B7A] to-[#F15A24] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                  {user?.role ? (user.role === 'IT' ? 'IT' : 'FN') : (user?.name ? user.name.slice(0, 2).toUpperCase() : 'AD')}
                </div>
                <div className="hidden sm:block text-left">
                  <div className="text-xs font-bold text-gray-900 leading-tight">
                    {user?.name || 'Administrator'}
                  </div>
                  <div className="text-[10px] text-gray-500 font-medium">
                    {user?.role ? `Divisi ${user.role}` : 'Administrator'}
                  </div>
                </div>
              </div>

              {/* Logout Button Header */}
              <button
                type="button"
                id="header-btn-logout"
                onClick={handleExecuteLogout}
                className="hidden sm:flex items-center space-x-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-gray-600 hover:text-[#F15A24] hover:bg-orange-50 transition-colors"
                title="Keluar dari sistem"
              >
                <LogOut className="w-4 h-4" />
                <span className="hidden md:inline">Keluar</span>
              </button>
            </div>
          </header>

          {/* DASHBOARD BODY */}
          <main className="p-4 sm:p-6 lg:p-8 space-y-6">
            {activeMenu === 'inventaris' ? (
              <ErrorBoundary title="Terjadi Kendala pada Halaman Inventaris">
                <InventarisPage
                  onSessionExpired={onSessionExpired || onLogout}
                  initialFilterStatusKontrak={initialInventarisFilter}
                  onOpenScanner={onOpenScanner}
                />
              </ErrorBoundary>
            ) : activeMenu === 'kontrak' ? (
              <ErrorBoundary title="Terjadi Kendala pada Halaman Kontrak">
                <KontrakPage
                  onSessionExpired={onSessionExpired || onLogout}
                  onDataChanged={fetchDashboardData}
                />
              </ErrorBoundary>
            ) : activeMenu === 'laporan' ? (
              <ErrorBoundary title="Terjadi Kendala pada Halaman Laporan">
                <LaporanPage
                  onSessionExpired={onSessionExpired || onLogout}
                />
              </ErrorBoundary>
            ) : (
              <>
                {/* Error Message jika terjadi gangguan koneksi */}
                {errorMessage && (
                  <div className="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 flex items-start space-x-3 shadow-xs">
                    <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div className="flex-1 text-xs sm:text-sm">
                      <span className="font-bold">Pemberitahuan Sistem:</span> {errorMessage}
                    </div>
                    <button
                      type="button"
                      onClick={fetchDashboardData}
                      className="text-xs font-bold text-amber-700 hover:underline flex-shrink-0"
                    >
                      Coba Lagi
                    </button>
                  </div>
                )}

            {/* Modern Executive Overview Header */}
            <div className="bg-white rounded-2xl p-6 sm:p-7 border border-gray-200/80 shadow-xs relative overflow-hidden">
              <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="space-y-2 max-w-2xl">
                  <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#176B7A]/10 text-[11px] font-bold text-[#176B7A] border border-[#176B7A]/20">
                    <Building2 className="w-3.5 h-3.5" />
                    <span>PT Banyan Koalindo Lestari</span>
                    <span>•</span>
                    <span>Sistem Informasi Inventaris Sewa Beli</span>
                  </div>
                  <h3 className="text-lg sm:text-xl font-extrabold text-gray-900 tracking-tight">
                    Ringkasan Operasional Inventaris & Kontrak
                  </h3>
                  <p className="text-xs sm:text-sm text-[#6B7280] leading-relaxed">
                    Pantau ketersediaan aset operasional, masa berlaku kontrak sewa beli, estimasi jatuh tempo, dan rincian komitmen finansial secara akurat.
                  </p>
                </div>

                <div className="flex flex-row md:flex-col items-center md:items-end justify-between gap-2 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100">
                  <div className="text-xs text-gray-500 font-medium">Status Basis Data</div>
                  <div className="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                    <span>Terhubung & Aktif</span>
                  </div>
                </div>
              </div>
            </div>

            {/* 4 SUMMARY METRIC CARDS — INTERAKTIF & DIHITUNG DINAMIS DARI DATA KONTRAK MYSQL */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              {/* Card 1: Total Dokumen */}
              <button
                type="button"
                id="card-total-dokumen"
                onClick={() => handleCardClick('semua')}
                className={`bg-white rounded-2xl p-5 border text-left shadow-xs hover:shadow-md transition-all relative overflow-hidden cursor-pointer ${
                  selectedCardFilter === 'semua'
                    ? 'border-[#176B7A] ring-2 ring-[#176B7A]/20 bg-[#176B7A]/5'
                    : 'border-gray-200/80 hover:border-gray-300'
                }`}
                title="Klik untuk menampilkan seluruh unit inventaris terdaftar"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#6B7280]">Total Dokumen</span>
                  <div className="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                    <FileText className="w-5 h-5" />
                  </div>
                </div>

                <div className="mt-4">
                  {loading ? (
                    <div className="h-8 w-20 bg-gray-200 rounded-lg animate-pulse" />
                  ) : (
                    <div className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                      {stats.totalKon} <span className="text-xs font-semibold text-[#6B7280]">Dokumen</span>
                    </div>
                  )}
                </div>

                <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-[#6B7280]">
                  <span>Total Unit Terdaftar</span>
                  <span>•</span>
                  <span className="font-semibold text-gray-800">{stats.totalInv} Aset</span>
                </div>
              </button>

              {/* Card 2: Kontrak Aktif */}
              <button
                type="button"
                id="card-kontrak-aktif"
                onClick={() => handleCardClick('sewa_aktif')}
                className={`bg-white rounded-2xl p-5 border text-left shadow-xs hover:shadow-md transition-all relative overflow-hidden cursor-pointer ${
                  selectedCardFilter === 'sewa_aktif'
                    ? 'border-[#176B7A] ring-2 ring-[#176B7A]/20 bg-[#176B7A]/5'
                    : 'border-gray-200/80 hover:border-gray-300'
                }`}
                title="Klik untuk memfilter unit sewa dengan kontrak aktif"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#6B7280]">Kontrak Aktif</span>
                  <div className="w-10 h-10 rounded-xl bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center">
                    <CheckCircle2 className="w-5 h-5" />
                  </div>
                </div>

                <div className="mt-4">
                  {loading ? (
                    <div className="h-8 w-20 bg-gray-200 rounded-lg animate-pulse" />
                  ) : (
                    <div className="text-2xl sm:text-3xl font-extrabold text-[#176B7A] tracking-tight">
                      {stats.kontrakAktif} <span className="text-xs font-semibold text-[#6B7280]">Aktif</span>
                    </div>
                  )}
                </div>

                <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-[#6B7280]">
                  <span>Masa sewa berjalan</span>
                  <span className="font-semibold text-[#176B7A]">&gt; 30 Hari</span>
                </div>
              </button>

              {/* Card 3: Akan Berakhir */}
              <button
                type="button"
                id="card-akan-berakhir"
                onClick={() => handleCardClick('akan_berakhir')}
                className={`bg-white rounded-2xl p-5 border text-left shadow-xs hover:shadow-md transition-all relative overflow-hidden cursor-pointer ${
                  selectedCardFilter === 'akan_berakhir'
                    ? 'border-[#F15A24] ring-2 ring-[#F15A24]/20 bg-[#F15A24]/5'
                    : 'border-gray-200/80 hover:border-gray-300'
                }`}
                title="Klik untuk memfilter unit dengan sisa kontrak 1-30 hari"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#6B7280]">Akan Berakhir</span>
                  <div className="w-10 h-10 rounded-xl bg-orange-50 text-[#F15A24] flex items-center justify-center">
                    <AlertTriangle className="w-5 h-5" />
                  </div>
                </div>

                <div className="mt-4">
                  {loading ? (
                    <div className="h-8 w-20 bg-gray-200 rounded-lg animate-pulse" />
                  ) : (
                    <div className="text-2xl sm:text-3xl font-extrabold text-[#F15A24] tracking-tight">
                      {stats.kontrakAkanBerakhir} <span className="text-xs font-semibold text-[#6B7280]">Kontrak</span>
                    </div>
                  )}
                </div>

                <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-[#6B7280]">
                  <span>1–30 Hari Jatuh Tempo</span>
                  <span className="font-semibold text-[#F15A24]">Perlu Perhatian</span>
                </div>
              </button>

              {/* Card 4: Akumulasi Komitmen */}
              <button
                type="button"
                id="card-akumulasi-komitmen"
                onClick={() => handleMenuClick('kontrak', 'Kontrak')}
                className="bg-white rounded-2xl p-5 border border-gray-200/80 hover:border-gray-300 text-left shadow-xs hover:shadow-md transition-all relative overflow-hidden cursor-pointer"
                title="Klik untuk melihat rincian manajemen kontrak sewa beli"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-[#6B7280]">Akumulasi Komitmen</span>
                  <div className="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                    <DollarSign className="w-5 h-5" />
                  </div>
                </div>

                <div className="mt-4">
                  {loading ? (
                    <div className="h-8 w-28 bg-gray-200 rounded-lg animate-pulse" />
                  ) : (
                    <div className="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight truncate">
                      {formatRupiah(stats.totalBiaya)}
                    </div>
                  )}
                </div>

                <div className="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-[#6B7280]">
                  <span>Estimasi nilai total</span>
                  <span className="font-semibold text-[#176B7A]">Buka Kontrak &rarr;</span>
                </div>
              </button>
            </div>

            {/* SECONDARY ROW: KONDISI FISIK ASET & INFORMASI KEUANGAN KONTRAK */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Kolom 1 & 2: Distribusi Status Fisik Aset */}
              <div className="lg:col-span-2 bg-white rounded-2xl p-6 border border-gray-200/80 shadow-xs space-y-4">
                <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                  <div>
                    <h4 className="text-sm font-bold text-gray-900">Distribusi Status Fisik Aset</h4>
                    <p className="text-xs text-[#6B7280]">Kondisi aktual seluruh unit inventaris di lapangan</p>
                  </div>
                  <span className="text-xs font-semibold text-[#176B7A] bg-[#176B7A]/10 px-2.5 py-1 rounded-lg">
                    Total {stats.totalInv} Unit
                  </span>
                </div>

                {loading ? (
                  <div className="py-8 text-center space-y-3">
                    <RefreshCw className="w-6 h-6 text-[#176B7A] animate-spin mx-auto" />
                    <p className="text-xs text-[#6B7280]">Memuat distribusi aset...</p>
                  </div>
                ) : stats.totalInv === 0 ? (
                  <div className="py-8 text-center text-xs text-[#6B7280]">
                    Belum ada data inventaris yang tercatat di database.
                  </div>
                ) : (
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {/* Ada / Baik */}
                    <div className="p-4 rounded-xl bg-emerald-50/70 border border-emerald-200/70 space-y-1">
                      <div className="flex items-center justify-between text-xs font-semibold text-emerald-800">
                        <span>Aset Tersedia (Ada)</span>
                        <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                      </div>
                      <div className="text-2xl font-extrabold text-emerald-700">{stats.statusAda}</div>
                      <div className="text-[11px] text-emerald-600">
                        {stats.totalInv ? Math.round((stats.statusAda / stats.totalInv) * 100) : 0}% dari total aset
                      </div>
                    </div>

                    {/* Rusak */}
                    <div className="p-4 rounded-xl bg-amber-50/70 border border-amber-200/70 space-y-1">
                      <div className="flex items-center justify-between text-xs font-semibold text-amber-800">
                        <span>Dalam Perbaikan (Rusak)</span>
                        <AlertTriangle className="w-4 h-4 text-amber-600" />
                      </div>
                      <div className="text-2xl font-extrabold text-amber-700">{stats.statusRusak}</div>
                      <div className="text-[11px] text-amber-600">
                        {stats.totalInv ? Math.round((stats.statusRusak / stats.totalInv) * 100) : 0}% dari total aset
                      </div>
                    </div>

                    {/* Hilang / Tidak Ditemukan */}
                    <div className="p-4 rounded-xl bg-rose-50/70 border border-rose-200/70 space-y-1">
                      <div className="flex items-center justify-between text-xs font-semibold text-rose-800">
                        <span>Hilang / Anomali</span>
                        <AlertCircle className="w-4 h-4 text-rose-600" />
                      </div>
                      <div className="text-2xl font-extrabold text-rose-700">{stats.statusHilang}</div>
                      <div className="text-[11px] text-rose-600">
                        {stats.totalInv ? Math.round((stats.statusHilang / stats.totalInv) * 100) : 0}% dari total aset
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Kolom 3: Ringkasan Nilai Komitmen Kontrak */}
              <div className="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-xs space-y-4 flex flex-col justify-between">
                <div className="space-y-1">
                  <div className="flex items-center justify-between">
                    <h4 className="text-sm font-bold text-gray-900">Nilai Komitmen Sewa</h4>
                    <span className="w-7 h-7 rounded-lg bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center font-bold text-xs">
                      IDR
                    </span>
                  </div>
                  <p className="text-xs text-[#6B7280]">Akumulasi nilai seluruh kontrak aktif</p>
                </div>

                <div className="my-2">
                  {loading ? (
                    <div className="h-8 w-36 bg-gray-200 rounded-lg animate-pulse" />
                  ) : (
                    <div>
                      <div className="text-xl sm:text-2xl font-extrabold text-gray-900">
                        {formatRupiah(stats.totalBiaya)}
                      </div>
                      <p className="text-[11px] text-[#6B7280] mt-1">
                        Berdasarkan {stats.totalKon} dokumen kontrak terdaftar
                      </p>
                    </div>
                  )}
                </div>

                <div className="p-3 rounded-xl bg-[#F8FAFC] border border-gray-200 text-[11px] text-[#6B7280] space-y-1.5">
                  <div className="flex justify-between items-center">
                    <span>Rata-rata Nilai Kontrak:</span>
                    <span className="font-bold text-gray-800">
                      {stats.totalKon ? formatRupiah(Math.round(stats.totalBiaya / stats.totalKon)) : 'Rp 0'}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span>Status Jatuh Tempo:</span>
                    <span className={`font-bold ${stats.akanBerakhir > 0 ? 'text-[#F15A24]' : 'text-[#176B7A]'}`}>
                      {stats.akanBerakhir > 0 ? `${stats.akanBerakhir} Mendekati Jatuh Tempo` : 'Semua Masa Sewa Terkendali'}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* PREVIEW TABEL INVENTARIS — MENAMPILKAN DAFTAR SESUAI KATEGORI KARTU YANG DIKLIK */}
            <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
              <div className="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                  <div className="flex items-center space-x-2">
                    <h4 className="text-sm font-bold text-gray-900">
                      {selectedCardFilter === 'sewa_aktif'
                        ? 'Daftar Inventaris Sewa Aktif'
                        : selectedCardFilter === 'akan_berakhir'
                        ? 'Daftar Inventaris Akan Berakhir (Urut Sisa Hari Terkecil)'
                        : selectedCardFilter === 'sewa_berakhir'
                        ? 'Daftar Inventaris Sewa Berakhir (Milik Perusahaan)'
                        : 'Daftar Seluruh Inventaris (Preview)'}
                    </h4>
                    <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20">
                      {filteredInventarisList.length} Unit
                    </span>
                  </div>
                  <p className="text-xs text-[#6B7280] mt-0.5">
                    {selectedCardFilter === 'akan_berakhir'
                      ? 'Diurutkan berdasarkan sisa hari kontrak dari yang paling sedikit ke yang paling banyak'
                      : selectedCardFilter === 'sewa_aktif'
                      ? 'Unit inventaris dengan masa kontrak berjalan lebih dari 30 hari'
                      : selectedCardFilter === 'sewa_berakhir'
                      ? 'Unit inventaris dengan kontrak telah lewat jatuh tempo dan kepemilikan menjadi milik perusahaan'
                      : 'Menampilkan data inventaris aset operasional perusahaan'}
                  </p>
                </div>

                <div className="flex items-center space-x-2 w-full sm:w-auto justify-end">
                  {selectedCardFilter !== 'semua' && (
                    <button
                      type="button"
                      id="btn-reset-dashboard-filter"
                      onClick={() => setSelectedCardFilter('semua')}
                      className="text-xs font-semibold px-2.5 py-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
                    >
                      Tampilkan Semua
                    </button>
                  )}
                  <button
                    type="button"
                    id="btn-open-kelola-inventaris"
                    onClick={() => handleOpenInventarisWithFilter(selectedCardFilter)}
                    className="text-xs font-bold text-[#176B7A] hover:text-[#0f4e5a] flex items-center space-x-1"
                  >
                    <span>
                      {selectedCardFilter !== 'semua'
                        ? 'Buka di Kelola Inventaris'
                        : 'Buka Kelola Inventaris'}
                    </span>
                    <ChevronRight className="w-4 h-4" />
                  </button>
                </div>
              </div>

              {/* Table Container */}
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
                    <tr>
                      <th className="py-3 px-4">Kode Inventaris</th>
                      <th className="py-3 px-4">Nama Barang & Merek</th>
                      <th className="py-3 px-4">Kategori</th>
                      <th className="py-3 px-4">Status Fisik</th>
                      <th className="py-3 px-4">Kepemilikan</th>
                      <th className="py-3 px-4">Kontrak & Sisa Hari</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {loading ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-gray-500">
                          <RefreshCw className="w-5 h-5 animate-spin mx-auto text-[#176B7A] mb-2" />
                          Memuat data inventaris...
                        </td>
                      </tr>
                    ) : filteredInventarisList.length === 0 ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-gray-500">
                          Tidak ada unit inventaris dalam kategori ini.
                        </td>
                      </tr>
                    ) : (
                      (selectedCardFilter === 'semua'
                        ? filteredInventarisList.slice(0, 10)
                        : filteredInventarisList
                      ).map((item) => (
                        <tr key={item.id} className="hover:bg-gray-50/80 transition-colors">
                          <td className="py-3 px-4 font-mono font-bold text-[#176B7A]">
                            {item.kode_inventaris}
                          </td>
                          <td className="py-3 px-4">
                            <div className="font-bold text-gray-900">{item.nama_barang}</div>
                            <div className="text-[11px] text-[#6B7280]">
                              {item.merek || '-'} {item.tipe_model ? `(${item.tipe_model})` : ''}
                            </div>
                          </td>
                          <td className="py-3 px-4 text-gray-700">{item.kategori || '-'}</td>
                          <td className="py-3 px-4">
                            <span
                              className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                item.status_aset === 'Ada'
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                  : item.status_aset === 'Rusak'
                                  ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                  : 'bg-rose-50 text-rose-700 border border-rose-200'
                              }`}
                            >
                              {item.status_aset}
                            </span>
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`px-2 py-0.5 rounded-md text-[10px] font-semibold ${
                                item.status_kepemilikan === 'SEWA' || item.status_kepemilikan === 'Sewa'
                                  ? 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20'
                                  : 'bg-slate-100 text-slate-700 border border-slate-200'
                              }`}
                            >
                              {item.status_kepemilikan || (item.kontrak_id ? 'SEWA' : 'MILIK PERUSAHAAN')}
                            </span>
                          </td>
                          <td className="py-3 px-4 text-gray-600">
                            {item.kontrak ? (
                              <div className="flex flex-col space-y-0.5">
                                <span className="font-mono font-semibold text-gray-900 text-[11px]">
                                  {item.kontrak.kode_kontrak}
                                </span>
                                <div>
                                  <span
                                    className={`inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold ${
                                      item.kontrak.sisa_hari !== null && item.kontrak.sisa_hari <= 30 && item.kontrak.sisa_hari >= 0
                                        ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                                        : item.kontrak.sisa_hari !== null && item.kontrak.sisa_hari < 0
                                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                    }`}
                                  >
                                    {item.kontrak.sisa_hari !== null
                                      ? item.kontrak.sisa_hari < 0
                                        ? `Habis (${Math.abs(item.kontrak.sisa_hari)} hr lalu)`
                                        : `${item.kontrak.sisa_hari} hari lagi`
                                      : item.status_kontrak}
                                  </span>
                                </div>
                              </div>
                            ) : (
                              <span className="text-gray-400 italic">Milik Mandiri</span>
                            )}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            {/* PREVIEW TABEL 2: 5 KONTRAK SEWA BELI TERBARU (READ-ONLY) */}
            <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
              <div className="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                  <h4 className="text-sm font-bold text-gray-900">Status Kontrak Sewa Beli Terkini</h4>
                  <p className="text-xs text-[#6B7280]">
                    Menampilkan 5 kontrak dengan tanggal jatuh tempo dan vendor terkait
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => handleMenuClick('kontrak', 'Kontrak')}
                  className="text-xs font-bold text-[#176B7A] hover:text-[#0f4e5a] flex items-center space-x-1"
                >
                  <span>Buka Kelola Kontrak</span>
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>

              {/* Table Container */}
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
                    <tr>
                      <th className="py-3 px-4">Kode Kontrak</th>
                      <th className="py-3 px-4">Vendor</th>
                      <th className="py-3 px-4">Masa Berlaku</th>
                      <th className="py-3 px-4">Sisa Hari</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4">Total Nilai</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100">
                    {loading ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-gray-500">
                          <RefreshCw className="w-5 h-5 animate-spin mx-auto text-[#176B7A] mb-2" />
                          Memuat data kontrak...
                        </td>
                      </tr>
                    ) : kontrakData.length === 0 ? (
                      <tr>
                        <td colSpan="6" className="py-8 text-center text-gray-500">
                          Belum ada data kontrak terdaftar di database.
                        </td>
                      </tr>
                    ) : (
                      kontrakData.slice(0, 5).map((k) => (
                        <tr key={k.id} className="hover:bg-gray-50/80 transition-colors">
                          <td className="py-3 px-4 font-mono font-bold text-[#176B7A]">
                            {k.kode_kontrak}
                          </td>
                          <td className="py-3 px-4 font-semibold text-gray-900">{k.vendor}</td>
                          <td className="py-3 px-4 text-[#6B7280]">
                            {formatDate(k.tanggal_mulai)} s/d {formatDate(k.tanggal_berakhir)}
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                k.sisa_hari === null
                                  ? 'bg-gray-100 text-gray-600'
                                  : k.sisa_hari > 30
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                  : k.sisa_hari >= 0
                                  ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                                  : 'bg-rose-50 text-rose-700 border border-rose-200'
                              }`}
                            >
                              {k.sisa_hari !== null
                                ? k.sisa_hari < 0
                                  ? `Habis (${Math.abs(k.sisa_hari)} hr lalu)`
                                  : `${k.sisa_hari} hari`
                                : '-'}
                            </span>
                          </td>
                          <td className="py-3 px-4">
                            <span
                              className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${
                                String(k.status_kontrak || '').toUpperCase() === 'AKTIF' || String(k.status_kontrak || '').toUpperCase() === 'SEDANG BERJALAN'
                                  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                  : String(k.status_kontrak || '').toUpperCase() === 'AKAN BERAKHIR'
                                  ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                                  : 'bg-rose-50 text-rose-700 border border-rose-200'
                              }`}
                            >
                              {k.status_kontrak}
                            </span>
                          </td>
                          <td className="py-3 px-4 font-semibold text-gray-800">
                            {formatRupiah(k.total_biaya || k.biaya_sewa)}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
            </>
          )}
          </main>

          {/* DASHBOARD FOOTER */}
          <footer className="mt-auto bg-white border-t border-gray-200/80 py-4 px-6 text-xs text-[#6B7280]">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-2">
              <div>
                &copy; 2026 <strong className="text-gray-900">PT Banyan Koalindo Lestari</strong>. Seluruh hak cipta dilindungi.
              </div>
              <div className="flex items-center space-x-3 text-[11px]">
                <span className="text-[#176B7A] font-medium">Sistem Informasi Inventaris Sewa Beli</span>
                <span>•</span>
                <span className="text-gray-500">Panel Operasional Perusahaan</span>
              </div>
            </div>
          </footer>
        </div>
      </div>
    </div>
  );
}
