@extends('layouts.public')

@section('title', 'Daftar Akun SPMB - SMK Tiara Nusa')

@section('content')
<div class="min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 transition-all duration-500">
    <div class="max-w-2xl w-full space-y-6 bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100">
        
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Daftar Akun SPMB</h2>
            <p class="mt-2 text-sm text-gray-500">Lengkapi data di bawah ini untuk memulai pendaftaran</p>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('register') }}" method="POST" id="registerForm">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Nama Lengkap -->
                <div class="col-span-1 sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="Sesuai Akta Kelahiran">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Aktif <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="nama@email.com">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp <span class="text-red-500">*</span></label>
                    <input id="whatsapp" name="whatsapp" type="text" value="{{ old('whatsapp') }}" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="08123456789">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required class="appearance-none block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="Password">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password', 'eye-reg-1')">
                            <svg id="eye-reg-1" class="h-5 w-5 text-gray-400 hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="appearance-none block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="Password">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password_confirmation', 'eye-reg-2')">
                            <svg id="eye-reg-2" class="h-5 w-5 text-gray-400 hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </div>
                    </div>
                </div>
                
                <!-- Indikator Syarat Password Dinamis -->
                <div class="col-span-1 sm:col-span-2 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                    <p class="text-xs text-blue-800 font-semibold mb-2">Syarat Password:</p>
                    <ul class="text-xs grid grid-cols-2 gap-2 list-none">
                        <li id="req-length" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Minimal 12 karakter</li>
                        <li id="req-case" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Huruf besar & kecil</li>
                        <li id="req-number" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Mengandung angka</li>
                        <li id="req-symbol" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Mengandung simbol</li>
                    </ul>
                </div>
            </div>

            <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-md hover:shadow-lg active:scale-95">
                Daftar
            </button>
            
            <p class="text-center text-sm text-gray-600 mt-4">
                Sudah punya akun? <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-500 transition-colors">Masuk di sini</a>
            </p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // 1. Toggle Password Visibility
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

    // 2. Live Password Validation
    const passwordInput = document.getElementById('password');
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        
        // Cek Syarat
        const isLength = val.length >= 12;
        const isCase = /[a-z]/.test(val) && /[A-Z]/.test(val);
        const isNumber = /[0-9]/.test(val);
        const isSymbol = /[^a-zA-Z0-9]/.test(val);

        // Update UI
        updateReqUI('req-length', isLength);
        updateReqUI('req-case', isCase);
        updateReqUI('req-number', isNumber);
        updateReqUI('req-symbol', isSymbol);
    });

    function updateReqUI(elementId, isValid) {
        const el = document.getElementById(elementId);
        if (isValid) {
            el.classList.remove('text-gray-500');
            el.classList.add('text-green-600', 'font-semibold');
        } else {
            el.classList.remove('text-green-600', 'font-semibold');
            el.classList.add('text-gray-500');
        }
    }

    // 3. Loading Submit
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        Swal.fire({
            title: 'Memproses Data...',
            text: 'Mohon tunggu sebentar.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    });

    // 4. Custom Error Handling
// 4. Custom Error Handling dengan Perbaikan Tombol Tailwind
    @if($errors->any())
        @if($errors->has('email'))
            Swal.fire({
                icon: 'warning',
                title: 'Email Sudah Terdaftar',
                text: '{{ $errors->first('email') }}',
                confirmButtonText: 'Ganti Email',
                showDenyButton: true,
                denyButtonText: 'Pergi ke Login',
                buttonsStyling: false,
                customClass: { 
                    popup: 'rounded-3xl',
                    confirmButton: 'bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl mx-2 transition-colors',
                    denyButton: 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2.5 px-5 rounded-xl mx-2 transition-colors'
                }
            }).then((result) => {
                if (result.isDenied) {
                    window.location.href = "{{ route('login') }}";
                }
            });
        @else
            Swal.fire({
                icon: 'error',
                title: 'Data Belum Sesuai',
                text: '{{ $errors->first() }}',
                confirmButtonText: 'Perbaiki Data',
                buttonsStyling: false,
                customClass: { 
                    popup: 'rounded-3xl',
                    confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-5 rounded-xl transition-colors'
                }
            });
        @endif
    @endif
</script>
@endsection