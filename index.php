
<?php
session_start(); // Start the session to check for login data

// Check if the user is already logged in
if (isset($_SESSION['user_data'])) {
    // If they are, redirect them straight to the dashboard
    header('Location: dashboard.php');
    exit; // Important to stop the script from rendering the login page
}

// Check for any error messages from other pages (like a failed login)
$error = $_GET['error'] ?? '';
$errorMessage = '';

if ($error === 'unauthorized') {
    $errorMessage = 'Your email account is not authorized to access this system.';
} elseif ($error === 'auth_required') {
    $errorMessage = 'You must be logged in to view that page.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JRU PULSE Login</title>
    <link href="css/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   
    <style>
      
    </style>
</head>
<body class="min-h-screen bg-gray-100 bg-[url('assets/JRU-PULSE-bg.svg')] bg-left-top bg-no-repeat bg-fixed bg-[length:auto_100vh] overflow-hidden relative">
    <div class="relative z-10 min-h-screen flex items-center px-4 sm:px-8 md:px-12 lg:px-16">
        <div class="flex-shrink-0 w-3/6"></div>
        <div class="w-full max-w-md">
            <div class="bg-white rounded-3xl shadow-2xl p-8 backdrop-blur-sm bg-opacity-95">
                <div class="text-center mb-8">
                    <img src="assets/JRU-PULSE-logo.svg" alt="JRU-A-PULSE Logo" class="inline-block h-15 w-auto mb-2">
                    <h2 class="text-sm font-semibold text-gray-600">Sign In</h2>
                </div>
                <div class="space-y-4">
                    <!-- IMPORTANT: Link to callback.php -->
                      <?php if (!empty($errorMessage)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative text-sm" role="alert">
                            <strong class="font-bold">Access Denied:</strong>
                            <span class="block sm:inline"><?php echo htmlspecialchars($errorMessage); ?></span>
                        </div>
                    <?php endif; ?>

                    <a href="callback.php" id="googleLoginBtn" class="google-btn w-full bg-white border-2 border-gray-200 rounded-full py-4 px-6 flex items-center justify-center space-x-3 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-jru-orange focus:ring-offset-2">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="text-gray-700 font-medium">Login in with Google</span>
                    </a>

                    
                </div>
                <div class="my-6 flex items-center">
                    <div class="flex-1 border-t border-gray-200"></div>
                    <span class="px-4 text-sm text-gray-500">Secure Admin Access</span>
                    <div class="flex-1 border-t border-gray-200"></div>
                </div>
                <div class="text-center space-y-2">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-shield-alt text-jru-orange mr-2"></i>
                        Authorized Personnel Only
                    </p>
                    <p class="text-xs text-gray-500">
                        Use your JRU Google account to access
                    </p>
                </div>
                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <p class="text-xs text-gray-400">
                          José Rizal University <!-- This PHP won't work in .html, change to static or use .php extension -->
                        <!-- Simpler: © <span id="year"></span> Jose Rizal University -->
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                       © Performance & User-satisfaction Linked Service Evaluation
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>