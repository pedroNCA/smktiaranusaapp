<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\SendOtpMail;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }
    
    public function showRegister()
    {
        return view('auth.register');
    }
    
    public function showVerifyOtp()
    {
        return view('auth.verify-otp');
    }
    
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'whatsapp' => 'required|string|max:20',
        'password' => [
            'required',
            'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols()
        ],
    ], [
        'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain.',
        'password.min' => 'Password minimal harus 12 karakter.',
        'password.mixed' => 'Password harus mengandung huruf besar dan kecil.',
        'password.numbers' => 'Password harus mengandung angka.',
        'password.symbols' => 'Password harus mengandung simbol.',
    ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        $this->generateAndSendOTP($user);

        Auth::login($user);

        return redirect()->route('verify.otp')->with('success', 'Akun berhasil dibuat! Silakan cek email Anda untuk kode OTP.');
    }

    private function generateAndSendOTP($user)
    {
        $otp = rand(100000, 999999);

        OtpVerification::create([
            'user_id' => $user->id,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'is_verified' => false
        ]);

        Mail::to($user->email)->send(new SendOtpMail($otp));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|numeric|digits:6']);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $otpRecord = OtpVerification::where('user_id', $user->id)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp)) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }

        $otpRecord->update(['is_verified' => true]);
        $user->email_verified_at = now();
        $user->save();

        return redirect()->intended('/student/dashboard')->with('status', 'Verifikasi berhasil! Selamat datang di portal SPMB.');
    }

    public function resendOtp()
    {
        $user = Auth::user();
        if ($user->email_verified_at) {
            return redirect('/student/dashboard');
        }

        $this->generateAndSendOTP($user);
        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            if (is_null(Auth::user()->email_verified_at)) {
                return redirect()->route('verify.otp')->with('success', 'Silakan verifikasi email Anda terlebih dahulu.');
            }

            if (Auth::user()->role === 'student') {
                return redirect()->intended('/student/dashboard')->with('status', 'Berhasil Login!');
            } else {
                return redirect()->intended('/admin/dashboard')->with('status', 'Berhasil Login sebagai Panitia/Admin!');
            }
        }

        return back()->withErrors([
            'email' => 'Email atau Password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar.');
    }

    // ==========================================
    // 5. LUPA PASSWORD & RESET
    // ==========================================
    public function sendResetLink(Request $request)
    {
        $request->validate(
            ['email' => 'required|email|exists:users,email'],
            ['email.exists' => 'Email ini tidak terdaftar di sistem kami.']
        );

        // Buat Token Acak
        $token = Str::random(64);

        // Simpan token ke database bawaan Laravel
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Kirim Email secara langsung tanpa harus membuat Mailable baru
        Mail::send('emails.reset-password', ['token' => $token, 'email' => $request->email], function($message) use ($request) {
            $message->to($request->email);
            $message->subject('Pemulihan Password Akun SPMB - SMK Tiara Nusa');
        });

        return back()->with('status', 'Tautan untuk mengatur ulang sandi telah dikirim ke email Anda.');
    }

    // Menampilkan halaman form input password baru
    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    // Memproses penggantian password di database
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || $record->token !== $request->token) {
            return back()->withErrors(['email' => 'Token kadaluarsa atau tidak valid. Silakan ulangi proses lupa password.']);
        }

        // Update password baru
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus token agar tidak bisa dipakai 2x
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Password berhasil diubah! Silakan masuk dengan password baru Anda.');
    }
}