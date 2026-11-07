@extends('layouts.public')

@section('title', 'Verifikasi Email - SMK Tiara Nusa')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 bg-gray-50 relative overflow-hidden">
    
    <div class="absolute top-10 left-10 w-72 h-72 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>
    <div class="absolute bottom-10 right-10 w-72 h-72 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000"></div>

    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100 text-center relative z-10">
        
        <div class="mx-auto h-20 w-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-blue-100">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        
        <h2 class="text-3xl font-extrabold text-gray-900 mb-3 tracking-tight">Cek Email Anda</h2>
        <p class="text-sm text-gray-500 mb-8 leading-relaxed px-2">
            Kami telah mengirimkan 6 digit kode OTP. Masukkan kode tersebut di bawah ini untuk mengaktifkan akun Anda.
        </p>

        <form action="{{ route('verify.otp') }}" method="POST" id="otpForm">
            @csrf
            <div class="flex justify-center gap-2 sm:gap-3 mb-8" id="otp-container">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" required autocomplete="off"
                        class="otp-input w-12 h-14 sm:w-14 sm:h-16 text-center text-2xl font-extrabold text-gray-900 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all shadow-inner"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                @endfor
            </div>
            
            <input type="hidden" name="otp" id="otp-hidden">

            <button type="submit" class="w-full py-3.5 px-4 text-lg font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all shadow-md hover:shadow-lg active:scale-95 flex justify-center items-center group">
                Verifikasi Akun
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </form>

        <div class="mt-8 text-sm text-gray-500 border-t border-gray-100 pt-6">
            Belum menerima kode OTP? 
            <form action="{{ route('resend.otp') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="font-bold text-blue-600 hover:text-blue-800 transition-colors bg-transparent border-none cursor-pointer hover:underline" onclick="resendAlert(event)">Kirim Ulang</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const inputs = document.querySelectorAll('.otp-input');
    const hiddenInput = document.getElementById('otp-hidden');

    function updateHiddenInput() {
        hiddenInput.value = Array.from(inputs).map(input => input.value).join('');
    }

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6).split('');
            inputs.forEach((input, i) => {
                if (pastedData[i]) {
                    input.value = pastedData[i];
                    if (i < inputs.length - 1) inputs[i + 1].focus();
                }
            });
            updateHiddenInput();
        });
    });

    document.getElementById('otpForm').addEventListener('submit', function() {
        updateHiddenInput();
    });

    window.onload = () => inputs[0].focus();

    function resendAlert(e) {
        Swal.fire({
            icon: 'info',
            title: 'Mengirim Ulang...',
            text: 'Permintaan pengiriman ulang OTP sedang diproses.',
            showConfirmButton: false,
            timer: 2000,
            customClass: { popup: 'rounded-2xl' }
        });
    }

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Verifikasi Gagal',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#2563EB',
            customClass: { popup: 'rounded-3xl' }
        });
    @endif

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '{{ session('success') }}',
            confirmButtonColor: '#10B981',
            customClass: { popup: 'rounded-3xl' }
        });
    @endif
</script>

<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob { animation: blob 7s infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
</style>
@endsection