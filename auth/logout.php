<?php
// auth/logout.php
session_start();

// 1. Unset all session superglobal variables
$_SESSION = array();

// 2. Destroys the active session cookie client-side if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Clear the session state from server memory
session_destroy();

// 4. Redirect back to your login workspace root page
header("Location: login.php");
exit;