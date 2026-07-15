@extends('layouts.app')

@section('header', 'Update Aplikasi')

@section('content')
    @php
        $value = fn (string $key) => old($key, $item?->{$key} ?? $defaults[$key] ?? null);
        $isActive = old('is_active', $item?->is_active ?? $defaults['is_active']);
    @endphp

    <div class="space-y-6">
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Update Ta'lim</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Atur versi terbaru dan versi minimum yang boleh memakai aplikasi Android Ta'lim.
                </p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700">
                Android
            </span>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <form action="{{ route('app-updates.store') }}" method="POST"
                class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Latest Version Code
                        </label>
                        <input type="number" name="latest_version_code" min="1" required
                            value="{{ $value('latest_version_code') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('latest_version_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Latest Version Name
                        </label>
                        <input type="text" name="latest_version_name" maxlength="40" required
                            value="{{ $value('latest_version_name') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('latest_version_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Minimum Version Code
                        </label>
                        <input type="number" name="minimum_version_code" min="1" required
                            value="{{ $value('minimum_version_code') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('minimum_version_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Judul</label>
                    <input type="text" name="title" maxlength="160" required value="{{ $value('title') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Pesan</label>
                    <textarea name="message" rows="3" maxlength="2000"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Pesan yang tampil di aplikasi saat update tersedia.">{{ $value('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Changelog</label>
                    <textarea name="changelog" rows="5" maxlength="5000"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="- Perbaikan bug&#10;- Fitur baru">{{ $value('changelog') }}</textarea>
                    @error('changelog')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">
                        URL Play Store
                    </label>
                    <input type="url" name="play_store_url" maxlength="500" value="{{ $value('play_store_url') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    @error('play_store_url')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="is_active" @checked($isActive)
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_active" class="text-sm font-semibold text-gray-700">
                        Aktifkan pengecekan update aplikasi
                    </label>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        Simpan Kebijakan Update
                    </button>
                </div>
            </form>

            <div class="space-y-4">
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-500">Cara Kerja</p>
                    <div class="mt-3 space-y-3 text-sm text-indigo-950">
                        <p>
                            <strong>Update opsional:</strong> jika version code aplikasi guru lebih kecil dari latest
                            version code.
                        </p>
                        <p>
                            <strong>Update wajib:</strong> jika version code aplikasi guru lebih kecil dari minimum
                            version code.
                        </p>
                        <p>
                            Tombol update di aplikasi akan membuka Google Play Store dari URL yang diisi di sini.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Status Saat Ini</p>
                    @if($item)
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Latest</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ $item->latest_version_name }} ({{ $item->latest_version_code }})
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Minimum</dt>
                                <dd class="font-semibold text-gray-900">{{ $item->minimum_version_code }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Status</dt>
                                <dd>
                                    @if($item->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">
                                            Nonaktif
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Diperbarui</dt>
                                <dd class="font-semibold text-gray-900">
                                    {{ optional($item->updated_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                                </dd>
                            </div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-gray-500">Belum ada kebijakan update tersimpan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
