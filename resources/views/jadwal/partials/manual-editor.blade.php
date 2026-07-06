{{-- Tab mode tampilan manual scheduling — Matriks Lengkap pertama --}}
<div class="print:hidden mb-4 flex flex-wrap gap-2">
    <button type="button" @click="viewMode = 'matrix'"
        :class="viewMode === 'matrix' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
        class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide transition-all">
        Matriks Lengkap
    </button>
    <button type="button" @click="viewMode = 'kelas'"
        :class="viewMode === 'kelas' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
        class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide transition-all">
        Per Kelas
    </button>
    <button type="button" @click="viewMode = 'guru'"
        :class="viewMode === 'guru' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
        class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide transition-all">
        Per Guru
    </button>
    <button type="button" @click="viewMode = 'hari'"
        :class="viewMode === 'hari' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
        class="px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wide transition-all">
        Per Hari
    </button>
</div>

{{-- PER KELAS --}}
<div x-show="viewMode === 'kelas'" x-cloak class="print:hidden mb-8">
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="lg:w-64 shrink-0 space-y-3">
            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest">Pilih Kelas</label>
            <select x-model.number="selectedKelasId"
                class="w-full bg-white border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-indigo-900 shadow-sm">
                <template x-for="k in kelasFlat" :key="k.id">
                    <option :value="k.id" x-text="k.tingkat + ' — ' + k.nama"></option>
                </template>
            </select>
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3">
                <div class="text-[10px] font-black uppercase text-indigo-400 tracking-widest mb-1">Progress JTM</div>
                <div class="text-2xl font-black text-indigo-900">
                    <span x-text="kelasFilledCount(selectedKelasId)"></span>
                    <span class="text-indigo-300">/</span>
                    <span x-text="strukturHariTotal"></span>
                </div>
                <div class="mt-2 h-2 bg-indigo-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 transition-all"
                        :style="'width:' + Math.min(100, (kelasFilledCount(selectedKelasId) / strukturHariTotal) * 100) + '%'"></div>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-sm">
                <div class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-2">Mapel Belum Penuh</div>
                <ul class="space-y-1 max-h-48 overflow-y-auto">
                    <template x-for="b in incompleteBebanForKelas(selectedKelasId)" :key="b.id">
                        <li class="text-[10px] flex justify-between gap-2 py-1 border-b border-gray-50 last:border-0">
                            <span class="font-bold text-gray-800 truncate" x-text="b.mapel"></span>
                            <span class="shrink-0 font-mono text-amber-600" x-text="b.placed + '/' + b.jtm"></span>
                        </li>
                    </template>
                    <li x-show="incompleteBebanForKelas(selectedKelasId).length === 0"
                        class="text-[10px] text-emerald-600 font-bold italic">Semua mapel sudah penuh ✓</li>
                </ul>
            </div>
        </div>
        <div class="flex-1 overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-xs border-collapse min-w-[480px]">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-20">Hari</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-16">Jam</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-24">Waktu</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px]">Isi Jadwal</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="hari in days" :key="hari">
                        <template x-for="jam in jamRange(hari)" :key="hari + '-' + jam">
                            <tr class="hover:bg-indigo-50/30">
                                <template x-if="jam === 1">
                                    <td class="border border-gray-200 p-2 font-bold text-center text-[10px] uppercase bg-gray-50"
                                        :rowspan="strukturHari[hari]" x-text="hari"></td>
                                </template>
                                <td class="border border-gray-200 p-1 text-center font-mono font-bold" x-text="jam"></td>
                                <td class="border border-gray-200 p-1 text-center text-[9px] text-gray-500"
                                    x-text="getJamLabel(hari, jam)"></td>
                                <td @dblclick="is_active && openEditor(hari, jam, selectedKelasId)"
                                    class="border border-gray-200 p-2 cursor-pointer min-h-[36px] transition-colors"
                                    :class="getSlot(hari, jam, selectedKelasId) ? 'bg-indigo-50' : 'bg-white hover:bg-amber-50'">
                                    <template x-if="getSlot(hari, jam, selectedKelasId)">
                                        <div>
                                            <span class="font-black text-indigo-700"
                                                x-text="'[' + getSlot(hari, jam, selectedKelasId).kg + ']'"></span>
                                            <span class="text-gray-700 ml-1"
                                                x-text="getSlot(hari, jam, selectedKelasId).mapel"></span>
                                        </div>
                                    </template>
                                    <span x-show="!getSlot(hari, jam, selectedKelasId)"
                                        class="text-gray-300 italic text-[10px]">kosong — double klik</span>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- PER GURU --}}
<div x-show="viewMode === 'guru'" x-cloak class="print:hidden mb-8">
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="lg:w-64 shrink-0 space-y-3">
            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest">Pilih Guru</label>
            <select x-model.number="selectedGuruIdView"
                class="w-full bg-white border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-indigo-900 shadow-sm">
                <template x-for="g in guruList" :key="g.id">
                    <option :value="g.id" x-text="'[' + g.kode + '] ' + g.nama"></option>
                </template>
            </select>
            <div class="bg-orange-50 border border-orange-100 rounded-xl p-3">
                <div class="text-[10px] font-black uppercase text-orange-400 tracking-widest mb-1">Beban Mengajar</div>
                <div class="text-2xl font-black text-orange-900">
                    <span x-text="guruPlacedCount(selectedGuruIdView)"></span>
                    <span class="text-orange-300">/</span>
                    <span x-text="guruJtmTotal(selectedGuruIdView)"></span>
                    <span class="text-xs font-bold text-orange-500 ml-1">jam</span>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-sm">
                <div class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-2">Belum Terisi</div>
                <ul class="space-y-1 max-h-48 overflow-y-auto">
                    <template x-for="b in bebanListForGuruIncomplete(selectedGuruIdView)" :key="b.id">
                        <li class="text-[10px] py-1 border-b border-gray-50 last:border-0">
                            <div class="font-bold text-gray-800" x-text="b.mapel"></div>
                            <div class="text-gray-500 flex justify-between">
                                <span x-text="'Kelas ' + kelasName(b.kelas_id)"></span>
                                <span class="font-mono text-amber-600" x-text="b.placed + '/' + b.jtm"></span>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
            <p class="text-[9px] text-gray-400 italic">Preset blokir (merah/oranye) ditandai di sel.</p>
        </div>
        <div class="flex-1 overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-xs border-collapse min-w-[480px]">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-20">Hari</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-16">Jam</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px] w-24">Waktu</th>
                        <th class="border border-gray-200 p-2 font-black uppercase text-[10px]">Kelas / Mapel</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="hari in days" :key="'g-' + hari">
                        <template x-for="jam in jamRange(hari)" :key="'g-' + hari + '-' + jam">
                            <tr>
                                <template x-if="jam === 1">
                                    <td class="border border-gray-200 p-2 font-bold text-center text-[10px] uppercase bg-gray-50"
                                        :rowspan="strukturHari[hari]" x-text="hari"></td>
                                </template>
                                <td class="border border-gray-200 p-1 text-center font-mono font-bold" x-text="jam"></td>
                                <td class="border border-gray-200 p-1 text-center text-[9px] text-gray-500"
                                    x-text="getJamLabel(hari, jam)"></td>
                                <td @dblclick="is_active && openEditorFromGuru(hari, jam)"
                                    class="border border-gray-200 p-2 cursor-pointer min-h-[36px]"
                                    :class="{
                                        'bg-red-100 ring-1 ring-red-300': hasConstraintForGuru(selectedGuruIdView, hari, jam, 0),
                                        'bg-orange-100 ring-1 ring-orange-300': hasConstraintForGuru(selectedGuruIdView, hari, jam, 1),
                                        'bg-indigo-50': findGuruSlot(selectedGuruIdView, hari, jam) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 0) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 1),
                                        'hover:bg-amber-50': !findGuruSlot(selectedGuruIdView, hari, jam)
                                    }">
                                    <template x-if="findGuruSlot(selectedGuruIdView, hari, jam)">
                                        <div>
                                            <span class="font-black text-indigo-700"
                                                x-text="findGuruSlot(selectedGuruIdView, hari, jam).kelas"></span>
                                            <span class="text-gray-600 ml-1"
                                                x-text="findGuruSlot(selectedGuruIdView, hari, jam).mapel"></span>
                                        </div>
                                    </template>
                                    <span x-show="!findGuruSlot(selectedGuruIdView, hari, jam) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 0) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 1)"
                                        class="text-gray-300 italic text-[10px]">kosong</span>
                                    <span x-show="hasConstraintForGuru(selectedGuruIdView, hari, jam, 0)"
                                        class="text-[9px] font-bold text-red-600 uppercase">Blokir</span>
                                    <span x-show="hasConstraintForGuru(selectedGuruIdView, hari, jam, 1)"
                                        class="text-[9px] font-bold text-orange-600 uppercase">Preserve</span>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- PER HARI --}}
<div x-show="viewMode === 'hari'" x-cloak class="print:hidden mb-8">
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">Pilih Hari</label>
        <select x-model="selectedHariView"
            class="bg-white border border-gray-200 rounded-lg p-2 text-sm font-bold text-indigo-900 shadow-sm">
            <template x-for="hari in days" :key="'d-' + hari">
                <option :value="hari" x-text="hari"></option>
            </template>
        </select>
        <span class="text-[10px] text-gray-400 font-bold"
            x-text="hariFilledCount(selectedHariView) + ' / ' + strukturHari[selectedHariView] + ' slot terisi'"></span>
    </div>
    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
        <table class="w-full text-[10px] border-collapse min-w-[900px]">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border border-gray-200 p-2 font-black w-12">Jam</th>
                    <th class="border border-gray-200 p-2 font-black w-20">Waktu</th>
                    <template x-for="k in kelasFlat" :key="'h-' + k.id">
                        <th class="border border-gray-200 p-1 font-black text-center min-w-[56px]"
                            x-text="k.nama"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="jam in jamRange(selectedHariView)" :key="'hr-' + jam">
                    <tr>
                        <td class="border border-gray-200 p-1 text-center font-mono font-bold" x-text="jam"></td>
                        <td class="border border-gray-200 p-1 text-center text-[9px] text-gray-500"
                            x-text="getJamLabel(selectedHariView, jam)"></td>
                        <template x-for="k in kelasFlat" :key="'hc-' + k.id + '-' + jam">
                            <td @dblclick="is_active && openEditor(selectedHariView, jam, k.id)"
                                class="border border-gray-200 p-1 cursor-pointer text-center min-h-[28px] align-middle"
                                :class="getSlot(selectedHariView, jam, k.id) ? 'bg-indigo-50' : 'hover:bg-amber-50'">
                                <template x-if="getSlot(selectedHariView, jam, k.id)">
                                    <div>
                                        <div class="font-black text-indigo-700 leading-tight"
                                            x-text="getSlot(selectedHariView, jam, k.id).kg"></div>
                                        <div class="text-[8px] text-gray-500 truncate leading-tight"
                                            x-text="getSlot(selectedHariView, jam, k.id).mapel"></div>
                                    </div>
                                </template>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Editor — kontekstual per mode --}}
<div x-show="showEditorModal" x-cloak
    class="fixed inset-0 z-[8000] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
    @keydown.escape.window="closeEditor()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-indigo-100 overflow-hidden"
        @click.stop>
        <div class="bg-indigo-600 px-5 py-3 flex justify-between items-center">
            <div>
                <h3 class="text-white font-black text-sm uppercase tracking-wide">Edit Slot Jadwal</h3>
                <p class="text-indigo-200 text-[10px] font-bold mt-0.5"
                    x-text="editorSubtitle()"></p>
            </div>
            <button type="button" @click="closeEditor()" class="text-indigo-200 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4" x-show="editor">

            {{-- Per Kelas & Per Hari: Mapel → Guru --}}
            <template x-if="editor && (editor.context === 'kelas' || editor.context === 'hari')">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Mapel</label>
                        <select x-model="editor.selectedMapel" @change="onMapelSelectKelas()"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih mapel —</option>
                            <template x-for="mg in mapelGroupsForKelas(editor.kelasId)" :key="mg.mapel">
                                <option :value="mg.mapel" :disabled="mg.isFull"
                                    x-text="mg.mapel + ' (' + mg.placed + '/' + mg.total + ' jam)' + (mg.isFull ? ' — PENUH' : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedMapel && guruOptionsForKelasMapel(editor.kelasId, editor.selectedMapel).length > 1">
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Guru</label>
                        <select x-model.number="editor.selectedBebanId"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih guru —</option>
                            <template x-for="b in guruOptionsForKelasMapel(editor.kelasId, editor.selectedMapel)" :key="b.id">
                                <option :value="b.id"
                                    x-text="'[' + b.kg + '] ' + b.guru + ' (' + b.placed + '/' + b.jtm + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedMapel && guruOptionsForKelasMapel(editor.kelasId, editor.selectedMapel).length === 1"
                        class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-800">
                        <span class="font-black">Guru:</span>
                        <span x-text="'[' + (guruOptionsForKelasMapel(editor.kelasId, editor.selectedMapel)[0]?.kg || '') + '] ' + (guruOptionsForKelasMapel(editor.kelasId, editor.selectedMapel)[0]?.guru || '')"></span>
                    </div>
                </div>
            </template>

            {{-- Per Guru: Mapel → Kelas --}}
            <template x-if="editor && editor.context === 'guru'">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Mapel</label>
                        <select x-model="editor.selectedMapel" @change="onMapelSelectGuru()"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih mapel —</option>
                            <template x-for="mg in mapelGroupsForGuru(editor.guruId)" :key="mg.mapel">
                                <option :value="mg.mapel"
                                    x-text="mg.mapel + ' (' + mg.placed + '/' + mg.total + ' jam tersisa)'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedMapel && kelasOptionsForGuruMapel(editor.guruId, editor.selectedMapel).length > 1">
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Kelas</label>
                        <select x-model.number="editor.selectedBebanId"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih kelas —</option>
                            <template x-for="b in kelasOptionsForGuruMapel(editor.guruId, editor.selectedMapel)" :key="b.id">
                                <option :value="b.id"
                                    x-text="kelasName(b.kelas_id) + ' (' + b.placed + '/' + b.jtm + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedMapel && kelasOptionsForGuruMapel(editor.guruId, editor.selectedMapel).length === 1"
                        class="bg-orange-50 border border-orange-100 rounded-lg p-3 text-xs text-orange-900">
                        <span class="font-black">Kelas:</span>
                        <span x-text="kelasName(kelasOptionsForGuruMapel(editor.guruId, editor.selectedMapel)[0]?.kelas_id)"></span>
                    </div>
                </div>
            </template>

            {{-- Matriks: KG → Mapel → Blok Jam --}}
            <template x-if="editor && editor.context === 'matrix'">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Guru (KG)</label>
                        <select x-model.number="editor.selectedGuruId" @change="onMatrixKgSelect()"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih KG —</option>
                            <template x-for="g in guruListForKelas(editor.kelasId, editor.selectedGuruId)" :key="g.guru_id">
                                <option :value="g.guru_id" x-text="'[' + g.kg + '] ' + g.guru"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedGuruId && mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId).length > 1">
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Mapel</label>
                        <select x-model.number="editor.selectedBebanId" @change="onMatrixMapelSelect()"
                            class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500">
                            <option value="">— Pilih mapel —</option>
                            <template x-for="b in mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId)" :key="b.id">
                                <option :value="b.id"
                                    x-text="b.mapel + ' (' + b.placed + '/' + b.jtm + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="editor.selectedGuruId && mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId).length === 1"
                        class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-800">
                        <span class="font-black">Mapel:</span>
                        <span x-text="mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId)[0]?.mapel"></span>
                        <span class="font-mono text-amber-600 ml-1"
                            x-text="'(' + (mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId)[0]?.placed || 0) + '/' + (mapelOptionsForKelasGuru(editor.kelasId, editor.selectedGuruId)[0]?.jtm || 0) + ')'"></span>
                    </div>
                    <div x-show="editor.selectedBebanId">
                        <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-2">3. Isi Berapa Jam?</label>
                        <div class="flex gap-2">
                            <template x-for="h in [1, 2, 3]" :key="'bh-' + h">
                                <button type="button" @click="editor.blockHours = h"
                                    :disabled="maxBlockHours() < h"
                                    :class="editor.blockHours === h ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                                    class="flex-1 py-2.5 rounded-lg border font-black text-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                    x-text="h + ' jam'"></button>
                            </template>
                        </div>
                        <p class="text-[9px] text-gray-400 mt-1 italic"
                            x-text="'Maks. ' + maxBlockHours() + ' jam berturut dari jam ke-' + editor.jam"></p>
                    </div>
                </div>
            </template>
        </div>

        <div class="px-5 py-4 bg-gray-50 border-t flex justify-between gap-2">
            <button type="button" @click="clearSlot()"
                class="px-4 py-2 rounded-lg text-[10px] font-black uppercase text-red-600 hover:bg-red-50 border border-red-200 transition-colors">
                Kosongkan
            </button>
            <div class="flex gap-2">
                <button type="button" @click="closeEditor()"
                    class="px-4 py-2 rounded-lg text-[10px] font-black uppercase text-gray-600 hover:bg-gray-200 transition-colors">
                    Batal
                </button>
                <button type="button" @click="saveFromEditor()"
                    class="px-5 py-2 rounded-lg text-[10px] font-black uppercase bg-indigo-600 hover:bg-indigo-700 text-white shadow-md transition-colors">
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
