<?php
declare(strict_types=1);

// Start session to destroy it
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Additional cookie cleanup
setcookie('PHPSESSID', '', time() - 3600, '/', '', false, true);

// Destroy the session
session_destroy();

// Redirect to login route
header("Location: " . $_ENV['BASE_PATH'] . "/login");
exit;
