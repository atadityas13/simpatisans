<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Surat Pernyataan Zakat Profesi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #047857;
            --primary-dark: #064e3b;
            --muted: #64748b;
            --border: #dbe4ee;
            --bg: #f0fdf4;
            --card: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #ecfdf5 0%, #f8fafc 55%);
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            line-height: 1.5;
            padding: 24px 16px 48px;
        }
        .wrap {
            max-width: 560px;
            margin: 0 auto;
        }
        .hero {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: #fff;
            border-radius: 22px;
            padding: 28px 24px;
            margin-bottom: 20px;
            box-shadow: 0 16px 40px rgba(4, 120, 87, 0.25);
        }
        .hero h1 {
            margin: 0 0 8px;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .hero p {
            margin: 0;
            opacity: 0.92;
            font-size: 0.95rem;
        }
        .card {
            background: var(--card);
            border: 1px solid rgba(6, 95, 70, 0.1);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 12px 32px rgba(6, 95, 70, 0.08);
        }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .field { margin-bottom: 16px; }
        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font: inherit;
            background: #f8fafc;
        }
        input:focus {
            outline: 2px solid rgba(4, 120, 87, 0.35);
            border-color: var(--primary);
            background: #fff;
        }
        .hint {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 4px;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .btn {
            flex: 1;
            border: none;
            border-radius: 14px;
            padding: 14px 16px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #047857, #059669);
            color: #fff;
            box-shadow: 0 10px 24px rgba(4, 120, 87, 0.28);
        }
        .btn-primary:hover { filter: brightness(1.05); }
        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .errors ul { margin: 0; padding-left: 18px; }
        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 0.8rem;
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <h1>Surat Pernyataan Zakat Profesi</h1>
            <p>Isi data di bawah, lalu klik Cetak untuk membuka surat siap print.</p>
        </div>

        <div class="card">
            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" action="{{ route('public.surat-pernyataan-zakat.cetak') }}">
                <div class="field">
                    <label for="nama">Nama <span style="color:#dc2626">*</span></label>
                    <input id="nama" name="nama" type="text" required maxlength="200"
                        value="{{ old('nama') }}" placeholder="Nama lengkap beserta gelar">
                </div>

                <div class="field">
                    <label for="nip">NIP</label>
                    <input id="nip" name="nip" type="text" maxlength="50"
                        value="{{ old('nip') }}" placeholder="NIP / NIK">
                </div>

                <div class="field">
                    <label for="golongan">Pangkat / Golongan</label>
                    <input id="golongan" name="golongan" type="text" maxlength="100"
                        value="{{ old('golongan') }}" placeholder="Contoh: Penata / III-c">
                </div>

                <div class="field">
                    <label for="jabatan">Jabatan</label>
                    <input id="jabatan" name="jabatan" type="text" maxlength="150"
                        value="{{ old('jabatan') }}" placeholder="Contoh: Guru / Kepala Madrasah">
                </div>

                <div class="field">
                    <label for="unit_kerja">Unit Kerja</label>
                    <input id="unit_kerja" name="unit_kerja" type="text" maxlength="150"
                        value="{{ old('unit_kerja', $unitKerjaDefault) }}">
                </div>

                <div class="field">
                    <label for="tanggal">Tanggal surat</label>
                    <input id="tanggal" name="tanggal" type="date"
                        value="{{ old('tanggal', $tanggalDefault) }}">
                    <div class="hint">Digunakan pada baris “Majalengka, …”</div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Cetak Surat</button>
                </div>
            </form>
        </div>

        <p class="footer-note">Halaman publik MTsN 11 Majalengka — tidak perlu login.</p>
    </div>
</body>
</html>
