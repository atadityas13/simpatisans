<div x-data="{ activeCategory: '{{ request()->is('master/*') ? 'master' : (request()->is('pengaturan/*') ? 'config' : 'manajemen') }}' }"
    class="space-y-6">

    <!-- DASHBOARD (Standalone) -->
    <div class="px-4">
        @php $isGuruMode = session('active_role') === 'guru'; @endphp
        <a href="{{ $isGuruMode ? '/guru' : '/' }}"
            class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ (request()->is('/') || request()->is('guru')) ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
            <svg class="transition-all duration-300 nav-icon-v06 {{ (request()->is('/') || request()->is('guru')) ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                :class="sidebarOpen ? 'w-4 h-4 mr-3' : 'w-8 h-8'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="sidebarOpen" x-transition>{{ $isGuruMode ? 'Dashboard Guru' : 'Dashboard Utama' }}</span>
        </a>
    </div>

    @if(session('active_role') === 'admin')
        <!-- KATEGORI MANAJEMEN -->
        <div class="space-y-1">
            <button @click="activeCategory = activeCategory === 'manajemen' ? null : 'manajemen'"
                class="w-full group flex items-center justify-between px-5 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] hover:text-indigo-400 transition-colors">
                <div class="flex items-center">
                    <svg x-show="!sidebarOpen" class="w-8 h-8 text-slate-500 group-hover:text-indigo-400 transition-all"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span x-show="sidebarOpen">MANAJEMEN</span>
                </div>
                <svg x-show="sidebarOpen" class="w-3 h-3 transition-transform duration-300 transform"
                    :class="activeCategory === 'manajemen' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="activeCategory === 'manajemen'" x-collapse class="px-4 space-y-1 mt-1">
                <a href="/pembagian-tugas"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('pembagian-tugas*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('pembagian-tugas*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span x-show="sidebarOpen">Pembagian Tugas</span>
                </a>
                <a href="/jadwal"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('jadwal*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('jadwal*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-show="sidebarOpen">Penjadwalan</span>
                </a>
                <a href="/cetak"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('cetak*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('cetak*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span x-show="sidebarOpen">Cetak</span>
                </a>
            </div>
        </div>

        <!-- KATEGORI DATA MASTER -->
        <div class="space-y-1">
            <button @click="activeCategory = activeCategory === 'master' ? null : 'master'"
                class="w-full group flex items-center justify-between px-5 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] hover:text-indigo-400 transition-colors">
                <div class="flex items-center">
                    <svg x-show="!sidebarOpen" class="w-8 h-8 text-slate-500 group-hover:text-indigo-400 transition-all"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span x-show="sidebarOpen">DATA MASTER</span>
                </div>
                <svg x-show="sidebarOpen" class="w-3 h-3 transition-transform duration-300 transform"
                    :class="activeCategory === 'master' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="activeCategory === 'master'" x-collapse class="px-4 space-y-1 mt-1">
                <a href="/master/guru"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('master/guru*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('master/guru*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span x-show="sidebarOpen">Data Guru</span>
                </a>
                <a href="/master/kelas"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('master/kelas*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('master/kelas*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span x-show="sidebarOpen">Rombel</span>
                </a>
                <a href="/master/mapel"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('master/mapel*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('master/mapel*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span x-show="sidebarOpen">Mata Pelajaran</span>
                </a>
                <a href="/master/tugas-tambahan"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('master/tugas-tambahan*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="transition-all duration-300 nav-icon-v06 w-4 h-4 mr-3 {{ request()->is('master/tugas-tambahan*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span x-show="sidebarOpen">Tugas Tambahan</span>
                </a>
            </div>
        </div>

        <!-- KATEGORI PENGATURAN -->
        <div class="space-y-1">
            <button @click="activeCategory = activeCategory === 'config' ? null : 'config'"
                class="w-full group flex items-center justify-between px-5 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] hover:text-indigo-400 transition-colors">
                <div class="flex items-center">
                    <svg x-show="!sidebarOpen" class="w-8 h-8 text-slate-500 group-hover:text-indigo-400 transition-all"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span x-show="sidebarOpen">PENGATURAN</span>
                </div>
                <svg x-show="sidebarOpen" class="w-3 h-3 transition-transform duration-300 transform"
                    :class="activeCategory === 'config' ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="activeCategory === 'config'" x-collapse class="px-4 space-y-1 mt-1">
                <a href="/pengaturan/semester"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('pengaturan/semester*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->is('pengaturan/semester*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-show="sidebarOpen">Semester</span>
                </a>
                <a href="/pengaturan/pengumuman"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('pengaturan/pengumuman*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->is('pengaturan/pengumuman*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span x-show="sidebarOpen">Pengumuman</span>
                </a>
                <a href="/config/pengguna"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('config/pengguna*') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->is('config/pengguna*') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span x-show="sidebarOpen">Pengguna</span>
                </a>
                @if(auth()->user()?->isSuperAdmin())
                    <a href="/pengaturan/admin"
                        class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('pengaturan/admin*') ? 'bg-purple-600/10 text-purple-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->is('pengaturan/admin*') ? 'text-purple-400' : 'text-slate-500 group-hover:text-purple-400' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span x-show="sidebarOpen">Admin</span>
                    </a>
                    <a href="{{ route('database.index') }}"
                        class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('pengaturan/database*') ? 'bg-red-600/10 text-red-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->is('pengaturan/database*') ? 'text-red-400' : 'text-slate-500 group-hover:text-red-400' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                        <span x-show="sidebarOpen">Database</span>
                    </a>
                @endif
            </div>
        </div>
    @else
        <!-- KATEGORI GURU -->
        <div class="space-y-1">
            <div class="px-5 py-2 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                <span x-show="sidebarOpen">MENU GURU</span>
            </div>
            <div class="px-4 space-y-1">
                <a href="/guru"
                    class="group flex items-center px-4 py-2 text-sm font-semibold rounded-xl transition-all nav-anchor-v06 {{ request()->is('guru') ? 'bg-indigo-600/10 text-indigo-400' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-4 h-4 mr-3 {{ request()->is('guru') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-indigo-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-show="sidebarOpen">Jadwal Saya</span>
                </a>
            </div>
        </div>
    @endif

    {{-- SWITCH ROLE & LOGOUT dipindah ke header dropdown (Profil) --}}
</div>