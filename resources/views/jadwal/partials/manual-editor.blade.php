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
    <span class="text-[9px] text-gray-500 font-bold ml-auto self-center hidden sm:inline">
        <span class="inline-block w-3 h-3 rounded-sm slot-issue-critical align-middle mr-1"></span> Masalah kritis
        <span class="inline-block w-3 h-3 rounded-sm slot-issue-info align-middle mx-1 ml-3"></span> Penanda kualitas — hover sel untuk detail
    </span>
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
                                    class="border border-gray-200 p-2 cursor-pointer min-h-[36px] transition-colors relative"
                                    :class="{
                                        'slot-issue-critical': getSlotIssueLevel(hari, jam, selectedKelasId) === 'critical',
                                        'slot-issue-info': getSlotIssueLevel(hari, jam, selectedKelasId) === 'info',
                                        'bg-indigo-50': getSlot(hari, jam, selectedKelasId) && !getSlotIssueLevel(hari, jam, selectedKelasId) && !isBtqOnlySlot(hari, jam),
                                        'bg-white hover:bg-amber-50': !getSlot(hari, jam, selectedKelasId) && !getSlotIssueLevel(hari, jam, selectedKelasId) && !isBtqOnlySlot(hari, jam),
                                        'bg-emerald-100 hover:bg-emerald-200 ring-1 ring-emerald-400': isBtqOnlySlot(hari, jam) && !getSlot(hari, jam, selectedKelasId) && !getSlotIssueLevel(hari, jam, selectedKelasId),
                                        'cell-jumat-5': isBtqOnlySlot(hari, jam) && getSlot(hari, jam, selectedKelasId) && !getSlotIssueLevel(hari, jam, selectedKelasId)
                                    }"
                                    :title="slotIssueTooltip(hari, jam, selectedKelasId) || null">
                                    <template x-if="getSlot(hari, jam, selectedKelasId)">
                                        <div>
                                            <span class="font-black text-indigo-700"
                                                x-text="'[' + getSlot(hari, jam, selectedKelasId).kg + ']'"></span>
                                            <span class="text-gray-700 ml-1"
                                                x-text="getSlot(hari, jam, selectedKelasId).mapel"></span>
                                        </div>
                                    </template>
                                    <span x-show="!getSlot(hari, jam, selectedKelasId) && isBtqOnlySlot(hari, jam)"
                                        class="text-emerald-700 italic text-[10px] font-bold">BTQ — double klik</span>
                                    <span x-show="!getSlot(hari, jam, selectedKelasId) && !isBtqOnlySlot(hari, jam)"
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
                                <td @dblclick="is_active && canEditGuruSlot(hari, jam) && openEditorFromGuru(hari, jam)"
                                    class="border border-gray-200 p-2 min-h-[36px] relative"
                                    :class="{
                                        'slot-issue-critical': getGuruSlotIssueLevel(hari, jam, selectedGuruIdView) === 'critical',
                                        'slot-issue-info': getGuruSlotIssueLevel(hari, jam, selectedGuruIdView) === 'info',
                                        'bg-red-100 ring-1 ring-red-300': !getGuruSlotIssueLevel(hari, jam, selectedGuruIdView) && hasConstraintForGuru(selectedGuruIdView, hari, jam, 0),
                                        'bg-orange-100 ring-1 ring-orange-300': !getGuruSlotIssueLevel(hari, jam, selectedGuruIdView) && hasConstraintForGuru(selectedGuruIdView, hari, jam, 1),
                                        'bg-indigo-50 cursor-pointer': findGuruSlot(selectedGuruIdView, hari, jam) && !getGuruSlotIssueLevel(hari, jam, selectedGuruIdView) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 0) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 1) && canEditGuruSlot(hari, jam),
                                        'hover:bg-amber-50 cursor-pointer': !findGuruSlot(selectedGuruIdView, hari, jam) && canEditGuruSlot(hari, jam) && !getGuruSlotIssueLevel(hari, jam, selectedGuruIdView),
                                        'bg-gray-100 opacity-50 cursor-not-allowed': !canEditGuruSlot(hari, jam)
                                    }"
                                    :title="guruSlotIssueTooltip(hari, jam, selectedGuruIdView) || null">
                                    <template x-if="findGuruSlot(selectedGuruIdView, hari, jam)">
                                        <div>
                                            <span class="font-black text-indigo-700"
                                                x-text="findGuruSlot(selectedGuruIdView, hari, jam).kelas"></span>
                                            <span class="text-gray-600 ml-1"
                                                x-text="findGuruSlot(selectedGuruIdView, hari, jam).mapel"></span>
                                        </div>
                                    </template>
                                    <span x-show="!findGuruSlot(selectedGuruIdView, hari, jam) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 0) && !hasConstraintForGuru(selectedGuruIdView, hari, jam, 1) && canEditGuruSlot(hari, jam)"
                                        class="text-gray-300 italic text-[10px]">kosong</span>
                                    <span x-show="!canEditGuruSlot(hari, jam)"
                                        class="text-[9px] font-bold text-gray-500 uppercase">Slot BTQ</span>
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
                                class="border border-gray-200 p-1 cursor-pointer text-center min-h-[28px] align-middle relative"
                                :class="{
                                    'slot-issue-critical': getSlotIssueLevel(selectedHariView, jam, k.id) === 'critical',
                                    'slot-issue-info': getSlotIssueLevel(selectedHariView, jam, k.id) === 'info',
                                    'bg-indigo-50': getSlot(selectedHariView, jam, k.id) && !getSlotIssueLevel(selectedHariView, jam, k.id) && !isBtqOnlySlot(selectedHariView, jam),
                                    'hover:bg-amber-50': !getSlot(selectedHariView, jam, k.id) && !getSlotIssueLevel(selectedHariView, jam, k.id) && !isBtqOnlySlot(selectedHariView, jam),
                                    'bg-emerald-100 hover:bg-emerald-200': isBtqOnlySlot(selectedHariView, jam) && !getSlot(selectedHariView, jam, k.id) && !getSlotIssueLevel(selectedHariView, jam, k.id),
                                    'cell-jumat-5': isBtqOnlySlot(selectedHariView, jam) && getSlot(selectedHariView, jam, k.id) && !getSlotIssueLevel(selectedHariView, jam, k.id)
                                }"
                                :title="slotIssueTooltip(selectedHariView, jam, k.id) || null">
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

{{-- Modal Editor — teleport ke body agar tidak tertimpa matriks --}}
<template x-teleport="body">
<div x-show="showEditorModal" x-cloak
    class="fixed inset-0 flex items-center justify-center p-4"
    style="z-index: 99999; background: rgba(15, 23, 42, 0.65);"
    @keydown.escape.window="closeEditor()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-indigo-200 overflow-hidden relative"
        style="z-index: 100000; isolation: isolate;"
        @click.stop>
        <div class="bg-indigo-600 px-5 py-3 flex justify-between items-center relative z-10">
            <div>
                <h3 class="text-white font-black text-sm uppercase tracking-wide">Edit Slot Jadwal</h3>
                <p class="text-indigo-200 text-[10px] font-bold mt-0.5"
                    x-text="editorSubtitle()"></p>
            </div>
            <button type="button" @click="closeEditor()" class="text-indigo-200 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4 bg-white relative z-10" x-show="editor">

            <div x-show="editor && isBtqOnlySlot(editor.hari, editor.jam)"
                class="bg-emerald-50 border border-emerald-300 rounded-lg p-3 text-xs text-emerald-900 font-bold">
                Slot khusus BTQ — hanya guru pengampu BTQ di kelas ini
            </div>

            {{-- Per Kelas & Per Hari: Guru → Mapel --}}
            <div x-show="editor && (editor.context === 'kelas' || editor.context === 'hari')" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Guru</label>
                    <select x-model.number="editor.selectedGuruId" @change="onGuruSelectKelas()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih guru —</option>
                        <template x-for="g in guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId)" :key="g.guru_id">
                            <option :value="g.guru_id" :disabled="g.isFull"
                                x-text="'[' + g.kg + '] ' + g.guru + (g.isFull ? ' — PENUH' : '')"></option>
                        </template>
                    </select>
                    <p x-show="editor && isBtqOnlySlot(editor.hari, editor.jam) && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length === 0"
                        class="mt-2 text-xs text-emerald-800 font-bold italic">
                        Tidak ada guru pengampu BTQ di kelas ini
                    </p>
                </div>

                <div x-show="editor.mapelFullMessage"
                    class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800 font-bold">
                    <span x-text="editor.mapelFullMessage"></span>
                </div>

                <div x-show="editor.selectedGuruId && !editor.mapelFullMessage && mapelOptionsForSelectedGuruKelas().length > 1">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Mapel</label>
                    <select x-model.number="editor.selectedBebanId" @change="onMapelSelectFromGuruKelas()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih mapel —</option>
                        <template x-for="b in mapelOptionsForSelectedGuruKelas()" :key="b.id">
                            <option :value="b.id"
                                x-text="b.mapel + ' (' + b.placed + '/' + b.jtm + ' jam)'"></option>
                        </template>
                    </select>
                </div>

                <div x-show="editor.selectedGuruId && !editor.mapelFullMessage && mapelOptionsForSelectedGuruKelas().length === 1"
                    class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-800">
                    <span class="font-black">Mapel:</span>
                    <span x-text="mapelOptionsForSelectedGuruKelas()[0]?.mapel"></span>
                    <span class="font-mono text-amber-600 ml-1"
                        x-text="'(' + (mapelOptionsForSelectedGuruKelas()[0]?.placed || 0) + '/' + (mapelOptionsForSelectedGuruKelas()[0]?.jtm || 0) + ' jam)'"></span>
                </div>

                <div x-show="editor.selectedBebanId && !editor.mapelFullMessage">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-2">Alokasi Jam</label>
                    <div class="flex gap-2">
                        <template x-for="h in [1, 2, 3]" :key="'kh-' + h">
                            <button type="button" @click="editor.blockHours = h"
                                :disabled="maxBlockHours() < h"
                                :class="editor.blockHours === h ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                                class="flex-1 py-2.5 rounded-lg border font-black text-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <span x-text="h + ' jam'"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-1 italic"
                        x-text="'Maks. ' + maxBlockHours() + ' jam berturut dari jam ke-' + editor.jam"></p>
                </div>
            </div>

            {{-- Per Guru: Kelas → Mapel --}}
            <div x-show="editor && editor.context === 'guru'" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Kelas</label>
                    <select x-model.number="editor.selectedKelasId" @change="onKelasSelectGuru()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih kelas —</option>
                        <template x-for="k in kelasOptionsForGuruInput(editor.guruId, editor.selectedKelasId)" :key="k.kelas_id">
                            <option :value="k.kelas_id" :disabled="k.isFull"
                                x-text="kelasName(k.kelas_id) + (k.isFull ? ' — PENUH' : '')"></option>
                        </template>
                    </select>
                </div>

                <div x-show="editor.mapelFullMessage"
                    class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800 font-bold">
                    <span x-text="editor.mapelFullMessage"></span>
                </div>

                <div x-show="editor.selectedKelasId && !editor.mapelFullMessage && mapelOptionsForSelectedKelasGuru().length > 1">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Mapel</label>
                    <select x-model.number="editor.selectedBebanId" @change="onMapelSelectFromGuruKelas()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih mapel —</option>
                        <template x-for="b in mapelOptionsForSelectedKelasGuru()" :key="b.id">
                            <option :value="b.id"
                                x-text="b.mapel + ' (' + b.placed + '/' + b.jtm + ' jam)'"></option>
                        </template>
                    </select>
                </div>

                <div x-show="editor.selectedKelasId && !editor.mapelFullMessage && mapelOptionsForSelectedKelasGuru().length === 1"
                    class="bg-orange-50 border border-orange-100 rounded-lg p-3 text-xs text-orange-900">
                    <span class="font-black">Mapel:</span>
                    <span x-text="mapelOptionsForSelectedKelasGuru()[0]?.mapel"></span>
                    <span class="font-mono text-amber-600 ml-1"
                        x-text="'(' + (mapelOptionsForSelectedKelasGuru()[0]?.placed || 0) + '/' + (mapelOptionsForSelectedKelasGuru()[0]?.jtm || 0) + ' jam)'"></span>
                </div>

                <div x-show="editor.selectedBebanId && !editor.mapelFullMessage">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-2">Alokasi Jam</label>
                    <div class="flex gap-2">
                        <template x-for="h in [1, 2, 3]" :key="'gu-' + h">
                            <button type="button" @click="editor.blockHours = h"
                                :disabled="maxBlockHours() < h"
                                :class="editor.blockHours === h ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                                class="flex-1 py-2.5 rounded-lg border font-black text-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <span x-text="h + ' jam'"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-1 italic"
                        x-text="'Maks. ' + maxBlockHours() + ' jam berturut dari jam ke-' + editor.jam"></p>
                </div>
            </div>

            {{-- Matriks: KG → Mapel → Blok Jam --}}
            <div x-show="editor && editor.context === 'matrix'" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">1. Pilih Guru (KG)</label>
                    <select x-model.number="editor.selectedGuruId" @change="onMatrixKgSelect()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih KG —</option>
                        <template x-for="g in guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId)" :key="'mx-' + g.guru_id">
                            <option :value="g.guru_id" :disabled="g.isFull"
                                x-text="'[' + g.kg + '] ' + g.guru + (g.isFull ? ' — PENUH' : '')"></option>
                        </template>
                    </select>
                    <p x-show="editor && isBtqOnlySlot(editor.hari, editor.jam) && guruOptionsForKelasInput(editor.kelasId, editor.selectedGuruId).length === 0"
                        class="mt-2 text-xs text-emerald-800 font-bold italic">
                        Tidak ada guru pengampu BTQ di kelas ini
                    </p>
                </div>

                <div x-show="editor.mapelFullMessage"
                    class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-800 font-bold">
                    <span x-text="editor.mapelFullMessage"></span>
                </div>

                <div x-show="editor.selectedGuruId && !editor.mapelFullMessage && mapelOptionsForSelectedGuruKelas().length > 1">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest">2. Pilih Mapel</label>
                    <select x-model.number="editor.selectedBebanId" @change="onMatrixMapelSelect()"
                        class="mt-1 w-full border border-gray-200 rounded-lg p-2.5 text-sm font-bold text-gray-800 focus:ring-indigo-500 bg-white">
                        <option value="">— Pilih mapel —</option>
                        <template x-for="b in mapelOptionsForSelectedGuruKelas()" :key="b.id">
                            <option :value="b.id"
                                x-text="b.mapel + ' (' + b.placed + '/' + b.jtm + ' jam)'"></option>
                        </template>
                    </select>
                </div>

                <div x-show="editor.selectedGuruId && !editor.mapelFullMessage && mapelOptionsForSelectedGuruKelas().length === 1"
                    class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-800">
                    <span class="font-black">Mapel:</span>
                    <span x-text="mapelOptionsForSelectedGuruKelas()[0]?.mapel"></span>
                    <span class="font-mono text-amber-600 ml-1"
                        x-text="'(' + (mapelOptionsForSelectedGuruKelas()[0]?.placed || 0) + '/' + (mapelOptionsForSelectedGuruKelas()[0]?.jtm || 0) + ' jam)'"></span>
                </div>

                <div x-show="editor.selectedBebanId && !editor.mapelFullMessage">
                    <label class="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-2">3. Alokasi Jam</label>
                    <div class="flex gap-2">
                        <template x-for="h in [1, 2, 3]" :key="'bh-' + h">
                            <button type="button" @click="editor.blockHours = h"
                                :disabled="maxBlockHours() < h"
                                :class="editor.blockHours === h ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                                class="flex-1 py-2.5 rounded-lg border font-black text-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <span x-text="h + ' jam'"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-1 italic"
                        x-text="'Maks. ' + maxBlockHours() + ' jam berturut dari jam ke-' + editor.jam"></p>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 bg-gray-50 border-t flex justify-between gap-2 relative z-10">
            <button type="button" @click="clearSlot()"
                class="px-4 py-2 rounded-lg text-[10px] font-black uppercase text-red-600 hover:bg-red-50 border border-red-200 transition-colors bg-white">
                Kosongkan
            </button>
            <div class="flex gap-2">
                <button type="button" @click="closeEditor()"
                    class="px-4 py-2 rounded-lg text-[10px] font-black uppercase text-gray-600 hover:bg-gray-200 transition-colors bg-white">
                    Batal
                </button>
                <button type="button" @click="saveFromEditor()"
                    :disabled="!!editor?.mapelFullMessage"
                    :class="editor?.mapelFullMessage ? 'opacity-40 cursor-not-allowed' : 'hover:bg-indigo-700'"
                    class="px-5 py-2 rounded-lg text-[10px] font-black uppercase bg-indigo-600 text-white shadow-md transition-colors">
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
</template>
