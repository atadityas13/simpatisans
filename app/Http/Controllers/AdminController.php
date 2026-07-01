<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Require super admin locally until middleware is fully mapped if needed,
        // but for now we expect routing or middleware to handle access later.
        
        $users = User::whereIn('role', ['super_admin', 'admin_kurikulum'])->orderBy('nama_lengkap')->paginate(10);
        return view('admin.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $existingUser = User::where('username', $request->username)->first();

        // Jika user sudah terdaftar dan rolenya adalah admin, beri pesan error username sudah digunakan
        if ($existingUser && ($existingUser->isSuperAdmin() || $existingUser->isAdminKurikulum())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'username' => 'Username/NIP ini sudah terdaftar sebagai Admin.'
            ]);
        }

        $validated = $request->validate([
            'username'     => 'required|string|max:255',
            'role'         => 'required|in:super_admin,admin_kurikulum',
            'nama_lengkap' => 'required|string|max:255',
            'jabatan'      => 'nullable|string|max:255',
            'password'     => $existingUser ? 'nullable|string|min:6' : 'required|string|min:6',
            'foto'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Jika user 'guru' sudah ada dan password dikosongkan saat ganti role ke admin, pakai password lamanya
            unset($validated['password']);
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('admin_photos', 'public');
            $validated['foto'] = $path;
        }

        if ($existingUser) {
            // Upgrade role guru yang sudah ada menjadi admin
            $existingUser->update($validated);
            $user = $existingUser;
        } else {
            // Buat user admin baru dari nol
            $user = User::create($validated);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil ditambahkan!',
                'data'    => $user
            ]);
        }

        return redirect()->route('admin.index')->with('success', 'Akun Admin ditambahkan!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $admin)
    {
        $rules = [
            'username'     => 'required|string|max:255|unique:users,username,' . $admin->id,
            'role'         => 'required|in:super_admin,admin_kurikulum',
            'nama_lengkap' => 'required|string|max:255',
            'jabatan'      => 'nullable|string|max:255',
            'password'     => 'nullable|string|min:6',
            'foto'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($admin->foto && Storage::disk('public')->exists($admin->foto)) {
                Storage::disk('public')->delete($admin->foto);
            }
            $path = $request->file('foto')->store('admin_photos', 'public');
            $validated['foto'] = $path;
        }

        $admin->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil diperbarui!',
                'data'    => $admin
            ]);
        }

        return redirect()->route('admin.index')->with('success', 'Akun Admin diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $admin)
    {
        if ($admin->foto && Storage::disk('public')->exists($admin->foto)) {
            Storage::disk('public')->delete($admin->foto);
        }

        // Ensure a super admin cannot delete themselves easily if they are the last one,
        // but for simplicity, we just delete.
        $admin->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Admin berhasil dihapus!'
            ]);
        }

        return redirect()->route('admin.index')->with('success', 'Akun Admin dihapus!');
    }
}
