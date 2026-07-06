{{-- Combobox pencarian guru by KG — dipakai di modal editor (kelas/hari/matriks) --}}
<div class="relative" @click.outside="editor.guruKgDropdownOpen = false">
    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">{{ $label ?? '1. Pilih Guru' }}</label>
    <input type="text"
        x-model="editor.guruKgQuery"
        @input="onGuruKgInput()"
        @focus="editor.guruKgDropdownOpen = true"
        @keydown.enter.prevent="confirmGuruKgFromQuery()"
        @keydown.escape="editor.guruKgDropdownOpen = false"
        @keydown.arrow-down.prevent="focusGuruKgOption(1)"
        @keydown.arrow-up.prevent="focusGuruKgOption(-1)"
        placeholder="Ketik KG, mis. AL atau DD"
        autocomplete="off"
        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 focus:border-indigo-400 bg-white uppercase tracking-wider" />

    <div x-show="editor.guruKgDropdownOpen && filteredGuruOptionsForEditor().length > 0"
        x-cloak
        class="absolute z-30 w-full mt-1 max-h-52 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-xl">
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
    </div>

    <p x-show="editor.guruKgQuery && filteredGuruOptionsForEditor().length === 0 && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length > 0"
        class="mt-2 text-xs text-red-600 font-bold italic">
        KG tidak ditemukan di kelas ini
    </p>
    <p x-show="editor && isBtqOnlySlot(editor.hari, editor.jam) && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length === 0"
        class="mt-2 text-xs text-emerald-800 font-bold italic">
        Tidak ada guru pengampu BTQ di kelas ini
    </p>
    <p class="mt-1 text-[9px] text-gray-400 italic">Ketik kode KG → Enter untuk pilih, atau klik dari daftar</p>
</div>
