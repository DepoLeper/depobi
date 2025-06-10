<?php
// date_default_timezone_set('Europe/Budapest');
session_start();

// Munkamenet változók törlése
$_SESSION = array();

// Munkamenet megszüntetése
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Átirányítás a login oldalra
header("location: login.php");
exit;
?>