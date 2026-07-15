@extends('layouts.app')

@section('header', 'Kalender App')

@section('content')
    <div class="space-y-6">
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div>
            <h2 class="text-2xl font-bold text-gray-900">Acara Kalender Ta'lim</h2>
            <p class="mt-1 text-sm text-gray-600">
                Tambahkan acara madrasah agar muncul di kalender aplikasi Ta'lim seluruh guru.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(320px,420px)_1fr]">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-bold text-gray-900">Tambah Acara</h3>
                <form action="{{ route('calendar-events.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Judul acara</label>
                        <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Contoh: Rapat pembinaan guru">
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" name="event_date" value="{{ old('event_date', now('Asia/Jakarta')->toDateString()) }}"
                                required class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('event_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Jam</label>
                            <input type="time" name="event_time" value="{{ old('event_time') }}"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('event_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Keterangan</label>
                        <textarea name="note" rows="4" maxlength="2000"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Detail tempat, peserta, atau catatan acara...">{{ old('note') }}</textarea>
                        @error('note') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_important" value="1"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('is_important'))>
                            Tandai penting
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('is_active', '1'))>
                            Aktif
                        </label>
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        Simpan Acara
                    </button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Acara</h3>
                    <p class="text-sm text-gray-500">Acara aktif akan dikirim ke aplikasi Ta'lim.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4">Acara</th>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $item)
                                @php
                                    $dateValue = optional($item->event_date)->format('Y-m-d');
                                    $timeValue = $item->event_time instanceof \DateTimeInterface
                                        ? $item->event_time->format('H:i')
                                        : substr((string) $item->event_time, 0, 5);
                                @endphp
                                <tr class="align-top transition hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">{{ $item->title }}</div>
                                        @if($item->note)
                                            <p class="mt-1 max-w-md whitespace-pre-line text-gray-600">{{ $item->note }}</p>
                                        @endif
                                        @if($item->is_important)
                                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">Penting</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ optional($item->event_date)->format('d M Y') }}
                                        @if($timeValue)
                                            <div class="text-xs font-semibold text-indigo-600">Jam {{ str_replace(':', '.', $timeValue) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($item->is_active)
                                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <details class="group inline-block text-left">
                                            <summary class="cursor-pointer list-none rounded-lg px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50">
                                                Edit
                                            </summary>
                                            <div class="absolute right-8 z-20 mt-2 w-80 rounded-xl border border-gray-100 bg-white p-4 text-left shadow-xl">
                                                <form action="{{ route('calendar-events.update', $item) }}" method="POST" class="space-y-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="title" value="{{ $item->title }}" required maxlength="200"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="date" name="event_date" value="{{ $dateValue }}" required
                                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <input type="time" name="event_time" value="{{ $timeValue }}"
                                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                    <textarea name="note" rows="3" maxlength="2000"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $item->note }}</textarea>
                                                    <div class="flex flex-wrap gap-3">
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                                            <input type="checkbox" name="is_important" value="1" class="rounded border-gray-300 text-indigo-600" @checked($item->is_important)>
                                                            Penting
                                                        </label>
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked($item->is_active)>
                                                            Aktif
                                                        </label>
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-1">
                                                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                                            Simpan
                                                        </button>
                                                    </div>
                                                </form>
                                                <form action="{{ route('calendar-events.destroy', $item) }}" method="POST" class="mt-2"
                                                    data-confirm="Hapus acara kalender ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                        Hapus acara
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">Belum ada acara kalender.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
