@extends('layouts.public')

@section('title', 'Login - SPMB SMK Tiara Nusa')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 bg-gray-50 relative overflow-hidden">
    <!-- Dekorasi Hiasan -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
        <div class="absolute bottom-10 right-10 w-72 h-72 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
    </div>

    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100 relative z-10 transition-all duration-500">
        <div class="text-center mb-8">
            <div class="mx-auto h-16 w-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4 border border-blue-100 shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang</h2>
            <p class="text-sm text-gray-500 mt-2">Masuk ke akun SPMB Anda untuk melanjutkan</p>
        </div>

        <form action="{{ route('login') }}" method="POST" id="loginForm" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-3 transition-colors" placeholder="nama@email.com" required autofocus>
            </div>
            
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="text-sm font-medium text-gray-900">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-600 hover:underline">Lupa password?</a>
                </div>
                <div class="relative">
                    <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-4 pr-10 py-3 transition-colors" required>
                    <!-- Ikon Mata -->
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password', 'eye-icon')">
                        <svg id="eye-icon" class="h-5 w-5 text-gray-400 hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 text-blue-600">
                </div>
                <label for="remember" class="ml-2 text-sm font-medium text-gray-900">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-bold rounded-xl text-md px-5 py-3.5 text-center transition-all shadow-md active:scale-95">
                Masuk Sekarang
            </button>
            
            <p class="text-sm font-medium text-gray-500 text-center mt-6">
                Belum punya akun? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Daftar SPMB di sini</a>
            </p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Fungsi Toggle Password
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === "password") {
            input.type = "text";
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        } else {
            input.type = "password";
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    }

    // Efek Loading Saat Submit
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        Swal.fire({
            title: 'Memeriksa Data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    });

    // Notifikasi Error (Login Gagal)
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: '{{ $errors->first() }}',
            buttonsStyling: false,
            customClass: { 
                popup: 'rounded-3xl',
                confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-6 rounded-xl transition-colors'
            }
        });
    @endif

    // Notifikasi Sukses (Misal: Setelah Logout atau Reset Password)
    @if(session('status'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '{{ session('status') }}',
            buttonsStyling: false,
            customClass: { 
                popup: 'rounded-3xl',
                confirmButton: 'bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-6 rounded-xl transition-colors'
            }
        });
    @endif
</script>
@endsection