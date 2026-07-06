@php
    $mode = $mode ?? 'server';
    $value = $value ?? '';
    $action = $action ?? request()->url();
    $wrapperClass = $wrapperClass ?? 'flex flex-wrap items-center gap-2';
@endphp

<div class="{{ $wrapperClass }}">
    @if($mode === 'server')
        <form method="GET" action="{{ $action }}" class="relative flex-1 min-w-[220px] max-w-lg">
            @foreach(request()->except(['q', 'page']) as $key => $val)
                @if(is_scalar($val))
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" name="q" value="{{ $value }}"
                placeholder="Cari KG, nama, NIP/NUPTK, jabatan, mapel..."
                class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 bg-white shadow-sm" />
        </form>
        @if($value !== '')
            @php
                $resetQuery = request()->except(['q', 'page']);
                $resetUrl = $action . (count($resetQuery) ? '?' . http_build_query($resetQuery) : '');
            @endphp
            <a href="{{ $resetUrl }}"
                class="text-xs font-bold text-gray-500 hover:text-indigo-600 uppercase tracking-wide whitespace-nowrap">
                Reset pencarian
            </a>
        @endif
    @else
        <div class="relative flex-1 min-w-[220px] max-w-lg">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" x-model="guruSearch"
                placeholder="Cari KG, nama, NIP/NUPTK, jabatan, mapel..."
                class="w-full pl-10 pr-9 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 bg-white shadow-sm" />
            <button type="button" x-show="guruSearch" x-cloak @click="guruSearch = ''"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 text-sm font-bold"
                title="Hapus pencarian">&times;</button>
        </div>
        <span x-show="guruSearch.trim()" x-cloak class="text-xs text-gray-500 font-medium whitespace-nowrap"
            x-text="guruMatchCount() + ' guru ditemukan'"></span>
    @endif
</div>
