<?php
    session_start();
    if (!isset($_SESSION['user_data'])) { header('Location: index.php?error=auth_required'); exit; }
    $user = $_SESSION['user_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JRU PULSE - Performance Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
       <?php $currentPage = 'analytics'; require_once 'includes/sidebar.php'; require_once 'includes/logout.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Performance Analytics</h1>
                        <p class="text-sm text-gray-600 mt-1">A deep-dive analysis of survey feedback and performance trends.</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow-sm border mb-6 space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                        <div id="preset-filters" class="flex items-center space-x-2 flex-wrap">
                            <button data-period="this_week" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Week</button>
                            <button data-period="this_month" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Month</button>
                            <button data-period="this_quarter" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Quarter</button>
                            <button data-period="this_year" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Year</button>
                            <button data-period="all_time" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">All Time</button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600 font-medium">Custom Range:</span>
                            <input type="date" id="startDate" class="px-3 py-2 border border-gray-300 rounded-lg w-36">
                            <span class="text-gray-400">to</span>
                            <input type="date" id="endDate" class="px-3 py-2 border border-gray-300 rounded-lg w-36">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <div>
                            <label for="office-filter" class="text-sm font-medium text-gray-700">Filter by Office</label>
                            <select id="office-filter" class="filter-control mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm" <?php echo $user['role'] === 'office_head' ? 'disabled' : ''; ?>>
                                <option value="all">All Offices</option>
                            </select>
                        </div>
                        <div>
                            <label for="service-filter" class="text-sm font-medium text-gray-700">Filter by Service</label>
                            <select id="service-filter" class="filter-control mt-1 block w-full p-2 border-gray-300 rounded-md shadow-sm" disabled>
                                <option value="all">All Services</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- "NO DATA" MESSAGE & LOADING SPINNER (HIDDEN BY DEFAULT) -->
                <div id="loading-message" class="text-center py-20"><i class="fas fa-spinner fa-spin text-4xl text-jru-blue"></i><p class="mt-2 text-gray-600">Loading Analytics...</p></div>
                <div id="no-data-message" class="hidden text-center py-20">...</div>
                
                <!-- Main Analytics Content -->
                <div id="analytics-content" class="hidden space-y-6">
                    <!-- KPI Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600">Overall Satisfaction</p><p id="kpi-overall-satisfaction" class="text-3xl font-bold text-gray-900 mt-1">...</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600">Total Responses</p><p id="kpi-total-responses" class="text-3xl font-bold text-gray-900 mt-1">...</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600">Net Promoter Score (NPS)</p><p id="kpi-nps" class="text-3xl font-bold text-gray-900 mt-1">...</p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600">Excellent Rating %</p><p id="kpi-excellent-pct" class="text-3xl font-bold text-gray-900 mt-1">...%</p></div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Performance by Day of the Week</h3><div class="h-64 mt-4"><canvas id="performanceByDayChart"></canvas></div></div>
                        <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Satisfaction by Student Division</h3><div class="h-64 mt-4"><canvas id="satisfactionByDivisionChart"></canvas></div></div>
                        <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Satisfaction by Respondent Type</h3><div class="h-64 mt-4"><canvas id="satisfactionByTypeChart"></canvas></div></div>
                        <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Rating Distribution</h3><div class="h-64 mt-4"><canvas id="ratingDistChart"></canvas></div></div>
                        <div id="service-breakdown-card" class="bg-white p-6 rounded-xl shadow-sm border hidden lg:col-span-2"><h3 class="text-lg font-semibold text-gray-800">Service-Level Breakdown</h3><div class="h-64 mt-4"><canvas id="serviceBreakdownChart"></canvas></div></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="js/main.js"></script>
    <script src="js/analytics.js"></script>
</body>
</html>