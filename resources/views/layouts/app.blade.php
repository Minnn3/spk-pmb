<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPK PMB') — SPK Strategi Promosi dan Alokasi Staf PMB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-toast />

    @php
        $pengguna = auth()->user();
        $inisial = collect(explode(' ', trim((string) ($pengguna->name ?? 'U'))))
            ->filter()
            ->take(2)
            ->map(static fn (string $kata): string => mb_strtoupper(mb_substr($kata, 0, 1)))
            ->implode('');
    @endphp

    <div class="app-shell">
        <div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>

        <aside id="appSidebar" class="sidebar" aria-label="Navigasi Utama">
            <div class="sidebar-brand">
                <div class="sidebar-logo" aria-hidden="true">
                    <x-icon name="TrendingUp" :size="18" :strokeWidth="1.75" />
                </div>
                <div class="brand-text">
                    <div class="brand-title">SPK PMB</div>
                    <div class="brand-subtitle">Strategi Promosi &amp; Alokasi Staf</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-group">
                    <div class="nav-group-label">Analisis</div>
                    <a
                        href="{{ route('dashboard.index') }}"
                        class="nav-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}"
                        title="Dashboard"
                    >
                        <x-icon name="LayoutDashboard" :size="20" :strokeWidth="1.5" />
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a
                        href="{{ route('perhitungan.index') }}"
                        class="nav-item {{ request()->routeIs('perhitungan.*') ? 'active' : '' }}"
                        title="Perhitungan"
                    >
                        <x-icon name="TrendingUp" :size="20" :strokeWidth="1.5" />
                        <span class="nav-label">Perhitungan</span>
                    </a>
                </div>

                @can('kelola-spk')
                    <div class="nav-group">
                        <div class="nav-group-label">Data</div>
                        <a
                            href="{{ route('data-historis.index') }}"
                            class="nav-item {{ request()->routeIs('data-historis.*') ? 'active' : '' }}"
                            title="Data Historis"
                        >
                            <x-icon name="Database" :size="20" :strokeWidth="1.5" />
                            <span class="nav-label">Data Historis</span>
                        </a>
                        <a
                            href="{{ route('import-excel.index') }}"
                            class="nav-item {{ request()->routeIs('import-excel.*') ? 'active' : '' }}"
                            title="Import Excel"
                        >
                            <x-icon name="Upload" :size="20" :strokeWidth="1.5" />
                            <span class="nav-label">Import Excel</span>
                        </a>
                    </div>

                    <div class="nav-group">
                        <div class="nav-group-label">Sistem</div>
                        <a
                            href="{{ route('pengaturan-spk.index') }}"
                            class="nav-item {{ request()->routeIs('pengaturan-spk.*') ? 'active' : '' }}"
                            title="Pengaturan SPK"
                        >
                            <x-icon name="Settings" :size="20" :strokeWidth="1.5" />
                            <span class="nav-label">Pengaturan SPK</span>
                        </a>
                    </div>
                @endcan
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-avatar" aria-hidden="true">{{ $inisial }}</div>
                    <div class="sidebar-footer-text">
                        <div class="sidebar-user-name">{{ $pengguna->name }}</div>
                        <div class="sidebar-user-role">{{ $pengguna->role }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="table-action-btn"
                        title="Keluar"
                        aria-label="Keluar dari sesi"
                    >
                        <x-icon name="LogOut" :size="18" :strokeWidth="1.75" />
                    </button>
                </form>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button
                        type="button"
                        id="sidebarToggleBtn"
                        class="topbar-hamburger"
                        aria-label="Buka navigasi"
                        aria-expanded="false"
                        aria-controls="appSidebar"
                    >
                        <x-icon name="Menu" :size="18" :strokeWidth="2" />
                    </button>
                    <h1 class="topbar-page-title">@yield('page_title', 'Dashboard')</h1>
                </div>

                <div class="topbar-right">
                    @yield('topbar_actions')
                </div>
            </header>

            <main class="app-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

