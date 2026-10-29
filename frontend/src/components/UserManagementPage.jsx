import React, { useState, useEffect, useCallback } from 'react';
import {
  Users,
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
  Shield,
  Mail,
  User as UserIcon,
  Lock,
  KeyRound
} from 'lucide-react';
import {
  getUserList,
  getUserDetail,
  createUser,
  updateUser,
  deleteUser
} from '../api';

const roleBadge = {
  IT: 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20',
  KEUANGAN: 'bg-blue-50 text-blue-700 border border-blue-200',
  USER: 'bg-gray-100 text-gray-700 border border-gray-200'
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

export default function UserManagementPage({ user, onSessionExpired }) {
  const [userList, setUserList] = useState([]);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Filters
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [page, setPage] = useState(1);

  // Alert
  const [alert, setAlert] = useState(null);

  // Modals
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [detailItem, setDetailItem] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(false);

  const [formModalOpen, setFormModalOpen] = useState(false);
  const [isEditMode, setIsEditMode] = useState(false);
  const [editId, setEditId] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const [deleteItem, setDeleteItem] = useState(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const initialForm = {
    name: '',
    email: '',
    role: 'USER',
    password: ''
  };
  const [formData, setFormData] = useState(initialForm);

  useEffect(() => {
    if (alert) {
      const timer = setTimeout(() => setAlert(null), 4000);
      return () => clearTimeout(timer);
    }
  }, [alert]);

  const loadUsers = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page,
        per_page: 10,
        ...(search.trim() ? { search: search.trim() } : {}),
        ...(roleFilter ? { role: roleFilter } : {})
      };
      const res = await getUserList(params);
      if (res?.data) {
        setUserList(res.data);
        if (res.meta) setMeta(res.meta);
      }
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal memuat data pengguna.'
        });
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [page, search, roleFilter, onSessionExpired]);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const handleRefresh = () => {
    setRefreshing(true);
    loadUsers();
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setPage(1);
    loadUsers();
  };

  const handleOpenCreate = () => {
    setFormData(initialForm);
    setFieldErrors({});
    setIsEditMode(false);
    setEditId(null);
    setFormModalOpen(true);
  };

  const handleOpenEdit = (item) => {
    setIsEditMode(true);
    setEditId(item.id || item.user_id);
    setFieldErrors({});
    setFormData({
      name: item.name || '',
      email: item.email || '',
      role: item.role || 'USER',
      password: ''
    });
    setFormModalOpen(true);
  };

  const handleOpenDetail = async (item) => {
    setShowDetailModal(true);
    setLoadingDetail(true);
    setDetailItem(item);
    try {
      const res = await getUserDetail(item.id || item.user_id);
      if (res?.data) {
        setDetailItem(res.data);
      }
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      } else {
        setAlert({ type: 'error', message: 'Gagal memuat detail akun pengguna.' });
      }
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleOpenDelete = (item) => {
    setDeleteItem(item);
    setShowDeleteModal(true);
  };

  const handleSubmitForm = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFieldErrors({});

    try {
      const payload = {
        name: formData.name.trim(),
        email: formData.email.trim(),
        role: formData.role
      };

      if (!isEditMode) {
        payload.password = formData.password;
      } else if (formData.password.trim()) {
        payload.password = formData.password;
      }

      if (isEditMode) {
        await updateUser(editId, payload);
        setAlert({
          type: 'success',
          message: `Akun ${payload.name} berhasil diperbarui.`
        });
      } else {
        await createUser(payload);
        setAlert({
          type: 'success',
          message: `Akun ${payload.name} (${payload.role}) berhasil dibuat.`
        });
      }

      setFormModalOpen(false);
      loadUsers();
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      } else if (err.status === 422 && err.errors) {
        setFieldErrors(err.errors);
        setAlert({
          type: 'error',
          message: 'Terdapat kesalahan pada input pengguna. Periksa kolom yang ditandai.'
        });
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menyimpan akun pengguna.'
        });
      }
    } finally {
      setSubmitting(false);
    }
  };

  const handleConfirmDelete = async () => {
    if (!deleteItem) return;
    setDeleting(true);
    try {
      await deleteUser(deleteItem.id || deleteItem.user_id);
      setAlert({
        type: 'success',
        message: `Akun ${deleteItem.name} berhasil dihapus.`
      });
      setShowDeleteModal(false);
      setDeleteItem(null);
      loadUsers();
    } catch (err) {
      if (err.status === 401 && onSessionExpired) {
        onSessionExpired();
      } else {
        setAlert({
          type: 'error',
          message: err.message || 'Gagal menghapus akun pengguna.'
        });
      }
    } finally {
      setDeleting(false);
    }
  };

  // Guard: Halaman ini hanya boleh diakses role IT
  if (user?.role !== 'IT') {
    return (
      <div className="p-8 text-center bg-white rounded-2xl border border-gray-200">
        <AlertTriangle className="w-12 h-12 text-[#F15A24] mx-auto mb-3" />
        <h3 className="text-lg font-bold text-gray-900">Akses Terbatas</h3>
        <p className="text-xs text-gray-500 mt-1">
          Halaman Manajemen Pengguna hanya dapat diakses oleh Administrator IT.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <div>
          <div className="flex items-center space-x-2 text-xs text-[#6B7280] mb-1">
            <span>Dashboard</span>
            <span>/</span>
            <span className="text-[#176B7A] font-semibold">Manajemen Pengguna</span>
          </div>
          <h2 className="text-xl font-extrabold text-gray-900 tracking-tight flex items-center space-x-2.5">
            <Users className="w-6 h-6 text-[#176B7A] flex-shrink-0" />
            <span>Manajemen Akun Pengguna</span>
            <span className="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20">
              {meta.total} Pengguna
            </span>
          </h2>
          <p className="text-xs text-[#6B7280] mt-0.5">
            Kelola hak akses role (IT, KEUANGAN, USER) dan akun pengguna sistem
          </p>
        </div>

        <div className="flex items-center gap-2 w-full sm:w-auto">
          <button
            type="button"
            onClick={handleRefresh}
            disabled={loading || refreshing}
            className="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50"
            title="Muat Ulang Data"
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin text-[#176B7A]' : ''}`} />
          </button>

          <button
            type="button"
            onClick={handleOpenCreate}
            className="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs transition-all"
          >
            <Plus className="w-4 h-4" />
            <span>Tambah Pengguna</span>
          </button>
        </div>
      </div>

      {/* Alert */}
      {alert && (
        <div
          className={`p-4 rounded-2xl border text-xs sm:text-sm flex items-start justify-between shadow-xs ${
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
          <button type="button" onClick={() => setAlert(null)} className="text-gray-400 hover:text-gray-600 p-1">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Search & Filter Toolbar */}
      <div className="bg-white p-4 sm:p-5 rounded-2xl border border-gray-200/80 shadow-xs">
        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Cari nama atau email pengguna..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
            />
            {search && (
              <button
                type="button"
                onClick={() => { setSearch(''); setPage(1); }}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>

          <div className="w-full sm:w-48">
            <select
              value={roleFilter}
              onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
              className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm bg-white text-gray-700 focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
            >
              <option value="">Semua Role</option>
              <option value="IT">Role: IT</option>
              <option value="KEUANGAN">Role: KEUANGAN</option>
              <option value="USER">Role: USER</option>
            </select>
          </div>

          <button
            type="submit"
            className="px-4 py-2.5 rounded-xl bg-[#176B7A] hover:bg-[#0f4e5a] text-white font-bold text-xs shadow-xs transition-colors"
          >
            Terapkan
          </button>
        </form>
      </div>

      {/* Table */}
      <div className="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-[#F8FAFC] text-[#6B7280] uppercase text-[10px] font-bold tracking-wider border-b border-gray-200">
              <tr>
                <th className="py-3.5 px-4">Nama Pengguna</th>
                <th className="py-3.5 px-4">Email</th>
                <th className="py-3.5 px-4 text-center">Hak Akses (Role)</th>
                <th className="py-3.5 px-4">Terdaftar Sejak</th>
                <th className="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-gray-500">
                    <RefreshCw className="w-6 h-6 animate-spin text-[#176B7A] mx-auto mb-2" />
                    Memuat data akun pengguna...
                  </td>
                </tr>
              ) : userList.length === 0 ? (
                <tr>
                  <td colSpan="5" className="py-12 text-center text-gray-500">
                    <Users className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                    <p className="font-semibold text-gray-700">Tidak ada pengguna ditemukan.</p>
                  </td>
                </tr>
              ) : (
                userList.map((item) => {
                  const isCurrent = (user?.id && item.id && user.id === item.id) || (user?.email && item.email && user.email === item.email);
                  return (
                    <tr key={item.id || item.user_id} className="hover:bg-gray-50/80 transition-colors">
                      <td className="py-3.5 px-4">
                        <div className="font-bold text-gray-900 flex items-center space-x-1.5">
                          <span>{item.name}</span>
                          {isCurrent && (
                            <span className="px-1.5 py-0.2 rounded text-[9px] font-bold bg-[#176B7A]/10 text-[#176B7A]">
                              (Anda)
                            </span>
                          )}
                        </div>
                        <div className="text-[10px] text-gray-400 font-mono">ID: #{item.id || item.user_id}</div>
                      </td>
                      <td className="py-3.5 px-4 text-gray-700 font-mono text-[11px]">
                        {item.email}
                      </td>
                      <td className="py-3.5 px-4 text-center">
                        <span
                          className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                            roleBadge[item.role] || 'bg-gray-100 text-gray-700'
                          }`}
                        >
                          {item.role}
                        </span>
                      </td>
                      <td className="py-3.5 px-4 text-gray-600">
                        {formatDate(item.created_at)}
                      </td>
                      <td className="py-3.5 px-4 text-center whitespace-nowrap">
                        <div className="inline-flex items-center space-x-1.5">
                          <button
                            type="button"
                            onClick={() => handleOpenDetail(item)}
                            className="p-1.5 rounded-lg text-gray-600 hover:text-[#176B7A] hover:bg-[#176B7A]/10 transition-colors"
                            title="Detail"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleOpenEdit(item)}
                            className="p-1.5 rounded-lg text-gray-600 hover:text-[#F15A24] hover:bg-[#F15A24]/10 transition-colors"
                            title="Ubah Role / Info"
                          >
                            <Edit2 className="w-4 h-4" />
                          </button>
                          {!isCurrent && (
                            <button
                              type="button"
                              onClick={() => handleOpenDelete(item)}
                              className="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                              title="Hapus Akun"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6B7280]">
          <div>
            Menampilkan <span className="font-bold text-gray-900">{userList.length > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0}</span> s/d{' '}
            <span className="font-bold text-gray-900">{Math.min(meta.current_page * meta.per_page, meta.total)}</span> dari{' '}
            <span className="font-bold text-gray-900">{meta.total}</span> data
          </div>
          <div className="flex items-center space-x-2">
            <button
              type="button"
              disabled={meta.current_page <= 1 || loading}
              onClick={() => setPage((p) => Math.max(p - 1, 1))}
              className="p-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40"
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
              className="p-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Detail Modal */}
      {showDetailModal && detailItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-4">
            <div className="flex items-center justify-between border-b pb-3 border-gray-100">
              <h3 className="text-base font-extrabold text-gray-900">Detail Akun Pengguna</h3>
              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="p-1.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3 bg-gray-50/80 p-4 rounded-2xl border border-gray-200 text-xs">
              <div>
                <span className="text-[#6B7280] text-[11px] block">Nama Lengkap:</span>
                <span className="font-bold text-gray-900 text-sm">{detailItem.name}</span>
              </div>
              <div>
                <span className="text-[#6B7280] text-[11px] block">Alamat Email:</span>
                <span className="font-mono text-gray-800">{detailItem.email}</span>
              </div>
              <div>
                <span className="text-[#6B7280] text-[11px] block">Hak Akses (Role):</span>
                <span className={`inline-flex items-center px-2 py-0.5 mt-0.5 rounded text-[10px] font-bold ${roleBadge[detailItem.role]}`}>
                  {detailItem.role}
                </span>
              </div>
              <div>
                <span className="text-[#6B7280] text-[11px] block">Waktu Pembuatan Akun:</span>
                <span className="text-gray-700">{formatDate(detailItem.created_at)}</span>
              </div>
            </div>

            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={() => setShowDetailModal(false)}
                className="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Form Modal */}
      {formModalOpen && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-200 flex flex-col">
            <div className="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-[#F8FAFC]">
              <div>
                <h3 className="text-base font-extrabold text-gray-900">
                  {isEditMode ? `Ubah Data Pengguna (${formData.name})` : 'Tambah Pengguna Baru'}
                </h3>
              </div>
              <button
                type="button"
                disabled={submitting}
                onClick={() => setFormModalOpen(false)}
                className="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSubmitForm} className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Nama Lengkap <span className="text-[#F15A24]">*</span>
                </label>
                <input
                  type="text"
                  required
                  placeholder="Misal: Ahmad Zaky"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                    fieldErrors.name ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500' : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.name && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.name[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Email Akun <span className="text-[#F15A24]">*</span>
                </label>
                <input
                  type="email"
                  required
                  placeholder="Misal: user@banyankoalindo.co.id"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                    fieldErrors.email ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500' : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.email && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.email[0]}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Role / Hak Akses <span className="text-[#F15A24]">*</span>
                </label>
                <select
                  required
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                  className="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs sm:text-sm bg-white focus:outline-none focus:border-[#176B7A] focus:ring-2 focus:ring-[#176B7A]/20"
                >
                  <option value="IT">IT — Administrator Penuh</option>
                  <option value="KEUANGAN">KEUANGAN — Pengelolaan Kontrak & Finansial</option>
                </select>
                <p className="text-[11px] text-gray-500 mt-1">
                  Catatan: Karyawan umum (USER) dapat langsung memindai QR Code aset tanpa akun login.
                </p>
              </div>

              <div>
                <label className="block text-xs font-bold text-gray-700 mb-1">
                  Kata Sandi {isEditMode ? '(Opsional - kosongkan jika tidak diubah)' : <span className="text-[#F15A24]">*</span>}
                </label>
                <input
                  type="password"
                  required={!isEditMode}
                  minLength={8}
                  placeholder={isEditMode ? 'Kosongkan jika tidak ingin mengubah password' : 'Min. 8 karakter'}
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  className={`w-full px-3.5 py-2.5 rounded-xl border text-xs sm:text-sm focus:outline-none focus:ring-2 ${
                    fieldErrors.password ? 'border-rose-300 bg-rose-50/40 focus:border-rose-500' : 'border-gray-300 focus:border-[#176B7A] focus:ring-[#176B7A]/20'
                  }`}
                />
                {fieldErrors.password && (
                  <p className="text-[11px] text-rose-600 mt-1">{fieldErrors.password[0]}</p>
                )}
              </div>

              <div className="pt-4 border-t border-gray-200 flex justify-end space-x-2">
                <button
                  type="button"
                  disabled={submitting}
                  onClick={() => setFormModalOpen(false)}
                  className="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 font-bold text-xs"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-[#F15A24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-xs"
                >
                  {submitting && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                  <span>{isEditMode ? 'Simpan Perubahan' : 'Buat Pengguna'}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && deleteItem && (
        <div className="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-200 space-y-4">
            <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <AlertTriangle className="w-6 h-6" />
            </div>

            <div className="text-center space-y-1.5">
              <h3 className="text-base font-extrabold text-gray-900">Konfirmasi Hapus Akun</h3>
              <p className="text-xs text-[#6B7280]">
                Apakah Anda yakin ingin menghapus akun pengguna <strong className="text-gray-900">{deleteItem.name}</strong> ({deleteItem.email})?
              </p>
            </div>

            <div className="flex items-center space-x-2.5 pt-2">
              <button
                type="button"
                disabled={deleting}
                onClick={() => setShowDeleteModal(false)}
                className="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 font-bold text-xs"
              >
                Batal
              </button>
              <button
                type="button"
                disabled={deleting}
                onClick={handleConfirmDelete}
                className="flex-1 inline-flex items-center justify-center space-x-1.5 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md"
              >
                {deleting && <RefreshCw className="w-3.5 h-3.5 animate-spin" />}
                <span>Ya, Hapus Akun</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

