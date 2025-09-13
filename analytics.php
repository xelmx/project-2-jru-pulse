<?php
    session_start();
    // This page is accessible to both 'admin' and 'office_head'
    if (!isset($_SESSION['user_data'])) {
        header('Location: index.php?error=auth_required');
        exit;
    }
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

                <div id="loading-message" class="hidden text-center py-20"><i class="fas fa-spinner fa-spin text-4xl text-jru-blue"></i><p class="mt-2 text-gray-600">Loading Analytics...</p></div>
                <div id="no-data-message" class="hidden text-center py-20"><div class="bg-white rounded-lg shadow-sm border p-12"><i class="fas fa-info-circle text-5xl text-gray-300 mb-4"></i><h2 class="text-xl font-bold text-gray-700">No Data Available</h2><p class="text-gray-500 mt-2">There are no survey responses for the selected filters.</p></div></div>
                
                <div id="analytics-content" class="hidden space-y-6">
                    <!-- Hero Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Satisfaction Trend & Forecast</h3><div class="h-80 mt-4"><canvas id="satisfactionTrendChart"></canvas></div></div>
                        <div class="flex flex-col space-y-4">
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-star text-jru-gold mr-2"></i>Overall Satisfaction</p><div class="flex items-baseline space-x-2 mt-2"><span id="overall-satisfaction-score" class="text-4xl font-bold text-gray-900">...</span></div><p id="overall-satisfaction-comparison" class="text-xs mt-1 h-4"></p></div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-wand-magic-sparkles text-purple-500 mr-2"></i>Predicted Satisfaction</p><span id="predicted-satisfaction-kpi" class="text-4xl font-bold text-gray-900">...</span><p class="text-xs text-gray-500 mt-1">Forecast for next period</p></div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-poll text-green-500 mr-2"></i>Total Responses</p><span id="total-responses" class="text-4xl font-bold text-gray-900">...</span><p id="total-responses-comparison" class="text-xs mt-1 h-4"></p></div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center">
                                <p class="text-sm font-medium text-gray-600 flex items-center">
                                    <i class="fas fa-bullhorn text-blue-500 mr-2"></i>Net Promoter Score (NPS)
                                </p>
                                <span id="nps-score" class="text-4xl font-bold text-gray-900">...</span>
                                <p class="text-xs text-gray-500 mt-1">Promoters vs. Detractors</p>
                            </div>

                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center">
                                <p class="text-sm font-medium text-gray-600 flex items-center">
                                    <i class="fas fa-award text-yellow-500 mr-2"></i>Excellent Rating %
                                </p>
                                <span id="excellent-rating-percentage" class="text-4xl font-bold text-gray-900">...</span>
                                <p class="text-xs text-gray-500 mt-1">Percentage of '5-star' ratings</p>
                            </div>
                        </div>
                    </div>

                    <!-- Office Performance Section -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Office Performance Breakdown</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                            <div><h4 class="text-sm font-semibold text-green-700 mb-2 pb-1 border-b">Top 3 Performing Offices</h4><div id="top-offices-list" class="space-y-2"></div></div>
                            <div><h4 class="text-sm font-semibold text-red-700 mb-2 pb-1 border-b">3 Offices Needing Attention</h4><div id="bottom-offices-list" class="space-y-2"></div></div>
                        </div>
                    </div>
                    
                    <!-- Insights Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Sentiment Breakdown</h3><div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center"><div class="h-48 flex justify-center"><canvas id="sentimentDoughnutChart"></canvas></div><div id="sentiment-breakdown-list"></div></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Sentiment Trend & Forecast</h3><div class="h-64 mt-4"><canvas id="sentimentTrendChart"></canvas></div></div>
                        </div>
                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Service Performance</h3><div id="service-performance-bars" class="space-y-4 mt-4"></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Rating Distribution</h3><div class="h-48 mt-4"><canvas id="ratingDistChart"></canvas></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border">
                                <div id="feedback-tabs-container">
                                    <div class="border-b border-gray-200"><nav class="-mb-px flex space-x-6"><button id="common-feedback-tab" class="py-2 px-1 border-b-2 font-medium text-sm border-jru-blue text-jru-blue">Common Feedback</button><button id="recent-comments-tab" class="py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">Recent Comments</button></nav></div>
                                    <div id="common-feedback-content" class="h-64 overflow-y-auto space-y-2 pr-2 mt-4"></div>
                                    <div id="recent-comments-content" class="hidden h-64 overflow-y-auto space-y-3 pr-2 mt-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="js/main.js"></script>
    <script src="js/analytics.js"></script>
</body>
</html>