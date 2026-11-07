@extends('layouts.public')

@section('title', 'Atur Ulang Password - SMK Tiara Nusa')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100 text-center transition-all duration-500">
        
        <div class="mx-auto h-16 w-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-6 shadow-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
        </div>

        <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Buat Password Baru</h2>
        <p class="text-sm text-gray-500 mb-8">Silakan masukkan password baru yang kuat untuk akun Anda.</p>

        <form action="{{ route('password.update') }}" method="POST" id="resetForm" class="text-left space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- Password Baru -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input id="password" name="password" type="password" required class="appearance-none block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="Password Baru">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password', 'eye-reg-1')">
                        <svg id="eye-reg-1" class="h-5 w-5 text-gray-400 hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="appearance-none block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="Konfirmasi Password Baru">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword('password_confirmation', 'eye-reg-2')">
                        <svg id="eye-reg-2" class="h-5 w-5 text-gray-400 hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Indikator Syarat Password Dinamis -->
            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                <p class="text-xs text-blue-800 font-semibold mb-2">Syarat Password:</p>
                <ul class="text-xs grid grid-cols-2 gap-2 list-none">
                    <li id="req-length" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Minimal 12 karakter</li>
                    <li id="req-case" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Huruf besar & kecil</li>
                    <li id="req-number" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Mengandung angka</li>
                    <li id="req-symbol" class="text-gray-500 flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Mengandung simbol</li>
                </ul>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all shadow-md active:scale-95">
                Simpan Password Baru
            </button>
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
        
        const isLength = val.length >= 12;
        const isCase = /[a-z]/.test(val) && /[A-Z]/.test(val);
        const isNumber = /[0-9]/.test(val);
        const isSymbol = /[^a-zA-Z0-9]/.test(val);

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

    // 3. Loading Konfirmasi
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        Swal.fire({
            title: 'Memproses Perubahan...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    });

    // 4. Custom Error Notification
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Penyimpanan Gagal',
            text: '{{ $errors->first() }}',
            buttonsStyling: false,
            customClass: { 
                popup: 'rounded-3xl',
                confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-6 rounded-xl transition-colors'
            }
        });
    @endif
</script>
@endsection