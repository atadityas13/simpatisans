<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Ensure guru relationship is loaded if it exists, regardless of user role
        // This allows admins who are also teachers to see their teacher profile data.
        $user->load('guru.mapelIjazah', 'guru.rumpunIjazah', 'guru.mapelSertifikasi', 'guru.mapelDiampu');
        
        return view('profile.index', compact('user'));
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

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

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        
        $rules = [
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(6)],
        ];

        // Force security question if not set
        if (!$user->security_question) {
            $rules['security_question'] = 'required|string|min:10';
            $rules['security_answer'] = 'required|string|min:3';
        }

        $request->validate($rules);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
        }

        $user->password = Hash::make($request->password);
        $user->plain_password = $request->password; 
        
        if ($request->has('security_question')) {
            $user->security_question = $request->security_question;
            $user->security_answer = $request->security_answer;
        }

        $user->save();

        return back()->with('success', 'Password dan pertanyaan keamanan berhasil diperbarui.');
    }
}
