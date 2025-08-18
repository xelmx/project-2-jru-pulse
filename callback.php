<?php
session_start();

require __DIR__ . '/vendor/autoload.php'; // 1. Load Composer's autoloader for Google's library
require_once 'config/connection.php'; // 2. Load our standard database connection


try {
    // __DIR__ points to the directory of this file, which is the project root
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // If the .env file is missing or unreadable, give a very clear error.
    error_log("FATAL: Could not load .env file. Error: " . $e->getMessage());
    die("FATAL ERROR: The application's configuration file (.env) is missing or could not be read. Please check the file exists in the root directory: " . __DIR__);
}


$CLIENT_ID = $_ENV['GOOGLE_CLIENT_ID'];
$CLIENT_SECRET = $_ENV['GOOGLE_CLIENT_SECRET'];              
$REDIRECT_URI = $_ENV['GOOGLE_REDIRECT_URI'];

$client = new Google_Client();
$client->setClientId($CLIENT_ID);
$client->setClientSecret($CLIENT_SECRET);
$client->setRedirectUri($REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// --- Google OAuth Flow ---

// 3. If no authorization code is present, redirect to Google to get one
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit;
}

// 4. Exchange the authorization code for an access token
try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        // Redirect back to login with a clear error
        header('Location: index.php?error=' . urlencode($token['error_description']));
        exit;
    }
    $client->setAccessToken($token);
    
    // Get user's profile information from Google
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $firstName = $google_account_info->givenName;
    $lastName = $google_account_info->familyName;

} catch (Exception $e) {
    // If anything goes wrong with the Google communication, redirect with an error
    error_log("Google OAuth Error: " . $e->getMessage());
    header('Location: index.php?error=google_communication_failed');
    exit;
}


// 5. --- THIS IS THE NEW, CRITICAL DATABASE VERIFICATION STEP ---
try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if the user's email exists in our 'users' table and is active
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = TRUE");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // --- USER IS AUTHORIZED ---
        // The user was found in our database. Store their info in the session.
        
        $_SESSION['user_data'] = [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        // Redirect to the dashboard upon successful login
        header('Location: dashboard.php');
        exit;

    } else {
        // --- USER IS NOT AUTHORIZED ---
        // The user's Google account is valid, but their email is not in our 'users' table.
        // Redirect them back to the login page with a specific "unauthorized" error.
        header('Location: index.php?error=unauthorized');
        exit;
    }

} catch (Exception $e) {
    // Handle database connection errors
    error_log("Database verification error: " . $e->getMessage());
    header('Location: index.php?error=database_error');
    exit;
}

?>