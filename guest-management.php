<?php
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
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
    <title>JRU PULSE - Guest Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="css/main.css"> <!-- Use the shared admin stylesheet -->
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php
        $currentPage = 'guest-management'; // MODIFIED: Set the current page for the sidebar
        require_once 'includes/sidebar.php';
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <!-- MODIFIED: Title and description -->
                        <h1 class="text-2xl font-bold text-gray-800">Guest Database</h1>
                        <p class="text-sm text-gray-500 mt-1">Manage records of non-student survey respondents.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- REMOVED: The "Import from CSV" button is gone -->
                        <button id="openAddModalBtn" class="bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-900 transition-colors flex items-center font-medium shadow-sm">
                            <i class="fas fa-plus mr-2"></i>
                            Add Guest
                        </button>
                    </div> 
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Search Bar -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="searchInput" placeholder="Search by name, email, or role..." class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-jru-blue focus:border-transparent transition">
                        </div>
                    </div>

                    <!-- Guest Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <!-- MODIFIED: Table headers are simpler -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="guestsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- JS will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Guest Modal -->
    <div id="guestModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform transition-all" id="guestModalContent">
            <form id="guestForm">
                <div class="p-6 border-b bg-gray-50 rounded-t-xl"><h3 id="modalTitle" class="text-xl font-bold text-gray-800">Add New Guest</h3></div>
                <div class="p-8 space-y-6">
                    <input type="hidden" id="guestId" name="id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label><input type="text" name="first_name" id="first_name" required class="block w-full border-gray-300 rounded-lg"></div>
                        <div><label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label><input type="text" name="last_name" id="last_name" required class="block w-full border-gray-300 rounded-lg"></div>
                    </div>
                    <div><label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" id="email" required class="block w-full border-gray-300 rounded-lg"></div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="role" required class="block w-full border-gray-300 rounded-lg">
                            <option value="" disabled selected>Please select a role</option>
                            <option value="JRU Alumnus">JRU Alumnus</option>
                            <option value="JRU Parent/Guardian">JRU Parent/Guardian</option>
                            <option value="Student (Non-JRU)">Student (Non-JRU)</option>
                            <option value="Parent/Guardian (Non-JRU)">Parent/Guardian (Non-JRU)</option>
                            <option value="Industry Partner">Industry Partner</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-xl">
                    <button type="button" class="closeModalBtn bg-white border border-gray-300 rounded-lg px-4 py-2">Cancel</button>
                    <button type="submit" id="saveBtn" class="bg-jru-blue text-white rounded-lg px-4 py-2">Save Guest</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal (This is reused) -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6"><div class="flex items-start"><div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100"><i class="fas fa-exclamation-triangle text-red-600 text-xl"></i></div><div class="ml-4 text-left"><h3 id="confirmationTitle" class="text-lg font-bold text-gray-900">Confirm</h3><div class="mt-2"><p id="confirmationMessage" class="text-sm text-gray-600">Are you sure?</p></div></div></div></div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4 rounded-b-xl">
                <button id="confirmCancelBtn" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md">Cancel</button>
                <button id="confirmActionBtn" type="button" class="px-4 py-2 bg-red-600 text-white rounded-md">Confirm</button>
            </div>
        </div>
    </div>

   <div id="toastNotification" class="fixed top-5 right-5 text-white py-3 px-6 rounded-lg shadow-xl z-[100] transition-all duration-300 ease-in-out opacity-0 hidden">
        <div class="flex items-center">
            <i id="toastIcon" class="mr-3 text-xl"></i>
            <span id="toastMessage" class="font-medium"></span>
        </div>
    </div>

    <!-- Include necessary scripts -->
    <script src="js/main.js"></script>
    <script src="js/guest-management.js"></script> <!-- MODIFIED: Points to the new JS file -->
</body>
</html>