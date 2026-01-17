@extends('layouts.app')

@section('title', 'Scoring Algo 101 - SoVest')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #F3F4F6;
    }
    .chart-container {
        position: relative;
        width: 100%;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        height: 350px;
        max-height: 400px;
    }
    @media (min-width: 768px) {
        .chart-container {
            height: 400px;
        }
    }
</style>
@endpush

@section('content')
<div class="text-[#1A1A1A]">
    <div class="container mx-auto p-4 md:p-8 max-w-7xl">

        <header class="text-center mb-12 md:mb-16">
            <h1 class="text-4xl md:text-6xl font-black text-[#1A1A1A] mb-2">The SoVest Scoring Algorithm</h1>
            <p class="text-lg md:text-xl text-[#22C55E]">A guide to our skill-based scoring system.</p>
        </header>

        <section class="text-center mb-16">
            <h2 class="text-2xl font-bold mb-4">Your Score: More Than Just a Number</h2>
            <div class="flex justify-center items-center space-x-4">
                <div class="text-5xl font-black text-[#EF4444]">0</div>
                <div class="text-2xl font-bold text-[#22C55E] px-4">&harr;</div>
                <div class="text-5xl font-black text-[#22C55E]">1000</div>
            </div>
            <p class="mt-4 max-w-2xl mx-auto text-base">The SoVest score is our most advanced measure of prediction skill, designed to reward deep market insight, long-term consistency, and quality analysis.</p>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="md:col-span-2 bg-white rounded-lg shadow-lg p-6 md:p-8">
                <h2 class="text-3xl font-bold text-center mb-6">Part 1: Grading Your Prediction</h2>
                <p class="text-center max-w-3xl mx-auto mb-8">Each prediction is graded through a multi-stage process. We start with a base grade and then apply a series of multipliers based on the skill you demonstrated.</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <h3 class="text-2xl font-bold text-center mb-4">Core Grade: Accuracy & Bonuses</h3>
                <p class="text-center mb-6">First, we calculate a core grade based on your accuracy, the magnitude of the price change you predicted, and how far in advance you made the call. As before, we penalize under-predictions more heavily.</p>
                <div class="chart-container h-96 md:h-[450px]">
                    <canvas id="penaltyChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <h3 class="text-2xl font-bold text-center mb-4">The "Alpha" Multiplier</h3>
                <p class="text-center mb-6">A rising tide lifts all boats. The Alpha score measures your ability to outperform the market. Predicting a 15% gain is good, but it's brilliant if the rest of the market was flat.</p>
                <div class="chart-container h-96 md:h-[450px]">
                    <canvas id="alphaChart"></canvas>
                </div>
            </div>

            <div class="md:col-span-2 bg-white rounded-lg shadow-lg p-6 md:p-8">
                 <h2 class="text-3xl font-bold text-center mb-6">Part 2: Updating Your Overall Score</h2>
                 <p class="text-center max-w-3xl mx-auto mb-8">The final grade from your prediction is used to update your overall score. The Scoring Algorithm makes this process smarter and more mature.</p>
            </div>

             <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <h3 class="text-2xl font-bold text-center mb-4">Dynamic Learning Rate</h3>
                <p class="text-center mb-6">Your score's sensitivity changes with experience. New users' scores move quickly, allowing them to establish a reputation. Veteran users' scores are more stable, reflecting their long track record.</p>
                <div class="chart-container h-80 md:h-96">
                    <canvas id="learningRateChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                <h3 class="text-2xl font-bold text-center mb-4">The "Bell Curve" Factor</h3>
                <p class="text-center mb-6">This core feature remains. It becomes harder to change your score the further you are from the average (500). This ensures high scores are meaningful and prevents scores from dropping to zero on a single bad streak.</p>
                <div class="chart-container h-80 md:h-96">
                    <canvas id="bellCurveChart"></canvas>
                </div>
            </div>

            <div class="md:col-span-2 bg-white rounded-lg shadow-lg p-6 md:p-8">
                 <h2 class="text-3xl font-bold text-center mb-6">The Long Run: How Skill Stacks Up</h2>
                 <p class="text-center max-w-3xl mx-auto mb-8">This simulation visualizes the long-term score progression for three distinct investor profiles: The Pro, The Gambler, and The Market Follower. Over hundreds of predictions, the algorithm's mechanics distinguish sustained skill from high-risk strategies and passive market tracking.</p>
                 <div class="chart-container h-[450px] md:h-[500px] max-w-5xl">
                    <canvas id="simulationChart"></canvas>
                </div>
            </div>
        </div>

        <section class="mt-16">
            <h2 class="text-3xl font-bold text-center mb-8">Prediction Snapshots: The Story Behind the Scores</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-2xl font-bold mb-2 text-green-600">The Pro (High Alpha)</h3>
                    <p class="text-sm mb-6">Consistently seeks to outperform the market through research and identifying mispriced assets. Thesis quality is generally high.</p>
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-lg">Prediction #50</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> NVDA +15% in 90 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "Upcoming earnings report is being underestimated... data center revenue will significantly beat analyst consensus..." (4 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> NVDA +18%, Market +1%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Huge boost from accuracy and high alpha.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #100</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> PFE -10% in 6 months.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "The market is overvaluing their current pipeline. I believe a key drug in Phase III trials will fail..." (5 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> PFE -12%, Market +3%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Major increase from a correct contrarian prediction.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #200</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> DAL to remain flat in 30 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "Oil prices are rising... but strong summer travel demand will offset that impact, leading to a period of consolidation..." (5 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> DAL -1%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Positive gain from high accuracy and a great thesis.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-2xl font-bold mb-2 text-red-600">The Gambler (High Volatility)</h3>
                    <p class="text-sm mb-6">Makes high-risk, often binary bets on volatile stocks. Success is sporadic and driven by luck. Thesis quality is typically low.</p>
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-lg">Prediction #50</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> GME +150% in 14 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "We're going to the moon! Diamond hands!" (1 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> GME +200%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Massive lucky score increase, reduced by poor thesis.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #100</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> Small biotech +300% in 1 day.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "FDA approval is guaranteed." (1 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> Stock crashes 75%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Catastrophic score drop from a total miss.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #200</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> TSLA -40% in 60 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "This stock is a bubble and has to pop sometime." (2 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> TSLA +15%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Major loss from being wrong on all counts.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-2xl font-bold mb-2 text-blue-600">The Market Follower (Low Alpha)</h3>
                    <p class="text-sm mb-6">Makes safe, consensus predictions on large, stable companies. They rarely take risks and their performance closely tracks the market.</p>
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-lg">Prediction #50</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> SPY +2% in 90 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "The market generally trends upwards." (3 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> SPY +2.5%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Negligible score change due to zero alpha.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #100</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> AAPL +5% in 60 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "They have a new iPhone coming out which is usually good for the stock." (3 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> AAPL +6%, Market +6%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Tiny gain, again with no alpha generated.</p>
                        </div>
                        <hr>
                        <div>
                            <h4 class="font-bold text-lg">Prediction #200</h4>
                            <p class="text-sm mt-1"><strong>Prediction:</strong> MSFT +3% in 90 days.</p>
                            <p class="text-sm mt-1"><strong>Thesis:</strong> "Microsoft is a solid company and should continue its slow and steady growth." (4 ⭐)</p>
                            <p class="text-sm mt-1"><strong>Outcome:</strong> MSFT +4%, Market +3.5%.</p>
                            <p class="text-sm mt-1"><strong>Impact:</strong> Small positive impact from good thesis and tiny alpha.</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if dark mode is enabled
    const isDarkMode = document.body.classList.contains('dark-mode');

    const sovestPalette = {
        black: isDarkMode ? '#f3f4f6' : '#1A1A1A',
        green: '#22C55E',
        lightGreen: '#4ADE80',
        gray: isDarkMode ? '#6b7280' : '#E5E7EB',
        red: '#EF4444',
        blue: '#3B82F6'
    };

    const tooltipTitleCallback = function(tooltipItems) {
        const item = tooltipItems[0];
        let label = item.chart.data.labels[item.dataIndex];
        if (Array.isArray(label)) {
            return label.join(' ');
        } else {
            return label;
        }
    };

    const chartDefaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    font: { family: 'Inter', size: 14 },
                    color: sovestPalette.black
                }
            },
            tooltip: {
                callbacks: { title: tooltipTitleCallback },
                bodyFont: { family: 'Inter' },
                titleFont: { family: 'Inter' }
            }
        },
        scales: {
            y: {
                ticks: { color: sovestPalette.black, font: { family: 'Inter' } },
                grid: { color: sovestPalette.gray }
            },
            x: {
                ticks: { color: sovestPalette.black, font: { family: 'Inter' } },
                 grid: { display: false }
            }
        }
    };

    // --- CHART IMPLEMENTATIONS ---

    new Chart(document.getElementById('penaltyChart'), {
        type: 'line',
        data: {
            labels: ['0%', '5%', '10%', '15%', '20%', '25%'],
            datasets: [{
                label: 'Penalty for Under-Prediction',
                data: [0, 20, 45, 80, 120, 150],
                borderColor: sovestPalette.red,
                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                fill: true, tension: 0.4
            }, {
                label: 'Penalty for Over-Prediction',
                data: [0, 15, 38, 65, 100, 125],
                borderColor: sovestPalette.lightGreen,
                backgroundColor: 'rgba(74, 222, 128, 0.2)',
                fill: true, tension: 0.4
            }]
        },
        options: { ...chartDefaultOptions, scales: { y: { ...chartDefaultOptions.scales.y, title: { display: true, text: 'Points Lost'}}, x: { ...chartDefaultOptions.scales.x, title: { display: true, text: 'Prediction Inaccuracy'}}}}
    });

    new Chart(document.getElementById('alphaChart'), {
        type: 'bar',
        data: {
            labels: ['Good Prediction', 'Great Prediction'],
            datasets: [{
                label: "Your Stock's Gain",
                data: [15, 15],
                backgroundColor: sovestPalette.green,
            }, {
                label: "Market (S&P 500) Gain",
                data: [12, 2],
                backgroundColor: sovestPalette.gray,
            }]
        },
        options: { ...chartDefaultOptions, scales: { y: { ...chartDefaultOptions.scales.y, title: { display: true, text: 'Performance (%)'}}}}
    });

    new Chart(document.getElementById('learningRateChart'), {
        type: 'line',
        data: {
            labels: ['1', '20', '50', '100', '150', '200+'],
            datasets: [{
                label: 'Learning Rate',
                data: [30, 30, 26.3, 20.5, 14.8, 5],
                borderColor: sovestPalette.green,
                backgroundColor: 'rgba(34, 197, 94, 0.2)',
                fill: true, tension: 0.1
            }]
        },
        options: { ...chartDefaultOptions, scales: { y: { ...chartDefaultOptions.scales.y, title: { display: true, text: 'Rate (%)'}}, x: { ...chartDefaultOptions.scales.x, title: { display: true, text: 'Number of Predictions'}}}}
    });

    new Chart(document.getElementById('bellCurveChart'), {
        type: 'line',
        data: {
            labels: ['0', '100', '250', '500 (Avg)', '750', '900', '1000'],
            datasets: [{
                label: 'Impact of New Predictions',
                data: [0, 25, 75, 100, 75, 25, 0],
                borderColor: sovestPalette.green,
                backgroundColor: 'rgba(34, 197, 94, 0.2)',
                fill: true, tension: 0.4
            }]
        },
        options: { ...chartDefaultOptions, scales: { y: { ...chartDefaultOptions.scales.y, title: { display: true, text: 'Impact (%)'}}, x: { ...chartDefaultOptions.scales.x, title: { display: true, text: 'Your Overall Score'}}}}
    });

    // --- SIMULATION CHART ---
    const generateSimulationData = (start, points, volatility, drift, alphaDrift) => {
        const data = [start];
        for (let i = 1; i < points; i++) {
            const randomFactor = (Math.random() - 0.5) * volatility;
            const alphaFactor = Math.random() < 0.6 ? alphaDrift : -alphaDrift / 2;
            let nextPoint = data[i-1] + drift + randomFactor + alphaFactor;
            nextPoint = Math.max(250, Math.min(850, nextPoint));
            data.push(nextPoint);
        }
        return data;
    };

    const simLabels = Array.from({ length: 201 }, (_, i) => i);
    const proData = generateSimulationData(500, 201, 15, 1.2, 0.7);
    const gamblerData = generateSimulationData(500, 201, 60, -1.5, 0);
    const marketFollowerData = generateSimulationData(500, 201, 8, 0.0, -0.5);

    const simulationChart = new Chart(document.getElementById('simulationChart'), {
        type: 'line',
        data: {
            labels: simLabels,
            datasets: [{
                label: 'The Pro',
                data: proData,
                borderColor: sovestPalette.green,
                borderWidth: 3,
                pointRadius: 0,
                tension: 0.4
            }, {
                label: 'The Gambler',
                data: gamblerData,
                borderColor: sovestPalette.red,
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4
            }, {
                label: 'The Market Follower',
                data: marketFollowerData,
                borderColor: sovestPalette.blue,
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    bottom: 30
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 14 },
                        color: sovestPalette.black
                    }
                },
                tooltip: {
                    callbacks: { title: tooltipTitleCallback },
                    bodyFont: { family: 'Inter' },
                    titleFont: { family: 'Inter' }
                }
            },
            scales: {
                y: {
                    ticks: { color: sovestPalette.black, font: { family: 'Inter' } },
                    grid: { color: sovestPalette.gray },
                    title: { display: true, text: 'SoVest Score'}
                },
                x: {
                    ticks: { color: sovestPalette.black, font: { family: 'Inter' } },
                    grid: { display: false },
                    title: { display: true, text: 'Number of Predictions'}
                }
            }
        }
    });
    simulationChart.update();
});
</script>
@endpush
