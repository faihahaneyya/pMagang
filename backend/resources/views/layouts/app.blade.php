<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventory Sewa Beli') — PT Banyan Koalindo Lestari</title>
    <meta name="description" content="Sistem Informasi Monitoring Inventaris Sewa Beli PT Banyan Koalindo Lestari">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="app-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">Inventory</span>
                        <span class="logo-subtitle">Sewa Beli</span>
                    </div>
                </div>
                <button class="sidebar-close" onclick="closeSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sidebar-company">
                <div class="company-badge">
                    <i class="fas fa-building"></i>
                    <span>PT Banyan Koalindo Lestari</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-label">Menu Utama</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('inventaris.index') }}" class="nav-link {{ request()->routeIs('inventaris.*') && !request()->routeIs('inventaris.show') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
                            <span class="nav-text">Inventaris</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('kontrak.index') }}" class="nav-link {{ request()->routeIs('kontrak.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="fas fa-file-contract"></i></span>
                            <span class="nav-text">Kontrak</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('barcode.index') }}" class="nav-link {{ request()->routeIs('barcode.*') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="fas fa-qrcode"></i></span>
                            <span class="nav-text">QR Code</span>
                        </a>
                    </li>
                </ul>

                <div class="nav-section-label" style="margin-top: 16px;">Lainnya</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="{{ route('pengaturan') }}" class="nav-link {{ request()->routeIs('pengaturan') ? 'active' : '' }}">
                            <span class="nav-icon"><i class="fas fa-gear"></i></span>
                            <span class="nav-text">Pengaturan</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-info">
                    <i class="fas fa-circle-check text-success"></i>
                    <span>Sistem Aktif</span>
                </div>
                <div class="sidebar-version">v1.0.0 · {{ date('Y') }}</div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            <!-- TOP NAVBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="topbar-breadcrumb">
                        <span class="breadcrumb-page">@yield('page-title', 'Dashboard')</span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-search">
                        <form action="{{ request()->routeIs('inventaris.*') ? route('inventaris.index') : route('dashboard') }}" method="GET" class="search-form">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Cari nama barang / kode inventaris / kode kontrak..."
                                class="search-input"
                                id="globalSearch"
                            >
                        </form>
                    </div>
                    <div class="topbar-date">
                        <i class="fas fa-calendar-alt"></i>
                        <span id="currentDate"></span>
                    </div>
                </div>
            </header>

            <!-- ALERTS -->
            @if(session('success'))
            <div class="alert alert-success" id="flashAlert">
                <i class="fas fa-circle-check"></i>
                <span>{{ session('success') }}</span>
                <button class="alert-close" onclick="document.getElementById('flashAlert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger" id="flashAlert">
                <i class="fas fa-circle-xmark"></i>
                <span>{{ session('error') }}</span>
                <button class="alert-close" onclick="document.getElementById('flashAlert').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            <!-- PAGE CONTENT -->
            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
    <script>
        // Set current date
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
            const now = new Date();
            dateEl.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        }

        // Auto-dismiss alerts
        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 4000);

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('visible');
        }
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('open');
            overlay.classList.remove('visible');
        }
    </script>
</body>
</html>
