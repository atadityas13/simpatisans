@extends('layouts.app')

@section('header', 'Profil Saya')

@section('content')

    {{-- Two-column layout using inline flex to avoid JIT compile issues --}}
    <div style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-start;">

        {{-- ===== KOLOM KIRI: Kartu Profil ===== --}}
        <div style="flex: 0 0 350px; max-width:350px; min-width:280px;">
            <div
                style="background:#fff; border-radius:1.25rem; border:1px solid #f1f5f9; padding:2rem 1.5rem; text-align:center; box-shadow:0 1px 4px rgba(0,0,0,.06);">

                {{-- Foto + tombol kamera (Ubah bingkai menjadi kotak rounded 160x200) --}}
                <div style="position:relative; width:160px; height:200px; margin:0 auto 1.25rem auto;">
                    <div
                        style="width:160px; height:200px; border-radius:1rem; overflow:hidden; border:4px solid #e0e7ff; box-shadow:0 4px 14px rgba(79,70,229,.15);">
                        @if($user->foto)
                            <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto Profil"
                                style="width:100%; height:100%; object-fit:cover; display:block;">
                        @else
                            <div
                                style="width:100%; height:100%; background:#f3f4f6; display:flex; align-items:center; justify-content:center;">
                                <svg style="width:56px; height:56px; color:#9ca3af;" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    {{-- Tombol ubah foto --}}
                    <button onclick="openModal('modal-foto')" title="Ubah Foto"
                        style="position:absolute; bottom:8px; right:8px; background:#4f46e5; color:#fff; border:3px solid #fff; border-radius:9999px; width:36px; height:36px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 6px rgba(79,70,229,.4); transition:background .2s; z-index: 10;">
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>

                {{-- Nama & Jabatan (Nama dengan Gelar depan dan belakang) --}}
                @php
                    $namaDisplay = $user->nama_lengkap;
                    if ($user->guru) {
                        $namaDisplay = $user->guru->nama_lengkap;
                    }
                @endphp
                <h3 style="font-size:1.15rem; font-weight:800; color:#111827; margin:0 0 .35rem 0; line-height:1.3;">
                    {{ $namaDisplay }}
                </h3>
                <p
                    style="font-size:.75rem; font-weight:700; color:#4f46e5; text-transform:uppercase; letter-spacing:.08em; margin:0 0 1.5rem 0;">
                    {{ $user->jabatan ?? $user->role }}
                </p>

                {{-- Divider --}}
                <div style="height:1px; background:#f1f5f9; margin-bottom:1.25rem;"></div>

                {{-- Tombol Ubah Password --}}
                <button onclick="openModal('modal-password')"
                    style="width:100%; display:flex; align-items:center; justify-content:center; gap:.5rem; padding:.65rem 1rem; background:#1e293b; color:#fff; border:none; border-radius:.75rem; font-size:.82rem; font-weight:700; cursor:pointer; transition:background .2s; box-shadow:0 2px 8px rgba(0,0,0,.15);">
                    <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Ubah Password
                </button>
            </div>
        </div>

        {{-- ===== KOLOM KANAN: Detail Data ===== --}}
        <div style="flex:1; min-width:0;">
            <div
                style="background:#fff; border-radius:1.25rem; border:1px solid #f1f5f9; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06);">

                {{-- Header card --}}
                <div
                    style="padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; display:flex; align-items:center; gap:.75rem;">
                    <div style="width:4px; height:1.25rem; background:#4f46e5; border-radius:2px; flex-shrink:0;"></div>
                    <h3 style="font-size:1rem; font-weight:800; color:#111827; margin:0;">Informasi Data Guru</h3>
                </div>

                <div style="padding:1.5rem;">
                    @if($user->guru)
                        {{-- Grid detail guru --}}
                        <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:1rem 2rem;">

                            @php $guru = $user->guru; @endphp

                            @php
                                $items = [
                                    ['label' => 'NIP / NIK', 'value' => $guru->username ?? '—'],
                                    ['label' => 'Kode Guru', 'value' => $guru->kode_guru ?? '—', 'badge' => true],
                                    ['label' => 'NUPTK', 'value' => $guru->nuptk ?? '—'],
                                    ['label' => 'DUK', 'value' => $guru->duk ?? '—'],
                                    ['label' => 'Status Pegawai', 'value' => $guru->status_pegawai ?? '—'],
                                    ['label' => 'Jabatan', 'value' => $guru->jabatan ?? '—'],
                                    ['label' => 'Golongan', 'value' => $guru->golongan ?? '—'],
                                    ['label' => 'Jenis Kelamin', 'value' => match ($guru->jenis_kelamin) {
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan',
                                        default => $guru->jenis_kelamin ?: '—',
                                    }],
                                    ['label' => 'Tempat, Tanggal Lahir', 'value' => trim(($guru->tempat_lahir ?? '') . ($guru->tanggal_lahir ? ', ' . $guru->tanggal_lahir->translatedFormat('d F Y') : ''), ', ') ?: '—'],
                                    ['label' => 'Agama', 'value' => $guru->agama ?? '—'],
                                    ['label' => 'Nomor HP', 'value' => $guru->nomor_hp ?? '—'],
                                    ['label' => 'Email', 'value' => $guru->email ?? '—'],
                                    ['label' => 'Alamat', 'value' => $guru->alamat ?? '—'],
                                    ['label' => 'Mapel Ijazah', 'value' => $guru->kualifikasi_ijazah ?? '—'],
                                ];
                            @endphp

                            @foreach($items as $item)
                                <div style="border-bottom:1px solid #f8fafc; padding-bottom:.9rem;">
                                    <dt
                                        style="font-size:.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.3rem;">
                                        {{ $item['label'] }}
                                    </dt>
                                    <dd style="font-size:.875rem; font-weight:700; color:#111827; margin:0;">
                                        @if(!empty($item['badge']))
                                            <span
                                                style="background:#e0e7ff; color:#3730a3; padding:.2rem .6rem; border-radius:.4rem; font-size:.8rem; font-weight:700;">
                                                {{ $item['value'] }}
                                            </span>
                                        @else
                                            {{ $item['value'] }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach

                            {{-- Status Sertifikasi (di bawah golongan - Col 2) --}}
                            <div style="border-bottom:1px solid #f8fafc; padding-bottom:.9rem;">
                                <dt
                                    style="font-size:.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.3rem;">
                                    Status Sertifikasi
                                </dt>
                                <dd style="margin:0;">
                                    @if($guru->status_sertifikasi)
                                        <span
                                            style="display:inline-block; background:#dcfce7; color:#166534; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; font-weight:800;">
                                            ✓ Sudah Sertifikasi
                                        </span>
                                    @else
                                        <span
                                            style="display:inline-block; background:#f3f4f6; color:#374151; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; font-weight:800;">
                                            Belum Sertifikasi
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            {{-- Mapel Sertifikasi --}}
                            <div style="border-bottom:1px solid #f8fafc; padding-bottom:.9rem;">
                                <dt
                                    style="font-size:.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.3rem;">
                                    Mapel Sertifikasi
                                </dt>
                                <dd style="font-size:.875rem; font-weight:700; color:#111827; margin:0;">
                                    {{ $guru->mapelSertifikasi?->nama_mapel ?? '—' }}
                                </dd>
                            </div>

                            {{-- Mapel Diampu --}}
                            <div style="border-bottom:1px solid #f8fafc; padding-bottom:.9rem; grid-column: span 2;">
                                <dt
                                    style="font-size:.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-bottom:.5rem;">
                                    Mata Pelajaran yang Diampu
                                </dt>
                                <dd style="margin:0; display:flex; flex-wrap:wrap; gap:.5rem;">
                                    @forelse($guru->mapelDiampu as $mapel)
                                        <span
                                            style="background:#f0f9ff; color:#0369a1; border:1px solid #e0f2fe; padding:.3rem .75rem; border-radius:.5rem; font-size:.8rem; font-weight:700;">
                                            {{ $mapel->nama_mapel }}
                                        </span>
                                    @empty
                                        <span style="font-size:.875rem; color:#9ca3af; font-style:italic;">Belum ada mapel
                                            diampu</span>
                                    @endforelse
                                </dd>
                            </div>
                        </div>

                        {{-- Note for Admin --}}
                        <div
                            style="margin-top:1.5rem; padding:1rem; background:#fff9db; border:1px solid #ffec99; border-radius:.75rem; display:flex; align-items:center; gap:.75rem;">
                            <svg style="width:20px; height:20px; color:#f08c00; flex-shrink:0;" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p style="font-size:.75rem; color:#856404; margin:0;">
                                Jika terdapat kesalahan data,
                                silakan hubungi <strong>Admin Kurikulum</strong> untuk melakukan perubahan.
                            </p>
                        </div>
                    @else
                        {{-- Admin info --}}
                        <div style="text-align:center; padding:2rem 0; color:#6b7280;">
                            <div
                                style="width:72px; height:72px; background:#e0e7ff; border-radius:9999px; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                                <svg style="width:36px; height:36px; color:#4f46e5;" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h4 style="font-size:1.05rem; font-weight:800; color:#111827; margin:0 0 .5rem;">Akun Administrator
                            </h4>
                            <p style="font-size:.875rem; margin:0;">Anda memiliki hak akses penuh terhadap sistem SIMPATISANS.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- end two-column --}}


    {{-- ===================== MODAL UBAH FOTO ===================== --}}
    <div id="modal-foto" class="hidden"
        style="position:fixed; inset:0; z-index:9999; display:none; align-items:center; justify-content:center; padding:1rem;">
        <div onclick="document.getElementById('modal-foto').classList.add('hidden'); document.getElementById('modal-foto').style.display='none';"
            style="position:absolute; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px);"></div>
        <div
            style="position:relative; background:#fff; border-radius:1.25rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:100%; max-width:480px; overflow:hidden; border:1px solid #f1f5f9;">
            {{-- Header --}}
            <div
                style="padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <div style="width:4px; height:1.1rem; background:#4f46e5; border-radius:2px;"></div>
                    <h3 style="font-size:1rem; font-weight:800; color:#111827; margin:0;">Ubah Foto Profil</h3>
                </div>
                <button
                    onclick="document.getElementById('modal-foto').classList.add('hidden'); document.getElementById('modal-foto').style.display='none';"
                    style="background:none; border:none; cursor:pointer; color:#9ca3af; padding:.25rem; border-radius:.5rem; display:flex; align-items:center;">
                    <svg style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            {{-- Body --}}
            <form action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data"
                style="padding:1.5rem;">
                @csrf
                <label style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.6rem;">Pilih
                    Foto Baru</label>
                <input type="file" name="foto" accept="image/*" required
                    style="display:block; width:100%; font-size:.82rem; color:#6b7280; border:1.5px dashed #c7d2fe; border-radius:.75rem; padding:.65rem 1rem; cursor:pointer; background:#f9fafb; box-sizing:border-box;">
                <p style="font-size:.72rem; color:#9ca3af; margin:.5rem 0 0;">Maks. 2MB · Format: JPG, PNG, JPEG</p>
                <div
                    style="display:flex; justify-content:flex-end; gap:.75rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                    <button type="button"
                        onclick="document.getElementById('modal-foto').classList.add('hidden'); document.getElementById('modal-foto').style.display='none';"
                        style="padding:.55rem 1.1rem; background:#fff; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.82rem; font-weight:700; color:#374151; cursor:pointer;">
                        Batal
                    </button>
                    <button type="submit"
                        style="padding:.55rem 1.25rem; background:#4f46e5; border:none; border-radius:.75rem; font-size:.82rem; font-weight:700; color:#fff; cursor:pointer; box-shadow:0 2px 8px rgba(79,70,229,.35);">
                        Simpan Foto
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ===================== MODAL UBAH PASSWORD ===================== --}}
    <div id="modal-password" class="hidden"
        style="position:fixed; inset:0; z-index:9999; display:none; align-items:center; justify-content:center; padding:1rem;">
        <div onclick="document.getElementById('modal-password').classList.add('hidden'); document.getElementById('modal-password').style.display='none';"
            style="position:absolute; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px);"></div>
        <div
            style="position:relative; background:#fff; border-radius:1.25rem; box-shadow:0 20px 60px rgba(0,0,0,.25); width:100%; max-width:420px; overflow:hidden; border:1px solid #f1f5f9;">
            {{-- Header --}}
            <div
                style="padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <div style="width:4px; height:1.1rem; background:#1e293b; border-radius:2px;"></div>
                    <h3 style="font-size:1rem; font-weight:800; color:#111827; margin:0;">Ubah Password</h3>
                </div>
                <button
                    onclick="document.getElementById('modal-password').classList.add('hidden'); document.getElementById('modal-password').style.display='none';"
                    style="background:none; border:none; cursor:pointer; color:#9ca3af; padding:.25rem; border-radius:.5rem; display:flex; align-items:center;">
                    <svg style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            {{-- Body --}}
            <form action="{{ route('profile.password.update') }}" method="POST" style="padding:1.5rem;">
                @csrf
                <div style="margin-bottom:1rem;">
                    <label
                        style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.4rem;">Password
                        Saat Ini</label>
                    <input type="password" name="current_password" required
                        style="width:100%; padding:.65rem 1rem; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.85rem; color:#111827; box-sizing:border-box; outline:none;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label
                        style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.4rem;">Password
                        Baru</label>
                    <input type="password" name="password" required
                        style="width:100%; padding:.65rem 1rem; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.85rem; color:#111827; box-sizing:border-box; outline:none;">
                    <p style="font-size:.72rem; color:#9ca3af; margin:.35rem 0 0;">Minimal 6 karakter.</p>
                </div>
                <div style="margin-bottom:1.25rem;">
                    <label
                        style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.4rem;">Konfirmasi
                        Password Baru</label>
                    <input type="password" name="password_confirmation" required
                        style="width:100%; padding:.65rem 1rem; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.85rem; color:#111827; box-sizing:border-box; outline:none;">
                </div>

                @if(!$user->security_question)
                    <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px dashed #e2e8f0; margin-bottom:1rem;">
                        <span style="display:block; font-size:.7rem; font-weight:800; color:#4f46e5; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.75rem;">
                            Pengaturan Keamanan Akun
                        </span>
                        
                        <div style="margin-bottom:1rem;">
                            <label style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.4rem;">Pertanyaan Keamanan</label>
                            <input type="text" name="security_question" required placeholder="Contoh: Siapa nama hewan peliharaan Anda?"
                                style="width:100%; padding:.65rem 1rem; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.85rem; color:#111827; box-sizing:border-box; outline:none;">
                            <p style="font-size:.72rem; color:#9ca3af; margin:.35rem 0 0;">Pertanyaan ini digunakan untuk memverifikasi permintaan reset password.</p>
                        </div>

                        <div style="margin-bottom:0;">
                            <label style="display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.4rem;">Jawaban</label>
                            <input type="text" name="security_answer" required placeholder="Masukkan jawaban Anda"
                                style="width:100%; padding:.65rem 1rem; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.85rem; color:#111827; box-sizing:border-box; outline:none;">
                        </div>
                    </div>
                @endif
                <div
                    style="display:flex; justify-content:flex-end; gap:.75rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                    <button type="button"
                        onclick="document.getElementById('modal-password').classList.add('hidden'); document.getElementById('modal-password').style.display='none';"
                        style="padding:.55rem 1.1rem; background:#fff; border:1.5px solid #d1d5db; border-radius:.75rem; font-size:.82rem; font-weight:700; color:#374151; cursor:pointer;">
                        Batal
                    </button>
                    <button type="submit"
                        style="padding:.55rem 1.25rem; background:#1e293b; border:none; border-radius:.75rem; font-size:.82rem; font-weight:700; color:#fff; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,.25);">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to show modal correctly
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.style.display = 'flex';
            }
        }
    </script>

@endsection