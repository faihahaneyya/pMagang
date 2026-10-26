import React, { useState, useEffect, useMemo } from 'react';
import {
  FileSpreadsheet,
  FileText,
  Boxes,
  Download,
  Printer,
  Search,
  Filter,
  RefreshCw,
  Calendar,
  Building2,
  DollarSign,
  Clock,
  CheckCircle2,
  AlertTriangle,
  AlertCircle,
  TrendingUp,
  BarChart3
} from 'lucide-react';
import { getInventarisList, getKontrakList, getDashboardStats } from '../api';

const formatRupiah = (val) => {
  const num = Number(val) || 0;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(d);
  } catch {
    return dateStr;
  }
};

export default function LaporanPage({ onSessionExpired }) {
  // Tab Laporan: 'inventaris' | 'kontrak' | 'ringkasan'
  const [activeTab, setActiveTab] = useState('inventaris');

  // Data State
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [inventarisData, setInventarisData] = useState([]);
  const [kontrakData, setKontrakData] = useState([]);
  const [dashboardStats, setDashboardStats] = useState(null);

  // Filters State
  const [search, setSearch] = useState('');
  const [filterStatusAset, setFilterStatusAset] = useState('');
  const [filterStatusKontrak, setFilterStatusKontrak] = useState('');
  const [filterKategori, setFilterKategori] = useState('');
  const [filterVendor, setFilterVendor] = useState('');

  useEffect(() => {
    loadLaporanData();
  }, []);

  const loadLaporanData = async () => {
    setLoading(true);
    try {
      const [invRes, konRes, statsRes] = await Promise.all([
        getInventarisList({ per_page: 200 }),
        getKontrakList({ per_page: 200 }),
        getDashboardStats().catch(() => null)
      ]);

      if (invRes?.data) setInventarisData(invRes.data);
      if (konRes?.data) setKontrakData(konRes.data);
      if (statsRes?.data) setDashboardStats(statsRes.data);
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = () => {
    setRefreshing(true);
    loadLaporanData();
  };

  const handleResetFilter = () => {
    setSearch('');
    setFilterStatusAset('');
    setFilterStatusKontrak('');
    setFilterKategori('');
    setFilterVendor('');
  };

  // Kategori unik dari data inventaris
  const kategoriOptions = useMemo(() => {
    const set = new Set();
    inventarisData.forEach((i) => {
      if (i.kategori) set.add(i.kategori);
    });
    return Array.from(set).sort();
  }, [inventarisData]);

  // Vendor unik dari data kontrak
  const vendorOptions = useMemo(() => {
    const set = new Set();
    kontrakData.forEach((k) => {
      if (k.vendor) set.add(k.vendor);
    });
    return Array.from(set).sort();
  }, [kontrakData]);

  // Filtered Inventaris
  const filteredInventaris = useMemo(() => {
    return inventarisData.filter((item) => {
      if (search) {
        const q = search.toLowerCase();
        const matchKode = (item.kode_inventaris || '').toLowerCase().includes(q);
        const matchNama = (item.nama_barang || '').toLowerCase().includes(q);
        const matchVendor = (item.vendor || '').toLowerCase().includes(q);
        const matchLokasi = (item.lokasi || '').toLowerCase().includes(q);
        if (!matchKode && !matchNama && !matchVendor && !matchLokasi) return false;
      }
      if (filterStatusAset && item.status_aset !== filterStatusAset) return false;
      if (filterKategori && item.kategori !== filterKategori) return false;
      if (filterStatusKontrak) {
        const sk = String(item.status_kontrak || item.kontrak?.status_kontrak || '').toUpperCase();
        if (filterStatusKontrak === 'AKTIF' && sk !== 'AKTIF' && sk !== 'SEDANG BERJALAN') return false;
        if (filterStatusKontrak === 'AKAN BERAKHIR' && sk !== 'AKAN BERAKHIR') return false;
        if (filterStatusKontrak === 'BERAKHIR' && sk !== 'BERAKHIR' && sk !== 'HABIS') return false;
      }
      return true;
    });
  }, [inventarisData, search, filterStatusAset, filterKategori, filterStatusKontrak]);

  // Filtered Kontrak
  const filteredKontrak = useMemo(() => {
    return kontrakData.filter((item) => {
      if (search) {
        const q = search.toLowerCase();
        const matchKode = (item.kode_kontrak || '').toLowerCase().includes(q);
        const matchVendor = (item.vendor || '').toLowerCase().includes(q);
        if (!matchKode && !matchVendor) return false;
      }
      if (filterVendor && item.vendor !== filterVendor) return false;
      if (filterStatusKontrak) {
        const sk = String(item.status_kontrak || '').toUpperCase();
        if (filterStatusKontrak === 'AKTIF' && sk !== 'AKTIF' && sk !== 'SEDANG BERJALAN') return false;
        if (filterStatusKontrak === 'AKAN BERAKHIR' && sk !== 'AKAN BERAKHIR') return false;
        if (filterStatusKontrak === 'BERAKHIR' && sk !== 'BERAKHIR' && sk !== 'HABIS') return false;
      }
      return true;
    });
  }, [kontrakData, search, filterVendor, filterStatusKontrak]);

  // Export URLs
  const inventarisPdfUrl = `/api/inventaris/export/pdf?${new URLSearchParams({
    ...(search ? { search } : {}),
    ...(filterStatusAset ? { status_aset: filterStatusAset } : {}),
    ...(filterStatusKontrak ? { status_kontrak: filterStatusKontrak } : {}),
    ...(filterKategori ? { kategori: filterKategori } : {}),
  }).toString()}`;

  const inventarisExcelUrl = `/api/inventaris/export/excel?${new URLSearchParams({
    ...(search ? { search } : {}),
    ...(filterStatusAset ? { status_aset: filterStatusAset } : {}),
    ...(filterStatusKontrak ? { status_kontrak: filterStatusKontrak } : {}),
    ...(filterKategori ? { kategori: filterKategori } : {}),
  }).toString()}`;

  const kontrakPdfUrl = `/api/kontrak/export/pdf?${new URLSearchParams({
    ...(search ? { search } : {}),
    ...(filterStatusKontrak ? { status: filterStatusKontrak } : {}),
    ...(filterVendor ? { vendor: filterVendor } : {}),
  }).toString()}`;

  const kontrakExcelUrl = `/api/kontrak/export/excel?${new URLSearchParams({
    ...(search ? { search } : {}),
    ...(filterStatusKontrak ? { status: filterStatusKontrak } : {}),
    ...(filterVendor ? { vendor: filterVendor } : {}),
  }).toString()}`;

  return (
    <div className="space-y-6">
      {/* 1. HEADER HALAMAN LAPORAN */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
          <h2 className="text-base sm:text-lg font-extrabold text-gray-900 tracking-tight flex items-center space-x-2">
            <FileSpreadsheet className="w-5 h-5 text-[#176B7A]" />
            <span>Pusat Laporan & Ekspor Data</span>
          </h2>
          <p className="text-xs text-[#6B7280] mt-0.5">
            Unduh laporan berkala aset inventaris, ringkasan kontrak sewa beli, dan komitmen finansial resmi
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
          <button
            type="button"
            id="btn-refresh-laporan"
            onClick={handleRefresh}
            disabled={loading || refreshing}
            className="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50"
            title="Muat Ulang Data Laporan"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
          </button>

          <button
            type="button"
            onClick={() => window.print()}
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs shadow-xs transition-colors"
            title="Cetak Halaman Laporan"
          >
            <Printer className="w-4 h-4 text-gray-600" />
            <span className="hidden sm:inline">Cetak Halaman</span>
          </button>

          {activeTab === 'inventaris' && (
            <>
              <a
                href={inventarisPdfUrl}
                id="btn-export-laporan-inv-pdf"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#135460] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Laporan Inventaris format PDF"
              >
                <Download className="w-4 h-4" />
                <span>Export PDF</span>
              </a>
              <a
                href={inventarisExcelUrl}
                id="btn-export-laporan-inv-excel"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#D84A15] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Laporan Inventaris format Excel"
              >
                <Download className="w-4 h-4" />
                <span>Export Excel</span>
              </a>
            </>
          )}

          {activeTab === 'kontrak' && (
            <>
              <a
                href={kontrakPdfUrl}
                id="btn-export-laporan-kon-pdf"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#135460] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Laporan Kontrak format PDF"
              >
                <Download className="w-4 h-4" />
                <span>Export PDF</span>
              </a>
              <a
                href={kontrakExcelUrl}
                id="btn-export-laporan-kon-excel"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#D84A15] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Laporan Kontrak format Excel"
              >
                <Download className="w-4 h-4" />
                <span>Export Excel</span>
              </a>
            </>
          )}

          {activeTab === 'ringkasan' && (
            <>
              <a
                href="/api/dashboard/export/pdf"
                id="btn-export-laporan-ringkasan-pdf"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#135460] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Ringkasan Dashboard PDF"
              >
                <Download className="w-4 h-4" />
                <span>Export PDF</span>
              </a>
              <a
                href="/api/dashboard/export/excel"
                id="btn-export-laporan-ringkasan-excel"
                className="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#D84A15] text-white font-semibold text-xs shadow-xs transition-colors"
                title="Unduh Ringkasan Dashboard Excel"
              >
                <Download className="w-4 h-4" />
                <span>Export Excel</span>
              </a>
            </>
          )}
        </div>
      </div>

      {/* 2. TAB NAVIGASI LAPORAN */}
      <div className="flex border-b border-gray-200 gap-1 bg-white p-1 rounded-xl shadow-xs">
        <button
          type="button"
          onClick={() => setActiveTab('inventaris')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${
            activeTab === 'inventaris'
              ? 'bg-[#176B7A] text-white shadow-xs'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
          }`}
        >
          <Boxes className="w-4 h-4" />
          <span>Laporan Aset Inventaris</span>
          <span className={`px-1.5 py-0.5 rounded-full text-[10px] ${activeTab === 'inventaris' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'}`}>
            {inventarisData.length}
          </span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('kontrak')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${
            activeTab === 'kontrak'
              ? 'bg-[#176B7A] text-white shadow-xs'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
          }`}
        >
          <FileText className="w-4 h-4" />
          <span>Laporan Kontrak Sewa Beli</span>
          <span className={`px-1.5 py-0.5 rounded-full text-[10px] ${activeTab === 'kontrak' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'}`}>
            {kontrakData.length}
          </span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab('ringkasan')}
          className={`flex items-center space-x-2 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${
            activeTab === 'ringkasan'
              ? 'bg-[#176B7A] text-white shadow-xs'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
          }`}
        >
          <BarChart3 className="w-4 h-4" />
          <span>Ringkasan Eksekutif</span>
        </button>
      </div>

      {/* 3. FILTER BAR */}
      <div className="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
        <div className="flex flex-col md:flex-row items-stretch md:items-center gap-3">
          {/* Input Pencarian */}
          <div className="relative flex-1">
            <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder={activeTab === 'inventaris' ? 'Cari kode inventaris, nama barang, lokasi...' : 'Cari kode kontrak, nama vendor...'}
              className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#176B7A]/30 focus:border-[#176B7A]"
            />
          </div>

          {/* Filter Status Kontrak */}
          <select
            value={filterStatusKontrak}
            onChange={(e) => setFilterStatusKontrak(e.target.value)}
            className="px-3 py-2 text-xs rounded-xl border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#176B7A]/30"
          >
            <option value="">Semua Status Kontrak</option>
            <option value="AKTIF">Kontrak Aktif</option>
            <option value="AKAN BERAKHIR">Akan Berakhir (≤30 Hari)</option>
            <option value="BERAKHIR">Selesai / Berakhir</option>
          </select>

          {/* Filter Khusus Inventaris */}
          {activeTab === 'inventaris' && (
            <>
              <select
                value={filterStatusAset}
                onChange={(e) => setFilterStatusAset(e.target.value)}
                className="px-3 py-2 text-xs rounded-xl border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#176B7A]/30"
              >
                <option value="">Semua Status Fisik</option>
                <option value="Ada">Tersedia (Ada)</option>
                <option value="Rusak">Perbaikan (Rusak)</option>
                <option value="Hilang">Hilang</option>
              </select>

              <select
                value={filterKategori}
                onChange={(e) => setFilterKategori(e.target.value)}
                className="px-3 py-2 text-xs rounded-xl border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#176B7A]/30"
              >
                <option value="">Semua Kategori</option>
                {kategoriOptions.map((k) => (
                  <option key={k} value={k}>{k}</option>
                ))}
              </select>
            </>
          )}

          {/* Filter Khusus Kontrak */}
          {activeTab === 'kontrak' && (
            <select
              value={filterVendor}
              onChange={(e) => setFilterVendor(e.target.value)}
              className="px-3 py-2 text-xs rounded-xl border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#176B7A]/30"
            >
              <option value="">Semua Vendor</option>
              {vendorOptions.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          )}

          {(search || filterStatusAset || filterStatusKontrak || filterKategori || filterVendor) && (
            <button
              type="button"
              onClick={handleResetFilter}
              className="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors whitespace-nowrap"
            >
              Reset Filter
            </button>
          )}
        </div>
      </div>

      {/* 4. CONTENT BERDASARKAN TAB */}
      {activeTab === 'inventaris' && (
        <div className="space-y-4">
          {/* Summary KPI Mini */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Total Unit Terfilter</span>
              <span className="text-xl font-extrabold text-gray-900">{filteredInventaris.length} Unit</span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Kondisi Baik (Ada)</span>
              <span className="text-xl font-extrabold text-emerald-600">
                {filteredInventaris.filter((i) => i.status_aset === 'Ada').length} Unit
              </span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Terkait Sewa Beli</span>
              <span className="text-xl font-extrabold text-[#176B7A]">
                {filteredInventaris.filter((i) => i.kontrak_id).length} Unit
              </span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Milik Mandiri</span>
              <span className="text-xl font-extrabold text-[#6B7280]">
                {filteredInventaris.filter((i) => !i.kontrak_id).length} Unit
              </span>
            </div>
          </div>

          {/* Tabel Laporan Inventaris */}
          <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div className="p-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-xs font-bold uppercase tracking-wider text-gray-700">
                Tabel Rincian Data Aset Inventaris
              </h3>
              <span className="text-xs text-[#6B7280]">
                Menampilkan {filteredInventaris.length} data
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#F8FAFC] text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                  <tr>
                    <th className="py-3 px-4 font-bold w-12 text-center">No</th>
                    <th className="py-3 px-4 font-bold">Kode Inventaris</th>
                    <th className="py-3 px-4 font-bold">Nama Barang & Merek</th>
                    <th className="py-3 px-4 font-bold">Kategori</th>
                    <th className="py-3 px-4 font-bold">Lokasi Penempatan</th>
                    <th className="py-3 px-4 font-bold">Status Fisik</th>
                    <th className="py-3 px-4 font-bold">Status Kepemilikan</th>
                    <th className="py-3 px-4 font-bold">Keterikatan Kontrak</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {loading ? (
                    <tr>
                      <td colSpan="8" className="py-12 text-center text-gray-500">
                        <RefreshCw className="w-5 h-5 animate-spin mx-auto text-[#176B7A] mb-2" />
                        Memuat data laporan inventaris...
                      </td>
                    </tr>
                  ) : filteredInventaris.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="py-12 text-center text-gray-500">
                        Tidak ada data inventaris yang sesuai dengan filter yang dipilih.
                      </td>
                    </tr>
                  ) : (
                    filteredInventaris.map((item, idx) => (
                      <tr key={item.id} className="hover:bg-gray-50/70 transition-colors">
                        <td className="py-3 px-4 text-center text-gray-400 font-mono">{idx + 1}</td>
                        <td className="py-3 px-4 font-mono font-bold text-[#F15A24]">
                          {item.kode_inventaris}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-bold text-gray-900">{item.nama_barang}</div>
                          <div className="text-[11px] text-gray-500">
                            {item.merek || '-'} {item.tipe_model ? `(${item.tipe_model})` : ''}
                          </div>
                        </td>
                        <td className="py-3 px-4 text-gray-700">{item.kategori || '-'}</td>
                        <td className="py-3 px-4 text-gray-700">{item.lokasi || '-'}</td>
                        <td className="py-3 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              item.status_aset === 'Ada'
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : item.status_aset === 'Rusak'
                                ? 'bg-amber-50 text-amber-700 border border-amber-200 font-bold'
                                : 'bg-rose-50 text-rose-700 border border-rose-200 font-bold'
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
                                : 'bg-gray-100 text-gray-700 border border-gray-200'
                            }`}
                          >
                            {item.status_kepemilikan || (item.kontrak_id ? 'SEWA' : 'MILIK PERUSAHAAN')}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-gray-600">
                          {item.kontrak ? (
                            <div className="font-mono text-[11px] font-semibold text-gray-900">
                              {item.kontrak.kode_kontrak}
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
        </div>
      )}

      {activeTab === 'kontrak' && (
        <div className="space-y-4">
          {/* Summary KPI Mini Kontrak */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Total Dokumen</span>
              <span className="text-xl font-extrabold text-gray-900">{filteredKontrak.length} Dokumen</span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Kontrak Aktif</span>
              <span className="text-xl font-extrabold text-[#176B7A]">
                {filteredKontrak.filter((k) => k.status_kontrak === 'AKTIF' || k.status_kontrak === 'Sedang Berjalan').length}
              </span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Akan Berakhir</span>
              <span className="text-xl font-extrabold text-[#F15A24]">
                {filteredKontrak.filter((k) => k.status_kontrak === 'AKAN BERAKHIR' || k.status_kontrak === 'Akan Berakhir').length}
              </span>
            </div>
            <div className="bg-white p-3.5 rounded-xl border border-gray-200 shadow-2xs">
              <span className="text-[11px] font-semibold text-gray-500 block">Total Komitmen</span>
              <span className="text-lg font-extrabold text-gray-900 truncate block">
                {formatRupiah(filteredKontrak.reduce((acc, curr) => acc + (Number(curr.total_biaya) || 0), 0))}
              </span>
            </div>
          </div>

          {/* Tabel Laporan Kontrak */}
          <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            <div className="p-4 border-b border-gray-100 flex items-center justify-between">
              <h3 className="text-xs font-bold uppercase tracking-wider text-gray-700">
                Tabel Rincian Kontrak Sewa Beli
              </h3>
              <span className="text-xs text-[#6B7280]">
                Menampilkan {filteredKontrak.length} kontrak
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#F8FAFC] text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                  <tr>
                    <th className="py-3 px-4 font-bold w-12 text-center">No</th>
                    <th className="py-3 px-4 font-bold">Kode Kontrak</th>
                    <th className="py-3 px-4 font-bold">Vendor Penyedia</th>
                    <th className="py-3 px-4 font-bold">Masa Berlaku</th>
                    <th className="py-3 px-4 font-bold">Sisa Masa Kontrak</th>
                    <th className="py-3 px-4 font-bold">Status</th>
                    <th className="py-3 px-4 font-bold text-right">Biaya Sewa / Bln</th>
                    <th className="py-3 px-4 font-bold text-right">Total Komitmen</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {loading ? (
                    <tr>
                      <td colSpan="8" className="py-12 text-center text-gray-500">
                        <RefreshCw className="w-5 h-5 animate-spin mx-auto text-[#176B7A] mb-2" />
                        Memuat data laporan kontrak...
                      </td>
                    </tr>
                  ) : filteredKontrak.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="py-12 text-center text-gray-500">
                        Tidak ada data kontrak yang sesuai dengan filter yang dipilih.
                      </td>
                    </tr>
                  ) : (
                    filteredKontrak.map((item, idx) => (
                      <tr key={item.id} className="hover:bg-gray-50/70 transition-colors">
                        <td className="py-3 px-4 text-center text-gray-400 font-mono">{idx + 1}</td>
                        <td className="py-3 px-4 font-mono font-bold text-[#176B7A]">
                          {item.kode_kontrak}
                        </td>
                        <td className="py-3 px-4 font-semibold text-gray-900">{item.vendor}</td>
                        <td className="py-3 px-4 text-gray-600">
                          {formatDate(item.tanggal_mulai)} s/d {formatDate(item.tanggal_berakhir)}
                        </td>
                        <td className="py-3 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              item.sisa_hari === null
                                ? 'bg-gray-100 text-gray-600'
                                : item.sisa_hari > 30
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : item.sisa_hari >= 0
                                ? 'bg-amber-50 text-amber-700 border border-amber-200 font-bold'
                                : 'bg-rose-50 text-rose-700 border border-rose-200'
                            }`}
                          >
                            {item.sisa_hari !== null
                              ? item.sisa_hari < 0
                                ? `Habis (${Math.abs(item.sisa_hari)} hr lalu)`
                                : `${item.sisa_hari} hari lagi`
                              : '-'}
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <span
                            className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${
                              item.status_kontrak === 'AKTIF' || item.status_kontrak === 'Sedang Berjalan'
                                ? 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20'
                                : item.status_kontrak === 'AKAN BERAKHIR' || item.status_kontrak === 'Akan Berakhir'
                                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                : 'bg-rose-50 text-rose-700 border border-rose-200'
                            }`}
                          >
                            {item.status_kontrak}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-right font-medium text-gray-700">
                          {formatRupiah(item.biaya_sewa)}
                        </td>
                        <td className="py-3 px-4 text-right font-bold text-gray-900">
                          {formatRupiah(item.total_biaya || (item.biaya_sewa * (item.durasi_bulan || 1)))}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'ringkasan' && (
        <div className="space-y-6">
          {/* Card Ringkasan Eksekutif Finansial */}
          <div className="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-xs space-y-6">
            <div className="border-b border-gray-100 pb-4">
              <h3 className="text-base font-bold text-gray-900">Ringkasan Eksekutif Portofolio Sewa Beli</h3>
              <p className="text-xs text-[#6B7280] mt-0.5">
                Konsolidasi total kewajiban sewa beli dan utilisasi aset operasional PT Banyan Koalindo Lestari
              </p>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div className="p-4 rounded-xl bg-[#F8FAFC] border border-gray-200 space-y-2">
                <div className="flex items-center space-x-2 text-xs font-semibold text-gray-500">
                  <Building2 className="w-4 h-4 text-[#176B7A]" />
                  <span>Total Aset Terdaftar</span>
                </div>
                <div className="text-2xl font-black text-gray-900">{inventarisData.length} Unit</div>
                <p className="text-[11px] text-gray-500">
                  {inventarisData.filter((i) => i.kontrak_id).length} unit sewa beli • {inventarisData.filter((i) => !i.kontrak_id).length} unit milik mandiri
                </p>
              </div>

              <div className="p-4 rounded-xl bg-[#F8FAFC] border border-gray-200 space-y-2">
                <div className="flex items-center space-x-2 text-xs font-semibold text-gray-500">
                  <FileText className="w-4 h-4 text-[#176B7A]" />
                  <span>Total Dokumen Kontrak</span>
                </div>
                <div className="text-2xl font-black text-gray-900">{kontrakData.length} Kontrak</div>
                <p className="text-[11px] text-gray-500">
                  {kontrakData.filter((k) => k.status_kontrak === 'AKTIF' || k.status_kontrak === 'Sedang Berjalan').length} aktif • {kontrakData.filter((k) => k.status_kontrak === 'AKAN BERAKHIR').length} akan berakhir
                </p>
              </div>

              <div className="p-4 rounded-xl bg-[#F8FAFC] border border-gray-200 space-y-2">
                <div className="flex items-center space-x-2 text-xs font-semibold text-gray-500">
                  <DollarSign className="w-4 h-4 text-[#F15A24]" />
                  <span>Akumulasi Komitmen</span>
                </div>
                <div className="text-2xl font-black text-[#F15A24]">
                  {formatRupiah(kontrakData.reduce((acc, curr) => acc + (Number(curr.total_biaya) || 0), 0))}
                </div>
                <p className="text-[11px] text-gray-500">
                  Total nilai komitmen dari seluruh kontrak tercatat
                </p>
              </div>
            </div>

            {/* Catatan Kepatuhan */}
            <div className="p-4 rounded-xl bg-teal-50/50 border border-teal-100 flex items-start space-x-3">
              <CheckCircle2 className="w-5 h-5 text-[#176B7A] flex-shrink-0 mt-0.5" />
              <div className="text-xs text-gray-700 leading-relaxed">
                <strong className="text-[#176B7A] block font-bold mb-0.5">Integritas Dokumen Terverifikasi:</strong>
                Laporan ini divalidasi langsung dari database operasional PT Banyan Koalindo Lestari. Seluruh nilai nominal dihitung berdasarkan perjanjian kontrak riil tanpa estimasi sepihak.
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
