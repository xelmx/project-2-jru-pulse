<?php
// --- logout.php ---

// 1. Always start the session to access the session data
session_start();

// 2. Unset all of the session variables
// This removes all data stored in the session, like the 'user_data' array
$_SESSION = [];

// 3. Destroy the session itself
// This invalidates the user's session ID cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirect the user back to the login page
// They are now fully logged out.
header('Location: index.php?status=logged_out');
exit;

?>