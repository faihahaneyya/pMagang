import React, { useState, useEffect, useRef } from 'react';
import {
  Camera,
  CameraOff,
  X,
  RefreshCw,
  AlertTriangle,
  CheckCircle2,
  Square,
  Play,
  RotateCcw,
  Search,
  ShieldCheck,
  Zap,
  ZapOff
} from 'lucide-react';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { scanInventaris } from '../api/scan';

export default function BarcodeScannerModal({ isOpen, onClose, onSelectAset }) {
  // Scanner state: 'IDLE' | 'STARTING' | 'SCANNING' | 'STOPPED' | 'VERIFYING' | 'NOT_FOUND' | 'PERMISSION_DENIED' | 'NO_CAMERA' | 'ERROR'
  const [scannerState, setScannerState] = useState('IDLE');
  const [errorMessage, setErrorMessage] = useState('');
  const [scannedCode, setScannedCode] = useState('');
  const [manualCode, setManualCode] = useState('');
  const [manualLoading, setManualLoading] = useState(false);
  const [manualError, setManualError] = useState('');
  const [hasTorch, setHasTorch] = useState(false);
  const [torchOn, setTorchOn] = useState(false);
  const [cameras, setCameras] = useState([]);
  const [selectedCameraId, setSelectedCameraId] = useState(null);

  const scannerRef = useRef(null);
  const isMountedRef = useRef(true);

  // Inisiasi scanner saat modal dibuka
  useEffect(() => {
    isMountedRef.current = true;
    if (isOpen) {
      setScannerState('STARTING');
      setErrorMessage('');
      setScannedCode('');
      setManualError('');
      // Beri sedikit jeda agar DOM element #barcode-reader-viewport sudah ter-render sempurna
      const timer = setTimeout(() => {
        startScanner();
      }, 200);
      return () => clearTimeout(timer);
    } else {
      stopScanner();
      setScannerState('IDLE');
    }

    return () => {
      isMountedRef.current = false;
      stopScanner();
    };
  }, [isOpen]);

  /**
   * Menghentikan scanner dan melepaskan stream hardware kamera
   */
  const stopScanner = async () => {
    try {
      if (scannerRef.current) {
        if (scannerRef.current.isScanning) {
          await scannerRef.current.stop();
        }
        scannerRef.current.clear();
        scannerRef.current = null;
      }
    } catch (err) {
      console.warn('Notice saat stop scanner:', err);
    }
  };

  /**
   * Memulai kamera dan pemindaian 1D Code 128
   */
  const startScanner = async (cameraId = null) => {
    if (!isMountedRef.current) return;
    setScannerState('STARTING');
    setErrorMessage('');
    setScannedCode('');

    // Hentikan scanner sebelumnya jika ada
    await stopScanner();

    // Periksa apakah browser mendukung MediaDevices dan Secure Context (HTTPS/localhost)
    if (typeof window !== 'undefined') {
      if (!navigator?.mediaDevices?.getUserMedia) {
        if (window.isSecureContext === false && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
          setScannerState('ERROR');
          setErrorMessage(
            'Akses kamera pada browser HP membutuhkan koneksi aman (HTTPS). Silakan akses melalui HTTPS atau masukkan kode inventaris secara manual.'
          );
          return;
        }
        setScannerState('NO_CAMERA');
        setErrorMessage(
          'Browser ini tidak mendukung fitur kamera (MediaDevices API). Silakan masukkan kode inventaris secara manual.'
        );
        return;
      }
    }

    try {
      // 1. Dapatkan daftar kamera video input
      let cameraList = [];
      try {
        cameraList = await Html5Qrcode.getCameras();
        if (isMountedRef.current) {
          setCameras(cameraList || []);
        }
      } catch (camErr) {
        console.warn('Gagal membaca getCameras():', camErr);
      }

      const viewportElem = document.getElementById('barcode-reader-viewport');
      if (!viewportElem) {
        if (isMountedRef.current) {
          setScannerState('ERROR');
          setErrorMessage('Elemen kamera tidak ditemukan di antarmuka.');
        }
        return;
      }

      // 2. Inisiasi Html5Qrcode HANYA dengan format QR_CODE (bukan Code 128)
      // Aktifkan experimentalFeatures useBarCodeDetectorIfSupported untuk akselerasi native BarcodeDetector browser HP
      const html5QrCode = new Html5Qrcode('barcode-reader-viewport', {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        verbose: false,
        experimentalFeatures: {
          useBarCodeDetectorIfSupported: true,
        },
      });
      scannerRef.current = html5QrCode;

      // 3. Konfigurasi kamera: prioritaskan kamera belakang (environment) di HP
      const cameraConfig = cameraId
        ? { deviceId: { exact: cameraId } }
        : { facingMode: 'environment' };

      // 4. Konfigurasi scan dioptimalkan untuk QR Code (2D)
      const scanConfig = {
        fps: 20,
        // qrbox berbentuk bujur sangkar / persegi khusus untuk QR Code (2D)
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const minDim = Math.min(viewfinderWidth, viewfinderHeight);
          const edge = Math.max(180, Math.min(Math.floor(minDim * 0.72), 260));
          return { width: edge, height: edge };
        },
        aspectRatio: 1.333333,
        videoConstraints: cameraId
          ? {
              deviceId: { exact: cameraId },
              width: { ideal: 1280 },
              height: { ideal: 720 },
            }
          : {
              facingMode: 'environment',
              width: { ideal: 1280 },
              height: { ideal: 720 },
            },
      };

      await html5QrCode.start(
        cameraConfig,
        scanConfig,
        handleScanSuccess,
        handleScanFailure
      );

      if (isMountedRef.current) {
        setScannerState('SCANNING');
        // Perbarui daftar kamera setelah izin aktif jika sebelumnya kosong
        try {
          const updatedCameras = await Html5Qrcode.getCameras();
          if (isMountedRef.current && updatedCameras?.length > 0) {
            setCameras(updatedCameras);
          }
        } catch {
          // Abaikan jika tidak dapat membaca ulang
        }

        // Periksa apakah torch/flash didukung
        try {
          const track = html5QrCode.getRunningTrackCameraCapabilities();
          if (track && track.torchFeature && track.torchFeature().isSupported()) {
            setHasTorch(true);
          }
        } catch {
          setHasTorch(false);
        }
      }
    } catch (err) {
      console.error('Scanner start error:', err);
      if (!isMountedRef.current) return;

      const errString = String(err).toLowerCase();
      if (
        errString.includes('notallowederror') ||
        errString.includes('permission denied') ||
        errString.includes('permission_denied')
      ) {
        setScannerState('PERMISSION_DENIED');
        setErrorMessage(
          'Izin akses kamera ditolak oleh browser. Silakan izinkan akses kamera di pengaturan browser (ikon gembok di sebelah URL) lalu tekan "Coba Lagi".'
        );
      } else if (
        errString.includes('notfounderror') ||
        errString.includes('devicesnotfounderror') ||
        errString.includes('no camera')
      ) {
        setScannerState('NO_CAMERA');
        setErrorMessage(
          'Kamera tidak terdeteksi pada perangkat ini atau sedang digunakan oleh aplikasi lain. Anda juga dapat memasukkan kode inventaris secara manual.'
        );
      } else {
        setScannerState('ERROR');
        setErrorMessage(
          err.message || 'Gagal memulai kamera untuk pemindaian QR Code. Pastikan izin kamera telah diberikan.'
        );
      }
    }
  };

  /**
   * Ekstraksi kode inventaris dari teks atau URL hasil pembacaan QR Code.
   * Mendukung:
   * - URL lengkap: http://domain/aset/INV-IT-001
   * - Path rute: /aset/INV-IT-001 atau /scan/INV-IT-001
   * - Parameter URL: ?kode=INV-IT-001
   * - Teks kode langsung: INV-IT-001
   */
  const extractKodeFromQr = (text) => {
    if (!text) return '';
    const trimmed = text.trim();

    // Pola 1: URL atau path /aset/{kode}
    const asetMatch = trimmed.match(/\/aset\/([A-Za-z0-9\-_]+)/i);
    if (asetMatch && asetMatch[1]) {
      return decodeURIComponent(asetMatch[1]);
    }

    // Pola 2: URL atau path /scan/{kode}
    const scanMatch = trimmed.match(/\/scan\/([A-Za-z0-9\-_]+)/i);
    if (scanMatch && scanMatch[1]) {
      return decodeURIComponent(scanMatch[1]);
    }

    // Pola 3: URL dengan query string ?kode=...
    try {
      if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        const urlObj = new URL(trimmed);
        if (urlObj.searchParams.has('kode')) {
          return urlObj.searchParams.get('kode');
        }
        const pathnameParts = urlObj.pathname.split('/').filter(Boolean);
        if (pathnameParts.length > 0) {
          return decodeURIComponent(pathnameParts[pathnameParts.length - 1]);
        }
      }
    } catch {
      // Abaikan jika bukan URL standar
    }

    return trimmed;
  };

  /**
   * Callback saat frame video belum menemukan QR Code
   */
  const handleScanFailure = () => {
    // Diabaikan agar tidak membanjiri log
  };

  /**
   * Callback ketika QR Code berhasil terbaca
   */
  const handleScanSuccess = async (decodedText) => {
    if (!decodedText) return;
    const cleanCode = extractKodeFromQr(decodedText).trim();
    if (!cleanCode) return;

    // Hentikan pemindaian agar tidak memicu panggilan ganda
    await stopScanner();

    if (!isMountedRef.current) return;
    setScannedCode(cleanCode);
    setScannerState('VERIFYING');

    // Verifikasi kode ke API backend Laravel
    try {
      const res = await scanInventaris(cleanCode);
      if (res?.success && res?.data) {
        setScannerState('SUCCESS');
        setTimeout(() => {
          if (onSelectAset) {
            onSelectAset(cleanCode);
          }
        }, 500);
      } else {
        throw new Error('Data tidak sesuai');
      }
    } catch (err) {
      if (!isMountedRef.current) return;
      if (err.status === 404) {
        setScannerState('NOT_FOUND');
        setErrorMessage('Data inventaris tidak ditemukan. Silakan hubungi Admin.');
      } else {
        setScannerState('ERROR');
        setErrorMessage(
          err.message || 'Gagal menghubungi server untuk memverifikasi QR Code.'
        );
      }
    }
  };

  /**
   * Tombol hentikan kamera oleh pengguna
   */
  const handleUserStopCamera = async () => {
    await stopScanner();
    setScannerState('STOPPED');
  };

  /**
   * Tombol coba scan kembali
   */
  const handleRetryScan = () => {
    setScannedCode('');
    setErrorMessage('');
    startScanner(selectedCameraId);
  };

  /**
   * Toggle senter / torch HP
   */
  const toggleTorch = async () => {
    if (!scannerRef.current || !hasTorch) return;
    try {
      const newStatus = !torchOn;
      await scannerRef.current.applyVideoConstraints({
        advanced: [{ torch: newStatus }],
      });
      setTorchOn(newStatus);
    } catch (err) {
      console.warn('Gagal mengubah status senter:', err);
    }
  };

  /**
   * Ganti kamera (depan / belakang)
   */
  const handleCameraChange = (e) => {
    const newId = e.target.value;
    setSelectedCameraId(newId);
    startScanner(newId);
  };

  /**
   * Pengajuan kode inventaris manual
   */
  const handleManualSubmit = async (e) => {
    e.preventDefault();
    const parsed = extractKodeFromQr(manualCode);
    const code = parsed.trim().toUpperCase();
    if (!code) {
      setManualError('Masukkan kode inventaris atau tempel URL aset terlebih dahulu.');
      return;
    }

    setManualLoading(true);
    setManualError('');
    try {
      const res = await scanInventaris(code);
      if (res?.success && res?.data) {
        await stopScanner();
        if (onSelectAset) {
          onSelectAset(code);
        }
      } else {
        throw new Error('Data tidak sesuai');
      }
    } catch (err) {
      if (err.status === 404) {
        setManualError('Data inventaris tidak ditemukan. Silakan hubungi Admin.');
      } else {
        setManualError(err.message || 'Gagal memverifikasi kode inventaris.');
      }
    } finally {
      setManualLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-950/75 backdrop-blur-sm animate-fadeIn">
      {/* Modal Container */}
      <div className="bg-white rounded-3xl w-full max-w-lg shadow-2xl border border-gray-200 overflow-hidden flex flex-col max-h-[92vh]">
        {/* Modal Header */}
        <div className="px-5 py-4 bg-gradient-to-r from-[#F05D24] to-[#D84A15] text-white flex items-center justify-between border-b-2 border-[#BF3E0F]">
          <div className="flex items-center space-x-2.5">
            <div className="w-9 h-9 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-white">
              <Camera className="w-5 h-5 text-white" />
            </div>
            <div>
              <h3 className="font-extrabold text-sm sm:text-base tracking-tight text-white flex items-center space-x-2">
                <span>Pindai QR Code</span>
                <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-white text-[#F05D24] tracking-wider">
                  QR Code (2D)
                </span>
              </h3>
              <p className="text-[11px] text-orange-100">
                Arahkan kamera ke label QR Code aset inventaris
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="p-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors"
            title="Tutup Pemindai"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Modal Body / Viewport Area */}
        <div className="p-4 sm:p-5 flex-1 overflow-y-auto space-y-4">
          {/* CAMERA VIEWPORT BOX (Bentuk Persegi untuk QR Code) */}
          <div className="relative bg-gray-900 rounded-2xl overflow-hidden aspect-[4/3] sm:aspect-[1/1] max-h-[340px] w-full flex items-center justify-center shadow-inner border border-gray-800">
            {/* Target HTML5-QRCode rendering container */}
            <div
              id="barcode-reader-viewport"
              className="w-full h-full object-cover [&_video]:w-full [&_video]:h-full [&_video]:object-cover"
            />

            {/* OVERLAY: Reticle Pemindai Bujur Sangkar / Persegi (Format QR Code 2D) */}
            {scannerState === 'SCANNING' && (
              <div className="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                {/* Square Guide Reticle */}
                <div className="relative w-[210px] h-[210px] sm:w-[230px] sm:h-[230px] rounded-2xl border-2 border-dashed border-[#F05D24] bg-transparent shadow-[0_0_0_9999px_rgba(0,0,0,0.55)] flex items-center justify-center">
                  {/* Corner accents */}
                  <div className="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-[#F05D24] rounded-tl-md" />
                  <div className="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-[#F05D24] rounded-tr-md" />
                  <div className="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-[#F05D24] rounded-bl-md" />
                  <div className="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-[#F05D24] rounded-br-md" />

                  {/* Animated Laser Scanning Line */}
                  <div className="absolute left-0 right-0 h-[2.5px] bg-gradient-to-r from-transparent via-[#F05D24] to-transparent shadow-[0_0_10px_#F05D24] animate-laserScan" />
                </div>

                <div className="absolute bottom-3 text-center px-4">
                  <span className="text-[11px] font-semibold text-white bg-black/70 px-3.5 py-1.5 rounded-full backdrop-blur-xs border border-white/20 shadow-md">
                    Posisikan QR Code di dalam kotak pemindai
                  </span>
                </div>
              </div>
            )}

            {/* OVERLAY: Starting / Loading State */}
            {scannerState === 'STARTING' && (
              <div className="absolute inset-0 bg-gray-900/90 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center space-y-3 z-10">
                <RefreshCw className="w-8 h-8 text-[#F05D24] animate-spin" />
                <p className="text-xs font-semibold text-white">
                  Menyiapkan kamera untuk pemindaian QR Code...
                </p>
                <p className="text-[11px] text-gray-400 max-w-xs">
                  Pastikan memberikan izin akses kamera bila diminta oleh browser.
                </p>
              </div>
            )}

            {/* OVERLAY: Verifying Decoded Barcode */}
            {scannerState === 'VERIFYING' && (
              <div className="absolute inset-0 bg-gray-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center space-y-3 z-10 animate-fadeIn">
                <RefreshCw className="w-9 h-9 text-[#F05D24] animate-spin" />
                <div className="space-y-1">
                  <span className="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#F05D24] text-white">
                    {scannedCode}
                  </span>
                  <h4 className="text-sm font-bold text-white">Memverifikasi Data Aset...</h4>
                  <p className="text-xs text-gray-300">
                    Mengecek kode inventaris ke basis data BKL
                  </p>
                </div>
              </div>
            )}

            {/* OVERLAY: Success State */}
            {scannerState === 'SUCCESS' && (
              <div className="absolute inset-0 bg-orange-950/95 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center space-y-3 z-10 animate-fadeIn">
                <CheckCircle2 className="w-12 h-12 text-orange-400" />
                <div className="space-y-1">
                  <h4 className="text-sm font-extrabold text-white">QR Code Terverifikasi!</h4>
                  <p className="text-xs text-orange-200 font-mono">{scannedCode}</p>
                  <p className="text-[11px] text-orange-300">Membuka halaman detail aset...</p>
                </div>
              </div>
            )}

            {/* OVERLAY: Camera Stopped / Paused */}
            {scannerState === 'STOPPED' && (
              <div className="absolute inset-0 bg-gray-900/95 flex flex-col items-center justify-center p-6 text-center space-y-3 z-10 animate-fadeIn">
                <div className="w-12 h-12 rounded-2xl bg-gray-800 text-gray-400 flex items-center justify-center">
                  <CameraOff className="w-6 h-6" />
                </div>
                <div className="space-y-1">
                  <h4 className="text-sm font-bold text-white">Kamera Dihentikan</h4>
                  <p className="text-xs text-gray-400">
                    Kamera telah dimatikan untuk menghemat daya baterai.
                  </p>
                </div>
                <button
                  type="button"
                  id="btn-restart-from-overlay"
                  onClick={handleRetryScan}
                  className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#F05D24] hover:bg-[#d84a15] text-white font-bold text-xs shadow-md transition-all"
                >
                  <Play className="w-3.5 h-3.5 fill-current" />
                  <span>Mulai Kamera Kembali</span>
                </button>
              </div>
            )}

            {/* OVERLAY: Not Found State */}
            {scannerState === 'NOT_FOUND' && (
              <div className="absolute inset-0 bg-gray-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-5 text-center space-y-3 z-10 animate-fadeIn">
                <div className="w-12 h-12 rounded-2xl bg-orange-500/20 text-orange-400 flex items-center justify-center">
                  <AlertTriangle className="w-6 h-6" />
                </div>
                <div className="space-y-1.5 max-w-xs">
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-orange-950 text-orange-300 border border-orange-800">
                    {scannedCode}
                  </span>
                  <h4 className="text-sm font-bold text-white">
                    Data inventaris tidak ditemukan. Silakan hubungi Admin.
                  </h4>
                  <p className="text-[11px] text-gray-400">
                    Pastikan QR Code yang dipindai adalah label resmi inventaris PT BKL.
                  </p>
                </div>
                <div className="flex items-center space-x-2 pt-1">
                  <button
                    type="button"
                    id="btn-retry-scan"
                    onClick={handleRetryScan}
                    className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#F05D24] hover:bg-[#D84A15] text-white font-semibold text-xs shadow-md transition-colors"
                  >
                    <RotateCcw className="w-3.5 h-3.5" />
                    <span>Coba Scan Kembali</span>
                  </button>
                </div>
              </div>
            )}

            {/* OVERLAY: Permission Denied */}
            {scannerState === 'PERMISSION_DENIED' && (
              <div className="absolute inset-0 bg-orange-950/95 backdrop-blur-sm flex flex-col items-center justify-center p-5 text-center space-y-3 z-10 animate-fadeIn">
                <div className="w-12 h-12 rounded-2xl bg-orange-500/20 text-orange-400 flex items-center justify-center">
                  <CameraOff className="w-6 h-6" />
                </div>
                <div className="space-y-1 max-w-xs">
                  <h4 className="text-sm font-extrabold text-white">Izin Kamera Ditolak</h4>
                  <p className="text-xs text-orange-200 leading-relaxed">{errorMessage}</p>
                </div>
                <button
                  type="button"
                  id="btn-retry-permission"
                  onClick={handleRetryScan}
                  className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-white text-orange-950 hover:bg-orange-50 font-bold text-xs shadow-md transition-colors"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Coba Lagi</span>
                </button>
              </div>
            )}

            {/* OVERLAY: No Camera / General Error */}
            {(scannerState === 'NO_CAMERA' || scannerState === 'ERROR') && (
              <div className="absolute inset-0 bg-gray-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-5 text-center space-y-3 z-10 animate-fadeIn">
                <div className="w-12 h-12 rounded-2xl bg-gray-800 text-gray-300 flex items-center justify-center">
                  <AlertTriangle className="w-6 h-6 text-orange-400" />
                </div>
                <div className="space-y-1 max-w-xs">
                  <h4 className="text-sm font-bold text-white">
                    {scannerState === 'NO_CAMERA' ? 'Kamera Tidak Tersedia' : 'Gangguan Kamera'}
                  </h4>
                  <p className="text-xs text-gray-300 leading-relaxed">{errorMessage}</p>
                </div>
                <button
                  type="button"
                  id="btn-retry-error"
                  onClick={handleRetryScan}
                  className="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-[#F05D24] hover:bg-[#D84A15] text-white font-semibold text-xs shadow-md transition-colors"
                >
                  <RotateCcw className="w-3.5 h-3.5" />
                  <span>Coba Lagi</span>
                </button>
              </div>
            )}
          </div>

          {/* SCANNER CONTROLS BAR */}
          <div className="flex flex-wrap items-center justify-between gap-2 p-2.5 rounded-xl bg-gray-50 border border-gray-200 text-xs">
            <div className="flex items-center space-x-2">
              <span
                className={`w-2 h-2 rounded-full ${
                  scannerState === 'SCANNING'
                    ? 'bg-[#F05D24] animate-pulse'
                    : scannerState === 'VERIFYING'
                    ? 'bg-[#F05D24] animate-spin'
                    : 'bg-gray-400'
                }`}
              />
              <span className="font-semibold text-gray-700">
                {scannerState === 'SCANNING'
                  ? 'Kamera Aktif — Siap Scan'
                  : scannerState === 'VERIFYING'
                  ? 'Memverifikasi...'
                  : scannerState === 'STOPPED'
                  ? 'Kamera Dihentikan'
                  : 'Status Kamera'}
              </span>
            </div>

            <div className="flex items-center space-x-1.5">
              {/* Tombol Senter/Torch jika tersedia */}
              {hasTorch && scannerState === 'SCANNING' && (
                <button
                  type="button"
                  onClick={toggleTorch}
                  className={`p-1.5 rounded-lg border font-semibold text-xs transition-colors flex items-center space-x-1 ${
                    torchOn
                      ? 'bg-orange-100 text-orange-950 border-orange-300'
                      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                  }`}
                  title="Nyalakan / Matikan Lampu Senter"
                >
                  {torchOn ? <ZapOff className="w-3.5 h-3.5" /> : <Zap className="w-3.5 h-3.5" />}
                  <span className="hidden sm:inline">{torchOn ? 'Lampu ON' : 'Lampu'}</span>
                </button>
              )}

              {/* Selector Kamera jika ada lebih dari 1 */}
              {cameras.length > 1 && scannerState === 'SCANNING' && (
                <select
                  value={selectedCameraId || ''}
                  onChange={handleCameraChange}
                  className="text-[11px] px-2 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 max-w-[130px] truncate"
                >
                  {cameras.map((cam, idx) => (
                    <option key={cam.id} value={cam.id}>
                      {cam.label || `Kamera ${idx + 1}`}
                    </option>
                  ))}
                </select>
              )}

              {/* Tombol Hentikan Kamera / Coba Lagi */}
              {scannerState === 'SCANNING' ? (
                <button
                  type="button"
                  id="btn-stop-camera"
                  onClick={handleUserStopCamera}
                  className="px-2.5 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-[11px] flex items-center space-x-1 transition-colors"
                  title="Hentikan sementara streaming kamera"
                >
                  <Square className="w-3 h-3 text-[#F05D24] fill-[#F05D24]" />
                  <span>Hentikan Kamera</span>
                </button>
              ) : (
                <button
                  type="button"
                  id="btn-restart-camera"
                  onClick={handleRetryScan}
                  className="px-2.5 py-1.5 rounded-lg bg-[#F05D24] hover:bg-[#D84A15] text-white font-semibold text-[11px] flex items-center space-x-1 transition-colors"
                >
                  <Play className="w-3 h-3 fill-current" />
                  <span>Mulai Kamera</span>
                </button>
              )}
            </div>
          </div>

          {/* FALLBACK: INPUT KODE MANUAL */}
          <div className="pt-2 border-t border-gray-100">
            <form onSubmit={handleManualSubmit} className="space-y-1.5">
              <label htmlFor="input-manual-barcode" className="block text-[11px] font-bold text-gray-700">
                Atau Masukkan Kode Inventaris Secara Manual:
              </label>
              <div className="flex space-x-2">
                <input
                  id="input-manual-barcode"
                  type="text"
                  placeholder="Contoh: INV-IT-001 atau tempel link aset"
                  value={manualCode}
                  onChange={(e) => setManualCode(e.target.value.toUpperCase())}
                  className="flex-1 px-3 py-2 text-xs font-mono rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#F05D24] focus:border-[#F05D24]"
                />
                <button
                  type="submit"
                  id="btn-submit-manual-barcode"
                  disabled={manualLoading}
                  className="px-4 py-2 rounded-xl bg-[#F05D24] hover:bg-[#D84A15] disabled:opacity-50 text-white font-semibold text-xs transition-colors flex items-center space-x-1"
                >
                  {manualLoading ? (
                    <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                  ) : (
                    <Search className="w-3.5 h-3.5" />
                  )}
                  <span>Buka Aset</span>
                </button>
              </div>
              {manualError && (
                <p id="manual-error-message" className="text-[11px] font-semibold text-[#BF3E0F] flex items-center space-x-1 pt-0.5">
                  <AlertTriangle className="w-3 h-3 flex-shrink-0" />
                  <span>{manualError}</span>
                </p>
              )}
            </form>
          </div>

          {/* Informasi Panduan Scan */}
          <div className="p-3 rounded-xl bg-orange-50/60 border border-orange-200/60 text-[11px] text-orange-950 space-y-1">
            <div className="font-bold flex items-center space-x-1.5 text-[#F05D24]">
              <ShieldCheck className="w-3.5 h-3.5" />
              <span>Petunjuk Pemindaian QR Code:</span>
            </div>
            <p className="text-gray-600 leading-relaxed">
              • Pastikan pencahayaan cukup dan seluruh kotak QR Code berada di dalam area pemindai.
              <br />
              • Jarak ideal kamera dengan stiker label berkisar antara 10–25 cm.
              <br />
              • Kamera secara otomatis membaca QR Code dan membuka informasi detail aset.
              <br />
              • Demi privasi, rekaman kamera tidak disimpan di server dan hanya diproses secara lokal pada browser.
            </p>
          </div>
        </div>

        {/* Modal Footer */}
        <div className="px-5 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
          <div className="text-[11px] text-gray-500 font-medium">
            Format: <span className="font-bold text-gray-700">QR Code (2D)</span>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-xs transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  );
}
