import React, { useState, useEffect, useCallback } from 'react';
import {
  Boxes,
  Plus,
  Search,
  Filter,
  RefreshCw,
  Eye,
  Edit,
  Trash2,
  X,
  AlertCircle,
  CheckCircle2,
  AlertTriangle,
  Upload,
  Image as ImageIcon,
  ExternalLink,
  ChevronLeft,
  ChevronRight,
  ShieldCheck,
  Building2,
  FileText,
  User,
  MapPin,
  Tag,
  Hash,
  Info,
  Printer,
  Download,
  Camera
} from 'lucide-react';
import {
  getInventarisList,
  getInventarisDetail,
  createInventaris,
  updateInventaris,
  deleteInventaris,
  getKontrakList
} from '../api';

const KATEGORI_OPTIONS = [
  'Komputer & Laptop',
  'Printer & Scanner',
  'Furnitur',
  'Jaringan',
  'Elektronik'
];

const STATUS_ASET_OPTIONS = ['Ada', 'Rusak', 'Hilang', 'Tidak Ditemukan'];
const KONDISI_OPTIONS = ['Baik', 'Rusak Ringan', 'Rusak Berat'];

export default function InventarisPage({ onSessionExpired, initialFilterStatusKontrak = '', onOpenScanner }) {
  // State Data & Paginasi
  const [inventaris, setInventaris] = useState([]);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  const [kontrakList, setKontrakList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // State Filter & Pencarian
  const [search, setSearch] = useState('');
  const [filterKategori, setFilterKategori] = useState('');
  const [filterStatusAset, setFilterStatusAset] = useState('');
  const [filterStatusKontrak, setFilterStatusKontrak] = useState(() => initialFilterStatusKontrak || '');
  const [page, setPage] = useState(1);

  useEffect(() => {
    if (initialFilterStatusKontrak) {
      setFilterStatusKontrak(initialFilterStatusKontrak);
      setPage(1);
    }
  }, [initialFilterStatusKontrak]);

  // State Alert Notifikasi
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

  // Form State
  const initialFormState = {
    kode_inventaris: '',
    nama_barang: '',
    kategori: '',
    merek: '',
    tipe_model: '',
    nomor_seri: '',
    spesifikasi: '',
    vendor: '',
    lokasi: '',
    pic: '',
    kondisi: 'Baik',
    status_aset: 'Ada',
    keterangan: '',
    kontrak_id: '',
    foto: null,
    photos: []
  };
  const [formData, setFormData] = useState(initialFormState);
  const [fotoPreview, setFotoPreview] = useState(null);

  // Load Data Inventaris
  const loadInventaris = useCallback(async (isSilent = false) => {
    if (!isSilent) setLoading(true);
    setAlert(null);

    try {
      const params = {
        page,
        per_page: 10,
        search: search.trim() || undefined,
        kategori: filterKategori || undefined,
        status_aset: filterStatusAset || undefined,
        status_kontrak: filterStatusKontrak || undefined
      };

      const res = await getInventarisList(params);
      if (res?.success) {
        setInventaris(res.data || []);
        if (res.meta) {
          setMeta(res.meta);
        }
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal memuat data inventaris. Silakan coba sesaat lagi.'
        });
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [page, search, filterKategori, filterStatusAset, filterStatusKontrak, onSessionExpired]);

  // Load Daftar Kontrak untuk Dropdown Pilihan
  const loadKontrakDropdown = useCallback(async () => {
    try {
      const res = await getKontrakList({ per_page: 100 });
      if (res?.data) {
        setKontrakList(res.data);
      }
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      }
    }
  }, [onSessionExpired]);

  useEffect(() => {
    loadInventaris();
  }, [loadInventaris]);

  useEffect(() => {
    loadKontrakDropdown();
  }, [loadKontrakDropdown]);

  // Manual Refresh Handler
  const handleRefresh = () => {
    setRefreshing(true);
    loadInventaris(true);
  };

  // Reset Filter
  const handleResetFilter = () => {
    setSearch('');
    setFilterKategori('');
    setFilterStatusAset('');
    setFilterStatusKontrak('');
    setPage(1);
  };

  // Open Detail Modal
  const handleOpenDetail = async (item) => {
    setDetailItem(item);
    setShowDetailModal(true);
    setLoadingDetail(true);

    try {
      // Ambil detail lengkap terbaru dari backend (termasuk relasi foto & kontrak)
      const res = await getInventarisDetail(item.id || item.kode_inventaris);
      if (res?.data) {
        setDetailItem(res.data);
      }
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      }
    } finally {
      setLoadingDetail(false);
    }
  };

  // Open Form Create Modal
  const handleOpenCreate = () => {
    setIsEditMode(false);
    setEditId(null);
    setFormData(initialFormState);
    setFotoPreview(null);
    setFieldErrors({});
    setFormModalOpen(true);
  };

  // Open Form Edit Modal
  const handleOpenEdit = async (item) => {
    setIsEditMode(true);
    setEditId(item.id);
    setFieldErrors({});
    setFotoPreview(item.photos?.[0]?.url || (item.foto ? `/storage/${item.foto}` : null));

    setFormData({
      kode_inventaris: item.kode_inventaris || '',
      nama_barang: item.nama_barang || '',
      kategori: item.kategori || '',
      merek: item.merek || '',
      tipe_model: item.tipe_model || '',
      nomor_seri: item.nomor_seri || '',
      spesifikasi: item.spesifikasi || '',
      vendor: item.vendor || '',
      lokasi: item.lokasi || '',
      pic: item.pic || '',
      kondisi: item.kondisi || 'Baik',
      status_aset: item.status_aset || 'Ada',
      keterangan: item.keterangan || '',
      kontrak_id: item.kontrak_id ? String(item.kontrak_id) : '',
      foto: null,
      photos: []
    });

    setFormModalOpen(true);
  };

  // Handle Input Changes in Form
  const handleFormChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (fieldErrors[name]) {
      setFieldErrors((prev) => ({ ...prev, [name]: undefined }));
    }
  };

  // Handle File Input Single Foto
  const handleFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validasi ukuran maksimal 2MB
      if (file.size > 2 * 1024 * 1024) {
        setFieldErrors((prev) => ({
          ...prev,
          foto: ['Ukuran file foto tidak boleh lebih dari 2MB.']
        }));
        return;
      }
      setFormData((prev) => ({ ...prev, foto: file }));
      setFotoPreview(URL.createObjectURL(file));
      setFieldErrors((prev) => ({ ...prev, foto: undefined }));
    }
  };

  // Handle File Input Multiple Photos
  const handleMultiplePhotosChange = (e) => {
    const files = Array.from(e.target.files);
    const validFiles = [];
    let hasSizeError = false;

    files.forEach((file) => {
      if (file.size > 2 * 1024 * 1024) {
        hasSizeError = true;
      } else {
        validFiles.push(file);
      }
    });

    if (hasSizeError) {
      setFieldErrors((prev) => ({
        ...prev,
        photos: ['Beberapa foto melebihi batas 2MB dan diabaikan.']
      }));
    } else {
      setFieldErrors((prev) => ({ ...prev, photos: undefined }));
    }

    setFormData((prev) => ({ ...prev, photos: validFiles }));
  };

  // Submit Form (Create / Update)
  const handleSubmitForm = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});

    // Validasi Sisi Klien Sederhana
    const errors = {};
    if (!formData.kode_inventaris.trim()) {
      errors.kode_inventaris = ['Kode inventaris wajib diisi.'];
    }
    if (!formData.nama_barang.trim()) {
      errors.nama_barang = ['Nama barang wajib diisi.'];
    }
    if (!formData.status_aset) {
      errors.status_aset = ['Status aset wajib dipilih.'];
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setSubmitting(false);
      return;
    }

    // Bangun FormData untuk mendukung unggahan berkas multipart
    const submissionData = new FormData();
    submissionData.append('kode_inventaris', formData.kode_inventaris.trim().toUpperCase());
    submissionData.append('nama_barang', formData.nama_barang.trim());
    if (formData.kategori) submissionData.append('kategori', formData.kategori);
    if (formData.merek) submissionData.append('merek', formData.merek);
    if (formData.tipe_model) submissionData.append('tipe_model', formData.tipe_model);
    if (formData.nomor_seri) submissionData.append('nomor_seri', formData.nomor_seri);
    if (formData.spesifikasi) submissionData.append('spesifikasi', formData.spesifikasi);
    if (formData.vendor) submissionData.append('vendor', formData.vendor);
    if (formData.lokasi) submissionData.append('lokasi', formData.lokasi);
    if (formData.pic) submissionData.append('pic', formData.pic);
    if (formData.kondisi) submissionData.append('kondisi', formData.kondisi);
    if (formData.status_aset) submissionData.append('status_aset', formData.status_aset);
    if (formData.keterangan) submissionData.append('keterangan', formData.keterangan);
    if (formData.kontrak_id) {
      submissionData.append('kontrak_id', formData.kontrak_id);
    }

    // Unggah foto jika dipilih
    if (formData.foto instanceof File) {
      submissionData.append('foto', formData.foto);
    }

    // Unggah multiple photos jika dipilih
    if (formData.photos && formData.photos.length > 0) {
      formData.photos.forEach((file) => {
        submissionData.append('photos[]', file);
      });
    }

    try {
      if (isEditMode) {
        await updateInventaris(editId, submissionData);
        setAlert({
          type: 'success',
          message: `Inventaris ${formData.kode_inventaris} berhasil diperbarui.`
        });
      } else {
        await createInventaris(submissionData);
        setAlert({
          type: 'success',
          message: `Inventaris ${formData.kode_inventaris} berhasil ditambahkan ke sistem.`
        });
      }

      setFormModalOpen(false);
      loadInventaris(true);
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else if (err.status === 422 && err.errors) {
        setFieldErrors(err.errors);
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menyimpan data inventaris ke server backend.'
        });
      }
    } finally {
      setSubmitting(false);
    }
  };

  // Open Delete Confirmation Modal
  const handleOpenDelete = (item) => {
    setDeleteItem(item);
    setShowDeleteModal(true);
  };

  // Execute Delete
  const handleConfirmDelete = async () => {
    if (!deleteItem) return;
    setDeleting(true);

    try {
      await deleteInventaris(deleteItem.id || deleteItem.kode_inventaris);
      setAlert({
        type: 'success',
        message: `Inventaris ${deleteItem.kode_inventaris} - ${deleteItem.nama_barang} berhasil dihapus.`
      });
      setShowDeleteModal(false);
      setDeleteItem(null);
      loadInventaris(true);
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menghapus data inventaris.'
        });
      }
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* 1. HEADER HALAMAN & ACTIONS */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
          <div className="flex items-center space-x-2 text-xs text-[#6B7280] mb-1">
            <span>Dashboard</span>
            <span>/</span>
            <span className="text-[#176B7A] font-semibold">Data Inventaris</span>
          </div>
          <h2 className="text-xl font-extrabold text-gray-900 tracking-tight flex items-center space-x-2.5">
            <Boxes className="w-6 h-6 text-[#176B7A]" />
            <span>Kelola Data Inventaris</span>
            <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20">
              {meta.total} Total Unit
            </span>
          </h2>
          <p className="text-xs text-[#6B7280] mt-0.5">
            Manajemen unit alat berat, elektronik, dan aset operasional PT Banyan Koalindo Lestari
          </p>
        </div>

        <div className="flex items-center flex-wrap gap-2 w-full sm:w-auto">
          <button
            type="button"
            id="btn-refresh-inventaris"
            onClick={handleRefresh}
            disabled={loading || refreshing}
            className="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50"
            title="Muat Ulang Data"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
          </button>

          <a
            href={`/admin/barcode/print-all?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
            target="_blank"
            rel="noopener noreferrer"
            id="btn-print-all-barcode"
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs shadow-2xs transition-colors"
            title="Cetak Semua Label QR Code"
          >
            <Printer className="w-4 h-4 text-[#176B7A]" />
            <span className="hidden md:inline">Cetak Semua QR Code</span>
          </a>

          {onOpenScanner && (
            <button
              type="button"
              onClick={onOpenScanner}
              id="btn-scan-barcode-inventaris"
              className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs shadow-2xs transition-colors"
              title="Pindai Label QR Code Menggunakan Kamera"
            >
              <Camera className="w-4 h-4 text-[#176B7A]" />
              <span className="hidden md:inline">Pindai QR</span>
            </button>
          )}

          <a
            href={`/api/inventaris/export/pdf?${new URLSearchParams({
              ...(search ? { search } : {}),
              ...(filterStatusAset ? { status_aset: filterStatusAset } : {}),
              ...(filterStatusKontrak ? { status_kontrak: filterStatusKontrak } : {}),
              ...(filterKategori ? { kategori: filterKategori } : {}),
            }).toString()}`}
            id="btn-export-inventaris-pdf"
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-2xs transition-colors"
            title="Unduh Laporan Inventaris format PDF"
          >
            <Download className="w-4 h-4 text-[#F15A24]" />
            <span className="hidden xl:inline">Export PDF</span>
          </a>

          <a
            href={`/api/inventaris/export/excel?${new URLSearchParams({
              ...(search ? { search } : {}),
              ...(filterStatusAset ? { status_aset: filterStatusAset } : {}),
              ...(filterStatusKontrak ? { status_kontrak: filterStatusKontrak } : {}),
              ...(filterKategori ? { kategori: filterKategori } : {}),
            }).toString()}`}
            id="btn-export-inventaris-excel"
            className="inline-flex items-center space-x-1.5 px-3 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-slate-50 text-gray-700 hover:text-[#176B7A] font-semibold text-xs shadow-2xs transition-colors"
            title="Unduh Laporan Inventaris format Excel (XLSX)"
          >
            <Download className="w-4 h-4 text-emerald-600" />
            <span className="hidden xl:inline">Export Excel</span>
          </a>

          <button
            type="button"
            id="btn-tambah-inventaris"
            onClick={handleOpenCreate}
            className="flex-1 sm:flex-none inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all duration-200"
          >
            <Plus className="w-4 h-4" />
            <span>Tambah Inventaris</span>
          </button>
        </div>
      </div>

      {/* 2. ALERT NOTIFIKASI TOAST / BANNER */}
      {alert && (
        <div
          id="inventaris-alert-box"
          className={`p-4 rounded-2xl flex items-start space-x-3 text-xs sm:text-sm border shadow-xs animate-fadeIn ${
            alert.type === 'success'
              ? 'bg-emerald-50 border-emerald-200 text-emerald-950'
              : 'bg-rose-50 border-rose-200 text-rose-950'
          }`}
        >
          {alert.type === 'success' ? (
            <CheckCircle2 className="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
          ) : (
            <AlertCircle className="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" />
          )}
          <div className="flex-1 font-medium">{alert.message}</div>
          {alert.type === 'error' && (
            <button
              type="button"
              onClick={() => loadInventaris()}
              className="text-xs font-bold text-rose-700 hover:underline flex-shrink-0 mr-2"
            >
              Coba Lagi
            </button>
          )}
          <button
            type="button"
            onClick={() => setAlert(null)}
            className="text-gray-400 hover:text-gray-600"
          >
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* 3. BAR PENCARIAN & FILTER */}
      <div className="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-xs space-y-3">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
          {/* Input Pencarian */}
          <div className="sm:col-span-2 lg:col-span-5 relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
              <Search className="w-4 h-4" />
            </div>
            <input
              id="filter-search-inventaris"
              type="text"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
              placeholder="Cari kode, nama barang, serial, PIC..."
              className="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-300 text-xs text-gray-900 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            />
          </div>

          {/* Filter Kategori */}
          <div className="lg:col-span-3">
            <select
              id="filter-kategori-inventaris"
              value={filterKategori}
              onChange={(e) => {
                setFilterKategori(e.target.value);
                setPage(1);
              }}
              className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs text-gray-700 bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            >
              <option value="">Semua Kategori</option>
              {KATEGORI_OPTIONS.map((cat) => (
                <option key={cat} value={cat}>
                  {cat}
                </option>
              ))}
            </select>
          </div>

          {/* Filter Status Fisik Aset */}
          <div className="lg:col-span-2">
            <select
              id="filter-status-inventaris"
              value={filterStatusAset}
              onChange={(e) => {
                setFilterStatusAset(e.target.value);
                setPage(1);
              }}
              className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs text-gray-700 bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            >
              <option value="">Semua Status Fisik</option>
              {STATUS_ASET_OPTIONS.map((st) => (
                <option key={st} value={st}>
                  {st}
                </option>
              ))}
            </select>
          </div>

          {/* Filter Status Kontrak */}
          <div className="lg:col-span-2">
            <select
              id="filter-kontrak-inventaris"
              value={filterStatusKontrak}
              onChange={(e) => {
                setFilterStatusKontrak(e.target.value);
                setPage(1);
              }}
              className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs text-gray-700 bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            >
              <option value="">Semua Kontrak</option>
              <option value="AKTIF">Kontrak Aktif</option>
              <option value="AKAN BERAKHIR">Akan Berakhir</option>
              <option value="BERAKHIR">Kontrak Habis</option>
              <option value="-">Tanpa Kontrak (Mandiri)</option>
            </select>
          </div>
        </div>

        {/* Indikator Filter Aktif & Reset */}
        {(search || filterKategori || filterStatusAset || filterStatusKontrak) && (
          <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-[#6B7280]">
            <div className="flex items-center space-x-1.5">
              <Filter className="w-3.5 h-3.5 text-[#176B7A]" />
              <span>Filter aktif diterapkan</span>
            </div>
            <button
              type="button"
              onClick={handleResetFilter}
              className="text-[#F15A24] font-semibold hover:underline"
            >
              Reset Semua Filter
            </button>
          </div>
        )}
      </div>

      {/* 4. TABEL DAFTAR DATA INVENTARIS */}
      <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
              <tr>
                <th className="py-3.5 px-4">Kode</th>
                <th className="py-3.5 px-4">Nama Barang & Detail</th>
                <th className="py-3.5 px-4">Kategori & Lokasi</th>
                <th className="py-3.5 px-4">Status Fisik</th>
                <th className="py-3.5 px-4">Kepemilikan / Kontrak</th>
                <th className="py-3.5 px-4">Foto</th>
                <th className="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-gray-500">
                    <RefreshCw className="w-6 h-6 animate-spin mx-auto text-[#176B7A] mb-2" />
                    <p className="font-medium">Memuat data inventaris...</p>
                  </td>
                </tr>
              ) : alert && alert.type === 'error' && inventaris.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-gray-500">
                    <AlertCircle className="w-8 h-8 mx-auto text-rose-500 mb-2" />
                    <p className="font-bold text-gray-800 text-sm">Gagal Memuat Data Inventaris</p>
                    <p className="text-xs text-rose-600 mt-1 max-w-sm mx-auto">
                      {alert.message || 'Terjadi kesalahan saat memuat data dari server backend.'}
                    </p>
                    <button
                      type="button"
                      id="btn-retry-inventaris"
                      onClick={() => loadInventaris()}
                      className="mt-3 inline-flex items-center space-x-1.5 px-3.5 py-1.5 bg-[#176B7A] hover:bg-[#0f4e5a] text-white text-xs font-semibold rounded-xl shadow-xs transition-colors"
                    >
                      <RefreshCw className="w-3.5 h-3.5" />
                      <span>Coba Lagi</span>
                    </button>
                  </td>
                </tr>
              ) : inventaris.length === 0 ? (
                <tr>
                  <td colSpan="7" className="py-12 text-center text-gray-500">
                    <Boxes className="w-8 h-8 mx-auto text-gray-400 mb-2 opacity-60" />
                    <p className="font-bold text-gray-800 text-sm">Tidak ada data inventaris ditemukan</p>
                    <p className="text-xs text-[#6B7280] mt-1 max-w-sm mx-auto">
                      {search || filterKategori || filterStatusAset || filterStatusKontrak
                        ? 'Coba sesuaikan kata kunci pencarian atau reset filter yang sedang aktif.'
                        : 'Belum ada aset inventaris yang terdaftar di sistem. Klik tombol Tambah Inventaris untuk menambahkan.'}
                    </p>
                  </td>
                </tr>
              ) : (
                inventaris.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50/80 transition-colors">
                    {/* Kode Inventaris */}
                    <td className="py-3 px-4">
                      <button
                        type="button"
                        onClick={() => handleOpenDetail(item)}
                        className="font-mono font-bold text-[#176B7A] hover:text-[#0f4e5a] hover:underline text-left inline-flex items-center space-x-1"
                        title="Klik untuk melihat detail lengkap"
                      >
                        <span>{item.kode_inventaris}</span>
                      </button>
                    </td>

                    {/* Nama Barang & Merek */}
                    <td className="py-3 px-4 max-w-xs">
                      <div className="font-bold text-gray-900 truncate">{item.nama_barang}</div>
                      <div className="text-[11px] text-[#6B7280] truncate">
                        {item.merek || '-'} {item.tipe_model ? `(${item.tipe_model})` : ''}
                      </div>
                    </td>

                    {/* Kategori & Lokasi */}
                    <td className="py-3 px-4">
                      <div className="text-gray-800 font-medium">{item.kategori || '-'}</div>
                      <div className="text-[11px] text-[#6B7280] flex items-center space-x-1">
                        <MapPin className="w-3 h-3 text-gray-400" />
                        <span className="truncate">{item.lokasi || '-'}</span>
                      </div>
                    </td>

                    {/* Status Fisik Aset */}
                    <td className="py-3 px-4">
                      <span
                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ${
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

                    {/* Status Kepemilikan & Kontrak */}
                    <td className="py-3 px-4">
                      <div className="flex items-center space-x-1">
                        <span
                          className={`px-2 py-0.5 rounded-md text-[10px] font-bold ${
                            item.status_kepemilikan === 'SEWA' || item.status_kepemilikan === 'Sewa'
                              ? 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20'
                              : 'bg-slate-100 text-slate-700 border border-slate-200'
                          }`}
                        >
                          {item.status_kepemilikan}
                        </span>
                      </div>
                      {item.kontrak && (
                        <div className="text-[10px] font-mono text-[#176B7A] mt-0.5">
                          {item.kontrak.kode_kontrak}
                        </div>
                      )}
                    </td>

                    {/* Foto Mini Thumbnail */}
                    <td className="py-3 px-4">
                      {item.photos && item.photos.length > 0 ? (
                        <div className="relative group cursor-pointer inline-block" onClick={() => handleOpenDetail(item)}>
                          <img
                            src={item.photos[0].url}
                            alt={item.nama_barang}
                            className="w-9 h-9 rounded-lg object-cover border border-gray-200 shadow-2xs"
                            onError={(e) => {
                              e.target.style.display = 'none';
                            }}
                          />
                          {item.photos.length > 1 && (
                            <span className="absolute -bottom-1 -right-1 px-1 py-0.2 bg-[#176B7A] text-white text-[9px] rounded-full font-bold">
                              +{item.photos.length - 1}
                            </span>
                          )}
                        </div>
                      ) : (
                        <span className="text-[11px] text-gray-400 italic">Tanpa Foto</span>
                      )}
                    </td>

                    {/* Tombol Aksi */}
                    <td className="py-3 px-4 text-center">
                      <div className="inline-flex items-center space-x-1">
                        {/* Detail */}
                        <button
                          type="button"
                          onClick={() => handleOpenDetail(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Lihat Detail Lengkap"
                        >
                          <Eye className="w-4 h-4" />
                        </button>

                        {/* Download QR Code */}
                        <a
                          href={`/admin/barcode/${encodeURIComponent(item.kode_inventaris)}/download?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                          download={`QR-${item.kode_inventaris}.png`}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Download QR Code (PNG)"
                        >
                          <Download className="w-4 h-4" />
                        </a>

                        {/* Cetak QR Code */}
                        <a
                          href={`/admin/barcode/${encodeURIComponent(item.kode_inventaris)}/print?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Cetak Label QR Code"
                        >
                          <Printer className="w-4 h-4" />
                        </a>

                        {/* Edit */}
                        <button
                          type="button"
                          onClick={() => handleOpenEdit(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#F15A24] hover:bg-[#F15A24]/10 transition-colors"
                          title="Ubah Data Inventaris"
                        >
                          <Edit className="w-4 h-4" />
                        </button>

                        {/* Hapus */}
                        <button
                          type="button"
                          onClick={() => handleOpenDelete(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                          title="Hapus Inventaris"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* 5. PAGINATION FOOTER */}
        {meta.total > 0 && (
          <div className="p-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6B7280]">
            <div>
              Menampilkan halaman <strong className="text-gray-900">{meta.current_page}</strong> dari{' '}
              <strong className="text-gray-900">{meta.last_page}</strong> (Total{' '}
              <strong className="text-gray-900">{meta.total}</strong> unit)
            </div>

            <div className="flex items-center space-x-1.5">
              <button
                type="button"
                onClick={() => setPage((p) => Math.max(p - 1, 1))}
                disabled={meta.current_page <= 1 || loading}
                className="px-3 py-1.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center space-x-1 font-medium"
              >
                <ChevronLeft className="w-4 h-4" />
                <span>Sebelumnya</span>
              </button>

              <span className="px-3 py-1.5 font-bold text-[#176B7A] bg-[#176B7A]/10 rounded-xl">
                {meta.current_page}
              </span>

              <button
                type="button"
                onClick={() => setPage((p) => Math.min(p + 1, meta.last_page))}
                disabled={meta.current_page >= meta.last_page || loading}
                className="px-3 py-1.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex items-center space-x-1 font-medium"
              >
                <span>Berikutnya</span>
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ========================================================================= */}
      {/* 6. MODAL DETAIL INVENTARIS LENGKAP                                         */}
      {/* ========================================================================= */}
      {showDetailModal && detailItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-200 max-h-[90vh] flex flex-col overflow-hidden">
            {/* Header Modal */}
            <div className="p-5 border-b border-gray-200 flex items-center justify-between bg-[#F8FAFC]">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-xl bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center font-bold text-base flex-shrink-0">
                  <Boxes className="w-5 h-5" />
                </div>
                <div>
                  <div className="flex items-center space-x-2">
                    <span className="font-mono font-bold text-sm text-[#176B7A] bg-white px-2 py-0.5 rounded border border-gray-200">
                      {detailItem.kode_inventaris}
                    </span>
                    <span
                      className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                        detailItem.status_aset === 'Ada'
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                          : detailItem.status_aset === 'Rusak'
                          ? 'bg-amber-50 text-amber-700 border border-amber-200'
                          : 'bg-rose-50 text-rose-700 border border-rose-200'
                      }`}
                    >
                      {detailItem.status_aset}
                    </span>
                  </div>
                  <h3 className="text-base font-bold text-gray-900 mt-0.5 truncate">
                    {detailItem.nama_barang}
                  </h3>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200/50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Body Modal Detail */}
            <div className="p-6 overflow-y-auto space-y-6 text-xs">
              {loadingDetail && (
                <div className="p-3 bg-[#176B7A]/5 rounded-xl border border-[#176B7A]/20 text-[#176B7A] text-[11px] flex items-center space-x-2">
                  <RefreshCw className="w-3.5 h-3.5 animate-spin text-[#176B7A]" />
                  <span>Memperbarui detail terkini dari server...</span>
                </div>
              )}

              {/* Grid Informasi Identifikasi */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/70 p-4 rounded-2xl border border-gray-200/70">
                <div>
                  <span className="text-[#6B7280]">Kategori:</span>
                  <p className="font-bold text-gray-900">{detailItem.kategori || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Merek & Tipe Model:</span>
                  <p className="font-bold text-gray-900">
                    {detailItem.merek || '-'} {detailItem.tipe_model ? `(${detailItem.tipe_model})` : ''}
                  </p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Nomor Seri (SN):</span>
                  <p className="font-mono font-bold text-gray-800">{detailItem.nomor_seri || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Kondisi Fisik:</span>
                  <p className="font-bold text-gray-900">{detailItem.kondisi || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Lokasi Penempatan:</span>
                  <p className="font-bold text-gray-900">{detailItem.lokasi || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">PIC Penanggung Jawab:</span>
                  <p className="font-bold text-gray-900">{detailItem.pic || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Vendor Penyedia:</span>
                  <p className="font-bold text-gray-900">{detailItem.vendor || '-'}</p>
                </div>
                <div>
                  <span className="text-[#6B7280]">Status Kepemilikan:</span>
                  <p className="font-bold text-[#176B7A]">{detailItem.status_kepemilikan || '-'}</p>
                </div>
              </div>

              {/* Status Kontrak Sewa (jika ada) */}
              <div className="p-4 rounded-2xl border border-gray-200 space-y-2">
                <h4 className="font-bold text-gray-900 text-xs flex items-center space-x-1.5">
                  <FileText className="w-4 h-4 text-[#176B7A]" />
                  <span>Keterikatan Kontrak Sewa Beli</span>
                </h4>
                {detailItem.kontrak ? (
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-xl text-[11px]">
                    <div>
                      <span className="text-[#6B7280]">Kode Kontrak:</span>
                      <p className="font-mono font-bold text-gray-900">{detailItem.kontrak.kode_kontrak}</p>
                    </div>
                    <div>
                      <span className="text-[#6B7280]">Vendor Kontrak:</span>
                      <p className="font-semibold text-gray-900">{detailItem.kontrak.vendor}</p>
                    </div>
                    <div>
                      <span className="text-[#6B7280]">Sisa Masa Berlaku:</span>
                      <p className="font-bold text-[#F15A24]">
                        {detailItem.kontrak.sisa_kontrak || `${detailItem.kontrak.sisa_hari} hari`}
                      </p>
                    </div>
                  </div>
                ) : (
                  <p className="text-[11px] text-[#6B7280] italic">
                    Aset ini merupakan milik mandiri perusahaan (tidak terikat kontrak sewa beli).
                  </p>
                )}
              </div>

              {/* Spesifikasi & Keterangan */}
              {(detailItem.spesifikasi || detailItem.keterangan) && (
                <div className="space-y-3">
                  {detailItem.spesifikasi && (
                    <div className="p-3 rounded-xl bg-gray-50 border border-gray-200">
                      <span className="text-[11px] font-bold text-[#6B7280] block mb-1">
                        Spesifikasi Teknis:
                      </span>
                      <p className="text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                        {detailItem.spesifikasi}
                      </p>
                    </div>
                  )}

                  {detailItem.keterangan && (
                    <div className="p-3 rounded-xl bg-gray-50 border border-gray-200">
                      <span className="text-[11px] font-bold text-[#6B7280] block mb-1">
                        Keterangan Operasional:
                      </span>
                      <p className="text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                        {detailItem.keterangan}
                      </p>
                    </div>
                  )}
                </div>
              )}

              {/* Card Label QR Code Aset */}
              <div className="p-4 rounded-2xl border border-gray-200 bg-gray-50/70 flex flex-col sm:flex-row items-center gap-4">
                <div className="bg-white p-2 rounded-xl border border-dashed border-gray-300 shadow-2xs flex-shrink-0">
                  <img
                    src={`/admin/barcode/${encodeURIComponent(detailItem.kode_inventaris)}/download?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                    alt={`QR Code ${detailItem.kode_inventaris}`}
                    className="w-24 h-24 object-contain"
                  />
                </div>
                <div className="flex-1 text-center sm:text-left space-y-1.5">
                  <div className="flex items-center justify-center sm:justify-start space-x-2">
                    <span className="font-mono font-bold text-xs text-gray-900 bg-white px-2 py-0.5 rounded border border-gray-200">
                      {detailItem.kode_inventaris}
                    </span>
                    <span className="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                      Label QR Code (2D)
                    </span>
                  </div>
                  <p className="text-[11px] text-gray-600 leading-relaxed">
                    Label QR Code aset resmi PT Banyan Koalindo Lestari. Unduh file gambar PNG untuk disimpan di komputer atau dicetak kembali kapan saja.
                  </p>
                  <div className="pt-1 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <a
                      href={`/admin/barcode/${encodeURIComponent(detailItem.kode_inventaris)}/download?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                      download={`QR-${detailItem.kode_inventaris}.png`}
                      className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs transition-colors"
                      title={`Download QR Code ${detailItem.kode_inventaris} (PNG)`}
                    >
                      <Download className="w-3.5 h-3.5 text-gray-500" />
                      <span>Download QR Code</span>
                    </a>
                    <a
                      href={`/admin/barcode/${encodeURIComponent(detailItem.kode_inventaris)}/print?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs transition-colors"
                    >
                      <Printer className="w-3.5 h-3.5 text-gray-500" />
                      <span>Cetak Label QR Code</span>
                    </a>
                  </div>
                </div>
              </div>

              {/* Galeri Foto Aset */}
              <div>
                <h4 className="font-bold text-gray-900 text-xs mb-2 flex items-center space-x-1.5">
                  <ImageIcon className="w-4 h-4 text-[#176B7A]" />
                  <span>Foto Dokumentasi Aset</span>
                </h4>
                {detailItem.photos && detailItem.photos.length > 0 ? (
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {detailItem.photos.map((p, idx) => (
                      <div key={idx} className="relative group rounded-xl overflow-hidden border border-gray-200 shadow-2xs">
                        <img
                          src={p.url}
                          alt={p.caption || `Foto ${idx + 1}`}
                          className="w-full h-28 object-cover group-hover:scale-105 transition-transform duration-200"
                        />
                        <div className="p-1.5 bg-white text-[10px] text-gray-600 truncate border-t border-gray-100">
                          {p.caption || `Foto ${idx + 1}`}
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="p-4 rounded-xl bg-gray-50 border border-gray-200 text-center text-[11px] text-[#6B7280] italic">
                    Belum ada foto dokumentasi fisik yang diunggah untuk aset ini.
                  </div>
                )}
              </div>
            </div>

            {/* Footer Modal Detail */}
            <div className="p-4 border-t border-gray-200 bg-[#F8FAFC] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
              <div className="flex items-center flex-wrap gap-2">
                {detailItem.barcode_url && (
                  <a
                    href={detailItem.barcode_url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center space-x-1 text-xs font-semibold text-[#176B7A] hover:underline mr-1"
                  >
                    <ExternalLink className="w-3.5 h-3.5" />
                    <span>Buka Halaman Scan Publik</span>
                  </a>
                )}
                <a
                  href={`/admin/barcode/${encodeURIComponent(detailItem.kode_inventaris)}/download?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                  download={`QR-${detailItem.kode_inventaris}.png`}
                  id="btn-download-qr-detail"
                  className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs transition-colors"
                  title={`Download QR Code ${detailItem.kode_inventaris} (PNG)`}
                >
                  <Download className="w-3.5 h-3.5 text-gray-500" />
                  <span>Download QR Code</span>
                </a>
                <a
                  href={`/admin/barcode/${encodeURIComponent(detailItem.kode_inventaris)}/print?frontend_url=${encodeURIComponent(typeof window !== 'undefined' ? window.location.origin : '')}`}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs transition-colors"
                >
                  <Printer className="w-3.5 h-3.5 text-gray-500" />
                  <span>Cetak Label QR Code</span>
                </a>
              </div>

              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-xs transition-colors text-center"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* 7. MODAL FORM TAMBAH / EDIT INVENTARIS                                     */}
      {/* ========================================================================= */}
      {formModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-gray-200 max-h-[92vh] flex flex-col overflow-hidden">
            {/* Header Modal Form */}
            <div className="p-5 border-b border-gray-200 flex items-center justify-between bg-[#F8FAFC]">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-xl bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center font-bold text-base flex-shrink-0">
                  {isEditMode ? <Edit className="w-5 h-5" /> : <Plus className="w-5 h-5" />}
                </div>
                <div>
                  <h3 className="text-base font-bold text-gray-900">
                    {isEditMode ? 'Ubah Data Inventaris' : 'Tambah Aset Inventaris Baru'}
                  </h3>
                  <p className="text-xs text-[#6B7280]">
                    {isEditMode
                      ? `Perbarui informasi inventaris untuk ${formData.kode_inventaris}`
                      : 'Masukkan rincian spesifikasi aset sesuai data fisik di lapangan'}
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setFormModalOpen(false)}
                disabled={submitting}
                className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200/50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Body Form */}
            <form onSubmit={handleSubmitForm} className="flex-1 overflow-y-auto p-6 space-y-4 text-xs" noValidate>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Kode Inventaris */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">
                    Kode Inventaris <span className="text-[#F15A24]">*</span>
                  </label>
                  <input
                    type="text"
                    name="kode_inventaris"
                    value={formData.kode_inventaris}
                    onChange={handleFormChange}
                    placeholder="Contoh: INV-IT-016"
                    disabled={submitting}
                    className={`w-full px-3 py-2 rounded-xl border text-xs font-mono uppercase focus:outline-none focus:ring-2 ${
                      fieldErrors.kode_inventaris
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  />
                  {fieldErrors.kode_inventaris && (
                    <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.kode_inventaris[0]}</p>
                  )}
                </div>

                {/* Nama Barang */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">
                    Nama Barang <span className="text-[#F15A24]">*</span>
                  </label>
                  <input
                    type="text"
                    name="nama_barang"
                    value={formData.nama_barang}
                    onChange={handleFormChange}
                    placeholder="Contoh: Laptop Dell Latitude 5420"
                    disabled={submitting}
                    className={`w-full px-3 py-2 rounded-xl border text-xs focus:outline-none focus:ring-2 ${
                      fieldErrors.nama_barang
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  />
                  {fieldErrors.nama_barang && (
                    <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.nama_barang[0]}</p>
                  )}
                </div>

                {/* Kategori */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Kategori</label>
                  <select
                    name="kategori"
                    value={formData.kategori}
                    onChange={handleFormChange}
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  >
                    <option value="">Pilih Kategori...</option>
                    {KATEGORI_OPTIONS.map((cat) => (
                      <option key={cat} value={cat}>
                        {cat}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Merek */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Merek</label>
                  <input
                    type="text"
                    name="merek"
                    value={formData.merek}
                    onChange={handleFormChange}
                    placeholder="Contoh: Dell, Komatsu, Epson"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* Tipe / Model */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Tipe / Model</label>
                  <input
                    type="text"
                    name="tipe_model"
                    value={formData.tipe_model}
                    onChange={handleFormChange}
                    placeholder="Contoh: Latitude 5420, PC200-8"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* Nomor Seri */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Nomor Seri (Serial Number)</label>
                  <input
                    type="text"
                    name="nomor_seri"
                    value={formData.nomor_seri}
                    onChange={handleFormChange}
                    placeholder="Contoh: SN-8921-99214"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs font-mono focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* Lokasi */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Lokasi Penempatan</label>
                  <input
                    type="text"
                    name="lokasi"
                    value={formData.lokasi}
                    onChange={handleFormChange}
                    placeholder="Contoh: Kantor Utama, Ruang Server, Pit Tambang"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* PIC */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">PIC Penanggung Jawab</label>
                  <input
                    type="text"
                    name="pic"
                    value={formData.pic}
                    onChange={handleFormChange}
                    placeholder="Contoh: Budi Santoso"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* Vendor Penyedia */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Vendor Penyedia</label>
                  <input
                    type="text"
                    name="vendor"
                    value={formData.vendor}
                    onChange={handleFormChange}
                    placeholder="Contoh: PT United Tractors Tbk"
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  />
                </div>

                {/* Kontrak Terikat */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Keterikatan Kontrak Sewa Beli</label>
                  <select
                    name="kontrak_id"
                    value={formData.kontrak_id}
                    onChange={handleFormChange}
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  >
                    <option value="">Tanpa Kontrak (Aset Milik Mandiri Perusahaan)</option>
                    {kontrakList.map((k) => (
                      <option key={k.id} value={k.id}>
                        {k.kode_kontrak} — {k.vendor}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Kondisi Fisik */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">Kondisi Fisik</label>
                  <select
                    name="kondisi"
                    value={formData.kondisi}
                    onChange={handleFormChange}
                    disabled={submitting}
                    className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                  >
                    {KONDISI_OPTIONS.map((kd) => (
                      <option key={kd} value={kd}>
                        {kd}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Status Aset */}
                <div className="space-y-1">
                  <label className="block font-bold text-gray-700">
                    Status Aset <span className="text-[#F15A24]">*</span>
                  </label>
                  <select
                    name="status_aset"
                    value={formData.status_aset}
                    onChange={handleFormChange}
                    disabled={submitting}
                    className={`w-full px-3 py-2 rounded-xl border text-xs bg-white focus:outline-none focus:ring-2 ${
                      fieldErrors.status_aset
                        ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                        : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                    }`}
                  >
                    {STATUS_ASET_OPTIONS.map((st) => (
                      <option key={st} value={st}>
                        {st}
                      </option>
                    ))}
                  </select>
                  {fieldErrors.status_aset && (
                    <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.status_aset[0]}</p>
                  )}
                </div>
              </div>

              {/* Spesifikasi Teknis */}
              <div className="space-y-1">
                <label className="block font-bold text-gray-700">Spesifikasi Teknis</label>
                <textarea
                  name="spesifikasi"
                  rows={2}
                  value={formData.spesifikasi}
                  onChange={handleFormChange}
                  placeholder="Contoh: RAM 16GB, SSD 512GB, Processor Intel i7-1185G7"
                  disabled={submitting}
                  className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                />
              </div>

              {/* Keterangan */}
              <div className="space-y-1">
                <label className="block font-bold text-gray-700">Keterangan Tambahan</label>
                <textarea
                  name="keterangan"
                  rows={2}
                  value={formData.keterangan}
                  onChange={handleFormChange}
                  placeholder="Contoh: Unit beroperasi normal, pemeliharaan berkala tiap bulan"
                  disabled={submitting}
                  className="w-full px-3 py-2 rounded-xl border border-gray-300 text-xs focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                />
              </div>

              {/* Unggah Foto Utama */}
              <div className="p-4 rounded-2xl border border-dashed border-gray-300 bg-gray-50/60 space-y-3">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-bold text-gray-800 text-xs block">Foto Utama Inventaris</span>
                    <span className="text-[11px] text-[#6B7280]">
                      Format: JPG, JPEG, PNG, WEBP (Maksimal 2MB)
                    </span>
                  </div>
                  {fotoPreview && (
                    <img
                      src={fotoPreview}
                      alt="Preview"
                      className="w-12 h-12 rounded-lg object-cover border border-gray-200 shadow-2xs"
                    />
                  )}
                </div>

                <input
                  type="file"
                  id="input-foto-inventaris"
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  onChange={handleFotoChange}
                  disabled={submitting}
                  className="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#176B7A]/10 file:text-[#176B7A] hover:file:bg-[#176B7A]/20 cursor-pointer"
                />

                {isEditMode && (
                  <p className="text-[10px] text-gray-500 italic">
                    Catatan: Jika tidak memilih foto baru, foto yang sudah ada di sistem tidak akan diubah atau dihapus.
                  </p>
                )}

                {fieldErrors.foto && (
                  <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.foto[0]}</p>
                )}
              </div>

              {/* Unggah Multiple Photos */}
              <div className="p-4 rounded-2xl border border-dashed border-gray-300 bg-gray-50/60 space-y-2">
                <div>
                  <span className="font-bold text-gray-800 text-xs block">
                    Foto Lampiran Tambahan (Opsional)
                  </span>
                  <span className="text-[11px] text-[#6B7280]">
                    Pilih beberapa foto dokumentasi sekaligus (Maksimal 2MB per file)
                  </span>
                </div>

                <input
                  type="file"
                  id="input-multiple-photos"
                  multiple
                  accept="image/jpeg,image/png,image/jpg,image/webp"
                  onChange={handleMultiplePhotosChange}
                  disabled={submitting}
                  className="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#176B7A]/10 file:text-[#176B7A] hover:file:bg-[#176B7A]/20 cursor-pointer"
                />

                {formData.photos && formData.photos.length > 0 && (
                  <p className="text-[11px] font-semibold text-[#176B7A]">
                    {formData.photos.length} file foto tambahan dipilih untuk diunggah.
                  </p>
                )}

                {fieldErrors.photos && (
                  <p className="text-[11px] text-rose-600 font-medium">{fieldErrors.photos[0]}</p>
                )}
              </div>

              {/* Tombol Aksi Form */}
              <div className="pt-3 border-t border-gray-200 flex items-center justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setFormModalOpen(false)}
                  disabled={submitting}
                  className="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-100 transition-colors"
                >
                  Batal
                </button>

                <button
                  type="submit"
                  id="btn-submit-inventaris-form"
                  disabled={submitting}
                  className="px-5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all flex items-center space-x-2 disabled:opacity-60"
                >
                  {submitting ? (
                    <>
                      <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                      <span>Menyimpan ke Server...</span>
                    </>
                  ) : (
                    <span>{isEditMode ? 'Simpan Perubahan' : 'Tambah Inventaris'}</span>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* 8. MODAL KONFIRMASI HAPUS INVENTARIS                                       */}
      {/* ========================================================================= */}
      {showDeleteModal && deleteItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-gray-200 p-6 space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <div>
              <h3 className="text-base font-bold text-gray-900">Konfirmasi Hapus Inventaris</h3>
              <p className="text-xs text-[#6B7280] mt-1 leading-relaxed">
                Apakah Anda yakin ingin menghapus data inventaris{' '}
                <strong className="text-gray-900">
                  {deleteItem.kode_inventaris} — {deleteItem.nama_barang}
                </strong>
                ? Seluruh berkas foto dan relasi aset ini akan dihapus secara permanen dari server.
              </p>
            </div>

            <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-[11px] text-rose-950">
              Peringatan: Tindakan ini tidak dapat dibatalkan.
            </div>

            <div className="pt-2 flex items-center justify-end space-x-2">
              <button
                type="button"
                onClick={() => setShowDeleteModal(false)}
                disabled={deleting}
                className="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-100 transition-colors"
              >
                Batal
              </button>

              <button
                type="button"
                id="btn-confirm-delete-inventaris"
                onClick={handleConfirmDelete}
                disabled={deleting}
                className="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-colors flex items-center space-x-2 disabled:opacity-60"
              >
                {deleting ? (
                  <>
                    <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                    <span>Menghapus...</span>
                  </>
                ) : (
                  <span>Ya, Hapus Permanen</span>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
