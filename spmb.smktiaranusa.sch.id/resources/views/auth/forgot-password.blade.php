@extends('layouts.public')

@section('title', 'Lupa Password - SMK Tiara Nusa')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 bg-gray-50">
    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-xl border border-gray-100 text-center transition-all duration-500">
        
        <div class="mx-auto h-16 w-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-6 shadow-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        
        <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Lupa Password?</h2>
        <p class="text-sm text-gray-500 mb-8 leading-relaxed">
            Jangan khawatir. Masukkan alamat email aktif Anda di bawah ini dan kami akan mengirimkan tautan untuk mengatur ulang password Anda.
        </p>

        <form action="{{ route('password.email') }}" method="POST" id="forgotForm">
            @csrf
            <div class="mb-6 text-left">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Terdaftar</label>
                <input id="email" name="email" type="email" required class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors sm:text-sm" placeholder="nama@email.com">
            </div>

            <button type="submit" class="w-full py-3.5 px-4 font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all shadow-md active:scale-95">
                Kirim Tautan Reset
            </button>
        </form>

        <div class="mt-6 text-sm text-center">
            <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-blue-600 transition-colors inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Login
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('forgotForm').addEventListener('submit', function(e) {
        Swal.fire({
            title: 'Memeriksa Email...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    });

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '{{ $errors->first() }}',
            buttonsStyling: false,
            customClass: { 
                popup: 'rounded-3xl',
                confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold py-2.5 px-6 rounded-xl transition-colors'
            }
        });
    @endif

    @if(session('status'))
        Swal.fire({
            icon: 'success',
            title: 'Terkirim!',
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