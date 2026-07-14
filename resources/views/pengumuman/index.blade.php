@extends('layouts.app')

@section('header', 'Pengumuman App')

@section('content')
    <div x-data="{ showAdd: false }">
        <div class="mb-6 flex justify-between items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Pengumuman Ta'lim</h2>
                <p class="text-gray-600 mt-1 text-sm">Kirim pengumuman ke guru melalui aplikasi Ta'lim.</p>
            </div>
            <button @click="showAdd = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tulis Pengumuman
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Judul</th>
                            <th class="px-6 py-4">Isi</th>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50 transition align-top">
                                <td class="px-6 py-4 font-semibold text-gray-900 max-w-[14rem]">{{ $item->judul }}</td>
                                <td class="px-6 py-4 text-gray-600 max-w-md">
                                    <p class="line-clamp-3 whitespace-pre-line">{{ $item->isi }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                    {{ optional($item->published_at ?? $item->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($item->is_active)
                                        <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Aktif</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('pengumuman.destroy', $item) }}" method="POST" class="inline-block"
                                        data-confirm="Hapus pengumuman ini?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Belum ada pengumuman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
            <div class="absolute inset-0 bg-black/40" @click="showAdd = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Tulis Pengumuman</h3>
                <form action="{{ route('pengumuman.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                        <input type="text" name="judul" required maxlength="200"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Contoh: Rapat koordinasi minggu ini">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Isi</label>
                        <textarea name="isi" rows="5" required maxlength="5000"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Tuliskan detail pengumuman..."></textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showAdd = false"
                            class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100">Batal</button>
                        <button type="submit"
                            class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
