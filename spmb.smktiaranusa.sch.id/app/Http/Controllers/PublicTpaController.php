<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\TpaQuestion;
use App\Models\TpaSetting;
use App\Models\TpaResult;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PublicTpaController extends Controller
{
    public function index()
    {
        $setting = TpaSetting::first();

        // Jika CBT ditutup, tampilkan halaman penutupan
        if (!$setting || !$setting->is_active) {
            return response()
                ->view('public.tpa.closed')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()
            ->view('public.tpa.login', compact('setting'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'spmb_id' => 'required',
            'birth_date' => 'required|date'
        ]);

        $registration = Registration::with('student', 'user')
            ->where('spmb_id', $request->spmb_id)
            ->first();

        if (
            !$registration ||
            !$registration->student ||
            $registration->student->birth_date != $request->birth_date
        ) {
            return back()->with(
                'error',
                'ID SPMB atau Tanggal Lahir tidak sesuai dengan data terdaftar.'
            );
        }

        // Cek apakah peserta sudah pernah menyelesaikan ujian
        $existing = TpaResult::where(
            'registration_id',
            $registration->id
        )->first();

        if ($existing) {
            return redirect()
                ->route('public.tpa.index')
                ->with('error', 'Anda sudah pernah mengikuti ujian CBT TPA. Anda tidak dapat mengerjakan ujian kembali.');
        }

        return redirect()->route(
            'public.tpa.exam',
            $registration->id
        );
    }

    public function examSession($id)
    {
        $setting = TpaSetting::first();

        if (!$setting || !$setting->is_active) {
            return redirect()
                ->route('public.tpa.index')
                ->with('error', 'Ujian CBT TPA sedang ditutup.');
        }

        $registration = Registration::with('student', 'user')
            ->findOrFail($id);

        // Cek apakah sudah pernah mengerjakan
        // Cegah peserta yang sudah mengerjakan ujian masuk kembali
        $existing = TpaResult::where(
            'registration_id',
            $registration->id
        )->first();

        if ($existing) {
            return redirect()
                ->route('public.tpa.index')
                ->with('error', 'Anda sudah pernah mengikuti ujian CBT TPA. Anda tidak dapat mengerjakan ujian kembali.');
        }

        // Urutan mata pelajaran
        $mapelOrder = [
            'Bahasa Indonesia',
            'Matematika',
            'Bahasa Inggris',
        ];

        // Ambil atau buat sesi ujian
        $examSession = DB::transaction(function () use ($id, $setting) {

            $session = DB::table('tpa_exam_sessions')
                ->where('registration_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$session) {

                // Ambil seluruh soal dan kelompokkan berdasarkan mapel
                $questionsByMapel = TpaQuestion::all()
                    ->groupBy(fn($question) => trim($question->mapel));

                $questionOrder = [];

                // Urutan mapel yang ditampilkan
                $mapelOrder = [
                    'Bahasa Indonesia',
                    'Matematika',
                    'Bahasa Inggris',
                ];

                foreach ($mapelOrder as $mapel) {

                    $mapelQuestions = $questionsByMapel
                        ->get($mapel, collect())
                        ->shuffle();

                    foreach ($mapelQuestions as $question) {
                        $questionOrder[] = $question->id;
                    }
                }

                // Jika nama mapel tidak sesuai daftar, tetap masukkan soalnya
                $knownMapelIds = collect($questionOrder);

                $otherQuestions = TpaQuestion::whereNotIn('id', $knownMapelIds)
                    ->get()
                    ->shuffle();

                foreach ($otherQuestions as $question) {
                    $questionOrder[] = $question->id;
                }

                // Jangan membuat sesi kosong
                if (empty($questionOrder)) {
                    return null;
                }

                $start = now();

                DB::table('tpa_exam_sessions')->insert([
                    'registration_id' => $id,
                    'started_at' => $start,
                    'ends_at' => $start->copy()->addMinutes(
                        $setting->duration_minutes
                    ),
                    'question_order' => json_encode($questionOrder),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $session = DB::table('tpa_exam_sessions')
                    ->where('registration_id', $id)
                    ->first();
            }

            return $session;
        });

        // Jika belum ada soal di bank soal
        if (!$examSession) {
            return redirect()
                ->route('public.tpa.index')
                ->with('error', 'Belum ada soal TPA yang tersedia. Silakan hubungi panitia.');
        }

        // Ambil urutan soal yang tersimpan
        $questionIds = json_decode($examSession->question_order, true) ?? [];

        $questionCollection = TpaQuestion::whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        // Susun kembali sesuai urutan sesi
        $questions = collect($questionIds)
            ->map(fn($questionId) => $questionCollection->get($questionId))
            ->filter()
            ->values();

        // Jika data sesi lama tidak valid
        if ($questions->isEmpty()) {
            return redirect()
                ->route('public.tpa.index')
                ->with('error', 'Data sesi ujian tidak valid. Silakan hubungi panitia untuk mengatur ulang sesi TPA.');
        }

        // Hitung sisa waktu berdasarkan database
        $remainingSeconds = max(
            0,
            now()->diffInSeconds(
                Carbon::parse($examSession->ends_at),
                false
            )
        );

        // Kelompokkan soal per mapel
        $questionSections = $questions->groupBy('mapel');

        return view('public.tpa.exam', compact(
            'registration',
            'questions',
            'questionSections',
            'remainingSeconds'
        ));
    }


    public function submitExam(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            // Kunci data pendaftaran selama proses submit
            $registration = Registration::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            // Cek apakah peserta sudah pernah menyelesaikan ujian
            $existing = TpaResult::where(
                'registration_id',
                $registration->id
            )->first();

            if ($existing) {
                // Pastikan checklist tetap tersinkron jika hasil sudah ada
                $registration->update([
                    'check_tpa' => 1,
                ]);

                return redirect()
                    ->route('public.tpa.index')
                    ->with('error', 'Anda sudah pernah menyelesaikan ujian TPA.');
            }

            $answers = $request->input('answers', []);

            $matematikaTotal = 0;
            $matematikaCorrect = 0;

            $indoTotal = 0;
            $indoCorrect = 0;

            $inggrisTotal = 0;
            $inggrisCorrect = 0;

            $questions = TpaQuestion::all();

            foreach ($questions as $q) {
                $userAns = $answers[$q->id] ?? null;

                if ($q->mapel == 'Matematika') {
                    $matematikaTotal++;

                    if ($userAns == $q->correct_answer) {
                        $matematikaCorrect++;
                    }
                } elseif ($q->mapel == 'Bahasa Indonesia') {
                    $indoTotal++;

                    if ($userAns == $q->correct_answer) {
                        $indoCorrect++;
                    }
                } elseif ($q->mapel == 'Bahasa Inggris') {
                    $inggrisTotal++;

                    if ($userAns == $q->correct_answer) {
                        $inggrisCorrect++;
                    }
                }
            }

            // Hitung nilai skala 100
            $scoreMat = $matematikaTotal > 0
                ? ($matematikaCorrect / $matematikaTotal) * 100
                : 0;

            $scoreIndo = $indoTotal > 0
                ? ($indoCorrect / $indoTotal) * 100
                : 0;

            $scoreIng = $inggrisTotal > 0
                ? ($inggrisCorrect / $inggrisTotal) * 100
                : 0;

            $totalScore = (
                $scoreMat +
                $scoreIndo +
                $scoreIng
            ) / 3;

            // Simpan hasil ujian
            TpaResult::create([
                'registration_id' => $registration->id,
                'score_matematika' => round($scoreMat, 2),
                'score_b_indonesia' => round($scoreIndo, 2),
                'score_b_inggris' => round($scoreIng, 2),
                'total_score' => round($totalScore, 2),
                'status_kelulusan_tpa' => $totalScore >= 65
                    ? 'Lulus TPA'
                    : 'Perlu Remedial',
                'submitted_at' => now(),
            ]);

            // Otomatis tandai TPA selesai di tabel registrations
            $registration->update([
                'check_tpa' => 1,
            ]);

            return redirect()
                ->route('public.tpa.index')
                ->with(
                    'success',
                    'Ujian berhasil dikirim! Nilai Anda telah tercatat.'
                );
        });
    }
}
