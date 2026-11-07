<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CBT Tes Potensi Akademik - SPMB SMK Tiara Nusa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
        <div class="text-center mb-6">
            <img src="{{ asset('logo.png') }}" alt="Logo" class="w-16 h-16 mx-auto mb-3 object-contain">
            <h2 class="text-2xl font-bold text-gray-900">Login CBT TPA</h2>
            <p class="text-sm text-gray-500 mt-1">Masukkan ID SPMB dan Tanggal Lahir Anda untuk mulai ujian.</p>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <form action="{{ route('public.tpa.verify') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ID SPMB</label>
                <input type="text" name="spmb_id" required placeholder="Contoh: SPMB-2026-0001" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                <input type="date" name="birth_date" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-md">
                Mulai Ujian TPA
            </button>
        </form>
    </div>
</body>
</html>