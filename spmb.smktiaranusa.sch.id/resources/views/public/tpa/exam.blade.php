
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ujian TPA - {{ $registration->user->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            -webkit-tap-highlight-color: transparent;
        }

        .question-card {
            display: none;
        }

        .question-card.active {
            display: block;
            animation: fadeIn .2s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .option-label:has(input:checked) {
            border-color: #2563eb;
            background-color: #eff6ff;
        }

        .nav-popup {
            animation: popup .2s ease-out;
        }

        @keyframes popup {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        html {
    scroll-behavior: smooth;
}

body {
    -webkit-tap-highlight-color: transparent;
    overflow-x: hidden;
}

button,
input,
label {
    touch-action: manipulation;
}

.option-label {
    min-height: 48px;
}

.question-card {
    overflow-wrap: anywhere;
}

#timer-container {
    transition: background-color .2s ease, border-color .2s ease;
}

@media (max-width: 640px) {
    .question-card {
        min-height: 300px;
    }

    #question-navigation {
        max-height: 60vh;
    }
}

#questions-container {
    scroll-margin-top: 100px;
}
    </style>
</head>

<body class="bg-gray-50 min-h-screen pb-10">

    <!-- HEADER -->
<header class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-200">
    <div class="max-w-5xl mx-auto px-3 sm:px-4 py-2 sm:py-3">
        <div class="flex items-center justify-between gap-2 sm:gap-4">

            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <img src="{{ asset('logo.png') }}"
                     alt="Logo"
                     class="w-9 h-9 sm:w-11 sm:h-11 object-contain shrink-0">

                <div class="min-w-0">
                    <h1 class="font-bold text-gray-900 text-xs sm:text-base leading-tight">
                        Ujian Potensi Akademik
                    </h1>

                    <p class="text-[10px] sm:text-xs text-gray-500 truncate">
                        {{ $registration->user->name }}
                        ({{ $registration->spmb_id }})
                    </p>
                </div>
            </div>

            <div id="timer-container"
                 class="bg-red-50 border border-red-200 px-2.5 sm:px-4 py-2 rounded-xl shrink-0">

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>

                    <span id="timer"
                          class="text-red-700 font-mono font-bold text-sm sm:text-lg tabular-nums">
                        00:00:00
                    </span>
                </div>
            </div>

        </div>
    </div>
</header>


    <!-- KONTEN UJIAN -->
    <main class="max-w-4xl mx-auto px-4 mt-6 md:mt-10">

        <form id="exam-form"
              action="{{ route('public.tpa.submit', $registration->id) }}"
              method="POST">

            @csrf

            <!-- DAFTAR SOAL -->
            <div id="questions-container">

                @foreach($questions as $index => $q)

                    <div class="question-card bg-white p-4 sm:p-6 md:p-8 rounded-xl sm:rounded-2xl shadow-sm border border-gray-200"
                         data-index="{{ $index }}"
                         data-id="{{ $q->id }}">

                        <!-- NOMOR DAN MAPEL -->
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-5 sm:mb-6">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 shrink-0 bg-blue-100 text-blue-700 font-bold rounded-xl flex items-center justify-center text-lg">
                                    {{ $index + 1 }}
                                </div>

                                <div>
                                    <p class="text-xs text-gray-400 font-medium">
                                        NOMOR SOAL
                                    </p>

                                    <p class="text-sm font-bold text-gray-800">
                                        Soal {{ $index + 1 }}
                                    </p>
                                </div>

                            </div>

                            <div class="flex items-center gap-2">

    <span class="text-[10px] sm:text-sm font-bold uppercase text-blue-600 bg-blue-50 px-2.5 sm:px-3 py-2 rounded-lg">
    {{ $q->mapel }}
</span>

    <button type="button"
        onclick="toggleNavigation()"
        class="nav-toggle flex items-center gap-1.5 sm:gap-2 bg-gray-800 hover:bg-gray-900 text-white px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold transition">

        <svg class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16">
            </path>
        </svg>

        <span>Navigasi</span>

    </button>

</div>

                        </div>


                        <!-- PERTANYAAN -->
                        <div class="border-t border-gray-100 pt-6">

                            <div class="text-gray-800 font-semibold text-sm sm:text-base md:text-lg leading-relaxed mb-5 sm:mb-7 break-words">
    {!! nl2br(e($q->question)) !!}
</div>


                            <!-- PILIHAN GANDA -->
                            <div class="space-y-3">

                                @foreach([
                                    'A' => $q->option_a,
                                    'B' => $q->option_b,
                                    'C' => $q->option_c,
                                    'D' => $q->option_d,
                                    'E' => $q->option_e
                                ] as $key => $option)

                                    @if(!empty($option))

                                        <label class="option-label flex items-start gap-3 p-3 sm:p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 cursor-pointer transition active:scale-[0.99]">

    <input type="radio"
           name="answers[{{ $q->id }}]"
           value="{{ $key }}"
           class="mt-1 w-4 h-4 shrink-0 text-blue-600 focus:ring-blue-500">

    <span class="text-gray-700 text-sm sm:text-base leading-relaxed break-words min-w-0">
        {!! nl2br(e($option)) !!}
    </span>

</label>

                                    @endif

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>


            <!-- NAVIGASI BAWAH -->
            <div class="mt-4 sm:mt-6 bg-white border border-gray-200 shadow-sm rounded-xl sm:rounded-2xl p-3 sm:p-4">

    <div class="flex items-center justify-between gap-2">

        <button type="button"
                id="prev-btn"
                onclick="previousQuestion()"
                class="flex-1 sm:flex-none px-3 sm:px-5 py-3 rounded-xl border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-100 transition disabled:opacity-40 disabled:cursor-not-allowed">

            <span class="hidden sm:inline">Sebelumnya</span>
            <span class="sm:hidden">← Kembali</span>

        </button>

        <label class="flex items-center justify-center gap-1.5 cursor-pointer px-2 sm:px-3 py-3 rounded-xl hover:bg-yellow-50 transition">

            <input type="checkbox"
                   id="doubtful-checkbox"
                   class="w-4 h-4 text-yellow-500 rounded focus:ring-yellow-500">

            <span class="text-xs sm:text-sm font-semibold text-gray-700 whitespace-nowrap">
                Ragu-ragu
            </span>

        </label>

        <button type="button"
                id="next-btn"
                onclick="nextQuestion()"
                class="flex-1 sm:flex-none px-3 sm:px-6 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 transition">

            Selanjutnya

        </button>

    </div>

</div>

        </form>

    </main>

    <!-- POPUP NAVIGASI SOAL -->
<div id="navigation-popup"
     class="hidden fixed top-20 sm:top-24 right-2 sm:right-4 z-50 w-72 max-w-[calc(100vw-16px)] bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 nav-popup">

    <div class="flex justify-between items-center mb-4">

        <h3 class="font-bold text-gray-800">
            Navigasi Soal
        </h3>

        <button type="button"
                onclick="toggleNavigation()"
                class="text-gray-500 hover:text-red-600 text-2xl leading-none">
            &times;
        </button>

    </div>

    <div id="question-navigation"
     class="max-h-[65vh] overflow-y-auto space-y-4">

    @php $navIndex = 0; @endphp

    @foreach($questionSections as $mapel => $sectionQuestions)

        <div>
            <div class="text-xs font-bold uppercase text-gray-500 mb-2">
                {{ $mapel }}
            </div>

            <div class="grid grid-cols-5 sm:grid-cols-6 gap-2">

                @foreach($sectionQuestions as $q)

                    <button type="button"
                            onclick="goToQuestion({{ $navIndex }})"
                            data-nav-index="{{ $navIndex }}"
                            class="question-nav-btn w-full aspect-square rounded-lg border font-semibold text-sm transition">

                        {{ $navIndex + 1 }}

                    </button>

                    @php $navIndex++; @endphp

                @endforeach

            </div>
        </div>

    @endforeach

</div>

</div>


    <!-- JAVASCRIPT -->
    <script>

        // =====================================
        // PENGATURAN DASAR
        // =====================================

        const formElement = document.getElementById('exam-form');

        const questionCards = document.querySelectorAll('.question-card');

        const navigationButtons = document.querySelectorAll('.question-nav-btn');

        const totalQuestions = questionCards.length;

        let currentQuestion = 0;

        let isSubmitting = false;

        let doubtfulQuestions = new Set();

        let timeInSeconds = {{ (int) $remainingSeconds }};

        let fiveMinuteWarningShown = false;


        // =====================================
        // ELEMEN HTML
        // =====================================

        const timerElement = document.getElementById('timer');

        const timerContainer = document.getElementById('timer-container');

        const prevButton = document.getElementById('prev-btn');

        const nextButton = document.getElementById('next-btn');

        const doubtfulCheckbox = document.getElementById('doubtful-checkbox');

        const navigationPopup = document.getElementById('navigation-popup');


        // =====================================
        // TAMPILKAN SOAL
        // =====================================

        function showQuestion(index) {

    if (index < 0 || index >= totalQuestions) {
        return;
    }

    currentQuestion = index;

    questionCards.forEach((card, i) => {
        card.classList.toggle('active', i === index);
    });

    // Update warna navigasi
    updateNavigationColors();

    // Tombol sebelumnya
    prevButton.disabled = index === 0;

    // Tombol selanjutnya / selesai
    if (index === totalQuestions - 1) {

        nextButton.textContent = 'Selesai';

        nextButton.classList.remove(
            'bg-blue-600',
            'hover:bg-blue-700'
        );

        nextButton.classList.add(
            'bg-green-600',
            'hover:bg-green-700'
        );

    } else {

        nextButton.textContent = 'Selanjutnya';

        nextButton.classList.remove(
            'bg-green-600',
            'hover:bg-green-700'
        );

        nextButton.classList.add(
            'bg-blue-600',
            'hover:bg-blue-700'
        );

    }

    // Update checkbox ragu-ragu
    const questionId = questionCards[index].dataset.id;

    doubtfulCheckbox.checked = doubtfulQuestions.has(questionId);

    // Tutup popup
    navigationPopup.classList.add('hidden');

    document.getElementById('questions-container').scrollIntoView({
    behavior: 'smooth',
    block: 'start'
});

}

// =====================================
// UPDATE WARNA NAVIGASI
// =====================================

function updateNavigationColors() {

    navigationButtons.forEach((button, index) => {

        const card = questionCards[index];

        const questionId = card.dataset.id;

        const isAnswered = card.querySelector(
            'input[type="radio"]:checked'
        ) !== null;

        const isDoubtful = doubtfulQuestions.has(questionId);

        // Reset seluruh warna
        button.className =
            'question-nav-btn w-full aspect-square rounded-lg border font-semibold text-sm transition';

        // Prioritas warna:
        // Ragu-ragu > Sudah dijawab > Soal aktif > Belum dijawab

        if (isDoubtful) {

            button.classList.add(
                'bg-yellow-400',
                'border-yellow-500',
                'text-yellow-950'
            );

        } else if (isAnswered) {

            button.classList.add(
                'bg-green-500',
                'border-green-600',
                'text-white'
            );

        } else if (index === currentQuestion) {

            button.classList.add(
                'bg-blue-600',
                'border-blue-600',
                'text-white'
            );

        } else {

            button.classList.add(
                'bg-gray-100',
                'border-gray-300',
                'text-gray-700'
            );

        }

        // Penanda soal aktif
        if (index === currentQuestion) {

            button.classList.add(
                'ring-2',
                'ring-blue-500',
                'ring-offset-2'
            );

        }

    });

}


        // =====================================
        // SOAL SEBELUMNYA
        // =====================================

        function previousQuestion() {

            if (currentQuestion > 0) {

                showQuestion(currentQuestion - 1);

            }

        }


        // =====================================
        // SOAL SELANJUTNYA
        // =====================================

        function nextQuestion() {

            // Jika belum soal terakhir
            if (currentQuestion < totalQuestions - 1) {

                showQuestion(currentQuestion + 1);

                return;

            }

            // Jika soal terakhir, proses penyelesaian
            confirmSubmit();

        }


        // =====================================
        // STATUS RAGU-RAGU
        // =====================================

        doubtfulCheckbox.addEventListener('change', function() {

    const questionId = questionCards[currentQuestion].dataset.id;

    if (this.checked) {

        doubtfulQuestions.add(questionId);

    } else {

        doubtfulQuestions.delete(questionId);

    }

    updateNavigationColors();

});

// =====================================
// DETEKSI JAWABAN SISWA
// =====================================

formElement.addEventListener('change', function(event) {

    if (event.target.matches('input[type="radio"]')) {

        updateNavigationColors();

    }

});


        // =====================================
        // NAVIGASI POPUP
        // =====================================

        function toggleNavigation() {

            navigationPopup.classList.toggle('hidden');

        }


        // =====================================
        // PINDAH SOAL MELALUI NAVIGASI
        // =====================================

        function goToQuestion(index) {

            showQuestion(index);

        }


        // =====================================
        // CEK SOAL RAGU-RAGU
        // =====================================

        function checkDoubtfulQuestions() {

            if (doubtfulQuestions.size > 0) {

                return false;

            }

            return true;

        }


        // =====================================
        // KONFIRMASI SELESAI
        // =====================================

        function confirmSubmit() {

    if (isSubmitting) {
        return;
    }

    // Cari soal yang belum dijawab
    const unansweredQuestions = [];

    // Cari soal ragu-ragu
    const doubtfulList = [];

    questionCards.forEach((card, index) => {

        const questionId = card.dataset.id;

        const answered = card.querySelector(
            'input[type="radio"]:checked'
        ) !== null;

        if (!answered) {

            unansweredQuestions.push(index);

        }

        if (doubtfulQuestions.has(questionId)) {

            doubtfulList.push(index);

        }

    });


    // PRIORITAS 1: SOAL RAGU-RAGU
    if (doubtfulList.length > 0) {

        const numbers = doubtfulList.map(i => i + 1);

        Swal.fire({

            title: 'Masih Ada Soal Ragu-ragu!',

            html: `
                <p class="text-gray-600 mb-3">
                    Anda belum bisa menyelesaikan ujian karena masih ada soal yang ditandai ragu-ragu.
                </p>

                <p class="font-bold text-yellow-600">
                    Nomor soal: ${numbers.join(', ')}
                </p>

                <p class="text-sm text-gray-500 mt-3">
                    Silakan periksa kembali dan hilangkan tanda ragu-ragu sebelum menyelesaikan ujian.
                </p>
            `,

            icon: 'warning',

            confirmButtonText: 'Periksa Sekarang',

            confirmButtonColor: '#2563eb'

        }).then(() => {

            showQuestion(doubtfulList[0]);

        });

        return;

    }


    // PRIORITAS 2: SOAL BELUM DIJAWAB
    if (unansweredQuestions.length > 0) {

        const numbers = unansweredQuestions.map(i => i + 1);

        Swal.fire({

            title: 'Masih Ada Soal Kosong!',

            html: `
                <p class="text-gray-600 mb-3">
                    Anda belum bisa menyelesaikan ujian karena masih terdapat soal yang belum dijawab.
                </p>

                <p class="font-bold text-red-600">
                    Nomor soal: ${numbers.join(', ')}
                </p>

                <p class="text-sm text-gray-500 mt-3">
                    Silakan jawab seluruh soal terlebih dahulu sebelum menyelesaikan ujian.
                </p>
            `,

            icon: 'warning',

            confirmButtonText: 'Jawab Sekarang',

            confirmButtonColor: '#2563eb'

        }).then(() => {

            showQuestion(unansweredQuestions[0]);

        });

        return;

    }


    // SEMUA SOAL SUDAH DIJAWAB DAN TIDAK ADA RAGU-RAGU
    Swal.fire({

        title: 'Selesaikan Ujian?',

        html: `
            <p class="text-gray-600">
                Seluruh soal sudah dijawab.
            </p>

            <p class="text-sm text-red-500 mt-3">
                Setelah jawaban dikirim, Anda tidak dapat mengerjakan kembali ujian ini.
            </p>
        `,

        icon: 'question',

        showCancelButton: true,

        confirmButtonColor: '#16a34a',

        cancelButtonColor: '#6b7280',

        confirmButtonText: 'Ya, Selesaikan',

        cancelButtonText: 'Periksa Kembali',

        reverseButtons: true

    }).then((result) => {

        if (result.isConfirmed) {

            submitExam();

        }

    });

}


        // =====================================
        // KIRIM JAWABAN
        // =====================================

        function submitExam() {

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;

            Swal.fire({

                title: 'Mengirim Jawaban...',

                text: 'Mohon tunggu, jawaban Anda sedang diproses.',

                allowOutsideClick: false,

                allowEscapeKey: false,

                showConfirmButton: false,

                didOpen: () => {

                    Swal.showLoading();

                }

            });

            formElement.submit();

        }


        // =====================================
        // FORMAT TIMER HH:MM:SS
        // =====================================

        function formatTime(seconds) {

            const hours = Math.floor(seconds / 3600);

            const minutes = Math.floor((seconds % 3600) / 60);

            const secs = seconds % 60;

            return [

                String(hours).padStart(2, '0'),

                String(minutes).padStart(2, '0'),

                String(secs).padStart(2, '0')

            ].join(':');

        }


        
// =====================================
// TIMER UJIAN
// =====================================

let timerInterval = null;

function updateTimer() {

    if (timeInSeconds <= 0) {

        timeInSeconds = 0;

        timerElement.textContent = '00:00:00';

        clearInterval(timerInterval);

        if (!isSubmitting) {

            isSubmitting = true;

            Swal.fire({
                title: 'Waktu Habis!',
                text: 'Waktu pengerjaan telah berakhir. Jawaban Anda akan dikirim secara otomatis.',
                icon: 'warning',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                timer: 3000
            }).then(() => {
                formElement.submit();
            });

        }

        return;
    }

    // Tampilkan waktu
    timerElement.textContent = formatTime(timeInSeconds);

    // Peringatan 5 menit terakhir
    if (timeInSeconds <= 300) {

        timerContainer.classList.add(
            'bg-yellow-100',
            'border-yellow-400'
        );

        timerContainer.classList.remove(
            'bg-red-50',
            'border-red-200'
        );

        if (!fiveMinuteWarningShown) {

            fiveMinuteWarningShown = true;

            Swal.fire({
                title: 'Perhatian!',
                html: `
                    <p>Waktu ujian tersisa:</p>
                    <h2 class="text-3xl font-bold text-red-600 mt-3">
                        05:00
                    </h2>
                    <p class="text-sm text-gray-500 mt-3">
                        Segera periksa dan selesaikan seluruh jawaban Anda.
                    </p>
                `,
                icon: 'warning',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2563eb'
            });

        }

    }

    // Kurangi waktu
    timeInSeconds--;

}

// =====================================
// JALANKAN TIMER
// =====================================

// Tampilkan waktu pertama kali
updateTimer();

// Jalankan hitung mundur setiap 1 detik
timerInterval = setInterval(updateTimer, 1000);



        // =====================================
// INISIALISASI
// =====================================

if (totalQuestions > 0) {

    showQuestion(0);

    updateNavigationColors();

} else {

    Swal.fire({

        title: 'Belum Ada Soal',

        text: 'Belum terdapat soal TPA yang tersedia.',

        icon: 'info'

    });

    nextButton.disabled = true;

}


        // =====================================
        // CEGAH NAVIGASI POPUP TERTINGGAL TERBUKA
        // =====================================

        document.addEventListener('click', function(event) {

    const toggleButtons = document.querySelectorAll('.nav-toggle');

    const clickedToggle = Array.from(toggleButtons).some(button =>
        button.contains(event.target)
    );

    if (
        !navigationPopup.contains(event.target) &&
        !clickedToggle
    ) {

        navigationPopup.classList.add('hidden');

    }

});


        // =====================================
        // CEGAH PENGIRIMAN FORM DARI ENTER
        // =====================================

        formElement.addEventListener('submit', function(event) {

            if (!isSubmitting) {

                event.preventDefault();

            }

        });

    </script>

</body>
</html>
