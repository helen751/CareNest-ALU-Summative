<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareNest - System Impact Analytics</title>
  <!-- favicon -->
  <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Chart.js for real-time visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              DEFAULT: '#0f5c5e',
              dark: '#0a3e40',
              soft: '#ddefea',
              accent: '#f3b7a4',
              accentDark: '#df9e88',
              bg: '#fafaf7',
              card: '#ffffff'
            }
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          }
        }
      }
    }
  </script>

  <style>
    body {
      background-color: #fafaf7;
      color: #243333;
      font-family: 'Inter', sans-serif;
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(226, 226, 223, 0.8);
    }
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #c0ded4;
      border-radius: 999px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: transparent;
    }
    .impact-page .text-xs {
      font-size: 0.85rem !important;
      line-height: 1.3rem !important;
    }
    .impact-page .text-sm {
      font-size: 1rem !important;
      line-height: 1.5rem !important;
    }
    .impact-page .text-base {
      font-size: 1.1rem !important;
      line-height: 1.6rem !important;
    }
    .impact-page .text-xl {
      font-size: 1.4rem !important;
    }
    .impact-page .text-2xl {
      font-size: 1.7rem !important;
    }
    .impact-page .text-3xl {
      font-size: 2.2rem !important;
    }
    .impact-page [class~="text-[10px]"] {
      font-size: 0.72rem !important;
    }
    .impact-page [class~="text-[11px]"] {
      font-size: 0.78rem !important;
    }
  </style>
</head>
<body class="impact-page min-h-screen flex flex-col antialiased selection:bg-brand-soft selection:text-brand">

  <!-- Top Navigation Header -->
  <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-200/80 shadow-sm transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col md:flex-row items-center justify-between gap-4">
      
      <!-- Brand & Title -->
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-full bg-brand flex items-center justify-center text-white shadow-md shadow-brand/20 p-1">
          <img src="../assets/images/logo.png" alt="CareNest" class="w-full h-full object-cover rounded-full">
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-extrabold text-brand tracking-tight">CareNest</h1>
           
          </div>
        </div>
      </div>

      <!-- Quick Action Controls -->
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
        
        <!-- Region Filter -->
        <div class="relative min-w-[140px]">
          <select id="countryFilter" onchange="refreshImpactAnalytics(true)" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold rounded-xl px-3 py-2 pr-8 appearance-none focus:outline-none focus:ring-2 focus:ring-brand/30 cursor-pointer">
            <option value="all">🌍 All Regions</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
        </div>

        <!-- Time Range Selector -->
        <div class="relative min-w-[130px]">
          <select id="timeFilter" onchange="refreshImpactAnalytics(true)" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold rounded-xl px-3 py-2 pr-8 appearance-none focus:outline-none focus:ring-2 focus:ring-brand/30 cursor-pointer">
            <option value="all">All Time</option>
            <option value="year">Past 12 Months</option>
            <option value="quarter">Past Quarter</option>
            <option value="month">Past 30 Days</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
        </div>

        <!-- Return to App Button -->
        <a href="../index" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-brand bg-brand-soft hover:bg-brand-soft/80 border border-brand/20 rounded-xl transition-all shadow-sm">
          <i class="fa-solid fa-arrow-left text-xs"></i>
          <span>Back to Home</span>
        </a>
      </div>

    </div>
  </header>

  <!-- Main Content Container -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
    
    <!-- Headline Banner & Live Sync Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">CareNest Impact Dashboard</h2>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Telemetry
          </span>
        </div>
        <p class="text-sm text-gray-500">Real-time aggregation of the  platform including pediatric demographics, health evaluations, severity classifications, and parent feedback ratings.</p>
      </div>
      <div class="text-xs text-gray-400 font-medium sm:text-right">
        <span>Last Synced:</span>
        <strong id="lastRefreshTime" class="text-gray-700 block text-xs">Just now</strong>
      </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      
      <!-- Card 1: Guardians Registered -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-brand shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Registered Guardians</span>
          <div class="w-8 h-8 rounded-xl bg-brand-soft flex items-center justify-center text-brand group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-user-shield"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiGuardians">0</span>
          <span class="text-xs font-bold text-emerald-600 flex items-center gap-0.5" id="guardianTrend">
            <i class="fa-solid fa-minus"></i> 0%
          </span>
        </div>
        <p class="text-xs text-gray-400 mt-2 font-medium">Registered guardian accounts</p>
      </div>

      <!-- Card 2: Children Monitored -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-brand-accent shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Children Profiles Monitored</span>
          <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-children"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiChildren">0</span>
          <span class="text-xs font-semibold text-gray-500">All Born & expected</span>
        </div>
        <p class="text-xs text-gray-400 mt-2 font-medium" id="childrenPerGuardian">Avg 0 profiles per guardian</p>
      </div>

      <!-- Card 3: Born Children -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-sky-500 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Born Children</span>
          <div class="w-8 h-8 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-baby"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiBornChildren">0</span>
          <span class="text-xs font-semibold text-gray-500">DOB reached</span>
        </div>
        <p class="text-xs text-gray-400 mt-2 font-medium">Registered profiles born on or before today</p>
      </div>

      <!-- Card 4: Expected Pregnancy Profiles -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-pink-400 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Expected Profiles</span>
          <div class="w-8 h-8 rounded-xl bg-pink-50 flex items-center justify-center text-pink-500 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-person-pregnant"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiExpectedProfiles">0</span>
          <span class="text-xs font-semibold text-gray-500">Pregnancy</span>
        </div>
        <p class="text-xs text-gray-400 mt-2 font-medium">Profiles with an expected delivery date after today</p>
      </div>

      <!-- Card 5: Total Assessments -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-teal-600 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Total Evaluations</span>
          <div class="w-8 h-8 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-stethoscope"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiAssessments">0</span>
          <span class="text-xs font-bold text-teal-600">Smart Checks</span>
        </div>
        <p class="text-xs text-gray-400 mt-2 font-medium">Completed symptom assessments</p>
      </div>

      <!-- Card 6: Parent Satisfaction -->
      <div class="glass-card p-6 min-h-[180px] rounded-2xl border-l-4 border-l-emerald-500 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center justify-between text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">
          <span>Parent Satisfaction</span>
          <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-star"></i>
          </div>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900" id="kpiSatisfaction">0.00</span>
          <span class="text-xs text-gray-400">/ 5.0</span>
        </div>
        <div class="flex items-center gap-1 text-amber-400 text-xs mt-2">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star-half-stroke"></i>
          <span class="text-xs font-semibold text-gray-500 ms-1" id="positiveFeedback">(0% positive)</span>
        </div>
      </div>

    </div>

    <!-- Row 1: Demographics Chart & Geographic Table -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Age Demographics Bar Chart-->
      <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <i class="fa-solid fa-chart-bar text-brand"></i>
              <span>Children Distribution by Age (0 - 12 Years)</span>
            </h3>
            <p class="text-xs text-gray-500">Volume breakdown across pediatric age milestones.</p>
          </div>
          <span class="px-2.5 py-1 text-[11px] font-semibold bg-gray-100 text-gray-600 rounded-lg">13 Age Categories</span>
        </div>

        <div class="relative w-full h-[300px]">
          <canvas id="ageDistributionChart"></canvas>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-brand inline-block"></span> Ages 0 to 5 (<span id="underSixShare">0%</span> total)</span>
          <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-brand/50 inline-block"></span> Ages 6 to 12 (<span id="olderChildrenShare">0%</span> total)</span>
        </div>
      </div>

      <!-- Regional Operational Footprint (5 Cols) -->
      <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <i class="fa-solid fa-globe text-brand"></i>
              <span>Country Operating Footprint</span>
            </h3>
            <p class="text-xs text-gray-500">Guardian registration density by geographic region.</p>
          </div>
          <button onclick="refreshImpactAnalytics(true)" class="text-xs text-brand font-semibold hover:underline">
            <i class="fa-solid fa-rotate-right me-1"></i>Sync
          </button>
        </div>

        <!-- Country Table -->
        <div class="overflow-x-auto custom-scrollbar flex-1">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">
                <th class="py-2.5">Territory</th>
                <th class="py-2.5 text-center">Guardians</th>
                <th class="py-2.5 text-right">Share %</th>
              </tr>
            </thead>
            <tbody id="countryTableBody" class="divide-y divide-gray-50 text-xs font-medium">
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="py-3 text-gray-400" colspan="3">Loading live country data…</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 text-center">
          <span class="text-[11px] text-gray-400" id="countryCoverage">Coverage data loading…</span>
        </div>
      </div>

    </div>

    <!-- Row 2: Clinical Severity Breakdown & Post-Assessment Ratings -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Severity Doughnut Chart (5 Cols) -->
      <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
        <div class="mb-2">
          <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <i class="fa-solid fa-pie-chart text-brand"></i>
            <span>Evaluated Risk Severity Index</span>
          </h3>
          <p class="text-xs text-gray-500">Proportionate breakdown of clinical outcome flags.</p>
        </div>

        <div class="relative w-full h-[250px] my-2 flex items-center justify-center">
          <canvas id="severityPieChart"></canvas>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center text-xs mt-2 pt-3 border-t border-gray-100">
          <div class="p-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100">
            <div class="font-bold text-sm" id="lowSeverityShare">0%</div>
            <div class="text-[10px] font-semibold text-emerald-600">Low Risk</div>
          </div>
          <div class="p-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-100">
            <div class="font-bold text-sm" id="mediumSeverityShare">0%</div>
            <div class="text-[10px] font-semibold text-amber-600">Medium Risk</div>
          </div>
          <div class="p-2 rounded-xl bg-red-50 text-red-800 border border-red-100">
            <div class="font-bold text-sm" id="highSeverityShare">0%</div>
            <div class="text-[10px] font-semibold text-red-600">High Risk</div>
          </div>
        </div>
      </div>

      <!-- Parent Feedback Satisfaction Breakdown Chart (7 Cols) -->
      <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-2">
          <div>
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <i class="fa-solid fa-star-half-stroke text-brand"></i>
              <span>Guardian Post-Assessment Rating Breakdown</span>
            </h3>
            <p class="text-xs text-gray-500">Distribution of ratings submitted by parents following symptom assessments.</p>
          </div>
          <span class="px-2.5 py-1 text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 rounded-lg">
            <i class="fa-solid fa-star text-amber-400 me-1"></i><span id="ratingAverageBadge">0.00 / 5 Avg Rating</span>
          </span>
        </div>

        <div class="relative w-full h-[250px] my-2">
          <canvas id="satisfactionRatingsChart"></canvas>
        </div>

        <div class="mt-2 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span>Total Ratings Evaluated: <strong class="text-gray-800" id="ratingResponseTotal">0 responses</strong></span>
          <span class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check me-1"></i><span id="ratingPositiveTotal">0% Positive Feedback (4 & 5 Stars)</span></span>
        </div>
      </div>

    </div>

  </main>

  <!-- Notification Toast Container -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none">
    <div class="bg-gray-900 text-white px-4 py-3 rounded-xl shadow-xl text-xs font-semibold flex items-center gap-2 border border-gray-700">
      <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
      <span id="toastMessage">Action completed successfully.</span>
    </div>
  </div>

  <!-- Footer -->
  <footer class="mt-12 py-10 text-white bg-brand" id="publicFooter">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
      <div class="text-center md:text-left">
        <div class="flex items-center justify-center md:justify-start gap-2 mb-2">
          <div class="w-11 h-11 rounded-xl overflow-hidden flex items-center justify-center p-1 bg-white">
            <img src="../assets/images/logo.png" alt="CareNest Logo" class="w-full h-full object-contain" onerror="this.style.display='none';">
          </div>
          <span class="font-extrabold text-white text-xl">CareNest</span>
        </div>
        <p class="text-white/60 mb-0 text-xs">Clear & Reassuring Guidance for Parents and Guardians.</p>
      </div>
      <div class="text-center md:text-right">
        <p class="text-white/60 mb-1 text-xs">&copy; 2026 CareNest. All rights reserved. Your trusted parenting partner.</p>
        <span class="text-white/60 text-[10px]">Developed with strict compliance to pediatric guidelines.</span>
      </div>
    </div>
  </footer>

  <script>
    // Global Chart Variables
    let ageChartInstance = null;
    let severityChartInstance = null;
    let satisfactionChartInstance = null;

    // 1. Age Profile Distribution Chart
    function initAgeChart(dataPoints) {
      const ctxAge = document.getElementById('ageDistributionChart').getContext('2d');
      if (ageChartInstance) ageChartInstance.destroy();

      ageChartInstance = new Chart(ctxAge, {
        type: 'bar',
        data: {
          labels: ['<1 yr', '1 yr', '2 yrs', '3 yrs', '4 yrs', '5 yrs', '6 yrs', '7 yrs', '8 yrs', '9 yrs', '10 yrs', '11 yrs', '12 yrs'],
          datasets: [{
            label: 'Registered Children',
            data: dataPoints,
            backgroundColor: '#0f5c5e',
            hoverBackgroundColor: '#0a3e40',
            borderRadius: 6,
            barThickness: 'flex',
            maxBarThickness: 24
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#243333',
              titleFont: { family: 'Inter', size: 12, weight: 'bold' },
              bodyFont: { family: 'Inter', size: 11 },
              padding: 10,
              cornerRadius: 8
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0,0,0,0.04)' },
              ticks: { font: { family: 'Inter', size: 11 } }
            },
            x: {
              grid: { display: false },
              ticks: { font: { family: 'Inter', size: 11 } }
            }
          }
        }
      });
    }

    // 2. Evaluated Severity Classifications Doughnut Chart
    function initSeverityChart(dataPoints) {
      const ctxSeverity = document.getElementById('severityPieChart').getContext('2d');
      if (severityChartInstance) severityChartInstance.destroy();

      severityChartInstance = new Chart(ctxSeverity, {
        type: 'doughnut',
        data: {
          labels: ['Low Risk', 'Medium Risk', 'High Risk'],
          datasets: [{
            data: dataPoints,
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { family: 'Inter', size: 12, weight: '600' },
                padding: 15
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return ` ${context.label}: ${context.parsed}%`;
                }
              }
            }
          }
        }
      });
    }

    // 3. Post-Assessment Guardian Ratings Horizontal Bar Chart
    function initSatisfactionChart(dataPoints) {
      const ctxSatisfaction = document.getElementById('satisfactionRatingsChart').getContext('2d');
      if (satisfactionChartInstance) satisfactionChartInstance.destroy();

      // Reverse data so 5 stars appears at the top
      const reversedData = [...dataPoints].reverse();

      satisfactionChartInstance = new Chart(ctxSatisfaction, {
        type: 'bar',
        data: {
          labels: ['5 Stars ★★★★★', '4 Stars ★★★★', '3 Stars ★★★', '2 Stars ★★', '1 Star ★'],
          datasets: [{
            label: 'Responses',
            data: reversedData,
            backgroundColor: [
              '#0f5c5e',
              '#10b981',
              '#f59e0b',
              '#f97316',
              '#ef4444'
            ],
            borderRadius: 6,
            barThickness: 16
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#243333',
              titleFont: { family: 'Inter', size: 12, weight: 'bold' },
              bodyFont: { family: 'Inter', size: 11 },
              padding: 10,
              cornerRadius: 8,
              callbacks: {
                label: function(context) {
                  return ` ${context.parsed.x.toLocaleString()} guardian reviews`;
                }
              }
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: 'rgba(0,0,0,0.04)' },
              ticks: { font: { family: 'Inter', size: 11 } }
            },
            y: {
              grid: { display: false },
              ticks: { font: { family: 'Inter', size: 11, weight: '600' } }
            }
          }
        }
      });
    }

    // Toast Notification Utility
    function showToast(message) {
      const toast = document.getElementById("toast");
      const toastMsg = document.getElementById("toastMessage");
      
      toastMsg.textContent = message;
      toast.classList.remove("translate-y-20", "opacity-0");
      toast.classList.add("translate-y-0", "opacity-100");

      setTimeout(() => {
        toast.classList.remove("translate-y-0", "opacity-100");
        toast.classList.add("translate-y-20", "opacity-0");
      }, 3000);
    }
  </script>
  <script src="impact.js?v=20260729-live-impact"></script>
</body>
</html>
