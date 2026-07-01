<?php

namespace App\Services;

use App\Models\Guru;

class GuruService
{
    /**
     * Hitung metrik beban kerja dan kelayakan sertifikasi guru.
     */
    public function hitungMetrik(Guru $guru, int $semesterId): array
    {
        $sertRumpun  = $guru->mapelSertifikasi?->rumpuns; // Updated to rumpuns, though still unused
        $sertMapelId = $guru->mapel_sertifikasi_id;
        $TARGET = 24;

        // ─── JTM KBM ───
        $jtmKbm = 0;
        $jtmLinearKbm = 0;
        // Filter beban mengajar berdasarkan semester
        $bebanMengajars = $guru->bebanMengajars->where('semester_id', $semesterId);
        
        foreach ($bebanMengajars as $bm) {
            $jtmKbm += $bm->jtm;
            if ($guru->isLinear($bm->mapel)) {
                $jtmLinearKbm += $bm->jtm;
            }
        }

        // ─── Tugas Tambahan ───
        $jtmTugas = 0;
        $jtmLinearTugas = 0;
        // Filter tugas tambahan berdasarkan semester
        $tugasTambahans = $guru->tugasTambahans->filter(function($t) use ($semesterId) {
            return $t->pivot->semester_id == $semesterId;
        });

        foreach ($tugasTambahans as $t) {
            if ($t->pivot->is_ekuivalen) {
                $jtmLinearTugas += $t->jtm_ekuivalen;
                $jtmTugas += $t->jtm_ekuivalen;
            }
        }

        $totalBeban  = $jtmKbm + $jtmTugas;
        $totalLinear = $jtmLinearKbm + $jtmLinearTugas;
        $layak       = $guru->status_sertifikasi ? ($totalLinear >= $TARGET) : null;

        return compact('jtmKbm', 'jtmTugas', 'totalBeban', 'totalLinear', 'layak', 'TARGET');
    }
}
