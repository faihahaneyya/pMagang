import React, { useState, useEffect } from 'react';
import {
  Boxes,
  ShieldCheck,
  Calendar,
  Clock,
  AlertTriangle,
  RefreshCw,
  ArrowLeft,
  Image as ImageIcon,
  CheckCircle2,
  FileText,
  Building2,
  ExternalLink,
  MapPin,
  Camera
} from 'lucide-react';
import { scanInventaris } from '../api/scan';

export default function PublicScanPage({ kode, onBackToHome, onOpenScanner }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [errorStatus, setErrorStatus] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const [activePhotoIdx, setActivePhotoIdx] = useState(0);

  useEffect(() => {
    if (kode) {
      loadScanData(kode);
    } else {
      setErrorStatus(404);
      setErrorMessage('Kode aset tidak disertakan pada tautan scan.');
      setLoading(false);
    }
  }, [kode]);

  const loadScanData = async (kodeAset) => {
    setLoading(true);
    setErrorStatus(null);
    setErrorMessage('');
    try {
      const res = await scanInventaris(kodeAset);
      if (res?.data) {
        setData(res.data);
      } else {
        throw new Error('Format data tidak sesuai.');
      }
    } catch (err) {
      setErrorStatus(err.status || 500);
      setErrorMessage(
        err.status === 404
          ? 'Data inventaris tidak ditemukan. Silakan hubungi Admin.'
          : err.message || 'Gagal memuat informasi aset. Periksa koneksi jaringan Anda.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#F3F4F6] flex flex-col justify-between text-gray-800 antialiased selection:bg-[#176B7A] selection:text-white">
      {/* 1. TOP BRAND HEADER */}
      <header className="bg-white border-b border-gray-200/80 shadow-xs sticky top-0 z-40">
        <div className="max-w-2xl mx-auto px-4 py-3.5 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#176B7A] to-[#0f4e5a] flex items-center justify-center font-extrabold text-base text-white shadow-xs">
              BKL
            </div>
            <div>
              <h1 className="text-xs sm:text-sm font-extrabold tracking-wide text-gray-900 uppercase">
                PT Banyan Koalindo Lestari
              </h1>
              <p className="text-[11px] text-[#6B7280] flex items-center space-x-1">
                <span>Verifikasi Label QR Code Aset Resmi</span>
              </p>
            </div>
          </div>

          <div className="flex items-center space-x-2">
            {onOpenScanner && (
              <button
                type="button"
                onClick={onOpenScanner}
                id="btn-public-pindai-barcode"
                className="text-[11px] font-semibold px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-[#176B7A] shadow-2xs transition-colors flex items-center space-x-1"
                title="Pindai QR Code Aset Menggunakan Kamera"
              >
                <Camera className="w-3.5 h-3.5 text-[#176B7A]" />
                <span className="hidden sm:inline">Pindai QR</span>
              </button>
            )}

            <button
              type="button"
              onClick={() => {
                if (onBackToHome) {
                  onBackToHome();
                } else {
                  window.location.href = '/';
                }
              }}
              className="text-[11px] font-semibold px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 hover:text-gray-900 shadow-2xs transition-colors flex items-center space-x-1"
              title="Ke Halaman Utama / Login"
            >
              <ArrowLeft className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Beranda</span>
            </button>
          </div>
        </div>
      </header>

      {/* 2. MAIN CONTAINER */}
      <main className="flex-1 max-w-2xl w-full mx-auto p-4 sm:p-6 space-y-5">
        {/* LOADING STATE */}
        {loading && (
          <div className="bg-white rounded-3xl p-8 text-center border border-gray-200 shadow-sm space-y-4 my-8 animate-fadeIn">
            <div className="w-14 h-14 rounded-2xl bg-orange-50 text-[#F05D24] flex items-center justify-center mx-auto shadow-xs">
              <RefreshCw className="w-7 h-7 animate-spin text-[#F05D24]" />
            </div>
            <div>
              <h2 className="text-base font-bold text-gray-900">Memverifikasi Data Aset...</h2>
              <p className="text-xs text-[#6B7280] mt-1 font-mono">Kode: {kode}</p>
            </div>
            <p className="text-xs text-gray-400">Menghubungkan ke basis data inventaris resmi BKL</p>
          </div>
        )}

        {/* ERROR / 404 STATE */}
        {!loading && errorStatus && (
          <div className="bg-white rounded-3xl p-6 sm:p-8 text-center border border-orange-200 shadow-sm space-y-5 my-6 animate-fadeIn">
            <div className="w-16 h-16 rounded-3xl bg-orange-50 text-[#F05D24] flex items-center justify-center mx-auto border border-orange-100 shadow-xs">
              <AlertTriangle className="w-8 h-8" />
            </div>
            <div className="space-y-1.5">
              <span className="px-3 py-1 rounded-full text-[11px] font-bold bg-orange-100 text-[#BF3E0F]">
                {errorStatus === 404 ? 'Kode Aset Tidak Terdaftar' : 'Gagal Memuat Informasi'}
              </span>
              <h2 className="text-lg font-extrabold text-gray-900 pt-1">
                {errorStatus === 404 ? 'Aset Tidak Ditemukan' : 'Terjadi Gangguan Koneksi'}
              </h2>
              <p className="text-xs text-gray-600 max-w-md mx-auto leading-relaxed">
                {errorMessage}
              </p>
            </div>

            <div className="p-3.5 rounded-2xl bg-gray-50 border border-gray-200 text-[11px] text-[#6B7280] text-left space-y-1">
              <div className="font-semibold text-gray-800 flex items-center space-x-1.5">
                <ShieldCheck className="w-4 h-4 text-[#F05D24]" />
                <span>Petunjuk Keamanan Scan QR Code:</span>
              </div>
              <p>• Pastikan QR Code terbaca utuh dan kamera terfokus dengan baik.</p>
              <p>• Jika barang baru didaftarkan, hubungi Administrator Inventaris PT BKL.</p>
            </div>

            <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5">
              {onOpenScanner && (
                <button
                  type="button"
                  id="btn-error-pindai-barcode"
                  onClick={onOpenScanner}
                  className="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-5 py-2.5 rounded-xl bg-[#F05D24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-md transition-all"
                >
                  <Camera className="w-4 h-4" />
                  <span>Pindai QR Code Lain</span>
                </button>
              )}
              <button
                type="button"
                onClick={() => loadScanData(kode)}
                className="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#F05D24] to-[#D84A15] text-white font-bold text-xs shadow-md hover:brightness-110 transition-all"
              >
                <RefreshCw className="w-4 h-4" />
                <span>Coba Lagi</span>
              </button>
              <button
                type="button"
                onClick={() => {
                  if (onBackToHome) onBackToHome();
                  else window.location.href = '/';
                }}
                className="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs transition-colors"
              >
                Halaman Utama
              </button>
            </div>
          </div>
        )}

        {/* SUCCESS DATA DISPLAY */}
        {!loading && !errorStatus && data && (
          <div className="space-y-5 animate-fadeIn">
            {/* CARD 1: IDENTITAS ASET */}
            <div className="bg-white rounded-3xl p-5 sm:p-6 border border-gray-200/80 shadow-xs space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                <div className="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                  <span>Aset Resmi Terverifikasi</span>
                </div>
                <div className="font-mono text-xs font-bold text-[#176B7A] bg-[#176B7A]/10 px-3 py-1 rounded-xl border border-[#176B7A]/20">
                  {data.kode_inventaris}
                </div>
              </div>

              <div>
                <h2 className="text-xl sm:text-2xl font-black text-gray-900 tracking-tight leading-snug">
                  {data.nama_barang}
                </h2>
                {data.spesifikasi && (
                  <p className="text-xs sm:text-sm text-[#6B7280] mt-2 whitespace-pre-line leading-relaxed bg-[#F8FAFC] p-3.5 rounded-2xl border border-gray-200/70">
                    {data.spesifikasi}
                  </p>
                )}
              </div>

              {/* Lokasi Penempatan Aset */}
              {data.lokasi && (
                <div className="flex items-center space-x-2 text-xs font-semibold text-gray-700 bg-gray-50 border border-gray-200/80 px-3.5 py-2.5 rounded-2xl">
                  <MapPin className="w-4 h-4 text-[#176B7A] flex-shrink-0" />
                  <span>Lokasi Penempatan: <strong className="text-gray-900 font-bold">{data.lokasi}</strong></span>
                </div>
              )}

              {/* Status Kepemilikan & Status Kontrak Badges */}
              <div className="grid grid-cols-2 gap-2.5 pt-1">
                <div className="p-3 rounded-2xl border bg-[#F8FAFC] border-gray-200 space-y-1">
                  <span className="text-[10px] font-bold text-[#6B7280] uppercase tracking-wider block">
                    Status Kepemilikan
                  </span>
                  <span
                    className={`inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold ${
                      data.status_kepemilikan === 'SEWA' || data.status_kepemilikan === 'Sewa'
                        ? 'bg-[#176B7A]/10 text-[#176B7A] border border-[#176B7A]/20'
                        : 'bg-slate-100 text-slate-700 border border-slate-200'
                    }`}
                  >
                    {data.status_kepemilikan}
                  </span>
                </div>

                <div className="p-3 rounded-2xl border bg-[#F8FAFC] border-gray-200 space-y-1">
                  <span className="text-[10px] font-bold text-[#6B7280] uppercase tracking-wider block">
                    Status Kontrak
                  </span>
                  <span
                    className={`inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold ${
                      data.status_kontrak === 'Sedang Berjalan' || data.status_kontrak === 'AKTIF'
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : data.status_kontrak === 'Akan Berakhir' || data.status_kontrak === 'AKAN BERAKHIR'
                        ? 'bg-[#F15A24]/10 text-[#F15A24] border border-[#F15A24]/20'
                        : data.status_kontrak === 'Habis' || data.status_kontrak === 'BERAKHIR'
                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                        : 'bg-gray-100 text-gray-800 border border-gray-200'
                    }`}
                  >
                    {data.status_kontrak || '-'}
                  </span>
                </div>
              </div>
            </div>

            {/* CARD 2: DOKUMENTASI FOTO FISIK ASET */}
            <div className="bg-white rounded-3xl p-5 sm:p-6 border border-gray-200/80 shadow-xs space-y-3.5">
              <h3 className="text-xs sm:text-sm font-bold text-gray-900 flex items-center space-x-2">
                <ImageIcon className="w-4 h-4 text-[#176B7A]" />
                <span>Dokumentasi Fisik Barang</span>
              </h3>

              {data.photos && data.photos.length > 0 ? (
                <div className="space-y-3">
                  {/* Foto Utama Pilihan */}
                  <div className="relative rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 aspect-video flex items-center justify-center">
                    <img
                      src={data.photos[activePhotoIdx]?.url || data.foto}
                      alt={data.nama_barang}
                      className="w-full h-full object-contain"
                      onError={(e) => {
                        e.target.style.display = 'none';
                      }}
                    />
                    <div className="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-white text-[11px] font-medium">
                      {data.photos[activePhotoIdx]?.caption || 'Dokumentasi Fisik Aset'}
                    </div>
                  </div>

                  {/* Thumbnail Jika Lebih dari 1 Foto */}
                  {data.photos.length > 1 && (
                    <div className="flex items-center space-x-2 overflow-x-auto pb-1">
                      {data.photos.map((p, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => setActivePhotoIdx(idx)}
                          className={`relative flex-shrink-0 w-16 h-14 rounded-xl overflow-hidden border-2 transition-all ${
                            activePhotoIdx === idx
                              ? 'border-[#176B7A] scale-102 shadow-xs'
                              : 'border-transparent opacity-70 hover:opacity-100'
                          }`}
                        >
                          <img
                            src={p.url}
                            alt={`Thumbnail ${idx + 1}`}
                            className="w-full h-full object-cover"
                          />
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              ) : data.foto ? (
                <div className="rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 aspect-video flex items-center justify-center">
                  <img
                    src={data.foto}
                    alt={data.nama_barang}
                    className="w-full h-full object-contain"
                  />
                </div>
              ) : (
                <div className="p-6 rounded-2xl bg-gray-50 border border-gray-200 text-center text-xs text-gray-400 italic">
                  Foto fisik belum diunggah untuk aset ini.
                </div>
              )}
            </div>

            {/* CARD 3: RINCIAN KONTRAK SEWA (JIKA TERIKAT KONTRAK) */}
            {data.kontrak && (
              <div className="bg-white rounded-3xl p-5 sm:p-6 border border-gray-200/80 shadow-xs space-y-3.5">
                <div className="flex items-center justify-between border-b border-gray-100 pb-2.5">
                  <h3 className="text-xs sm:text-sm font-bold text-gray-900 flex items-center space-x-2">
                    <FileText className="w-4 h-4 text-[#176B7A]" />
                    <span>Informasi Masa Sewa Beli</span>
                  </h3>
                  {data.kontrak.sisa_hari !== undefined && (
                    <span className="text-[11px] font-bold text-[#176B7A] bg-[#176B7A]/10 px-2.5 py-0.5 rounded-full border border-[#176B7A]/20">
                      {data.kontrak.sisa_hari > 0
                        ? `Sisa ${data.kontrak.sisa_hari} Hari`
                        : data.kontrak.sisa_hari === 0
                        ? 'Berakhir Hari Ini'
                        : 'Masa Sewa Berakhir'}
                    </span>
                  )}
                </div>

                <div className="grid grid-cols-2 gap-3 text-xs">
                  <div className="p-3 rounded-2xl bg-[#F8FAFC] border border-gray-200/80">
                    <span className="text-[10px] text-[#6B7280] font-semibold block flex items-center space-x-1">
                      <Calendar className="w-3 h-3 text-gray-400" />
                      <span>Tanggal Mulai</span>
                    </span>
                    <span className="font-extrabold text-gray-900 text-xs sm:text-sm mt-0.5 block">
                      {data.kontrak.tanggal_mulai || '-'}
                    </span>
                  </div>

                  <div className="p-3 rounded-2xl bg-[#F8FAFC] border border-gray-200/80">
                    <span className="text-[10px] text-[#6B7280] font-semibold block flex items-center space-x-1">
                      <Clock className="w-3 h-3 text-gray-400" />
                      <span>Tanggal Berakhir</span>
                    </span>
                    <span className="font-extrabold text-gray-900 text-xs sm:text-sm mt-0.5 block">
                      {data.kontrak.tanggal_berakhir || '-'}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* PRIVACY & SECURITY BANNER */}
            <div className="p-4 rounded-2xl bg-white border border-gray-200 text-[11px] text-[#6B7280] flex items-start space-x-2.5 shadow-2xs">
              <ShieldCheck className="w-4 h-4 text-[#176B7A] flex-shrink-0 mt-0.5" />
              <p className="leading-relaxed">
                Informasi ini diterbitkan resmi oleh <strong className="text-gray-900">PT Banyan Koalindo Lestari</strong> melalui pemindaian label QR Code inventaris. Demi keamanan dan privasi perusahaan, nomor seri, PIC penanggung jawab, serta rincian data finansial hanya dapat diakses melalui portal admin internal.
              </p>
            </div>
          </div>
        )}
      </main>

      {/* 3. FOOTER */}
      <footer className="bg-white border-t border-gray-200 py-4 px-4 text-center text-[11px] text-[#6B7280]">
        <div className="max-w-2xl mx-auto space-y-1">
          <p className="font-semibold text-gray-800">PT BANYAN KOALINDO LESTARI</p>
          <p>Sistem Informasi Inventaris Sewa Beli &copy; {new Date().getFullYear()}</p>
        </div>
      </footer>
    </div>
  );
}
