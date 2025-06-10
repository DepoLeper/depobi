<?php
// Egy egyszerű teszt szkript az adatbázis-kapcsolat és olvasás ellenőrzésére

// Hibák megjelenítése a tiszta diagnózisért
ini_set('display_errors', 1);
error_reporting(E_ALL);

// A válasz típusa JSON lesz
header('Content-Type: application/json; charset=utf-8');

// Adatbázis adatok
$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!'; // <-- FONTOS: Ide a helyes, működő jelszó kerüljön!

// A válasz objektum inicializálása
$response = ['success' => false, 'data' => [], 'message' => 'Ismeretlen hiba.'];

try {
    // 1. Kapcsolódás
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Adatbázis kapcsolódási hiba: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");

    // 2. A lehető legegyszerűbb lekérdezés
    $sql = "SELECT * FROM `kiszedesek` ORDER BY `id` DESC LIMIT 5";
    
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        throw new Exception("SQL lekérdezési hiba: " . mysqli_error($conn));
    }
    
    // 3. Eredmény feldolgozása
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    mysqli_close($conn);

    // 4. Sikeres válasz összeállítása
    $response['success'] = true;
    $response['data'] = $data;
    $response['message'] = count($data) . " sor sikeresen lekérdezve a 'kiszedesek' táblából.";

} catch (Exception $e) {
    // Hiba esetén a válasz összeállítása
    http_response_code(500); // Hibakód beállítása
    $response['message'] = $e->getMessage();
}

// A válasz elküldése
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>