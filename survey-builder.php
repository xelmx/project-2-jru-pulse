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
    <title>JRU-PULSE - Survey Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
     <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="css/admin-main.css">
    <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php
            $currentPage = 'survey-builder'; // Set the current page for active link highlighting
            require_once 'includes/sidebar.php';
        ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Survey Builder</h1>
                        <p class="text-sm text-gray-600 mt-1">Create and customize surveys with templates</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="saveAsTemplate" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center">
                            <i class="fas fa-copy mr-2"></i> <!-- NEW ICON -->
                            Save as Template
                        </button>
                        <button id="previewSurvey" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            Preview
                        </button>
                        <button id="saveSurvey" class="border border-jru-blue text-jru-blue px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center">
                            <i class="fas fa-save mr-2"></i> <!-- Standard Save Icon -->
                            Save Draft
                        </button>
                        <button id="publishSurvey" class="bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 flex items-center">
                            <i class="fas fa-rocket mr-2"></i>
                            Publish Survey
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Survey Builder Content -->
            <main class="flex-1 overflow-hidden">
                <div class="h-full flex">
                    <!-- Left Panel - Survey Builder -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <!-- Survey Basic Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Survey Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Survey Title</label>
                                    <input type="text" id="surveyTitle" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue" placeholder="Enter survey title" value="IT Services Experience Survey">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Office</label>
                                    <select id="surveyOffice" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue">
                                        <option value="it" selected>Information Technology Office</option>
                                        <option value="registrar">Registrar's Office</option>
                                        <option value="library">Library</option>
                                        <option value="cashier">Cashier</option>
                                        <option value="medical">Medical & Dental Clinic</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Service</label>
                                    <select id="surveyService" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue">
                                        <option value="classroom-assistance" selected>Classroom Technical Assistance</option>
                                        <option value="online-inquiry">Online Inquiry / Technical assistance</option>
                                        <option value="face-to-face">Face-To-Face inquiry assistance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                                    <select id="surveyTemplate" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue">
                                        <option value="standard" selected>Standard Service Template</option>
                                        <option value="blank">Start from Blank</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                       

                        <!-- Questions Builder -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">Survey Questions</h3>
                                <div class="flex space-x-2">
                                    <button id="addQuestion" class="bg-jru-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors flex items-center">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Question
                                    </button>
                                    <button id="addSection" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
                                        <i class="fas fa-layer-group mr-2"></i>
                                        Add Section
                                    </button>
                                </div>
                            </div>

                            <!-- Questions List -->
                            <div id="questionsList" class="space-y-4">
                                <!-- Questions will be dynamically added here -->
                            </div>

                            <!-- Add Question Button (Bottom) -->
                            <div class="mt-6 text-center">
                                <button id="addQuestionBottom" class="w-full border-2 border-dashed border-gray-300 rounded-lg py-4 text-gray-500 hover:border-gray-400 hover:text-gray-600 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add New Question
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Live Preview -->
                    
                </div>
            </main>
        </div>
    </div>

    <!-- Question Editor Modal -->
    <div id="questionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">Edit Question</h2>
                        <button id="closeQuestionModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    <form id="questionForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 pb-6 mb-6 border-b border-gray-200">
                        
                            <div>
                                <label for="questionType" class="block text-sm font-medium text-gray-700">Question Type</label>
                                <!-- FIX: Added padding, background, and appearance-none for a cleaner look -->
                                <div class="relative mt-1">
                                    <select id="questionType" class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-jru-blue bg-white appearance-none">
                                        <option value="likert">Emoji Scale (1-5)</option>
                                        <option value="rating">Star Rating (1-5)</option>
                                        <option value="textarea">Text Response</option>
                                    </select>
                                    <!-- FIX: Added a custom chevron icon for a professional dropdown style -->
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Required</label>
                                <!-- FIX: Used flexbox and alignment classes to perfectly align radio buttons with their text -->
                                <div class="flex items-center space-x-6 mt-2">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="required" value="true" class="h-4 w-4 text-jru-blue focus:ring-jru-blue border-gray-300" checked>
                                        <span class="ml-2 text-sm text-gray-800">Required</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="required" value="false" class="h-4 w-4 text-jru-blue focus:ring-jru-blue border-gray-300">
                                        <span class="ml-2 text-sm text-gray-800">Optional</span>
                                    </label>
                                </div>
                            </div>

                        </div>

                        <div class="space-y-6">
                            <div>
                                <label for="questionTitle" class="block text-sm font-medium text-gray-900">Question Title</label>
                                <p class="text-xs text-gray-500 mt-1">A short label for reports and analytics.</p>
                                <input type="text" id="questionTitle" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Staff Courtesy, Service Speed">
                            </div>

                            <div>
                                <label for="questionText" class="block text-sm font-medium text-gray-900">Full Question Text</label>
                                <p class="text-xs text-gray-500 mt-1">This is the complete question that will be shown to the respondent.</p>
                                <textarea id="questionText" rows="3" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., How would you rate the professionalism and courtesy of the staff?"></textarea>
                            </div>

                            <div>
                                <label for="questionHelp" class="block text-sm font-medium text-gray-900">Help Text <span class="font-normal text-gray-500">(Optional)</span></label>
                                <p class="text-xs text-gray-500 mt-1">Provide extra context or instructions for the user.</p>
                                <input type="text" id="questionHelp" class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Please consider their attitude and responsiveness.">
                            </div>
                        </div>

                    
                        <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                            
                            <button type="button" id="cancelQuestion" class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        
                            <button type="submit" class="px-6 py-2 bg-jru-blue text-white rounded-lg text-sm font-semibold hover:bg-blue-800 transition-colors">
                                Save Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
        <div id="templateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Save as New Template</h2>
                    <button id="closeTemplateModal" class="text-gray-400 hover:text-gray-600">×</button>
                </div>
                <div class="p-6">
                    <form id="templateForm">
                        <div class="mb-4">
                            <label for="templateName" class="block text-sm font-medium text-gray-700 mb-2">Template Name <span class="text-red-500">*</span></label>
                            <input type="text" id="templateName" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        </div>
                        <div class="mb-6">
                            <label for="templateDescription" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                            <textarea id="templateDescription" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button type="button" id="cancelTemplate" class="px-6 py-2 border rounded-lg">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-jru-blue text-white rounded-lg">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
     <!-- Reusable Confirmation Modal -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i id="confirmationIcon" class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4 text-left">
                            <h3 id="confirmationTitle" class="text-lg leading-6 font-bold text-gray-900">
                                Confirm Action
                            </h3>
                            <div class="mt-2">
                                <p id="confirmationMessage" class="text-sm text-gray-600">
                                    Are you sure? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
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
    </div>
        <!-- Success and Error Modal -->
    <div id="toastNotification" class="fixed top-5 right-5 text-white py-3 px-6 rounded-lg shadow-xl z-[100] transition-all duration-300 ease-in-out opacity-0 hidden">
    <div class="flex items-center">
        <i id="toastIcon" class="mr-3 text-xl"></i>
        <span id="toastMessage" class="font-medium"></span>
    </div>
    </div>
    <!-- Live Survey Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-gray-100 rounded-xl shadow-2xl w-full max-w-lg transform transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-eye mr-2 text-jru-blue"></i>Live Survey Preview</h2>
            <button id="previewCloseBtn" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
            <!-- Progress Indicator -->
            <div id="previewProgress" class="text-center text-sm font-medium text-gray-500 mb-4">
                Question 1 of 5
            </div>
            
            <!-- Question Content (dynamically populated) -->
            <div id="previewQuestionContainer" class="bg-white p-6 rounded-lg shadow-inner min-h-[200px]">
                <!-- Question text and input will be rendered here by JavaScript -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-200 rounded-b-xl flex justify-between items-center">
            <button id="previewPrevBtn" class="px-6 py-2 border border-gray-400 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Previous
            </button>
            <button id="previewNextBtn" class="px-6 py-2 bg-jru-blue text-white rounded-lg hover:bg-blue-800 transition-colors">
                Next<i class="fas fa-arrow-right ml-2"></i>
            </button>
            <!-- The submit button will be shown on the last question -->
            <button id="previewSubmitBtn" class="hidden px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-check-circle mr-2"></i>Submit
            </button>
        </div>
    </div>
</div>

    <script src="js/survey-builder.js">  </script>
</body>
</html>