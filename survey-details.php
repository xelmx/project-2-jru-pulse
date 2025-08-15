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
    <title>Survey Details - JRU-PULSE</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <link href="css/output.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (Copied from survey-management.php for consistency) -->
       <div id="sidebar" class="sidebar-transition sidebar-expanded bg-blue-950 shadow-lg flex flex-col border-r border-gray-200">
             <!-- Logo Section -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center">
                    <button id="sidebarToggle" class="p-2 rounded-lg hover:bg-gray-600 transition-colors mr-3">
                        <i class="fas fa-bars text-gray-100"></i>
                    </button>
                    <div id="logoContainer" class="logo-transition flex items-center">
                        <img src="assets\jru-pulse-final-white.png" alt="JRU-A-PULSE" class="h-8 w-auto">
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 p-4 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center px-3 py-3 text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                            <i class="fas fa-tachometer-alt text-lg w-6"></i>
                            <span class="menu-text ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="survey-management.php" class="flex items-center px-3 py-3 text-gray-50 hover:bg-gray-600 rounded-lg transition-colors"> 
                            <i class="fas fa-poll text-lg w-6"></i>
                            <span class="menu-text ml-3">Survey Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="performance-analytics-reports.php" class="flex items-center px-3 py-3 text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                            <i class="fas fa-chart-line text-lg w-6"></i>
                            <span class="menu-text ml-3">Performance Analytics & Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-3 py-3 text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                            <i class="fas fa-users text-lg w-6"></i>
                            <span class="menu-text ml-3">User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-3 py-3 text-gray-50 hover:bg-gray-600 rounded-lg transition-colors">
                            <i class="fas fa-cog text-lg w-6"></i>
                            <span class="menu-text ml-3">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- User Profile -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-r from-jru-gold to-yellow-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <div id="userInfo" class="menu-text ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-50">Administrator</p>
                        <p class="text-xs text-gray-100">gto@jru.edu.ph</p>
                    </div>
                    <button class="menu-text p-2 text-gray-50 hover:text-yellow-400 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 id="surveyTitleHeader" class="text-2xl font-bold text-gray-900">Loading Survey Details...</h1>
                        <p class="text-sm text-gray-600 mt-1">View survey structure, manage duplication, and analyze results</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="duplicateSurveyBtn" class="bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors flex items-center">
                            <i class="fas fa-copy mr-2"></i>
                            Duplicate as New Draft
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <div id="loadingState" class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-4xl text-jru-blue"></i>
                    <p class="mt-4 text-gray-600">Loading data...</p>
                </div>

                <div id="surveyContent" class="hidden">
                    <!-- Survey Metadata -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Survey Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Office</p>
                                <p id="metaOffice" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Service</p>
                                <p id="metaService" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Status</p>
                                <p id="metaStatus" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Responses</p>
                                <p id="metaResponses" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                             <div>
                                <p class="text-sm font-medium text-gray-500">Created On</p>
                                <p id="metaCreated" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Editability</p>
                                <p id="metaLocked" class="text-md font-semibold text-gray-800">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Preview -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Survey Questions (Read-Only)</h3>
                        <div id="questionsContainer" class="space-y-4">
                            <!-- Questions will be dynamically rendered here -->
                        </div>
                    </div>

                    <!-- Analytics Placeholder -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Response Analytics</h3>
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <i class="fas fa-chart-line text-4xl text-gray-300"></i>
                            <p class="mt-4 text-gray-600">Detailed charts and graphs will be available here soon.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Reusable Confirmation Modal and Toast Notification -->
    <?php include 'components/modals.php'; ?>

    <script src="js/survey-details.js"></script>
</body>
</html>