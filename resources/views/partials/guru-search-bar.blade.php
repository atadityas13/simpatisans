{{-- Toolbar pencarian guru realtime (Alpine: guruSearch, guruSearchBlobs, guruMatchCount) --}}
<div class="px-4 sm:px-5 py-3 border-b border-gray-100 bg-gray-50/90">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="relative flex-1 min-w-0">
            <label for="guru-search-input" class="sr-only">Cari guru</label>
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input id="guru-search-input" type="text" x-model="guruSearch"
                placeholder="Ketik KG, nama, NIP, NUPTK, jabatan, atau mapel..."
                autocomplete="off"
                class="w-full h-10 pl-10 pr-10 text-sm rounded-lg border border-gray-200 bg-white text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-400 transition-shadow shadow-sm" />
            <button type="button" x-show="guruSearch" x-cloak @click="guruSearch = ''"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center text-gray-400 hover:text-gray-700 rounded-md hover:bg-gray-100 text-base leading-none"
                title="Hapus pencarian" aria-label="Hapus pencarian">&times;</button>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 min-w-[7rem]">
            <span x-show="!guruSearch.trim()" class="text-[11px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap"
                x-text="guruSearchBlobs.length + ' guru'"></span>
            <span x-show="guruSearch.trim()" x-cloak class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider whitespace-nowrap"
                x-text="guruMatchCount() + ' ditemukan'"></span>
        </div>
    </div>
</div>
