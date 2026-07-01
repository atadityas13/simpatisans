<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PenggunaController extends Controller
{
    /**
     * Display a listing of teachers with their account status.
     */
    public function index()
    {
        $gurus = Guru::with('user')->orderBy('nama_guru')->paginate(15);
        return view('pengguna.index', compact('gurus'));
    }

    /**
     * Generate or reset a user account for a teacher.
     */
    public function generate(Guru $guru)
    {
        $existingUser = User::where('username', $guru->username)->first();
        
        if ($existingUser && ($existingUser->isSuperAdmin() || $existingUser->isAdminKurikulum())) {
            return back()->with('error', "Guru ini sudah terdaftar sebagai Admin. Kelola akun ini melalui menu Admin.");
        }

        $user = User::updateOrCreate(
            ['username' => $guru->username],
            [
                'nama_lengkap' => $guru->nama_lengkap, // Accessor from Guru model
                'role' => 'guru',
                'jabatan' => $guru->jabatan,
                'password' => Hash::make($guru->username),
                'plain_password' => $guru->username,
                'is_active' => true,
                'reset_requested_at' => null,
            ]
        );

        return back()->with('success', "Akun untuk {$guru->nama_guru} berhasil dibuat/direset.");
    }

    /**
     * Toggle the active status of a user.
     */
    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->username} berhasil {$status}.");
    }

    /**
     * Reset password and approve reset request if any.
     */
    public function resetPassword(User $user)
    {
        $user->password = Hash::make($user->username);
        $user->plain_password = $user->username;
        $user->reset_requested_at = null;
        $user->reset_answer_provided = null;
        $user->save();

        return back()->with('success', "Password untuk {$user->username} berhasil direset ke default.");
    }

    /**
     * Update user profile photo.
     */
    public function updatePhoto(Request $request, User $user)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            // Delete old photo
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            $path = $request->file('foto')->store('user_photos', 'public');
            $user->foto = $path;
            $user->save();
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }
}
