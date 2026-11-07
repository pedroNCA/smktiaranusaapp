@extends('layouts.admin')
@section('title', 'Rekapitulasi Pendaftar')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Rekapitulasi Pendaftar</h1>
    <p class="text-gray-500 text-sm mt-1">Grafik visualisasi data pendaftar SPMB beserta persentasenya.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <!-- 1. Pendaftar per Jurusan -->
    <div class="bg-white border rounded-xl shadow-sm p-4 flex flex-col">
        <h3 class="font-bold text-gray-700 mb-4 text-center">Pendaftar per Jurusan</h3>
        <div class="relative h-56 w-full mb-6">
            <canvas id="chartJurusan"></canvas>
        </div>
        <div class="overflow-x-auto mt-auto">
            <table class="w-full text-sm text-left border border-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr><th class="px-4 py-2 border-b">Jurusan</th><th class="px-4 py-2 border-b text-center">Jumlah</th><th class="px-4 py-2 border-b text-center">Persentase</th></tr>
                </thead>
                <tbody>
                    @php $totJurusan = collect($jurusan)->sum(); @endphp
                    @forelse($jurusan as $nama => $jml)
                    <tr class="border-b">
                        <td class="px-4 py-2 font-medium">{{ $nama ?: 'Belum Memilih' }}</td>
                        <td class="px-4 py-2 text-center">{{ $jml }}</td>
                        <td class="px-4 py-2 text-center">{{ $totJurusan > 0 ? number_format(($jml / $totJurusan) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-2 text-center text-gray-500">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr><td class="px-4 py-2 text-right">Total:</td><td class="px-4 py-2 text-center">{{ $totJurusan }}</td><td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 2. Jalur Pendaftaran -->
    <div class="bg-white border rounded-xl shadow-sm p-4 flex flex-col">
        <h3 class="font-bold text-gray-700 mb-4 text-center">Jalur Pendaftaran</h3>
        <div class="relative h-56 w-full mb-6">
            <canvas id="chartJalur"></canvas>
        </div>
        <div class="overflow-x-auto mt-auto">
            <table class="w-full text-sm text-left border border-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr><th class="px-4 py-2 border-b">Jalur</th><th class="px-4 py-2 border-b text-center">Jumlah</th><th class="px-4 py-2 border-b text-center">Persentase</th></tr>
                </thead>
                <tbody>
                    @php $totJalur = collect($jalur)->sum(); @endphp
                    @forelse($jalur as $nama => $jml)
                    <tr class="border-b">
                        <td class="px-4 py-2 font-medium">{{ $nama ?: 'Belum Mengisi' }}</td>
                        <td class="px-4 py-2 text-center">{{ $jml }}</td>
                        <td class="px-4 py-2 text-center">{{ $totJalur > 0 ? number_format(($jml / $totJalur) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-2 text-center text-gray-500">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr><td class="px-4 py-2 text-right">Total:</td><td class="px-4 py-2 text-center">{{ $totJalur }}</td><td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 3. Gelombang Pendaftaran -->
    <div class="bg-white border rounded-xl shadow-sm p-4 flex flex-col">
        <h3 class="font-bold text-gray-700 mb-4 text-center">Gelombang Pendaftaran</h3>
        <div class="relative h-56 w-full mb-6">
            <canvas id="chartGelombang"></canvas>
        </div>
        <div class="overflow-x-auto mt-auto">
            <table class="w-full text-sm text-left border border-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr><th class="px-4 py-2 border-b">Gelombang</th><th class="px-4 py-2 border-b text-center">Jumlah</th><th class="px-4 py-2 border-b text-center">Persentase</th></tr>
                </thead>
                <tbody>
                    @php $totGelombang = isset($gelombang) ? collect($gelombang)->sum() : 0; @endphp
                    @if(isset($gelombang))
                        @forelse($gelombang as $nama => $jml)
                        <tr class="border-b">
                            <td class="px-4 py-2 font-medium">{{ $nama }}</td>
                            <td class="px-4 py-2 text-center">{{ $jml }}</td>
                            <td class="px-4 py-2 text-center">{{ $totGelombang > 0 ? number_format(($jml / $totGelombang) * 100, 1) : 0 }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-2 text-center text-gray-500">Tidak ada data</td></tr>
                        @endforelse
                    @endif
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr><td class="px-4 py-2 text-right">Total:</td><td class="px-4 py-2 text-center">{{ $totGelombang }}</td><td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 4. Top 10 Asal Sekolah -->
    <div class="bg-white border rounded-xl shadow-sm p-4 flex flex-col">
        <h3 class="font-bold text-gray-700 mb-4 text-center">Top 10 Asal Sekolah</h3>
        <div class="relative h-56 w-full mb-6">
            <canvas id="chartSekolah"></canvas>
        </div>
        <div class="overflow-x-auto mt-auto">
            <table class="w-full text-sm text-left border border-gray-200">
                <thead class="bg-gray-50 text-gray-700">
                    <tr><th class="px-4 py-2 border-b">Nama Sekolah</th><th class="px-4 py-2 border-b text-center">Jumlah Siswa</th></tr>
                </thead>
                <tbody>
                    @forelse($sekolah as $nama => $jml)
                    <tr class="border-b">
                        <td class="px-4 py-2 font-medium">{{ $nama }}</td>
                        <td class="px-4 py-2 text-center">{{ $jml }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-4 py-2 text-center text-gray-500">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Tambahkan Plugin DataLabels untuk persentase -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
    // Registrasi plugin secara global
    Chart.register(ChartDataLabels);

    const jurusanData = @json($jurusan);
    const sekolahData = @json($sekolah);
    const jalurData = @json($jalur);
    const gelombangData = @json($gelombang ?? []);

    // Konfigurasi umum untuk label persentase (Pie & Doughnut)
    const getPercentConfig = (color = '#fff') => ({
        maintainAspectRatio: false,
        plugins: {
            datalabels: {
                color: color,
                font: { weight: 'bold', size: 12 },
                formatter: (value, ctx) => {
                    let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    if (sum === 0) return '0%';
                    return (value * 100 / sum).toFixed(1) + "%"; // 1 Angka desimal
                }
            },
            legend: { position: 'bottom' }
        }
    });

    // 1. Pie Chart Jurusan
    new Chart(document.getElementById('chartJurusan'), {
        type: 'pie',
        data: {
            labels: Object.keys(jurusanData),
            datasets: [{ data: Object.values(jurusanData), backgroundColor: ['#3b82f6', '#ec4899', '#9ca3af'] }]
        },
        options: getPercentConfig()
    });

    // 2. Doughnut Chart Jalur
    new Chart(document.getElementById('chartJalur'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(jalurData),
            datasets: [{ data: Object.values(jalurData), backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6', '#ef4444'] }]
        },
        options: getPercentConfig()
    });

    // 3. Doughnut/Pie Chart Gelombang
    new Chart(document.getElementById('chartGelombang'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(gelombangData),
            datasets: [{ data: Object.values(gelombangData), backgroundColor: ['#6366f1', '#14b8a6', '#f43f5e', '#eab308'] }]
        },
        options: getPercentConfig()
    });

    // 4. Bar Chart Asal Sekolah (Persentase dimatikan karena datanya top 10, difokuskan ke jumlah)
    new Chart(document.getElementById('chartSekolah'), {
        type: 'bar',
        data: {
            labels: Object.keys(sekolahData),
            datasets: [{ label: 'Jumlah Pendaftar', data: Object.values(sekolahData), backgroundColor: '#8b5cf6' }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold' },
                    anchor: 'end',
                    align: 'bottom',
                    formatter: Math.round // Tampilkan angka asli
                },
                legend: { display: false }
            },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
</script>
@endsection