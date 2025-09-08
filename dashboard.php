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
                <!-- "NO DATA" MESSAGE (HIDDEN BY DEFAULT)-->
                <div id="no-data-message" class="hidden text-center py-20"><div class="bg-white rounded-lg shadow-sm border p-12"><i class="fas fa-info-circle text-5xl text-gray-300 mb-4"></i><h2 class="text-xl font-bold text-gray-700">No Data Available</h2><p class="text-gray-500 mt-2">There are no survey responses for the selected date range.</p></div></div>

                <!-- Filters -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-6 space-y-4 lg:space-y-0">
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
                    <!-- KPI Banner -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600 mb-1">Overall Satisfaction</p><div class="flex items-baseline space-x-2"><span id="overall-satisfaction-score" class="text-3xl font-bold">...</span><div id="overall-satisfaction-stars" class="flex text-jru-gold"></div></div><p id="overall-satisfaction-comparison" class="text-xs text-gray-500 mt-1 h-4"></p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600 mb-1">Predicted Score</p><div class="flex items-baseline space-x-2"><span id="predicted-satisfaction-score" class="text-3xl font-bold">...</span><span class="text-xl text-gray-400">/ 5.0</span></div><p id="predicted-satisfaction-comparison" class="text-xs text-gray-500 mt-1 h-4"></p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600 mb-1">Total Responses</p><span id="total-responses" class="text-3xl font-bold">...</span><p id="total-responses-comparison" class="text-xs text-gray-500 mt-1 h-4"></p></div>
                        <div class="bg-white p-4 rounded-xl shadow-sm border"><p class="text-sm font-medium text-gray-600 mb-1">Feedback Freq. (Daily Avg)</p><span id="feedback-frequency" class="text-3xl font-bold">...</span><p class="text-xs text-gray-500 mt-1 h-4"></p></div>
                    </div>

                    <!-- Main Content Grid (Two Columns) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold">Satisfaction Trends</h3><div class="h-64 mt-4"><canvas id="trendsChart"></canvas></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold">Service Performance</h3><div id="service-performance-bars" class="space-y-4 mt-4"></div></div>
                        </div>
                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold">Sentiment Analysis</h3><div class="h-48 mt-4 flex justify-center"><canvas id="sentimentChart"></canvas></div><div id="sentiment-legend" class="grid grid-cols-3 gap-2 mt-4 text-center"></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold">Rating Distribution</h3><div class="h-48 mt-4"><canvas id="ratingDistChart"></canvas></div></div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border"><h3 class="text-lg font-semibold">Common Feedback</h3><div id="common-feedback-list" class="h-48 overflow-y-auto space-y-2 pr-2 mt-4"></div></div>
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