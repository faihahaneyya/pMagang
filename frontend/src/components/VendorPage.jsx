import React, { useState, useEffect, useCallback } from 'react';
import {
  Building2,
  Plus,
  Edit2,
  Trash2,
  Eye,
  RefreshCw,
  AlertCircle,
  CheckCircle2,
  X,
  Search,
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  Phone,
  Mail,
  MapPin,
  FileText,
} from 'lucide-react';
import {
  getVendorList,
  getVendorDetail,
  createVendor,
  updateVendor,
  deleteVendor,
} from '../api';

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

export default function VendorPage({ user, onSessionExpired }) {
  // ---------- Permissions ----------
  const canWrite =
    user?.role === 'IT' || user?.role === 'KEUANGAN';
  const canDelete = user?.role === 'IT' || user?.role === 'KEUANGAN';

  // ---------- Data & Pagination ----------
  const [vendorList, setVendorList] = useState([]);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // ---------- Filter & Search ----------
  const [searchTerm, setSearchTerm] = useState('');
  const [page, setPage] = useState(1);
  const [perPage] = useState(10);

  // ---------- KPI ----------
  const [kpi, setKpi] = useState({ total: 0, withActiveContract: 0 });

  // ---------- Toast Alert ----------
  const [alert, setAlert] = useState(null); // { type: 'success'|'error', message }

  // ---------- Detail Modal ----------
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [detailItem, setDetailItem] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(false);

  // ---------- Form Modal ----------
  const [formModalOpen, setFormModalOpen] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [editId, setEditId] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const initialForm = { name: '', address: '', phone: '', email: '' };
  const [formData, setFormData] = useState(initialForm);

  // ---------- Delete Modal ----------
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteItem, setDeleteItem] = useState(null);
  const [deleting, setDeleting] = useState(false);

  // Auto-dismiss alert after 4s
  useEffect(() => {
    if (alert) {
      const t = setTimeout(() => setAlert(null), 4000);
      return () => clearTimeout(t);
    }
  }, [alert]);

  // Fetch data when page changes
  useEffect(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, perPage]);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page,
        per_page: perPage,
        ...(searchTerm.trim() ? { search: searchTerm.trim() } : {}),
      };
      const res = await getVendorList(params);
      if (res?.data) {
        setVendorList(res.data);
        if (res.meta) setMeta(res.meta);
        // Derive KPI from response or meta
        const total = res.meta?.total ?? res.data.length;
        const withActive = res.data.filter(
          (v) => v.contracts_count > 0 || v.active_contracts_count > 0
        ).length;
        setKpi({ total, withActiveContract: withActive });
      }
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal memuat data vendor dari server.',
        });
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [page, perPage, searchTerm, onSessionExpired]);

  const handleRefresh = () => {
    setRefreshing(true);
    fetchData();
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    fetchData();
  };

  // ---------- Detail ----------
  const handleOpenDetail = async (item) => {
    setShowDetailModal(true);
    setLoadingDetail(true);
    setDetailItem(item);
    try {
      const res = await getVendorDetail(item.id);
      if (res?.data) setDetailItem(res.data);
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({ type: 'error', message: 'Gagal memuat detail vendor.' });
      }
    } finally {
      setLoadingDetail(false);
    }
  };

  // ---------- Create ----------
  const handleOpenCreate = () => {
    setFormData(initialForm);
    setFieldErrors({});
    setIsEditMode(false);
    setEditId(null);
    setFormModalOpen(true);
  };

  // ---------- Edit ----------
  const handleOpenEdit = (item) => {
    setIsEditMode(true);
    setEditId(item.id);
    setFieldErrors({});
    setFormData({
      name: item.name || '',
      address: item.address || '',
      phone: item.phone || '',
      email: item.email || '',
    });
    setFormModalOpen(true);
  };

  // ---------- Submit Form ----------
  const handleSubmitForm = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});

    // Basic client-side validation
    const errors = {};
    if (!formData.name.trim()) errors.name = ['Nama vendor wajib diisi.'];
    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email))
      errors.email = ['Format email tidak valid.'];
    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setAlert({ type: 'error', message: 'Mohon periksa kembali kolom yang ditandai merah.' });
      setSubmitting(false);
      return;
    }

    try {
      const payload = {
        name: formData.name.trim(),
        address: formData.address.trim() || null,
        phone: formData.phone.trim() || null,
        email: formData.email.trim() || null,
      };

      if (isEditMode) {
        await updateVendor(editId, payload);
        setAlert({ type: 'success', message: `Vendor "${payload.name}" berhasil diperbarui.` });
      } else {
        await createVendor(payload);
        setAlert({ type: 'success', message: `Vendor "${payload.name}" berhasil ditambahkan.` });
      }

      setFormModalOpen(false);
      fetchData();
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else if (err.status === 422 && err.errors) {
        setFieldErrors(err.errors);
        setAlert({ type: 'error', message: 'Terdapat kesalahan pada input formulir. Mohon periksa kembali kolom yang ditandai merah.' });
      } else {
        setAlert({ type: 'error', message: err.message || 'Gagal menyimpan data vendor.' });
      }
    } finally {
      setSubmitting(false);
    }
  };

  // ---------- Delete ----------
  const handleOpenDelete = (item) => {
    setDeleteItem(item);
    setShowDeleteModal(true);
  };

  const handleConfirmDelete = async () => {
    if (!deleteItem) return;
    setDeleting(true);
    try {
      await deleteVendor(deleteItem.id);
      setAlert({ type: 'success', message: `Vendor "${deleteItem.name}" berhasil dihapus.` });
      setShowDeleteModal(false);
      setDeleteItem(null);
      fetchData();
    } catch (err) {
      if (err.status === 401) {
        if (onSessionExpired) onSessionExpired();
      } else {
        setAlert({ type: 'error', message: err.message || 'Gagal menghapus data vendor.' });
      }
    } finally {
      setDeleting(false);
    }
  };

  const hasContracts = (item) =>
    (item.contracts_count ?? item.contracts?.length ?? 0) > 0;

  // ============================================================
  // RENDER
  // ============================================================
  return (
    <div className="space-y-6">

      {/* 1. TOP HEADER & ACTION BAR */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
          <div className="flex items-center space-x-2 text-xs text-[#6B7280] mb-1">
            <span>Dashboard</span>
            <span>/</span>
            <span className="text-[#176B7A] font-semibold">Manajemen Vendor</span>
          </div>
          <h2 className="text-xl font-extrabold text-gray-900 tracking-tight flex items-center space-x-2.5 flex-wrap gap-y-1">
            <Building2 className="w-6 h-6 text-[#176B7A] flex-shrink-0" />
            <span>Manajemen Vendor</span>
            <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20">
              {meta.total} Vendor
            </span>
          </h2>
          <p className="text-xs text-[#6B7280] mt-0.5">
            Kelola data perusahaan atau individu penyedia jasa dan barang
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          {/* Refresh */}
          <button
            type="button"
            onClick={handleRefresh}
            disabled={loading || refreshing}
            className="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50"
            title="Muat Ulang Data"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
          </button>

          {/* Tambah Vendor */}
          {canWrite && (
            <button
              type="button"
              onClick={handleOpenCreate}
              className="flex-1 sm:flex-none inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all duration-200"
            >
              <Plus className="w-4 h-4" />
              <span>Tambah Vendor</span>
            </button>
          )}
        </div>
      </div>

      {/* 2. ALERT TOAST */}
      {alert && (
        <div
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

      {/* 3. KPI CARDS */}
      <div className="grid grid-cols-2 gap-3 sm:gap-4">
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#6B7280]">
            <span>Total Vendor</span>
            <div className="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
              <Building2 className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-gray-900 mt-2">
            {meta.total} <span className="text-xs font-semibold text-[#6B7280]">Vendor</span>
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">Tersimpan di database</div>
        </div>

        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
          <div className="flex items-center justify-between text-xs font-semibold text-[#176B7A]">
            <span>Vendor Berkontrak</span>
            <div className="w-8 h-8 rounded-lg bg-[#176B7A]/10 text-[#176B7A] flex items-center justify-center">
              <FileText className="w-4 h-4" />
            </div>
          </div>
          <div className="text-xl sm:text-2xl font-black text-[#176B7A] mt-2">
            {kpi.withActiveContract} <span className="text-xs font-semibold text-[#176B7A]">Aktif</span>
          </div>
          <div className="text-[11px] text-[#6B7280] mt-1">Memiliki kontrak terikat</div>
        </div>
      </div>

      {/* 4. SEARCH TOOLBAR */}
      <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari nama vendor..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm text-gray-900 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20 transition-all"
            />
            {searchTerm && (
              <button
                type="button"
                onClick={() => { setSearchTerm(''); setPage(1); }}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
          <button
            type="submit"
            className="px-4 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#0f4e5a] text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center space-x-1"
          >
            <span>Cari</span>
          </button>
        </form>
      </div>

      {/* 5. DATA TABLE */}
      <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
              <tr>
                <th className="py-3.5 px-4">Nama Vendor</th>
                <th className="py-3.5 px-4">Kontak</th>
                <th className="py-3.5 px-4">Alamat</th>
                <th className="py-3.5 px-4 text-center">Kontrak</th>
                <th className="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-gray-500">
                    <RefreshCw className="w-6 h-6 animate-spin text-[#176B7A] mx-auto mb-2" />
                    <span>Memuat data vendor...</span>
                  </td>
                </tr>
              ) : vendorList.length === 0 ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-gray-500">
                    <Building2 className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                    <p className="font-semibold text-gray-700">Tidak ada data vendor yang ditemukan.</p>
                    <p className="text-[11px] text-gray-400 mt-0.5">
                      {searchTerm
                        ? 'Coba ubah kata kunci pencarian.'
                        : 'Silakan klik Tambah Vendor untuk menambahkan data baru.'}
                    </p>
                  </td>
                </tr>
              ) : (
                vendorList.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50/80 transition-colors">
                    {/* Nama */}
                    <td className="py-3.5 px-4">
                      <div className="font-bold text-gray-900">{item.name}</div>
                      <div className="text-[10px] text-gray-400">ID #{item.id}</div>
                    </td>

                    {/* Kontak */}
                    <td className="py-3.5 px-4">
                      {item.phone && (
                        <div className="flex items-center space-x-1 text-gray-700">
                          <Phone className="w-3 h-3 text-gray-400" />
                          <span>{item.phone}</span>
                        </div>
                      )}
                      {item.email && (
                        <div className="flex items-center space-x-1 text-gray-500 mt-0.5">
                          <Mail className="w-3 h-3 text-gray-400" />
                          <span className="truncate max-w-[160px]">{item.email}</span>
                        </div>
                      )}
                      {!item.phone && !item.email && (
                        <span className="text-gray-400 italic">-</span>
                      )}
                    </td>

                    {/* Alamat */}
                    <td className="py-3.5 px-4">
                      {item.address ? (
                        <div className="flex items-start space-x-1 text-gray-700 max-w-[200px]">
                          <MapPin className="w-3 h-3 text-gray-400 mt-0.5 flex-shrink-0" />
                          <span className="truncate" title={item.address}>{item.address}</span>
                        </div>
                      ) : (
                        <span className="text-gray-400 italic">-</span>
                      )}
                    </td>

                    {/* Kontrak count */}
                    <td className="py-3.5 px-4 text-center">
                      <span className={`inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg font-bold text-[11px] ${
                        hasContracts(item)
                          ? 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20'
                          : 'bg-gray-100 text-gray-500'
                      }`}>
                        <FileText className="w-3 h-3" />
                        <span>{item.contracts_count ?? item.contracts?.length ?? 0} kontrak</span>
                      </span>
                    </td>

                    {/* Aksi */}
                    <td className="py-3.5 px-4 text-center whitespace-nowrap">
                      <div className="inline-flex items-center space-x-1.5">
                        {/* Detail */}
                        <button
                          type="button"
                          onClick={() => handleOpenDetail(item)}
                          className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                          title="Lihat Detail Vendor"
                        >
                          <Eye className="w-4 h-4" />
                        </button>

                        {/* Edit */}
                        {canWrite && (
                          <button
                            type="button"
                            onClick={() => handleOpenEdit(item)}
                            className="p-1.5 rounded-lg text-gray-600 hover:text-[#F15A24] hover:bg-[#F15A24]/10 transition-colors"
                            title="Ubah Data Vendor"
                          >
                            <Edit2 className="w-4 h-4" />
                          </button>
                        )}

                        {/* Hapus */}
                        {canDelete && (
                          <button
                            type="button"
                            onClick={() => handleOpenDelete(item)}
                            className="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                            title="Hapus Vendor"
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

        {/* 6. PAGINATION */}
        <div className="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6B7280]">
          <div>
            Menampilkan{' '}
            <span className="font-bold text-gray-900">
              {vendorList.length > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0}
            </span>{' '}
            s/d{' '}
            <span className="font-bold text-gray-900">
              {Math.min(meta.current_page * meta.per_page, meta.total)}
            </span>{' '}
            dari <span className="font-bold text-gray-900">{meta.total}</span> vendor
          </div>

          <div className="flex items-center space-x-2">
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

      {/* ====================================================
          7. DETAIL MODAL
      ==================================================== */}
      {showDetailModal && detailItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            {/* Header */}
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC] z-10">
              <div>
                <div className="flex items-center space-x-2">
                  <Building2 className="w-5 h-5 text-[#176B7A]" />
                  <h3 className="text-base font-extrabold text-gray-900">{detailItem.name}</h3>
                </div>
                <p className="text-xs text-[#6B7280] mt-0.5">Detail Informasi Vendor</p>
              </div>
              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Body */}
            <div className="p-6 space-y-5">
              {loadingDetail ? (
                <div className="py-8 text-center text-gray-500">
                  <RefreshCw className="w-6 h-6 animate-spin text-[#176B7A] mx-auto mb-2" />
                  <span>Memuat detail vendor...</span>
                </div>
              ) : (
                <>
                  {/* Info Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/70 p-4 rounded-2xl border border-gray-200 text-xs">
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Nama Vendor:</span>
                      <span className="font-bold text-gray-900">{detailItem.name}</span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Nomor Telepon:</span>
                      <span className="font-bold text-gray-900">
                        {detailItem.phone || <span className="text-gray-400 font-normal">-</span>}
                      </span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Email:</span>
                      <span className="font-bold text-gray-900">
                        {detailItem.email || <span className="text-gray-400 font-normal">-</span>}
                      </span>
                    </div>
                    <div>
                      <span className="text-[#6B7280] text-[11px] block">Terdaftar Sejak:</span>
                      <span className="font-bold text-gray-900">{formatDate(detailItem.created_at)}</span>
                    </div>
                    {detailItem.address && (
                      <div className="sm:col-span-2 pt-2 border-t border-gray-200/80">
                        <span className="text-[#6B7280] text-[11px] block">Alamat:</span>
                        <p className="text-gray-900 mt-0.5">{detailItem.address}</p>
                      </div>
                    )}
                  </div>

                  {/* Kontrak Terkait */}
                  <div>
                    <h4 className="text-xs font-bold text-gray-900 uppercase tracking-wide flex items-center space-x-1.5 mb-2.5">
                      <FileText className="w-4 h-4 text-[#176B7A]" />
                      <span>
                        Kontrak Terkait ({detailItem.contracts ? detailItem.contracts.length : 0})
                      </span>
                    </h4>

                    {detailItem.contracts && detailItem.contracts.length > 0 ? (
                      <div className="rounded-xl border border-gray-200 overflow-hidden">
                        <table className="w-full text-left text-xs">
                          <thead className="bg-[#F8FAFC] text-[#6B7280] text-[10px] uppercase font-bold border-b border-gray-200">
                            <tr>
                              <th className="py-2.5 px-3">No. Kontrak</th>
                              <th className="py-2.5 px-3">Masa Berlaku</th>
                              <th className="py-2.5 px-3 text-center">Status</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-gray-100">
                            {detailItem.contracts.map((c) => (
                              <tr key={c.id || c.kode_kontrak} className="hover:bg-gray-50/80">
                                <td className="py-2.5 px-3 font-mono font-bold text-[#176B7A]">
                                  {c.kode_kontrak || c.id}
                                </td>
                                <td className="py-2.5 px-3 text-gray-700">
                                  {formatDate(c.tanggal_mulai)} s/d {formatDate(c.tanggal_berakhir)}
                                </td>
                                <td className="py-2.5 px-3 text-center">
                                  <span
                                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                      c.status_kontrak === 'AKTIF' || c.status_kontrak === 'Sedang Berjalan'
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : c.status_kontrak === 'AKAN BERAKHIR'
                                        ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                                    }`}
                                  >
                                    {c.status_kontrak || '-'}
                                  </span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <div className="p-4 rounded-xl bg-gray-50 border border-gray-200 text-center text-xs text-gray-500">
                        Vendor ini belum memiliki kontrak yang terdaftar.
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-gray-200 flex justify-end space-x-2 bg-[#F8FAFC] rounded-b-3xl">
              {canWrite && !loadingDetail && (
                <button
                  type="button"
                  onClick={() => {
                    setShowDetailModal(false);
                    handleOpenEdit(detailItem);
                  }}
                  className="px-4 py-2 rounded-xl border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs transition-colors"
                >
                  Ubah Vendor
                </button>
              )}
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

      {/* ====================================================
          8. FORM MODAL (CREATE / EDIT)
      ==================================================== */}
      {formModalOpen && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            {/* Header */}
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC] z-10">
              <div>
                <h3 className="text-base font-extrabold text-gray-900">
                  {isEditMode ? `Ubah Vendor (${formData.name})` : 'Tambah Vendor Baru'}
                </h3>
                <p className="text-xs text-[#6B7280] mt-0.5">
                  {isEditMode ? 'Perbarui informasi vendor' : 'Isi data vendor yang akan didaftarkan'}
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

            {/* Form */}
            <form onSubmit={handleSubmitForm} className="p-6 space-y-4">
              {/* Nama */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Nama Vendor <span className="text-[#F15A24]">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: PT United Tractors Tbk"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                    fieldErrors.name
                      ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                      : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.name && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.name[0]}</p>
                )}
              </div>

              {/* Alamat */}
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">Alamat</label>
                <textarea
                  rows="3"
                  placeholder="Misal: Jl. Raya Bekasi KM 22, Cakung, Jakarta Timur"
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                    fieldErrors.address
                      ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                      : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.address && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.address[0]}</p>
                )}
              </div>

              {/* Phone & Email */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">
                    Nomor Telepon
                  </label>
                  <div className="relative">
                    <Phone className="w-3.5 h-3.5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                      type="text"
                      placeholder="021-1234567"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      className={`w-full pl-9 pr-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                        fieldErrors.phone
                          ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                          : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                      }`}
                    />
                  </div>
                  {fieldErrors.phone && (
                    <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.phone[0]}</p>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Email</label>
                  <div className="relative">
                    <Mail className="w-3.5 h-3.5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                      type="email"
                      placeholder="vendor@contoh.com"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      className={`w-full pl-9 pr-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                        fieldErrors.email
                          ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500 focus:ring-rose-500/20'
                          : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                      }`}
                    />
                  </div>
                  {fieldErrors.email && (
                    <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.email[0]}</p>
                  )}
                </div>
              </div>

              {/* Actions */}
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
                  <span>{isEditMode ? 'Simpan Perubahan' : 'Tambah Vendor'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ====================================================
          9. DELETE CONFIRMATION MODAL
      ==================================================== */}
      {showDeleteModal && deleteItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <div className="text-center space-y-1.5">
              <h3 className="text-base font-extrabold text-gray-900">Konfirmasi Hapus Vendor</h3>
              <p className="text-xs text-[#6B7280]">
                Apakah Anda yakin ingin menghapus vendor{' '}
                <strong className="text-gray-900">"{deleteItem.name}"</strong>?
              </p>
            </div>

            {hasContracts(deleteItem) && (
              <div className="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <span className="font-bold flex items-center space-x-1.5 mb-1">
                  <AlertTriangle className="w-3.5 h-3.5" />
                  <span>Perhatian: Vendor Masih Memiliki Kontrak</span>
                </span>
                Vendor ini memiliki{' '}
                {deleteItem.contracts_count ?? deleteItem.contracts?.length ?? 0} kontrak terkait.
                Pastikan seluruh kontrak telah diselesaikan atau dialihkan sebelum menghapus vendor ini.
              </div>
            )}

            {!hasContracts(deleteItem) && (
              <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-950 text-[11px] leading-relaxed">
                <span className="font-bold">Tindakan ini tidak dapat dibatalkan.</span> Data vendor akan dihapus permanen dari sistem.
              </div>
            )}

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
                className="flex-1 inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-all disabled:opacity-50"
              >
                {deleting && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                <span>Ya, Hapus Vendor</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
