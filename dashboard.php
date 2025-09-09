<?php
    session_start(); //the very first thing on the page
    //Check if user is logged in and is an admin
    if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
        
        // If not logged in or not an admin, redirect to the login page
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
    <title>JRU-PULSE Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> <!--Font Awesome for icons-->
    <link href="css/output.css" rel="stylesheet">  <!--Tailwind CSS for styling-->
    <link rel="stylesheet" href="css/main.css"> <!-- Custom styles for admin -->
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
       <?php
            $currentPage = 'dashboard'; 
            require_once 'includes/sidebar.php';

            require_once 'includes/logout.php'; // Include the logout confirmation modal
        ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
                        <p class="text-sm text-gray-600 mt-1">Performance and User-satisfaction Linked Services Evaluation</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        
                        <div class="relative" id="notification-bell-container">
                            <button id="notification-bell" class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-colors">
                                <i class="fas fa-bell text-xl"></i>
                                <span id="notification-count" class="hidden absolute -top-1 -right-1 w-5 h-5 text-xs bg-red-500 text-white rounded-full flex items-center justify-center"></span>
                            </button>
                            
                            <div id="notification-dropdown" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-20">
                                <div class="p-3 border-b flex justify-between items-center">
                                    <span class="font-semibold text-gray-800">Notifications</span>
                                    <button id="mark-all-read-btn" class="text-xs text-jru-blue hover:underline hidden">Mark all as read</button>
                                </div>
                                <div id="notification-list" class="max-h-96 overflow-y-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!--Main Dashboard Area-->
           <main class="flex-1 overflow-y-auto p-6">
                <div id="no-data-message" class="hidden text-center py-20">
                    <div class="bg-white rounded-lg shadow-sm border p-12"><i class="fas fa-chart-pie text-5xl text-gray-300 mb-4"></i><h2 class="text-xl font-bold text-gray-700">No Data Available</h2><p class="text-gray-500 mt-2">There are no survey responses for the selected date range.</p></div>
                </div>

                <div id="filters-container" class="flex flex-col lg:flex-row lg:items-center justify-between mb-6 space-y-4 lg:space-y-0">
                    <div id="preset-filters" class="flex items-center space-x-2 flex-wrap">
                        <button data-period="this_week" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Week</button>
                        <button data-period="this_month" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Month</button>
                        <button data-period="this_quarter" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Quarter</button>
                        <button data-period="this_year" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Year</button>
                        <button data-period="all_time" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">All Time</button>
                    </div>
                    <div class="flex items-center space-x-2"><span class="text-sm text-gray-600 font-medium">Custom Range:</span><input type="date" id="startDate" class="px-3 py-2 border border-gray-300 rounded-lg w-36"><span class="text-gray-400">to</span><input type="date" id="endDate" class="px-3 py-2 border border-gray-300 rounded-lg w-36"></div>
                </div>
                
                <div id="dashboard-content" class="hidden">
                    <!-- Hero Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold text-gray-800">Satisfaction Trend & Forecast</h3><div class="h-80 mt-4"><canvas id="satisfactionTrendChart"></canvas></div></div>
                        <div class="flex flex-col space-y-4">
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-star text-jru-gold mr-2"></i>Overall Satisfaction</p><div class="flex items-baseline space-x-2 mt-2"><span id="overall-satisfaction-score" class="text-4xl font-bold text-gray-900">...</span></div><p id="overall-satisfaction-comparison" class="text-xs mt-1 h-4"></p></div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-wand-magic-sparkles text-purple-500 mr-2"></i>Predicted Satisfaction</p><span id="predicted-satisfaction-kpi" class="text-4xl font-bold text-gray-900">...</span><p class="text-xs text-gray-500 mt-1">Forecast for next period</p></div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border flex-1 flex flex-col justify-center"><p class="text-sm font-medium text-gray-600 flex items-center"><i class="fas fa-poll text-green-500 mr-2"></i>Total Responses</p><span id="total-responses" class="text-4xl font-bold text-gray-900">...</span><p id="total-responses-comparison" class="text-xs mt-1 h-4"></p></div>
                        </div>
                    </div>

                    <!-- Office Performance Section -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
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
    <!-- Trends Modal -->
    <div id="trends-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b flex-shrink-0">
                <h3 class="text-xl font-semibold text-gray-900">Satisfaction Trends (Detailed View)</h3>
             <!--   <button id="close-trends-modal-btn" class="text-gray-400 hover:text-gray-600 p-2 rounded-full"><i class="fas fa-times text-2xl"></i></button>
--></div>
            <div class="p-6 flex-grow overflow-hidden">
                <div class="relative h-full w-full">
                    <canvas id="modalTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Details Modal -->
    <div id="feedback-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <div id="feedback-modal-header" class="flex items-center justify-between p-4 border-b">
                <h3 id="feedback-modal-title" class="text-xl font-semibold text-gray-900">Feedback Details</h3>
                <button id="close-feedback-modal-btn" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div id="feedback-modal-body" class="p-6 space-y-4"></div>
        </div>
    </div>


    <script src="js/main.js"></script>
     <script src="js/dashboard.js"></script> 
</body>

</html>