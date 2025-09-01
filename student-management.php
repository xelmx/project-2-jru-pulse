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
    <title>JRU PULSE - Student Management</title>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php
        $currentPage = 'student-management'; // Set the current page for active link highlighting
        include 'includes/sidebar.php'; 

        include 'includes/logout.php';
        ?>

        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Student Database</h1>
                        <p class="text-sm text-gray-500 mt-1">Add, view, and manage the university's student records.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-4">
                            <button id="openImportModalBtn" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm font-medium transition-colors flex items-center shadow-sm">
                                <i class="fas fa-file-csv mr-2"></i>
                                Import from CSV
                            </button>
                            <button id="openAddModalBtn" class="bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-900 transition-colors flex items-center font-medium shadow-sm">
                                <i class="fas fa-plus mr-2"></i>
                                Add Student
                            </button>
                        </div> 
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Search and Filters -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="searchInput" placeholder="Search by name, email, or student number..." class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                        </div>
                    </div>

                    <!-- Student Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Division</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course/Strand</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- JS will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Add/Edit Student Modal -->
<div id="studentModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full transform transition-all opacity-0 -translate-y-4" id="studentModalContent">
        <form id="studentForm">
            <!-- Modal Header -->
            <div class="p-6 border-b bg-gray-50 rounded-t-xl">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-jru-blue/10 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-plus text-xl text-jru-blue"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Add New Student</h3>
                        <p class="text-sm text-gray-500">Fill in the details below to add a new record.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8 space-y-6 max-h-[65vh] overflow-y-auto">
                <input type="hidden" id="studentId" name="id">

                <!-- Student Number -->
                <div>
                    <label for="student_number" class="block text-sm font-medium text-gray-700 mb-1">Student Number</label>
                    <!-- NEW: A 'relative' container for just the input and icon -->
                    <div class="relative">
                        <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="student_number" id="student_number" required placeholder="e.g., 24-123456"
                               class="pl-10 pr-4 py-2 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                    </div>
                </div>

                <!-- First & Last Name -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="first_name" id="first_name" required placeholder="Juan"
                                   class="pl-10 pr-4 py-2 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                        </div>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="last_name" id="last_name" required placeholder="Dela Cruz"
                                   class="pl-10 pr-4 py-2 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                        </div>
                    </div>
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" id="email" required placeholder="juandelacruz@jru.edu"
                               class="pl-10 pr-4 py-2 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                    </div>
                </div>

                <!-- Division & Course/Strand -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="division" class="block text-sm font-medium text-gray-700 mb-1">Division</label>
                        <select name="division" id="division" required
                                class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                        </select>
                    </div>
                    <div>
                        <label for="course_or_strand" class="block text-sm font-medium text-gray-700 mb-1">Course / Strand</label>
                        <select name="course_or_strand" id="course_or_strand" required disabled
                                class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-jru-blue focus:border-transparent transition disabled:bg-gray-100">
                            <option value="">Select a division first</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-xl">
                <button type="button" class="closeModalBtn bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 shadow-sm transition-colors">Cancel</button>
                <button type="submit" id="saveStudentBtn" class="bg-jru-blue text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-900 shadow-sm transition-colors">Save Student</button>
            </div>
        </form>
    </div>
</div>

    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6">
            <div class="flex items-start">
                <!-- Icon -->
                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4 text-left">
                    <!-- Title (This ID is CRITICAL) -->
                    <h3 id="confirmationTitle" class="text-lg leading-6 font-bold text-gray-900">
                        Confirm Action
                    </h3>
                    <!-- Message Body (This ID is CRITICAL) -->
                    <div class="mt-2">
                        <p id="confirmationMessage" class="text-sm text-gray-600">
                           Are you sure?
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Action Buttons (These IDs are CRITICAL) -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4 rounded-b-xl">
            <button id="confirmCancelBtn" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button id="confirmActionBtn" type="button" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">
                Confirm
            </button>
        </div>
    </div>
</div>

    <div id="toastNotification" class="fixed top-5 right-5 text-white py-3 px-6 rounded-lg shadow-xl z-[100] transition-all duration-300 ease-in-out opacity-0 hidden">
        <div class="flex items-center">
            <i id="toastIcon" class="mr-3 text-xl"></i>
            <span id="toastMessage" class="font-medium"></span>
        </div>
    </div>
    
    <!-- Import from CSV Modal -->
    <div id="importModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full transform transition-all opacity-0 -translate-y-4" id="importModalContent">
            <form id="importForm" enctype="multipart/form-data">
                <!-- Modal Header -->
                <div class="p-6 border-b bg-gray-50 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-file-import text-xl text-green-700"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Import Students from CSV</h3>
                                <p class="text-sm text-gray-500">Upload a file to bulk-add student records.</p>
                            </div>
                        </div>
                        <button type="button" class="closeImportModalBtn text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-8">
                    <!-- Instructions & File Format -->
                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-jru-blue rounded-r-lg">
                        <h4 class="font-bold text-jru-blue">Instructions</h4>
                        <p class="text-sm text-gray-700 mt-1">
                            Ensure your CSV file has the following columns:
                        </p>
                        <code class="block bg-gray-200 text-gray-800 p-2 rounded-md mt-2 text-xs">
                            student_number,first_name,last_name,email,division,course_or_strand
                        </code>
                    </div>

                    <div class="mb-6">
                            <a href="templates/student_template.csv" download="student_template.csv"
                        class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-jru-blue transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Download CSV Template
                        </a>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                        <input type="file" name="csv_file" id="csv_file" required accept=".csv"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-jru-blue/10 file:text-jru-blue hover:file:bg-jru-blue/20 transition">
                    </div>
                    
                    <!-- Results Area (Initially Hidden) -->
                    <div id="importResultsArea" class="hidden mt-6"></div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-xl">
                    <button type="button" class="closeImportModalBtn bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 shadow-sm">Cancel</button>
                    <button type="submit" id="importSubmitBtn" class="bg-green-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-green-700 shadow-sm transition-colors flex items-center justify-center min-w-[120px]">
                        <span class="btn-text">Upload & Import</span>
                        <i class="fas fa-spinner fa-spin hidden ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
          
                
    <script src="js/main.js"></script>
    <script src="js/student-management.js"></script>
</body>
</html>