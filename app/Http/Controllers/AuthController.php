<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ], [
                'username.required' => 'Waduh, NIP/NIK harus diisi ya Bapak/Ibu.',
                'password.required' => 'Passwordnya jangan lupa diisi juga.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => $e->validator->errors()->all()
                ], 422);
            }
            throw $e;
        }

        // Check if user exists in User table
        $username = $credentials['username'];
        $userExists = User::where('username', $username)->first();

        if (!$userExists) {
            // Check if it exists in Guru table
            $guruExists = Guru::where('username', $username)->exists();
            $message = $guruExists 
                ? 'Akun Anda belum diaktifkan, silakan hubungi Admin.' 
                : 'Aduh, sepertinya NIP/NIK ini belum terdaftar di sistem kami.';
            
            if ($request->wantsJson()) {
                return response()->json(['errors' => [$message]], 422);
            }
            return back()->withErrors(['username' => $message])->onlyInput('username');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $message = 'Aduh, akun Anda dinonaktifkan. Silakan hubungi admin ya.';
                if ($request->wantsJson()) {
                    return response()->json(['errors' => [$message]], 422);
                }
                return back()->withErrors(['username' => $message]);
            }

            if ($request->wantsJson()) {
                // Determine redirect path for AJAX
                $hasAdminAccess = ($user->isSuperAdmin() || $user->isAdminKurikulum());
                $hasGuruRef = Guru::where('username', $user->username)->exists();
                $redirectUrl = ($hasAdminAccess && $hasGuruRef) ? route('auth.select-role') : ( $hasAdminAccess ? route('dashboard') : route('guru.dashboard') );
                
                return response()->json([
                    'success' => true,
                    'redirect' => $redirectUrl
                ]);
            }

            return $this->redirectBasedOnRole($user);
        }

        $message = 'Aduh, sepertinya Passwordnya salah. Coba dicek lagi ya.';
        if ($request->wantsJson()) {
            return response()->json(['errors' => [$message]], 422);
        }
        return back()->withErrors(['username' => $message])->onlyInput('username');
    }

    /**
     * Redirect logic based on multi-role detection.
     */
    protected function redirectBasedOnRole(User $user)
    {
        $hasAdminAccess = ($user->isSuperAdmin() || $user->isAdminKurikulum());
        $hasGuruRef = Guru::where('username', $user->username)->exists();

        if ($hasAdminAccess && $hasGuruRef) {
            // User is both Admin and Guru -> Go to SPLASH selection
            return redirect()->route('auth.select-role');
        }

        if ($hasAdminAccess) {
            session(['active_role' => 'admin']);
            return redirect()->intended(route('dashboard'));
        }

        // Default to Guru role
        session(['active_role' => 'guru']);
        return redirect()->intended(route('guru.dashboard'));
    }

    /**
     * Show role selection screen for dual-role users.
     */
    public function showSelectRole()
    {
        $user = Auth::user();
        if (!$user)
            return redirect()->route('login');

        $hasAdmin = ($user->isSuperAdmin() || $user->isAdminKurikulum());
        $hasGuru = Guru::where('username', $user->username)->exists();

        if (!$hasAdmin || !$hasGuru) {
            return $this->redirectBasedOnRole($user);
        }

        return view('auth.select-role');
    }

    /**
     * Set the active role in session.
     */
    public function selectRole(Request $request)
    {
        $role = $request->input('role');
        if (!in_array($role, ['admin', 'guru'])) {
            return back()->with('error', 'Role tidak valid.');
        }

        session(['active_role' => $role]);

        return ($role === 'admin')
            ? redirect()->route('dashboard')
            : redirect()->route('guru.dashboard');
    }

    /**
     * Quick switch role from sidebar.
     */
    public function switchRole($role)
    {
        $user = Auth::user();
        if (!in_array($role, ['admin', 'guru'])) {
            return back()->with('error', 'Role tidak valid.');
        }

        // Verify they actually have the rights for the role they are switching to
        if ($role === 'admin' && !($user->isSuperAdmin() || $user->isAdminKurikulum())) {
            return back()->with('error', 'Anda tidak memiliki hak akses Admin.');
        }

        session(['active_role' => $role]);

        return ($role === 'admin')
            ? redirect()->route('dashboard')
            : redirect()->route('guru.dashboard');
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Verify NIP/NIK and show security question.
     */
    public function verifyForgotPassword(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => 'Username tidak ditemukan.'])->withInput();
        }

        if (!$user->security_question) {
            return back()->withErrors(['username' => 'Anda belum menyetel pertanyaan keamanan. Silakan hubungi Admin Kurikulum secara langsung untuk reset password.'])->withInput();
        }

        return view('auth.forgot-password', [
            'username' => $user->username,
            'question' => $user->security_question,
            'step' => 2
        ]);
    }

    /**
     * Submit the answer and request reset.
     */
    public function requestReset(Request $request)
    {
        $request->validate([
            'username' => 'required|string|exists:users,username',
            'answer' => 'required|string'
        ]);

        $user = User::where('username', $request->username)->first();
        
        // Simpan jawaban yang diberikan user untuk dicek admin
        $user->reset_answer_provided = $request->answer;
        $user->reset_requested_at = now();
        $user->save();

        return redirect()->route('login')->with('success', 'Permintaan reset password berhasil dikirim. Silakan hubungi Admin untuk persetujuan.');
    }

    /**
     * Show first login password change form.
     */
    public function showFirstLogin()
    {
        $user = Auth::user();
        
        // Anti-bypass: Jika sudah ganti password, kembalikan ke dashboard
        if ($user->plain_password === null) {
            return $this->redirectBasedOnRole($user);
        }

        return view('auth.first-login', compact('user'));
    }

    /**
     * Handle first login password change.
     */
    public function completeFirstLogin(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(6)],
            'security_question' => 'required|string|min:10',
            'security_answer' => 'required|string|min:3',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
            'security_question.required' => 'Pertanyaan keamanan wajib diisi.',
            'security_question.min' => 'Pertanyaan keamanan minimal harus berisi minimal 10 karakter.',
            'security_answer.required' => 'Jawaban keamanan wajib diisi.',
            'security_answer.min' => 'Jawaban keamanan minimal harus 3 karakter.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
        }

        $user->password = Hash::make($request->password);
        $user->security_question = $request->security_question;
        $user->security_answer = $request->security_answer;
        $user->plain_password = null; // Clear the plain password to mark setup as complete
        $user->save();

        return $this->redirectBasedOnRole($user)->with('success', 'Akun Anda berhasil dikonfigurasi. Selamat datang di SIPASTI!');
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
