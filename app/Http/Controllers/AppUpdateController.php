<?php

namespace App\Http\Controllers;

use App\Models\AppUpdate;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AppUpdateController extends Controller
{
    public function index()
    {
        try {
            $item = AppUpdate::query()->where('platform', 'android')->first();
        } catch (QueryException $e) {
            $item = null;
            session()->flash(
                'error',
                'Tabel update aplikasi belum tersedia. Jalankan: php artisan migrate --force'
            );
        }

        return view('app-updates.index', [
            'item' => $item,
            'defaults' => $this->defaults(),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('app_updates')) {
            return redirect()
                ->route('app-updates.index')
                ->with('error', 'Tabel update aplikasi belum tersedia. Jalankan: php artisan migrate --force');
        }

        $data = $request->validate([
            'latest_version_code' => 'required|integer|min:1',
            'latest_version_name' => 'required|string|max:40',
            'minimum_version_code' => 'required|integer|min:1',
            'title' => 'required|string|max:160',
            'message' => 'nullable|string|max:2000',
            'changelog' => 'nullable|string|max:5000',
            'play_store_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data['minimum_version_code'] > $data['latest_version_code']) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Minimum version code tidak boleh lebih besar dari latest version code.');
        }

        $existing = AppUpdate::query()->where('platform', 'android')->first();
        $payload = array_merge($data, [
            'platform' => 'android',
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => $request->user()?->id,
        ]);

        if ($existing) {
            $existing->update($payload);
        } else {
            AppUpdate::create(array_merge($payload, [
                'created_by' => $request->user()?->id,
            ]));
        }

        return redirect()
            ->route('app-updates.index')
            ->with('success', 'Kebijakan update aplikasi berhasil disimpan.');
    }

    private function defaults(): array
    {
        return [
            'latest_version_code' => 1,
            'latest_version_name' => '1.0.0',
            'minimum_version_code' => 1,
            'title' => 'Update Ta\'lim tersedia',
            'message' => 'Versi terbaru Ta\'lim sudah tersedia di Google Play Store.',
            'changelog' => null,
            'play_store_url' => 'https://play.google.com/store/apps/details?id=com.atadevlabs.talim',
            'is_active' => true,
        ];
    }
}
