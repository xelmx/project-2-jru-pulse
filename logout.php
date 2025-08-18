<?php

session_start();

// Unset all of the session variables, removes all data stored in the session, like the 'user_data' array
$_SESSION = [];

// 3. Destroy the session itself, invalidates the user's session ID cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect the user back to the login pag, and are now fully logged out.
header('Location: index.php?status=logged_out');
exit;

?>