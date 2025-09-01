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

            <main class="flex-1 overflow-y-auto p-6">
                <!-- Filters -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-6 space-y-4 lg:space-y-0">
                    <div id="preset-filters" class="flex items-center space-x-2 flex-wrap">
                        <button data-period="this_week" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Week</button>
                        <button data-period="this_month" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Month</button>
                        <button data-period="this_quarter" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Quarter</button>
                        <button data-period="this_year" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">This Year</button>
                        <button data-period="all_time" class="filter-btn px-3 py-1 text-sm rounded-full mb-2">All Time</button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 font-medium">Custom Range:</span>
                        <input type="date" id="startDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue w-36">
                        <span class="text-gray-400">to</span>
                        <input type="date" id="endDate" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue w-36">
                    </div>
                </div>
                <!-- Metrics Grid -->
                <div id="metrics-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"><i class="fas fa-smile text-jru-blue text-lg"></i></div></div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Overall Satisfaction</p>
                            <div class="flex items-center">
                                <span id="overall-satisfaction-score" class="text-2xl font-bold text-gray-900 mr-2">...</span>
                                <div id="overall-satisfaction-stars" class="flex text-jru-gold"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"><i class="fas fa-chart-bar text-green-600 text-lg"></i></div></div>
                        <div><p class="text-sm font-medium text-gray-600 mb-1">Total Responses</p><p id="total-responses" class="text-2xl font-bold text-gray-900">...</p></div>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center"><i class="fas fa-users text-purple-600 text-lg"></i></div></div>
                        <div><p class="text-sm font-medium text-gray-600 mb-1">Feedback Freq. (Daily Avg)</p><p id="feedback-frequency" class="text-2xl font-bold text-gray-900">...</p></div>
                    </div>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center"><i class="fas fa-star text-jru-gold text-lg"></i></div>
                            <p id="rating-dist-total" class="text-xs text-gray-500">... total</p>
                        </div>
                        <p class="text-sm font-medium text-gray-600 mb-2">Rating Distribution</p>
                        <div class="flex-grow" style="position: relative; height: 120px;"> <!-- Chart container -->
                            <canvas id="ratingDistChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Charts and Data Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200" id="service-performance-card">
                        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-900">Service Performance</h3></div>
                        <div id="service-performance-bars" class="space-y-4"></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-semibold text-gray-900">Sentiment Analysis</h3></div>
                        <div class="chart-container"><canvas id="sentimentChart"></canvas></div>
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div class="text-center"><div class="flex items-center justify-center mb-1"><div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div><span class="text-sm text-gray-600">Positive</span></div><p id="sentiment-positive-percent" class="text-lg font-bold text-gray-900">...%</p></div>
                            <div class="text-center"><div class="flex items-center justify-center mb-1"><div class="w-3 h-3 bg-gray-400 rounded-full mr-2"></div><span class="text-sm text-gray-600">Neutral</span></div><p id="sentiment-neutral-percent" class="text-lg font-bold text-gray-900">...%</p></div>
                            <div class="text-center"><div class="flex items-center justify-center mb-1"><div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div><span class="text-sm text-gray-600">Negative</span></div><p id="sentiment-negative-percent" class="text-lg font-bold text-gray-900">12%</p></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Satisfaction Trends</h3>
                            <button id="open-trends-modal-btn" class="text-sm text-jru-blue hover:text-blue-800 font-medium"><i class="fas fa-expand-alt mr-1"></i></button>
                        </div>
                        <div class="chart-container" id="trends-chart-container"><canvas id="trendsChart"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Common Feedback</h3>
                        <div id="common-feedback-list" class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg"><div class="flex items-center"><span class="text-sm font-medium">Slow WiFi</span></div><span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">23</span></div>
                            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg"><div class="flex items-center"><span class="text-sm font-medium">Limited Parking</span></div><span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">18</span></div>
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg"><div class="flex items-center"><span class="text-sm font-medium">Helpful Staff</span></div><span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">45</span></div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg"><div class="flex items-center"><span class="text-sm font-medium">Quick Service</span></div><span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">32</span></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Trends Chart Modal -->
    <div id="trends-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-full flex flex-col">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-xl font-semibold text-gray-900">Satisfaction Trends</h3>
                <button id="close-trends-modal-btn" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-2xl"></i></button>
            </div>
            <div class="p-6 flex-grow">
                <div class="h-full w-full"><canvas id="modalTrendsChart"></canvas></div>
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

    <!-- Export Data Modal -->
    <div id="export-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
            <form id="export-form">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Export Feedback Data</h3>
                    <button type="button" id="close-export-modal-btn" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-2xl"></i></button>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <div class="flex items-center space-x-2">
                            <input type="date" id="export-startDate" required class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue w-full">
                            <span class="text-gray-400">to</span>
                            <input type="date" id="export-endDate" required class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue w-full">
                        </div>
                    </div>
                    <div>
                        <label for="export-office" class="block text-sm font-medium text-gray-700">Filter by Office</label>
                        <select id="export-office" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-jru-blue focus:border-jru-blue sm:text-sm">
                            <option value="all">All Offices</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Columns to Include</label>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <label class="flex items-center"><input type="checkbox" name="columns" value="student_no" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Student No</span></label>
                            <label class="flex items-center"><input type="checkbox" name="columns" value="division" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Division</span></label>
                            <label class="flex items-center"><input type="checkbox" name="columns" value="office_name" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Office</span></label>
                            <label class="flex items-center"><input type="checkbox" name="columns" value="service" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Service</span></label>
                            <label class="flex items-center"><input type="checkbox" name="columns" value="service_outcome" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Ratings</span></label>
                            <label class="flex items-center"><input type="checkbox" name="columns" value="suggestions" checked class="h-4 w-4 rounded border-gray-300 text-jru-blue focus:ring-jru-blue"> <span class="ml-2">Suggestions</span></label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File Format</label>
                        <div class="mt-2 flex space-x-4">
                            <label class="flex items-center"><input type="radio" name="format" value="xlsx" checked class="h-4 w-4 text-jru-blue focus:ring-jru-blue border-gray-300"><span class="ml-2">Excel (.xlsx)</span></label>
                            <label class="flex items-center"><input type="radio" name="format" value="csv" class="h-4 w-4 text-jru-blue focus:ring-jru-blue border-gray-300"><span class="ml-2">CSV</span></label>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-3 flex justify-end">
                    <button type="button" id="cancel-export-btn" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-jru-blue hover:bg-jru-navy">Export Data</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>