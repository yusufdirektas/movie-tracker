@extends('layouts.app')

@section('title', 'İstatistikler & Analiz')

@section('header')
    <div class="flex items-center gap-3">
        <div class="bg-indigo-500/10 p-2.5 rounded-xl border border-indigo-500/20">
            <i class="fas fa-chart-pie text-indigo-400 text-xl"></i>
        </div>
        <div>
            <h2 class="font-black flex items-center gap-3 text-2xl text-white leading-tight tracking-tight">
                {{ __('İstatistikler & Analiz') }}
            </h2>
            <p class="text-sm text-slate-400 font-medium mt-1">İzleme alışkanlıklarının detaylı analizi.</p>
        </div>
    </div>
@endsection

@section('content')

    <div class="py-12 bg-slate-950 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (!isset($hasData) || !$hasData)
                <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
                    <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <i class="fas fa-chart-bar text-slate-500 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-white mb-2">Henüz İstatistik Yok</h3>
                    <p class="text-slate-400 mb-8 max-w-sm mx-auto">İstatistiklerin oluşması için önce birkaç film izlemen gerekiyor.</p>
                    <a href="{{ route('movies.index') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3.5 rounded-2xl text-sm font-black transition-all shadow-lg shadow-indigo-600/20 inline-flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Film Arşivime Dön
                    </a>
                </div>
            @else

                {{-- ÖZET KARTLARI --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Toplam İzlenen -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-film text-9xl text-indigo-500 -mt-8 -mr-8"></i>
                        </div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Toplam İzlenen</h3>
                        <div class="flex items-end gap-2">
                            <span class="text-4xl font-black text-white">{{ $stats['totalWatched'] }}</span>
                            <span class="text-slate-500 font-medium mb-1">film</span>
                        </div>
                    </div>

                    <!-- Toplam Süre -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-clock text-9xl text-indigo-500 -mt-8 -mr-8"></i>
                        </div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Toplam Süre</h3>
                        <div class="flex items-end gap-2">
                            <span class="text-4xl font-black text-white">{{ $stats['totalHours'] }}</span>
                            <span class="text-slate-500 font-medium mb-1">saat</span>
                            @if($stats['remainingMinutes'] > 0)
                                <span class="text-2xl font-black text-white ml-1">{{ $stats['remainingMinutes'] }}</span>
                                <span class="text-slate-500 font-medium mb-1">dk</span>
                            @endif
                        </div>
                    </div>

                    <!-- Ortalama Puan -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fas fa-star text-9xl text-yellow-500 -mt-8 -mr-8"></i>
                        </div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Ort. TMDB Puanı</h3>
                        <div class="flex items-end gap-2">
                            <span class="text-4xl font-black text-yellow-400">{{ number_format($stats['averageRating'], 1) }}</span>
                            <span class="text-slate-500 font-medium mb-1">/ 10</span>
                        </div>
                    </div>

                    <!-- Kişisel Puan -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fas fa-heart text-9xl text-rose-500 -mt-8 -mr-8"></i>
                        </div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Ort. Kişisel Puanın</h3>
                        <div class="flex items-end gap-2">
                            @if($stats['averagePersonalRating'])
                                <span class="text-4xl font-black text-rose-400">{{ number_format($stats['averagePersonalRating'], 1) }}</span>
                                <span class="text-slate-500 font-medium mb-1">/ 5</span>
                            @else
                                <span class="text-xl font-bold text-slate-600 mt-2">Puan Yok</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- GRAFİKLER BÖLÜMÜ --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <!-- Tür Dağılımı (Pie Chart) -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-masks-theater text-indigo-400"></i> Favori Türlerin
                        </h3>
                        @if(empty($chartData['genres']['data']))
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-ghost text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">Filmlerinde henüz tür verisi bulunamadı.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="genreChart"></canvas>
                            </div>
                        @endif
                    </div>

                    <!-- Aylık İzleme (Bar Chart) -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-indigo-400"></i> Aylık İzleme Geçmişi
                        </h3>
                        @if(empty($chartData['monthly']['data']))
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-calendar-times text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">İzleme tarihi verisi bulunmuyor.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        @endif
                    </div>

                    <!-- Yıllara Göre Dağılım (Line Chart) -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-history text-indigo-400"></i> Filmlerin Çıkış Yılları
                        </h3>
                        @if(empty($chartData['years']['data']))
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-calendar-minus text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">Yayın yılı verisi bulunmuyor.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="yearsChart"></canvas>
                            </div>
                        @endif
                    </div>

                    <!-- Yönetmenler (Horizontal Bar) -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-bullhorn text-indigo-400"></i> En Çok İzlediğin Yönetmenler
                        </h3>
                        @if(empty($chartData['directors']['data']))
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-user-slash text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">Yönetmen verisi henüz bulunmuyor.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="directorsChart"></canvas>
                            </div>
                        @endif
                    </div>

                    {{-- 📚 YENİ: Haftalık İzleme Dağılımı (Radar Chart) --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-calendar-week text-indigo-400"></i> Haftalık İzleme Alışkanlığın
                        </h3>
                        @if(empty($chartData['weekdays']['data']) || array_sum($chartData['weekdays']['data']) === 0)
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-calendar-times text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">Haftalık izleme verisi bulunmuyor.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="weekdayChart"></canvas>
                            </div>
                        @endif
                    </div>

                    {{-- 📚 YENİ: Puan Dağılımı (Histogram) --}}
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-bar text-indigo-400"></i> TMDB Puan Dağılımı
                        </h3>
                        @if(empty($chartData['ratings']['data']))
                            <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <i class="fas fa-star-half-alt text-4xl mb-3 opacity-20"></i>
                                <p class="text-sm text-center px-4">Puan verisi bulunmuyor.</p>
                            </div>
                        @else
                            <div class="relative h-64 w-full">
                                <canvas id="ratingsChart"></canvas>
                            </div>
                        @endif
                    </div>

                </div>

            @endif
        </div>
    </div>

@endsection

@push('scripts')
    @if (isset($hasData) && $hasData)
        <!-- Chart.js Kütüphanesi -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Laravel'den gelen verileri JavaScript objesine dönüştürüyoruz
                const chartData = @json($chartData);

                // Ortak renk paleti
                const colors = [
                    'rgba(99, 102, 241, 0.8)',   // Indigo 500
                    'rgba(168, 85, 247, 0.8)',   // Purple 500
                    'rgba(236, 72, 153, 0.8)',   // Pink 500
                    'rgba(244, 63, 94, 0.8)',    // Rose 500
                    'rgba(249, 115, 22, 0.8)',   // Orange 500
                    'rgba(234, 179, 8, 0.8)',    // Yellow 500
                    'rgba(34, 197, 94, 0.8)',    // Green 500
                    'rgba(20, 184, 166, 0.8)'    // Teal 500
                ];

                // Chart.js global ayarları (Koyu tema uyumlu)
                Chart.defaults.color = '#94a3b8'; // text-slate-400
                Chart.defaults.font.family = "'Inter', sans-serif";
                Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)'; // bg-slate-900
                Chart.defaults.plugins.tooltip.titleColor = '#fff';
                Chart.defaults.plugins.tooltip.padding = 10;
                Chart.defaults.plugins.tooltip.cornerRadius = 8;
                Chart.defaults.plugins.tooltip.borderColor = 'rgba(51, 65, 85, 1)'; // border-slate-700
                Chart.defaults.plugins.tooltip.borderWidth = 1;

                // 1. Tür Dağılımı (Doughnut Chart)
                new Chart(document.getElementById('genreChart'), {
                    type: 'doughnut',
                    data: {
                        labels: chartData.genres.labels,
                        datasets: [{
                            data: chartData.genres.data,
                            backgroundColor: colors,
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right' }
                        },
                        cutout: '70%' // Ortadaki boşluk
                    }
                });

                // 2. Aylık İzleme (Bar Chart)
                new Chart(document.getElementById('monthlyChart'), {
                    type: 'bar',
                    data: {
                        labels: chartData.monthly.labels,
                        datasets: [{
                            label: 'İzlenen Film',
                            data: chartData.monthly.data,
                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                            borderRadius: 6 // Çubukların köşelerini yuvarlat
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }, // Sadece tam sayılar
                                grid: { color: 'rgba(51, 65, 85, 0.5)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // 3. Yıllara Göre Dağılım (Line Chart)
                new Chart(document.getElementById('yearsChart'), {
                    type: 'line',
                    data: {
                        labels: chartData.years.labels,
                        datasets: [{
                            label: 'Film Sayısı',
                            data: chartData.years.data,
                            borderColor: 'rgba(236, 72, 153, 1)', // Pink 500
                            backgroundColor: 'rgba(236, 72, 153, 0.1)',
                            borderWidth: 3,
                            tension: 0.4, // Çizgiyi yumuşat (eğriler)
                            fill: true, // Altını doldur
                            pointBackgroundColor: 'rgba(236, 72, 153, 1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 },
                                grid: { color: 'rgba(51, 65, 85, 0.5)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // 4. Yönetmenler (Horizontal Bar Chart)
                new Chart(document.getElementById('directorsChart'), {
                    type: 'bar',
                    data: {
                        labels: chartData.directors.labels,
                        datasets: [{
                            label: 'Film Sayısı',
                            data: chartData.directors.data,
                            backgroundColor: 'rgba(20, 184, 166, 0.8)', // Teal 500
                            borderRadius: 6
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Yatay bar chart
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 },
                                grid: { color: 'rgba(51, 65, 85, 0.5)' }
                            },
                            y: { grid: { display: false } }
                        }
                    }
                });

                // 📚 5. Haftalık İzleme (Radar Chart)
                // Radar chart hangi günlerde daha aktif olduğunu gösterir
                if (document.getElementById('weekdayChart')) {
                    new Chart(document.getElementById('weekdayChart'), {
                        type: 'radar',
                        data: {
                            labels: chartData.weekdays.labels,
                            datasets: [{
                                label: 'Film Sayısı',
                                data: chartData.weekdays.data,
                                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                                borderColor: 'rgba(99, 102, 241, 1)',
                                borderWidth: 2,
                                pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgba(99, 102, 241, 1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1, color: '#94a3b8' },
                                    grid: { color: 'rgba(51, 65, 85, 0.5)' },
                                    pointLabels: { color: '#94a3b8', font: { size: 11 } }
                                }
                            }
                        }
                    });
                }

                // 📚 6. Puan Dağılımı (Bar Chart - Histogram tarzı)
                if (document.getElementById('ratingsChart')) {
                    new Chart(document.getElementById('ratingsChart'), {
                        type: 'bar',
                        data: {
                            labels: chartData.ratings.labels,
                            datasets: [{
                                label: 'Film Sayısı',
                                data: chartData.ratings.data,
                                backgroundColor: 'rgba(234, 179, 8, 0.8)', // Yellow 500
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 },
                                    grid: { color: 'rgba(51, 65, 85, 0.5)' }
                                },
                                x: {
                                    grid: { display: false },
                                    title: { display: true, text: 'TMDB Puanı', color: '#94a3b8' }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
@endpush
