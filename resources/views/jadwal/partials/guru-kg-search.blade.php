{{-- Combobox guru: dropdown teleport ke body (tidak terpotong modal) --}}
<div x-ref="guruKgCombobox">
    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">{{ $label ?? '1. Pilih Guru' }}</label>

    <button type="button"
        @mousedown.prevent="toggleGuruKgDropdown()"
        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-left flex items-center gap-2 bg-white hover:border-indigo-300 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition-colors">
        <span x-show="selectedGuruDisplayLabel()"
            class="flex-1 truncate text-gray-800"
            x-text="selectedGuruDisplayLabel()"></span>
        <span x-show="!selectedGuruDisplayLabel()"
            class="flex-1 text-gray-400 font-bold">— Pilih guru —</span>
        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform"
            :class="{ 'rotate-180': editor.guruKgDropdownOpen }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div x-show="editor && editor.guruKgDropdownOpen"
            x-ref="guruKgDropdownPanel"
            x-cloak
            :style="guruKgDropdownStyle()"
            class="fixed bg-white border border-gray-200 rounded-lg shadow-2xl overflow-hidden"
            style="z-index: 100001;">

            <div class="p-2 border-b border-gray-100 bg-gray-50">
                <input type="text"
                    x-ref="guruKgSearchInput"
                    x-model="editor.guruKgQuery"
                    @input="onGuruKgInput()"
                    @keydown.enter.prevent="confirmGuruKgFromQuery()"
                    @keydown.escape.prevent="closeGuruKgDropdown()"
                    @keydown.arrow-down.prevent="focusGuruKgOption(1)"
                    @keydown.arrow-up.prevent="focusGuruKgOption(-1)"
                    placeholder="Ketik KG atau nama guru..."
                    autocomplete="off"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm font-bold text-gray-800 uppercase tracking-wider focus:ring-indigo-500 focus:border-indigo-400 bg-white" />
            </div>

            <div class="overflow-y-auto" style="max-height: min(14rem, 40vh);">
                <template x-for="(g, idx) in filteredGuruOptionsForEditor()" :key="'kgopt-' + g.guru_id">
                    <button type="button"
                        :data-kg-idx="idx"
                        @mousedown.prevent="selectGuruFromKgSearch(g)"
                        :disabled="g.isFull"
                        class="w-full text-left px-3 py-2.5 text-sm border-b border-gray-50 last:border-0 flex items-center gap-2 transition-colors"
                        :class="{
                            'opacity-50 cursor-not-allowed bg-gray-50': g.isFull,
                            'hover:bg-indigo-50 cursor-pointer': !g.isFull,
                            'bg-indigo-100': editor.selectedGuruId == g.guru_id
                        }">
                        <span class="font-black text-indigo-700 min-w-[2.5rem]" x-text="g.kg"></span>
                        <span class="text-gray-700 flex-1 truncate" x-text="g.guru"></span>
                        <span x-show="g.isFull" class="text-[9px] font-black text-red-500 uppercase shrink-0">Penuh</span>
                    </button>
                </template>

                <p x-show="editor.guruKgQuery && filteredGuruOptionsForEditor().length === 0 && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length > 0"
                    class="px-3 py-4 text-xs text-red-600 font-bold italic text-center">
                    Guru tidak ditemukan
                </p>
                <p x-show="!editor.guruKgQuery && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length === 0"
                    class="px-3 py-4 text-xs text-gray-500 font-bold italic text-center">
                    Tidak ada guru di kelas ini
                </p>
            </div>
        </div>
    </template>

    <p x-show="editor && isBtqOnlySlot(editor.hari, editor.jam) && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length === 0"
        class="mt-2 text-xs text-emerald-800 font-bold italic">
        Tidak ada guru pengampu BTQ di kelas ini
    </p>
</div>
