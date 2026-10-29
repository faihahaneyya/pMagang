import React, { useState, useEffect, useMemo } from 'react';
import {
  FileText,
  Plus,
  Edit2,
  Trash2,
  Eye,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  X,
  Calendar,
  Building2,
  Download,
  Search,
  Filter,
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  Boxes,
  Clock,
  DollarSign
} from 'lucide-react';
import {
  getKontrakList,
  getKontrakDetail,
  createKontrak,
  updateKontrak,
  deleteKontrak,
  getVendorAll,
  getContractItems,
  addContractItem,
  removeContractItem,
  getInventarisList,
} from '../api';

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

export default function KontrakPage({ user, onSessionExpired, onDataChanged }) {
  // State Data & Paginasi
  const [kontrakList, setKontrakList] = useState([]);
  const [kpiData, setKpiData] = useState(null);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Vendor dropdown data
  const [vendorList, setVendorList] = useState([]);
  const [loadingVendors, setLoadingVendors] = useState(false);

  // Contract Items state
  const [contractItems, setContractItems] = useState([]);
  const [showItemsModal, setShowItemsModal] = useState(false);
  const [selectedKontrak, setSelectedKontrak] = useState(null);
  const [loadingItems, setLoadingItems] = useState(false);
  const [inventarisDropdown, setInventarisDropdown] = useState([]);
  const [selectedInventarisId, setSelectedInventarisId] = useState('');
  const [addingItem, setAddingItem] = useState(false);
  const [removingItemId, setRemovingItemId] = useState(null);

  // State Filter & Pencarian
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [sortBy, setSortBy] = useState('tanggal_berakhir');
  const [sortOrder, setSortOrder] = useState('asc');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);

  // State Alert Notifikasi Toast
  const [alert, setAlert] = useState(null); // { type: 'success' | 'error', message: string }

  // State Modals
  const [detailItem, setDetailItem] = useState(null);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [loadingDetail, setLoadingDetail] = useState(false);

  const [formModalOpen, setFormModalOpen] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [editId, setEditId] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const [deleteItem, setDeleteItem] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleting, setDeleting] = useState(false);

  // Initial Form State — gunakan vendor_id (FK) bukan string vendor
  const initialForm = {
    kode_kontrak: '',
    vendor_id: '',
    vendor: '',
    tanggal_mulai: '',
    tanggal_berakhir: '',
    durasi: '',
    biaya_sewa: '',
    keterangan: '',
  };
  const [formData, setFormData] = useState(initialForm);

  // Role helpers
  const canWrite = user?.role === 'IT' || user?.role === 'KEUANGAN';
  const canDelete = user?.role === 'IT' || user?.role === 'KEUANGAN';

  // Load vendor dropdown saat komponen mount
  const loadVendors = async () => {
    setLoadingVendors(true);
    try {
      const res = await getVendorAll();
      if (res?.data) setVendorList(res.data);
    } catch (err) {
      if (err.status === 401 && onSessionExpired) onSessionExpired();
    } finally {
      setLoadingVendors(false);
    }
  };

  useEffect(() => {
    loadVendors();
  }, []);

  // Auto-dismiss alert after 4 seconds
  useEffect(() => {
    if (alert) {
      const timer = setTimeout(() => {
        setAlert(null);
      }, 4000);
      return () => clearTimeout(timer);
    }
  }, [alert]);

  // Load Data Kontrak saat filter/paginasi berubah
  useEffect(() => {
    fetchKontrakData();
  }, [page, perPage, filterStatus, sortBy, sortOrder]);

  const fetchKontrakData = async () => {
    setLoading(true);
    try {
      const params = {
        page,
        per_page: perPage,
        sort_by: sortBy,
        sort_order: sortOrder,
        ...(searchTerm.trim() ? { search: searchTerm.trim() } : {}),
        ...(filterStatus ? { status_kontrak: filterStatus } : {}),
      };

      const res = await getKontrakList(params);
      if (res?.data) {
        setKontrakList(res.data);
        if (res.meta) {
          setMeta(res.meta);
        }
        if (res.kpi) {
          setKpiData(res.kpi);
        }
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal memuat data kontrak dari server.',
        });
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = () => {
    setRefreshing(true);
    fetchKontrakData();
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchKontrakData();
  };

  // Kalkulasi durasi otomatis saat tanggal berubah
  const calculatedDuration = useMemo(() => {
    if (!formData.tanggal_mulai || !formData.tanggal_berakhir) return '';
    try {
      const start = new Date(formData.tanggal_mulai);
      const end = new Date(formData.tanggal_berakhir);
      if (end >= start) {
        const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        const months = Math.floor(diffDays / 30);
        const remDays = diffDays % 30;
        if (months > 0) {
          return remDays > 0 ? `${months} Bulan ${remDays} Hari` : `${months} Bulan`;
        }
        return `${diffDays} Hari`;
      }
    } catch {
      return '';
    }
    return '';
  }, [formData.tanggal_mulai, formData.tanggal_berakhir]);

  // Open Create Modal
  const handleOpenCreate = () => {
    setFormData(initialForm);
    setFieldErrors({});
    setIsEditMode(false);
    setEditId(null);
    setFormModalOpen(true);
  };

  // Open Edit Modal
  const handleOpenEdit = async (item) => {
    setIsEditMode(true);
    setEditId(item.id || item.kode_kontrak);
    setFieldErrors({});
    setFormData({
      kode_kontrak: item.kode_kontrak || '',
      vendor_id: item.vendor_id ? String(item.vendor_id) : '',
      vendor: item.vendor || '',
      tanggal_mulai: item.tanggal_mulai || '',
      tanggal_berakhir: item.tanggal_berakhir || '',
      durasi: item.durasi || '',
      biaya_sewa: item.biaya_sewa !== undefined && item.biaya_sewa !== null ? String(item.biaya_sewa) : '',
      keterangan: item.keterangan || '',
    });
    setFormModalOpen(true);
  };

  // Open Detail Modal
  const handleOpenDetail = async (item) => {
    setShowDetailModal(true);
    setLoadingDetail(true);
    setDetailItem(item);
    try {
      const res = await getKontrakDetail(item.id || item.kode_kontrak);
      if (res?.data) {
        setDetailItem(res.data);
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: 'Gagal memuat detail lengkap kontrak.',
        });
      }
    } finally {
      setLoadingDetail(false);
    }
  };

  // Open Delete Modal
  const handleOpenDelete = (item) => {
    setDeleteItem(item);
    setShowDeleteModal(true);
  };

  // Submit Form (Tambah / Edit)
  const handleSubmitForm = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});

    try {
      const payload = {
        kode_kontrak: formData.kode_kontrak.trim().toUpperCase(),
        vendor_id: formData.vendor_id ? Number(formData.vendor_id) : null,
        vendor: formData.vendor.trim(),
        tanggal_mulai: formData.tanggal_mulai,
        tanggal_berakhir: formData.tanggal_berakhir,
        durasi: formData.durasi.trim() || calculatedDuration || null,
        biaya_sewa: formData.biaya_sewa ? Number(formData.biaya_sewa) : 0,
        keterangan: formData.keterangan ? formData.keterangan.trim() : null,
      };

      if (isEditMode) {
        await updateKontrak(editId, payload);
        setAlert({
          type: 'success',
          message: `Kontrak ${payload.kode_kontrak} berhasil diperbarui.`,
        });
      } else {
        await createKontrak(payload);
        setAlert({
          type: 'success',
          message: `Kontrak ${payload.kode_kontrak} berhasil ditambahkan.`,
        });
      }

      setFormModalOpen(false);
      fetchKontrakData();
      if (onDataChanged) {
        onDataChanged();
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else if (err.status === 422 && err.errors) {
        setFieldErrors(err.errors);
        setAlert({
          type: 'error',
          message: 'Terdapat kesalahan pada input formulir. Mohon periksa kembali kolom yang ditandai merah.',
        });
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menyimpan data kontrak.',
        });
      }
    } finally {
      setSubmitting(false);
    }
  };

  // Execute Delete
  const handleConfirmDelete = async () => {
    if (!deleteItem) return;
    setDeleting(true);
    try {
      await deleteKontrak(deleteItem.id || deleteItem.kode_kontrak);
      setAlert({
        type: 'success',
        message: `Kontrak ${deleteItem.kode_kontrak} berhasil dihapus. Aset terkait telah dialihkan menjadi Milik Perusahaan.`,
      });
      setShowDeleteModal(false);
      setDeleteItem(null);
      fetchKontrakData();
      if (onDataChanged) {
        onDataChanged();
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menghapus data kontrak.',
        });
      }
    } finally {
      setDeleting(false);
    }
  };

  // ===== CONTRACT ITEMS HANDLERS =====
  const handleOpenItems = async (item) => {
    setSelectedKontrak(item);
    setShowItemsModal(true);
    setContractItems([]);
    setSelectedInventarisId('');
    setLoadingItems(true);
    try {
      // Load contract items
      const [itemsRes, invRes] = await Promise.all([
        getContractItems(item.id),
        getInventarisList({ per_page: 200 }),
      ]);
      if (itemsRes?.data) setContractItems(itemsRes.data);
      if (invRes?.data) setInventarisDropdown(invRes.data);
    } catch (err) {
      if (err.status === 401 && onSessionExpired) onSessionExpired();
      else setAlert({ type: 'error', message: err.message || 'Gagal memuat data barang kontrak.' });
    } finally {
      setLoadingItems(false);
    }
  };

  const handleAddContractItem = async () => {
    if (!selectedInventarisId || !selectedKontrak) return;
    setAddingItem(true);
    try {
      await addContractItem(selectedKontrak.id, Number(selectedInventarisId));
      setAlert({ type: 'success', message: 'Barang berhasil ditambahkan ke kontrak.' });
      const res = await getContractItems(selectedKontrak.id);
      if (res?.data) setContractItems(res.data);
      setSelectedInventarisId('');
      if (onDataChanged) onDataChanged();
    } catch (err) {
      if (err.status === 401 && onSessionExpired) onSessionExpired();
      else setAlert({ type: 'error', message: err.message || 'Gagal menambahkan barang ke kontrak.' });
    } finally {
      setAddingItem(false);
    }
  };

  const handleRemoveContractItem = async (itemId) => {
    if (!selectedKontrak) return;
    setRemovingItemId(itemId);
    try {
      await removeContractItem(selectedKontrak.id, itemId);
      setAlert({ type: 'success', message: 'Barang berhasil dihapus dari kontrak.' });
      setContractItems((prev) => prev.filter((ci) => ci.id !== itemId));
      if (onDataChanged) onDataChanged();
    } catch (err) {
      if (err.status === 401 && onSessionExpired) onSessionExpired();
      else setAlert({ type: 'error', message: err.message || 'Gagal menghapus barang dari kontrak.' });
    } finally {
      setRemovingItemId(null);
    }
  };

  // Ringkasan KPI Mini di atas tabel — diprioritaskan dari kalkulasi backend MySQL
  const kpiSummary = useMemo(() => {
    if (kpiData) {
      return {
        total: kpiData.total,
        aktif: kpiData.aktif,
        akanBerakhir: kpiData.akan_berakhir,
        berakhir: kpiData.berakhir,
        totalKomitmen: kpiData.total_komitmen,
      };
    }
    const total = meta.total || kontrakList.length;
    const aktif = kontrakList.filter((k) => k.status_kontrak === 'AKTIF' || k.status_kontrak === 'Sedang Berjalan').length;
    const akanBerakhir = kontrakList.filter((k) => k.status_kontrak === 'AKAN BERAKHIR' || k.status_kontrak === 'Akan Berakhir').length;
    const berakhir = kontrakList.filter((k) => k.status_kontrak === 'BERAKHIR' || k.status_kontrak === 'Habis').length;
    const totalKomitmen = kontrakList.reduce((acc, curr) => acc + (Number(curr.total_biaya) || 0), 0);

    return { total, aktif, akanBerakhir, berakhir, totalKomitmen };
  }, [kpiData, kontrakList, meta.total]);

  return (
    <div className="space-y-6">
      {/* 1. TOP HEADER & ACTION BAR */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
          <div className="flex items-center space-x-2 text-xs text-[#6B7280] mb-1">
            <span>Dashboard</span>
            <span>/</span>
            <span className="text-[#176B7A] font-semibold">Manajemen Kontrak</span>
          </div>
          <h2 className="text-xl font-extrabold text-gray-900 tracking-tight flex items-center space-x-2.5 flex-wrap gap-y-1">
            <FileText className="w-6 h-6 text-[#176B7A] flex-shrink-0" />
            <span>Manajemen Kontrak Sewa Beli</span>
            <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20">
              {meta.total} Total Dokumen
            </span>
          </h2>
          <p className="text-xs text-[#6B7280] mt-0.5">
            Kelola perjanjian sewa beli alat berat & perlengkapan kantor, jatuh tempo, serta keterikatan aset
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          {/* Refresh Button */}
          <button
            type="button"
            id="btn-refresh-kontrak"
            onClick={handleRefresh}
            disabled={loading || refreshing}
            className="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50"
            title="Muat Ulang Data Kontrak"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
          </button>

          {/* Export PDF Button */}
          <a
            href={`/api/kontrak/export/pdf${filterStatus ? `?status=${encodeURIComponent(filterStatus)}` : ''}`}
            id="btn-export-kontrak-pdf"
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-2xs transition-colors"
            title="Unduh Laporan Kontrak format PDF"
          >
            <Download className="w-4 h-4 text-[#F15A24]" />
            <span className="hidden md:inline">Export PDF</span>
          </a>

          {/* Export Excel Button */}
          <a
            href={`/api/kontrak/export/excel${filterStatus ? `?status=${encodeURIComponent(filterStatus)}` : ''}`}
            id="btn-export-kontrak-excel"
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-2xs transition-colors"
            title="Unduh Laporan Kontrak format Excel (XLSX)"
          >
            <Download className="w-4 h-4 text-emerald-600" />
            <span className="hidden md:inline">Export Excel</span>
          </a>

          {/* Tambah Kontrak Button */}
          {canWrite && (
          <button
            type="button"
            id="btn-tambah-kontrak"
            onClick={handleOpenCreate}
            className="flex-1 sm:flex-none inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all duration-200"
          >
            <Plus className="w-4 h-4" />
            <span>Tambah Kontrak</span>
          </button>
          )}
        </div>
      </div>

      {/* 2. ALERT TOAST BANNER */}
      {alert && (
        <div
          id="kontrak-alert-box"
          className={`p-4 rounded-2xl border text-xs sm:text-sm flex items-start justify-between shadow-xs transition-all ${
            alert.type === 'success'
              ? 'bg-emerald-50 border-emerald-200 text-emerald-950'
              : 'bg-rose-50 border-rose-200 text-rose-950'
          }`}
        >
          <div className="flex items-center space-x-2.5">
            {alert.type === 'success' ? (
              <CheckCircle2 className="w-5 h-5 text-emerald-600 flex-shrink-0" />
            ) : (
              <AlertCircle className="w-5 h-5 text-rose-600 flex-shrink-0" />
            )}
            <span className="font-semibold">{alert.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setAlert(null)}
            className="text-gray-400 hover:text-gray-600 p-1"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* 3. MINI KPI SUMMARY CARDS */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Total Dokumen */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#6B7280]">
            <span>Total Dokumen</span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
              <FileText className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-gray-900 mt-2">
            {kpiSummary.total} <span className="text-xs font-semibold text-[#6B7280]">Kontrak</span>
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">Tersimpan di database</div>
        </div>

        {/* Kontrak Aktif */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#176B7A]">
            <span>Kontrak Aktif</span>
            <div className="w-8 h-8 rounded-lg bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center">
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-[#176B7A] mt-2">
            {kpiSummary.aktif} <span className="text-xs font-semibold text-[#176B7A]">Aktif</span>
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">Masa sewa &gt; 30 hari</div>
        </div>

        {/* Akan Berakhir */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#F15A24]">
            <span>Akan Berakhir</span>
            <div className="w-8 h-8 rounded-lg bg-[#F15A24]/10 text-[#F15A24] flex items-center justify-center">
              <AlertTriangle className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-[#F15A24] mt-2">
            {kpiSummary.akanBerakhir} <span className="text-xs font-semibold text-[#F15A24]">Kontrak</span>
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">1–30 hari jatuh tempo</div>
        </div>

        {/* Akumulasi Komitmen */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#6B7280]">
            <span>Akumulasi Komitmen</span>
            <div className="w-8 h-8 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center">
              <DollarSign className="w-4 h-4" />
            </div>
          </div>
          <div className="text-base sm:text-lg font-black text-gray-900 mt-2 truncate">
            {formatRupiah(kpiSummary.totalKomitmen)}
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">Estimasi nilai total</div>
        </div>
      </div>

      {/* 4. SEARCH & FILTER TOOLBAR */}
      <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-3">
          {/* Search Input */}
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              id="input-search-kontrak"
              placeholder="Cari nomor kontrak, nama vendor, atau keterangan..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm text-gray-900 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            />
            {searchTerm && (
              <button
                type="button"
                onClick={() => {
                  setSearchTerm('');
                  setPage(1);
                }}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          {/* Filter Status Kontrak */}
          <div className="w-full sm:w-48">
            <select
              id="select-filter-status-kontrak"
              value={filterStatus}
              onChange={(e) => {
                setFilterStatus(e.target.value);
                setPage(1);
              }}
              className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm bg-white text-gray-700 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            >
              <option value="">Semua Status</option>
              <option value="AKTIF">Aktif (&gt; 30 hari)</option>
              <option value="AKAN BERAKHIR">Akan Berakhir (&le; 30 hari)</option>
              <option value="BERAKHIR">Berakhir (Habis)</option>
            </select>
          </div>

          {/* Sort By Dropdown */}
          <div className="w-full sm:w-48">
            <select
              id="select-sort-kontrak"
              value={`${sortBy}-${sortOrder}`}
              onChange={(e) => {
                const [sb, so] = e.target.value.split('-');
                setSortBy(sb);
                setSortOrder(so);
                setPage(1);
              }}
              className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm bg-white text-gray-700 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            >
              <option value="tanggal_berakhir-asc">Jatuh Tempo Terdekat</option>
              <option value="tanggal_berakhir-desc">Jatuh Tempo Terjauh</option>
              <option value="biaya_sewa-desc">Biaya Sewa Tertinggi</option>
              <option value="biaya_sewa-asc">Biaya Sewa Terendah</option>
              <option value="vendor-asc">Nama Vendor (A-Z)</option>
              <option value="id-desc">Terbaru Ditambahkan</option>
            </select>
          </div>

          <button
            type="submit"
            className="px-4 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#0f4e5a] text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center space-x-1"
          >
            <span>Terapkan</span>
          </button>
        </form>
      </div>

      {/* 5. DATA TABLE KONTRAK */}
      <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table id="table-kontrak" className="w-full text-left text-xs">
            <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
              <tr>
                <th className="py-3.5 px-4">No. Kontrak</th>
                <th className="py-3.5 px-4">Vendor & Keterangan</th>
                <th className="py-3.5 px-4">Masa Berlaku</th>
                <th className="py-3.5 px-4">Sisa Waktu</th>
                <th className="py-3.5 px-4 text-right">Biaya Sewa / Bln</th>
                <th className="py-3.5 px-4 text-center">Status</th>
                <th className="py-3.5 px-4 text-center">Aset Terikat</th>
                <th className="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan="8" className="py-12 text-center text-gray-500">
                    <RefreshCw className="w-6 h-6 animate-spin text-[#176B7A] mx-auto mb-2" />
                    <span>Memuat data kontrak dari server...</span>
                  </td>
                </tr>
              ) : kontrakList.length === 0 ? (
                <tr>
                  <td colSpan="8" className="py-12 text-center text-gray-500">
                    <FileText className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                    <p className="font-semibold text-gray-700">Tidak ada data kontrak yang ditemukan.</p>
                    <p className="text-[11px] text-gray-400 mt-0.5">
                      {searchTerm || filterStatus ? 'Coba ubah kata kunci pencarian atau filter Anda.' : 'Silakan klik Tambah Kontrak untuk memasukkan kontrak baru.'}
                    </p>
                  </td>
                </tr>
              ) : (
                kontrakList.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50/80 transition-colors">
                    {/* Kode Kontrak */}
                    <td className="py-3.5 px-4 font-mono font-bold text-[#176B7A]">
                      {item.kode_kontrak}
                    </td>

                    {/* Vendor & Keterangan */}
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-gray-900">{item.vendor}</div>
                      {item.keterangan && (
                        <div className="text-[11px] text-gray-500 max-w-xs truncate" title={item.keterangan}>
                          {item.keterangan}
                        </div>
                      )}
                    </td>

                    {/* Masa Berlaku */}
                    <td className="py-3.5 px-4 text-gray-700 whitespace-nowrap">
                      <div>{formatDate(item.tanggal_mulai)} s/d {formatDate(item.tanggal_berakhir)}</div>
                      <div className="text-[10px] text-gray-400 font-medium">Durasi: {item.durasi || '-'}</div>
                    </td>

                    {/* Sisa Waktu */}
                    <td className="py-3.5 px-4 whitespace-nowrap">
                      <span
                        className={`inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] font-bold ${
                          item.sisa_hari !== null && item.sisa_hari < 0
                            ? 'bg-rose-50 text-rose-700 border border-rose-200'
                            : item.sisa_hari !== null && item.sisa_hari <= 30
                            ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        }`}
                      >
                        <Clock className="w-3 h-3" />
                        <span>{item.sisa_kontrak || `${item.sisa_hari} hari lagi`}</span>
                      </span>
                    </td>

                    {/* Biaya Sewa */}
                    <td className="py-3.5 px-4 text-right font-semibold text-gray-900 whitespace-nowrap">
                      <div>{formatRupiah(item.biaya_sewa)}</div>
                      <div className="text-[10px] text-gray-400">Total: {formatRupiah(item.total_biaya)}</div>
                    </td>

                    {/* Status Kontrak Badge */}
                    <td className="py-3.5 px-4 text-center whitespace-nowrap">
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-bold ${
                          item.status_kontrak === 'AKTIF' || item.status_kontrak === 'Sedang Berjalan'
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : item.status_kontrak === 'AKAN BERAKHIR' || item.status_kontrak === 'Akan Berakhir'
                            ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                            : 'bg-rose-50 text-rose-700 border border-rose-200'
                        }`}
                      >
                        {item.status_kontrak}
                      </span>
                    </td>

                    {/* Aset Terikat */}
                    <td className="py-3.5 px-4 text-center whitespace-nowrap">
                      <span className="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 font-bold text-[11px]">
                        <Boxes className="w-3.5 h-3.5 text-[#176B7A]" />
                        <span>{item.jumlah_inventaris || 0} unit</span>
                      </span>
                    </td>

                    {/* Tombol Aksi */}
                    <td className="py-3.5 px-4 text-center whitespace-nowrap">
                      <div className="inline-flex items-center space-x-1.5">
                        {/* Detail */}
                        <button
                          type="button"
                          onClick={() => handleOpenDetail(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Lihat Rincian Kontrak"
                        >
                          <Eye className="w-4 h-4" />
                        </button>

                        {/* Kelola Barang */}
                        <button
                          type="button"
                          onClick={() => handleOpenItems(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Kelola Barang Terkait Kontrak"
                        >
                          <Boxes className="w-4 h-4" />
                        </button>

                        {/* Edit */}
                        {canWrite && (
                        <button
                          type="button"
                          onClick={() => handleOpenEdit(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#F15A24] hover:bg-[#F15A24]/10 transition-colors"
                          title="Ubah Data Kontrak"
                        >
                          <Edit2 className="w-4 h-4" />
                        </button>
                        )}

                        {/* Delete */}
                        {canDelete && (
                        <button
                          type="button"
                          onClick={() => handleOpenDelete(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                          title="Hapus Kontrak"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* 6. PAGINATION FOOTER */}
        <div className="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6B7280]">
          <div>
            Menampilkan <span className="font-bold text-gray-900">{kontrakList.length > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0}</span> s/d{' '}
            <span className="font-bold text-gray-900">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> dari{' '}
            <span className="font-bold text-gray-900">{meta.total}</span> data kontrak
          </div>

          <div className="flex items-center space-x-2">
            <select
              value={perPage}
              onChange={(e) => {
                setPerPage(Number(e.target.value));
                setPage(1);
              }}
              className="px-2.5 py-1.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-700 focus:outline-none"
            >
              <option value="10">10 per hal</option>
              <option value="25">25 per hal</option>
              <option value="50">50 per hal</option>
            </select>

            <button
              type="button"
              disabled={meta.current_page <= 1 || loading}
              onClick={() => setPage((p) => Math.max(p - 1, 1))}
              className="p-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition-colors"
              title="Halaman Sebelumnya"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>

            <span className="font-bold px-2 text-[#176B7A]">
              Hal {meta.current_page} / {meta.last_page || 1}
            </span>

            <button
              type="button"
              disabled={meta.current_page >= meta.last_page || loading}
              onClick={() => setPage((p) => p + 1)}
              className="p-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 transition-colors"
              title="Halaman Berikutnya"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* =========================================================================
          7. DETAIL MODAL
      ========================================================================== */}
      {showDetailModal && detailItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            {/* Modal Header */}
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC] backdrop-blur-xs z-10">
              <div>
                <div className="flex items-center space-x-2">
                  <span className="font-mono text-xs font-bold text-[#176B7A] bg-white px-2.5 py-1 rounded-lg border border-gray-200">
                    {detailItem.kode_kontrak}
                  </span>
                  <span
                    className={`px-2.5 py-0.5 rounded-md text-[10px] font-bold ${
                      detailItem.status_kontrak === 'AKTIF' || detailItem.status_kontrak === 'Sedang Berjalan'
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : detailItem.status_kontrak === 'AKAN BERAKHIR' || detailItem.status_kontrak === 'Akan Berakhir'
                        ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                    }`}
                  >
                    {detailItem.status_kontrak}
                  </span>
                </div>
                <h3 className="text-base font-extrabold text-gray-900 mt-1.5">{detailItem.vendor}</h3>
              </div>
              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-6 space-y-6">
              {loadingDetail ? (
                <div className="py-8 text-center text-gray-500">
                  <RefreshCw className="w-6 h-6 animate-spin text-[#176B7A] mx-auto mb-2" />
                  <span>Memuat rincian inventaris terikat...</span>
                </div>
              ) : (
                <>
                  {/* Grid Rincian Kontrak */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/70 p-4 rounded-2xl border border-gray-200 text-xs">
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Masa Berlaku Kontrak:</span>
                      <span className="font-bold text-gray-900">
                        {formatDate(detailItem.tanggal_mulai)} s/d {formatDate(detailItem.tanggal_berakhir)}
                      </span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Durasi Kontrak:</span>
                      <span className="font-bold text-gray-900">{detailItem.durasi || '-'}</span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Sisa Masa Kontrak:</span>
                      <span className="font-bold text-[#F15A24]">{detailItem.sisa_kontrak || '-'}</span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Biaya Sewa Bulanan:</span>
                      <span className="font-bold text-gray-900">{formatRupiah(detailItem.biaya_sewa)}</span>
                    </div>
                    <div className="sm:col-span-2 pt-2 border-t border-gray-200/80">
                      <span className="text-[#6B7280] text-[11px] block">Estimasi Total Nilai Komitmen:</span>
                      <span className="font-extrabold text-sm text-gray-900">{formatRupiah(detailItem.total_biaya)}</span>
                    </div>
                    {detailItem.keterangan && (
                      <div className="sm:col-span-2 pt-2 border-t border-gray-200/80">
                        <span className="text-[#6B7280] text-[11px] block">Keterangan / Catatan:</span>
                        <p className="text-gray-800 whitespace-pre-line mt-0.5">{detailItem.keterangan}</p>
                      </div>
                    )}
                  </div>

                  {/* Tabel Aset Inventaris yang Terikat */}
                  <div>
                    <div className="flex items-center justify-between mb-2.5">
                      <h4 className="text-xs font-bold text-gray-900 uppercase tracking-wide flex items-center space-x-1.5">
                        <Boxes className="w-4 h-4 text-[#176B7A]" />
                        <span>Aset Inventaris Terkait ({detailItem.inventaris ? detailItem.inventaris.length : 0} Unit)</span>
                      </h4>
                    </div>

                    {detailItem.inventaris && detailItem.inventaris.length > 0 ? (
                      <div className="rounded-xl border border-gray-200 overflow-hidden">
                        <table className="w-full text-left text-xs">
                          <thead className="bg-[#F8FAFC] text-[#6B7280] text-[10px] uppercase font-bold border-b border-gray-200">
                            <tr>
                              <th className="py-2.5 px-3">Kode Aset</th>
                              <th className="py-2.5 px-3">Nama Barang</th>
                              <th className="py-2.5 px-3">Kategori</th>
                              <th className="py-2.5 px-3">Lokasi</th>
                              <th className="py-2.5 px-3 text-center">Status Aset</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-gray-100">
                            {detailItem.inventaris.map((inv) => (
                              <tr key={inv.id} className="hover:bg-gray-50/80">
                                <td className="py-2.5 px-3 font-mono font-bold text-[#176B7A]">{inv.kode_inventaris}</td>
                                <td className="py-2.5 px-3 font-semibold text-gray-900">{inv.nama_barang}</td>
                                <td className="py-2.5 px-3 text-gray-600">{inv.kategori || '-'}</td>
                                <td className="py-2.5 px-3 text-gray-600">{inv.lokasi || '-'}</td>
                                <td className="py-2.5 px-3 text-center">
                                  <span
                                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                      inv.status_aset === 'Ada'
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : inv.status_aset === 'Rusak'
                                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    }`}
                                  >
                                    {inv.status_aset}
                                  </span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <div className="p-4 rounded-xl bg-gray-50 border border-gray-200 text-center text-xs text-gray-500">
                        Belum ada unit inventaris yang terikat pada kontrak ini.
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>

            {/* Modal Footer */}
            <div className="p-4 border-t border-gray-200 flex justify-end space-x-2 bg-[#F8FAFC] rounded-b-3xl">
              <button
                type="button"
                onClick={() => {
                  setShowDetailModal(false);
                  handleOpenEdit(detailItem);
                }}
                className="px-4 py-2 rounded-xl border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs transition-colors"
              >
                Ubah Kontrak
              </button>
              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs transition-colors"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* =========================================================================
          8. CREATE / EDIT FORM MODAL
      ========================================================================== */}
      {formModalOpen && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            {/* Header Form */}
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC] backdrop-blur-xs z-10">
              <div>
                <h3 className="text-base font-extrabold text-gray-900">
                  {isEditMode ? `Ubah Kontrak (${formData.kode_kontrak})` : 'Tambah Kontrak Sewa Beli Baru'}
                </h3>
                <p className="text-xs text-[#6B7280] mt-0.5">
                  Isi data perjanjian kontrak sewa beli dengan vendor terkait
                </p>
              </div>
              <button
                type="button"
                onClick={() => setFormModalOpen(false)}
                disabled={submitting}
                className="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 disabled:opacity-50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Form Body */}
            <form onSubmit={handleSubmitForm} className="p-6 space-y-4">
              {/* Kode Kontrak */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Nomor / Kode Kontrak <span className="text-[#F15A24]">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: KON-2026-006"
                  value={formData.kode_kontrak}
                  onChange={(e) => setFormData({ ...formData, kode_kontrak: e.target.value.toUpperCase() })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm font-mono uppercase focus:outline-none focus:ring-2 ${
                    fieldErrors.kode_kontrak
                      ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                      : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.kode_kontrak && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.kode_kontrak[0]}</p>
                )}
              </div>

              {/* Vendor */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Vendor / Penyedia <span className="text-[#F15A24]">*</span>
                </label>
                <select
                  required
                  value={formData.vendor_id || ''}
                  onChange={(e) => {
                    const vId = e.target.value;
                    const selected = vendorList.find((v) => String(v.vendor_id || v.id) === String(vId));
                    setFormData({
                      ...formData,
                      vendor_id: vId,
                      vendor: selected ? selected.name : ''
                    });
                  }}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm bg-white focus:outline-none focus:ring-2 ${
                    fieldErrors.vendor || fieldErrors.vendor_id
                      ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                      : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                >
                  <option value="">-- Pilih Vendor --</option>
                  {vendorList.map((v) => (
                    <option key={v.vendor_id || v.id} value={v.vendor_id || v.id}>
                      {v.name} {v.phone ? `(${v.phone})` : ''}
                    </option>
                  ))}
                </select>
                {loadingVendors && (
                  <p className="text-[10px] text-gray-400 mt-1">Memuat data vendor...</p>
                )}
                {(fieldErrors.vendor || fieldErrors.vendor_id) && (
                  <p className="text-[11px] text-rose-600 mt-1">
                    {(fieldErrors.vendor && fieldErrors.vendor[0]) || (fieldErrors.vendor_id && fieldErrors.vendor_id[0])}
                  </p>
                )}
              </div>

              {/* Tanggal Mulai & Tanggal Berakhir */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">
                    Tanggal Mulai <span className="text-[#F15A24]">*</span>
                  </label>
                  <input
                    type="date"
                    required
                    value={formData.tanggal_mulai}
                    onChange={(e) => setFormData({ ...formData, tanggal_mulai: e.target.value })}
                    className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                      fieldErrors.tanggal_mulai
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  />
                  {fieldErrors.tanggal_mulai && (
                    <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.tanggal_mulai[0]}</p>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">
                    Tanggal Berakhir <span className="text-[#F15A24]">*</span>
                  </label>
                  <input
                    type="date"
                    required
                    value={formData.tanggal_berakhir}
                    onChange={(e) => setFormData({ ...formData, tanggal_berakhir: e.target.value })}
                    className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                      fieldErrors.tanggal_berakhir
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  />
                  {fieldErrors.tanggal_berakhir && (
                    <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.tanggal_berakhir[0]}</p>
                  )}
                </div>
              </div>

              {/* Preview Durasi Otomatis & Custom Override */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Keterangan Durasi Kontrak
                </label>
                <div className="relative">
                  <input
                    type="text"
                    placeholder={calculatedDuration ? `Otomatis: ${calculatedDuration}` : 'Misal: 12 Bulan atau 360 Hari'}
                    value={formData.durasi}
                    onChange={(e) => setFormData({ ...formData, durasi: e.target.value })}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                  {calculatedDuration && !formData.durasi && (
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-[#176B7A] bg-[#176B7A]/10 px-2 py-0.5 rounded-md border border-[#176B7A]/20">
                      Auto: {calculatedDuration}
                    </span>
                  )}
                </div>
                <p className="text-[10px] text-gray-400 mt-1">
                  Biarkan kosong untuk menggunakan kalkulasi durasi otomatis dari tanggal mulai dan berakhir.
                </p>
              </div>

              {/* Biaya Sewa */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Biaya Sewa per Bulan (IDR)
                </label>
                <div className="relative">
                  <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">
                    Rp
                  </span>
                  <input
                    type="number"
                    min="0"
                    step="1000"
                    placeholder="0"
                    value={formData.biaya_sewa}
                    onChange={(e) => setFormData({ ...formData, biaya_sewa: e.target.value })}
                    className={`w-full pl-10 pr-4 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                      fieldErrors.biaya_sewa
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  />
                </div>
                {formData.biaya_sewa && !isNaN(Number(formData.biaya_sewa)) && (
                  <p className="text-[11px] text-[#176B7A] font-semibold mt-1">
                    Terbaca: {formatRupiah(formData.biaya_sewa)} / bulan
                  </p>
                )}
                {fieldErrors.biaya_sewa && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.biaya_sewa[0]}</p>
                )}
              </div>

              {/* Keterangan */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Catatan / Keterangan Kontrak
                </label>
                <textarea
                  rows="3"
                  placeholder="Catatan tambahan seperti klausul sewa beli, nomor SPK, atau PIC vendor..."
                  value={formData.keterangan}
                  onChange={(e) => setFormData({ ...formData, keterangan: e.target.value })}
                  className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                />
              </div>

              {/* Tombol Aksi Form */}
              <div className="pt-4 border-t border-gray-200 flex justify-end space-x-2.5">
                <button
                  type="button"
                  disabled={submitting}
                  onClick={() => setFormModalOpen(false)}
                  className="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 font-bold text-xs transition-colors disabled:opacity-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all disabled:opacity-50"
                >
                  {submitting && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                  <span>{isEditMode ? 'Simpan Perubahan' : 'Tambah Kontrak'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* =========================================================================
          9. DELETE CONFIRMATION MODAL
      ========================================================================== */}
      {showDeleteModal && deleteItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <div className="text-center space-y-1.5">
              <h3 className="text-base font-extrabold text-gray-900">Konfirmasi Hapus Kontrak</h3>
              <p className="text-xs text-[#6B7280]">
                Apakah Anda yakin ingin menghapus kontrak <strong className="text-gray-900">{deleteItem.kode_kontrak}</strong> ({deleteItem.vendor})?
              </p>
            </div>

            <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-950 text-[11px] leading-relaxed">
              <span className="font-bold">Keamanan Data Aset:</span> Menghapus kontrak ini tidak akan menghapus data unit barang fisik. Seluruh aset inventaris yang sebelumnya terikat akan secara otomatis dialihkan statusnya menjadi aset mandiri (Milik Perusahaan).
            </div>

            <div className="flex items-center space-x-2.5 pt-2">
              <button
                type="button"
                disabled={deleting}
                onClick={() => setShowDeleteModal(false)}
                className="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 font-bold text-xs transition-colors disabled:opacity-50"
              >
                Batal
              </button>
              <button
                type="button"
                disabled={deleting}
                onClick={handleConfirmDelete}
                className="flex-1 inline-flex items-center justify-center space-x-1.5 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-colors disabled:opacity-50"
              >
                {deleting && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                <span>Ya, Hapus Kontrak</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* =========================================================================
          10. CONTRACT ITEMS (BARANG DALAM KONTRAK) MODAL
      ========================================================================== */}
      {showItemsModal && selectedKontrak && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC] backdrop-blur-xs z-10">
              <div>
                <div className="flex items-center space-x-2">
                  <span className="font-mono text-xs font-bold text-[#176B7A] bg-white px-2.5 py-1 rounded-lg border border-gray-200">
                    {selectedKontrak.kode_kontrak}
                  </span>
                  <span className="text-xs text-gray-500 font-semibold">• {selectedKontrak.vendor}</span>
                </div>
                <h3 className="text-base font-extrabold text-gray-900 mt-1">
                  Kelola Barang Terikat Kontrak (Contract Items)
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setShowItemsModal(false)}
                className="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="p-6 space-y-6">
              {/* Add Item Section - Only for IT */}
              {user?.role === 'IT' && (
                <div className="bg-gray-50/80 p-4 rounded-2xl border border-gray-200 space-y-3">
                  <h4 className="text-xs font-bold text-gray-900 uppercase tracking-wide">
                    Tambahkan Inventaris ke Kontrak
                  </h4>
                  <div className="flex flex-col sm:flex-row gap-2">
                    <select
                      value={selectedInventarisId}
                      onChange={(e) => setSelectedInventarisId(e.target.value)}
                      className="flex-1 px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                    >
                      <option value="">-- Pilih Barang Inventaris --</option>
                      {inventarisDropdown
                        .filter(inv => !contractItems.some(ci => ci.inventaris_id === (inv.id || inv.inventory_id)))
                        .map((inv) => (
                          <option key={inv.id || inv.inventory_id} value={inv.id || inv.inventory_id}>
                            {inv.kode_inventaris} - {inv.nama_barang} ({inv.merek || 'Tanpa Merek'})
                          </option>
                        ))}
                    </select>
                    <button
                      type="button"
                      disabled={!selectedInventarisId || addingItem}
                      onClick={handleAddContractItem}
                      className="px-4 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#0f4e5a] text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center space-x-1.5 disabled:opacity-50"
                    >
                      {addingItem ? <RefreshCw className="w-3.5 h-3.5 animate-spin" /> : <Plus className="w-4 h-4" />}
                      <span>Hubungkan Barang</span>
                    </button>
                  </div>
                </div>
              )}

              {/* Items Table */}
              <div>
                <div className="flex items-center justify-between mb-2">
                  <h4 className="text-xs font-bold text-gray-900 uppercase tracking-wide">
                    Daftar Barang Terhubung ({contractItems.length} Unit)
                  </h4>
                </div>

                {loadingItems ? (
                  <div className="py-8 text-center text-gray-500 text-xs">
                    <RefreshCw className="w-5 h-5 animate-spin mx-auto text-[#176B7A] mb-2" />
                    Memuat barang kontrak...
                  </div>
                ) : contractItems.length === 0 ? (
                  <div className="p-6 rounded-2xl bg-gray-50 border border-gray-200 text-center text-xs text-gray-500">
                    Belum ada barang inventaris yang terhubung dengan kontrak ini.
                  </div>
                ) : (
                  <div className="rounded-xl border border-gray-200 overflow-hidden">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-[#F8FAFC] text-[#6B7280] text-[10px] uppercase font-bold border-b border-gray-200">
                        <tr>
                          <th className="py-2.5 px-3">Kode Aset</th>
                          <th className="py-2.5 px-3">Nama Barang</th>
                          <th className="py-2.5 px-3">Merek / Model</th>
                          <th className="py-2.5 px-3">Lokasi / PIC</th>
                          <th className="py-2.5 px-3 text-center">Status</th>
                          {user?.role === 'IT' && <th className="py-2.5 px-3 text-center">Aksi</th>}
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {contractItems.map((ci) => {
                          const item = ci.inventaris || {};
                          return (
                            <tr key={ci.id} className="hover:bg-gray-50/80">
                              <td className="py-2.5 px-3 font-mono font-bold text-[#176B7A]">
                                {item.kode_inventaris || '-'}
                              </td>
                              <td className="py-2.5 px-3 font-semibold text-gray-900">
                                {item.nama_barang || '-'}
                              </td>
                              <td className="py-2.5 px-3 text-gray-600">
                                {item.merek || '-'} {item.tipe_model ? `(${item.tipe_model})` : ''}
                              </td>
                              <td className="py-2.5 px-3 text-gray-600">
                                {item.lokasi || '-'} {item.pic ? `• ${item.pic}` : ''}
                              </td>
                              <td className="py-2.5 px-3 text-center">
                                <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                  item.status_aset === 'Ada'
                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                    : 'bg-amber-50 text-amber-700 border border-amber-200'
                                }`}>
                                  {item.status_aset || 'Ada'}
                                </span>
                              </td>
                              {user?.role === 'IT' && (
                                <td className="py-2.5 px-3 text-center">
                                  <button
                                    type="button"
                                    disabled={removingItemId === ci.id}
                                    onClick={() => {
                                      const itemLabel = (ci.nama_barang || item.nama_barang || 'Barang ini') + (ci.kode_inventaris || item.kode_inventaris ? ` (${ci.kode_inventaris || item.kode_inventaris})` : '');
                                      if (window.confirm(`Apakah Anda yakin ingin melepas relasi ${itemLabel} dari kontrak ini?\n\nCatatan: Data fisik inventaris tidak akan dihapus, hanya keterikatan kontrak yang dilepas.`)) {
                                        handleRemoveContractItem(ci.id);
                                      }
                                    }}
                                    className="p-1 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors disabled:opacity-50"
                                    title="Lepas Relasi Barang dari Kontrak"
                                  >
                                    {removingItemId === ci.id ? (
                                      <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                                    ) : (
                                      <Trash2 className="w-3.5 h-3.5" />
                                    )}
                                  </button>
                                </td>
                              )}
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>

            <div className="p-4 border-t border-gray-200 flex justify-end bg-[#F8FAFC] rounded-b-3xl">
              <button
                type="button"
                onClick={() => setShowItemsModal(false)}
                className="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs transition-colors"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
