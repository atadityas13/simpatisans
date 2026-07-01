<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-screen overflow-hidden">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMPATISANS - Sistem Informasi Manajemen Pembagian Tugas dan Penjadwalan Terintegrasi Administrasi</title>
    <!-- Anti-Flicker Script (FOUC Prevention) -->
    <script>
        (function () {
            if (localStorage.getItem('sidebar_v06_state') === 'false') {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js Plugins -->
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine.js for Interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Scrollbar khusus untuk area Sidebar (gelap) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Scrollbar khusus untuk area Main/Dashboard (terang) */
        .main-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .main-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .main-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.3); /* Lebih gelap untuk visibilitas */
            border-radius: 10px;
        }

        .main-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.5);
        }

        [x-cloak] {
            display: none !important;
        }

        /* FAIL-SAFE DESKTOP SIDEBAR (V06 MODIFIED) */
        @media (min-width: 1024px) {
            .mobile-drawer-v06 {
                display: none !important;
            }

            /* PREVENT FLICKER (Initial State from Head Class) */
            html.sidebar-is-collapsed .desktop-sidebar-v06 {
                width: 80px !important;
                transition: none !important;
            }

            html.sidebar-is-collapsed .desktop-sidebar-v06 .nav-anchor-v06 span {
                display: none !important;
            }

            html.sidebar-is-collapsed .toggle-icon-v06 {
                transform: rotate(180deg) !important;
                transition: none !important;
            }

            html.sidebar-is-collapsed .nav-icon-v06,
            html.sidebar-is-collapsed .nav-anchor-v06 {
                transition: none !important;
            }

            html.sidebar-is-collapsed #toggle-v06-btn {
                position: relative !important;
                inset: auto !important;
                margin: 0 auto 12px auto !important;
                display: flex !important;
                width: min-content !important;
                transition: none !important;
            }

            .desktop-sidebar-v06 {
                display: flex !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                background-color: #0f172a !important;
            }

            .sidebar-expanded {
                width: 240px !important;
            }

            .sidebar-collapsed {
                width: 72px !important;
            }

            /* FORCE ICON SIZE & HIDE REDUNDANT BUTTONS (FAIL-SAFE) */
            .header-hamburger-v06 {
                display: none !important;
            }

            .sidebar-collapsed .nav-icon-v06 {
                width: 32px !important;
                height: 32px !important;
                margin-right: 0 !important;
                transition: all 0.3s ease;
            }

            .sidebar-collapsed .nav-anchor-v06 {
                justify-content: center !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .sidebar-collapsed .toggle-icon-v06 {
                transform: rotate(180deg) !important;
                transform-origin: center !important;
                display: block !important;
            }

            #toggle-v06-btn {
                position: absolute !important;
                right: 8px !important;
                top: 12px !important;
                z-index: 50 !important;
                padding: 8px !important;
                transition: all 0.3s ease !important;
            }

            .sidebar-collapsed #toggle-v06-btn {
                position: relative !important;
                inset: auto !important;
                margin: 0 auto 12px auto !important;
                display: flex !important;
                width: min-content !important;
            }
        }

        @media (max-width: 1023px) {
            .desktop-sidebar-v06 {
                display: none !important;
            }

            .mobile-drawer-v06 {
                display: block;
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 antialiased" x-data="{ 
          sidebarOpen: localStorage.getItem('sidebar_v06_state') === 'false' ? false : true, 
          mobileOpen: false 
      }" x-init="$watch('sidebarOpen', val => { 
          localStorage.setItem('sidebar_v06_state', val);
          if(!val) document.documentElement.classList.add('sidebar-is-collapsed');
          else document.documentElement.classList.remove('sidebar-is-collapsed');
      })">
    <!-- WRAPPER UTAMA (Viewport-Locked App Shell - Inline Force) -->
    <div class="flex flex-row w-full relative flex-nowrap" style="height: 100vh; max-height: 100vh; overflow: hidden;">

        <!-- SIDEBAR MOBILE DRAWER (Overlay) -->
        <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-[100] mobile-drawer-v06" role="dialog"
            aria-modal="true">
            <!-- Backdrop -->
            <div x-show="mobileOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/80 backdrop-blur-sm"
                @click="mobileOpen = false"></div>

            <!-- Drawer Container with Background Image (V06 Style) -->
            <div x-show="mobileOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="relative flex-1 flex flex-col max-w-xs w-full bg-[#0f172a] text-white shadow-2xl h-full border-r border-white/10 overflow-hidden"
                style="background-color: #0f172a; background-image: url('{{ asset('img/sidebar_bg.jpg') }}'); background-size: cover; background-position: center;">

                <div class="absolute inset-0 bg-indigo-950/80 mix-blend-multiply"></div>

                <div class="relative z-10 flex flex-col h-full">
                    <div class="p-6 flex justify-end">
                        <button @click="mobileOpen = false"
                            class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-full transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 pb-12 flex flex-col items-center">
                        <div class="relative w-full flex justify-center">
                            <img src="{{ asset('img/logo.png') }}" alt="Logo SIMPATISANS"
                                class="h-32 w-auto object-contain">
                        </div>
                    </div>

                    <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto custom-scrollbar">
                        @include('layouts._nav_links')
                    </nav>
                </div>
            </div>
        </div>

        <!-- SIDEBAR DESKTOP (V06 PERSISTENT SIDEBAR - VIEWPORT-LOCKED) -->
        <aside class="flex flex-col shrink-0 text-white shadow-2xl z-50 border-r border-white/10 desktop-sidebar-v06 relative"
            :class="sidebarOpen ? 'sidebar-expanded' : 'sidebar-collapsed'"
            style="height: 100vh; max-height: 100vh; background-color: #0f172a; background-image: url('{{ asset('img/sidebar_bg.jpg') }}'); background-size: cover; background-position: center;">

            <!-- Dark Overlay for V06 aesthetic (Force solid background if image fails) -->
            <div class="absolute inset-0 bg-[#0f172a] opacity-95 backdrop-blur-[4px] pointer-events-none"></div>

            <div class="relative z-20 flex flex-col h-full">
                <div class="relative px-4 pt-4 pb-2">
                    <!-- Toggle Button (Absolute Corner) -->
                    <button @click="sidebarOpen = !sidebarOpen" id="toggle-v06-btn"
                        class="hover:bg-white/10 text-white/50 hover:text-white rounded-xl transition-all duration-300"
                        title="Toggle Sidebar">
                        <svg class="h-5 w-5 transition-all duration-500 toggle-icon-v06" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 19l-7-7 7-7M4 12h16" />
                        </svg>
                    </button>

                    <!-- Logo Section (Centered & High-Alignment) -->
                    <div class="flex flex-col items-center">
                        <div class="relative transition-all duration-500 w-full flex justify-center"
                            :class="sidebarOpen ? 'h-36' : 'h-12'">
                            <img src="{{ asset('img/logo.png') }}" alt="SIMPATISANS Logo"
                                class="max-h-full w-auto object-contain drop-shadow-[0_10px_10px_rgba(0,0,0,0.8)] filter brightness-110">
                        </div>
                    </div>
                </div>

                <!-- Navigation List (Labels hidden in mini mode) -->
                <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto custom-scrollbar scroll-smooth">
                    @include('layouts._nav_links')
                </nav>

            </div>
        </aside>

        <!-- MAIN LAYOUT WRAPPER (RIGHT SIDE - VIEWPORT-LOCKED) -->
        <div class="flex-1 flex flex-col min-w-0 bg-white relative h-screen overflow-hidden min-h-0" dir="ltr">

            <!-- UNIVERSAL DASHBOARD HEADER (Shrink-0 to keep static) -->
            <header
                class="h-20 bg-white border-b border-gray-100 flex items-center px-6 lg:px-10 justify-between shrink-0 relative z-40">
                <div class="flex items-center">
                    <!-- Dashboard Hamburger (For Mobile Only) -->
                    <button @click="mobileOpen = true"
                        class="p-2.5 -ml-2 text-gray-500 hover:bg-gray-50 hover:text-indigo-600 rounded-xl transition lg:hidden mr-4 header-hamburger-v06">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1
                        class="text-xl lg:text-2xl font-black tracking-tight text-gray-900 border-l-4 border-indigo-600 pl-5 truncate">
                        @yield('header', 'Dashboard SIMPATISANS')
                        @if (isset($activeSemester))
                            <span class="text-gray-400 font-medium ml-2 text-base lg:text-lg">
                                | TA-Semester : {{ $activeSemester->nama_tahun }} - {{ $activeSemester->tipe }}
                            </span>
                        @endif
                    </h1>
                </div>

                <div class="flex items-center">
                    <div class="relative mr-2 md:mr-4" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center bg-white hover:bg-gray-50 border border-gray-200 rounded-full shadow-sm transition pr-1 pl-3 py-1">
                            <div class="flex flex-col items-end hidden md:flex px-2" style="max-width: 12rem;">
                                <span class="text-sm font-bold text-gray-800 leading-none truncate w-full text-right">
                                    {{ auth()->check() ? (auth()->user()->nama_lengkap ?? auth()->user()->username) : 'Guest' }}
                                </span>
                                <span class="text-gray-500 font-medium truncate w-full text-right uppercase tracking-wide" style="font-size: 11px; margin-top: 4px;">
                                    {{ auth()->check() ? (auth()->user()->jabatan ?? auth()->user()->role) : 'Visitor' }}
                                </span>
                            </div>
                            <div class="rounded-full overflow-hidden border border-gray-200 bg-gray-100 shrink-0" style="width: 40px; height: 40px;">
                                @if(auth()->check() && auth()->user()->foto)
                                    <img class="h-full w-full object-cover" src="{{ asset('storage/' . auth()->user()->foto) }}" alt="Profile Photo">
                                @else
                                    <svg class="h-full w-full text-gray-400 p-1" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                @endif
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" 
                             style="display: none;"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50 overflow-hidden">
                            
                            <div class="px-4 py-3 md:hidden border-b border-gray-100 bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->check() ? (auth()->user()->nama_lengkap ?? auth()->user()->username) : 'Guest' }}</p>
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ auth()->check() ? (auth()->user()->jabatan ?? ucfirst(auth()->user()->role)) : 'Visitor' }}</p>
                            </div>

                            @auth
                            <a href="{{ route('profile.index') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Profil Saya
                            </a>

                            @php
                                $user = auth()->user();
                                $hasAdminRight = $user && ($user->isSuperAdmin() || $user->isAdminKurikulum());
                                $hasGuruRight = $user && \App\Models\Guru::where('username', $user->username)->exists();
                                $activeRole = session('active_role');
                            @endphp

                            @if($hasAdminRight && $hasGuruRight)
                                <a href="{{ route('auth.select-role') }}" 
                                   class="flex items-center px-4 py-2.5 text-sm text-indigo-600 hover:bg-indigo-50 transition">
                                   <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                   Pindah Akses
                                </a>
                            @endif
                            
                            <div class="h-px bg-gray-100 my-1"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                    <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Logout
                                </button>
                            </form>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- PRIMARY WORKING AREA (Independent Scroll Area) -->
            <main class="flex-1 p-6 lg:p-12 bg-gray-50/50 min-w-0 min-h-0 overflow-y-auto main-scrollbar" dir="ltr">
                <div class="max-w-[1500px] mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const confirmMsg = form.getAttribute('data-confirm');

            if (confirmMsg && !form.dataset.confirmed) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Tindakan',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#f43f5e',
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'rounded-2xl shadow-2xl border-0',
                        title: 'font-bold text-gray-900',
                        htmlContainer: 'text-gray-600'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = true;
                        form.submit();
                    }
                });
            }
        });

        // Toast Configuration for Flash Messages
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        @if(session('success'))
            Toast.fire({
                icon: 'success',
                title: @json(session('success'))
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: @json(session('error'))
            });
        @endif

        @if(session('warning'))
            Toast.fire({
                icon: 'warning',
                title: @json(session('warning'))
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                html: '<ul class="text-left text-sm">@foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-2xl shadow-2xl border-0',
                    title: 'font-bold text-gray-900',
                }
            });
        @endif
    </script>
    @stack('modals')
</body>

</html>