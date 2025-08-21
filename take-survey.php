<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JRU Pulse Survey</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
     <link href="css/output.css" rel="stylesheet">
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans p-4">

    <div class="w-full max-w-lg mx-auto">
        <!-- Survey Header (will be shown after verification) -->
        <div id="surveyHeader" class="text-center mb-4 hidden">
            <img src="assets/jru-pulse-final.png" alt="JRU Pulse Logo" class="mx-auto h-12 w-auto mb-2">
            <h1 id="surveyTitle" class="text-2xl font-bold text-gray-800">Loading Survey...</h1>
            <p id="surveyDescription" class="text-sm text-gray-600 mt-1"></p>
        </div>

        <!-- Main Container for all screens -->
        <div id="mainContainer" class="bg-white rounded-xl shadow-lg overflow-hidden">
            
            <!-- SCREEN 1: Identity Choice -->
            <div id="identityScreen" class="p-8">
                <div class="text-center mb-6">
                    <img src="assets/jru-pulse-final.png" alt="JRU Pulse Logo" class="mx-auto h-12 w-auto mb-2">
                    <h2 class="text-2xl font-bold text-gray-800">How are you connected to JRU?</h2>
                </div>
                <div class="space-y-4">
                    <button id="studentBtn" class="w-full text-lg font-semibold py-4 px-6 bg-jru-blue text-white rounded-lg hover:bg-blue-900 transition-colors">
                        I am a JRU Student
                    </button>
                    <button id="guestBtn" class="w-full text-lg font-semibold py-4 px-6 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors">
                        I am a Guest (Parent, Alumni, Visitor, etc.)
                    </button>
                </div>
            </div>

            <!-- SCREEN 2A: Student Path -->
            <div id="studentScreen" class="p-8 hidden">'
                <div id="studentError" class="error-message hidden bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md"></div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Student Verification</h2>
                <p class="text-gray-600 mb-6">Please sign in with your JRU Google account (<span class="font-semibold">@my.jru.edu</span>) to continue.</p>
                
                <div id="googleSignInButton" class="flex justify-center"></div>

                <div class="mt-6 flex items-center">
                    <input id="studentConsentCheck" type="checkbox" class="h-4 w-4 text-jru-blue border-gray-300 rounded focus:ring-jru-blue">
                    <label for="studentConsentCheck" class="ml-2 block text-sm text-gray-700">
                        I agree to the <a href="#" id="viewTermsStudent" class="font-medium text-jru-blue hover:underline">Data Privacy Terms</a>.
                    </label>
                </div>
                
                <div class="mt-8 text-center">
                    <button class="go-back-btn text-sm text-gray-500 hover:text-gray-700">← Go Back</button>
                </div>
            </div>

            <!-- SCREEN 2B: Guest Path -->
            <div id="guestScreen" class="p-8 hidden">
                 <div id="guestError" class="error-message hidden bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md"></div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Welcome!</h2>
                <p class="text-gray-600 mb-6">Please provide the following details to proceed with the survey.</p>
                
                <form id="guestForm">
                    <div class="space-y-4">
                        <div>
                            <label for="guestFirstName" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                            <input type="text" id="guestFirstName" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="guestLastName" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" id="guestLastName" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label for="guestEmail" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="guestEmail" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="you@example.com" required>
                        </div>
                        <div>
                            <label for="guestRole" class="block text-sm font-medium text-gray-700">Your Role</label>
                            <select id="guestRole" name="role" required 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-jru-blue focus:border-jru-blue sm:text-sm">
                                <option value="" disabled selected>Please select your role</option>
                                <option value="JRU Alumnus">JRU Alumnus</option>
                                <option value="JRU Parent/Guardian">JRU Parent/Guardian</option>
                                <option value="Student (Non-JRU)">Student (Non-JRU)</option>
                                <option value="Parent/Guardian (Non-JRU)">Parent/Guardian (Non-JRU)</option>
                                <option value="Industry Partner">Industry Partner</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center">
                        <input id="guestConsentCheck" type="checkbox" class="h-4 w-4 text-jru-blue border-gray-300 rounded focus:ring-jru-blue">
                        <label for="guestConsentCheck" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="#" id="viewTermsGuest" class="font-medium text-jru-blue hover:underline">Data Privacy Terms</a>.
                        </label>
                    </div>

                    <button id="guestContinueBtn" type="submit" class="mt-6 w-full text-lg font-semibold py-3 px-6 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition-colors" disabled>
                        Continue to Survey
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <button class="go-back-btn text-sm text-gray-500 hover:text-gray-700">← Go Back</button>
                </div>
            </div>

            <!-- SCREEN 3: The Actual Survey (will be populated later) -->
            <div id="surveyScreen" class="p-8 hidden">
                <!-- Survey questions will be rendered here by your existing logic -->
            </div>

             <div id="loadingScreen" class="p-8 text-center hidden">
                <i class="fas fa-spinner fa-spin text-jru-blue text-4xl"></i>
                <p id="loadingMessage" class="mt-4 text-gray-700">Loading...</p>
            </div>

             <div id="thankYouScreen" class="p-8 text-center hidden">
                <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                <h1 id="thankYouMessage" class="text-2xl font-bold text-gray-900">Thank you!</h1>
                <p class="text-gray-600 mt-2">Your feedback has been submitted.</p>
            </div>

        </div>
    </div>

    <!-- Data Privacy Terms Modal -->
    <div id="termsModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">JRU-PULSE Data Privacy Notice</h2>
                <button id="closeTermsModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-grow">
                <p class="mb-4 text-gray-700">
                    Jose Rizal University is committed to protecting your privacy in accordance with Republic Act No. 10173, also known as the Data Privacy Act of 2012.
                </p>
                <p class="mb-4 text-gray-700">
                    By participating in this survey, you agree to the collection and processing of your personal information (such as your role, student number, or email address) and your survey responses.
                </p>
                <h3 class="font-semibold mt-4 mb-2 text-gray-800">Purpose of Data Collection:</h3>
                <ul class="list-disc list-inside text-gray-700 space-y-1">
                    <li>To gather feedback for the sole purpose of improving university services, facilities, and processes.</li>
                    <li>To generate aggregated, statistical reports for university management and quality assurance.</li>
                </ul>
                <h3 class="font-semibold mt-4 mb-2 text-gray-800">Confidentiality:</h3>
                <p class="mb-4 text-gray-700">
                    Your individual responses will be treated with the utmost confidentiality. Any published reports will be in an aggregated and anonymized format, ensuring that individual respondents cannot be identified.
                </p>
                <p class="font-medium text-gray-900 mt-6">
                    By checking the consent box on the previous screen, you acknowledge that you have read and understood this notice and you give your consent to the collection and processing of your data for the purposes stated above.
                </p>
            </div>
            
            <div class="p-4 bg-gray-100 border-t flex justify-end">
                <button id="closeTermsModalFooter" class="px-6 py-2 bg-jru-blue text-white rounded-lg hover:bg-blue-800 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Load Google Library -->
    <script src="https://accounts.google.com/gsi/client?onload=onGoogleLibraryLoad" async defer></script>
    <script src="js/take-survey.js"></script>
</body>
</html>