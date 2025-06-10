<?php
// Egy egyszerű teszt szkript az adatbázis-kapcsolat és a dátumszűrés ellenőrzésére

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Budapest');

header('Content-Type: text/html; charset=utf-8');

// Adatbázis adatok
$db_host = 'localhost';
$db_name = 'tdepo_vezetoi_dash';
$db_user = 'tdepo_dash_admin';
$db_pass = 'Hammer11!';

echo "<!DOCTYPE html><html lang='hu'><head><title>Adatbázis Teszt</title>";
echo "<style>body{font-family:sans-serif; padding: 2em;} table{border-collapse:collapse; margin-top:1em;} th,td{border:1px solid #ccc;padding:0.5em;}</style>";
echo "</head><body><h1>Adatbázis Diagnosztika</h1>";

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        throw new Exception("Kapcsolódási hiba: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    mysqli_query($conn, "SET time_zone = 'Europe/Budapest'");
    echo "<p style='color:green;'>1. Adatbázis-kapcsolat sikeres, időzóna beállítva (Europe/Budapest).</p>";

    // --- TESZT 1: Mit gondol a szerver és az adatbázis a jelenlegi időről? ---
    echo "<h2>Időzóna Ellenőrzése</h2>";
    $php_time = date('Y-m-d H:i:s');
    $mysql_time_result = mysqli_query($conn, "SELECT NOW();");
    $mysql_time = mysqli_fetch_array($mysql_time_result)[0];
    echo "<p>PHP szerver ideje: <strong>$php_time</strong></p>";
    echo "<p>MySQL szerver ideje: <strong>$mysql_time</strong></p>";
    if (substr($php_time, 0, 13) == substr($mysql_time, 0, 13)) {
        echo "<p style='color:green;'>Az időzónák szinkronban vannak. Ez jó.</p>";
    } else {
        echo "<p style='color:red;'>Figyelem: A PHP és a MySQL időzónája eltérhet! Ez okozhatja a hibát.</p>";
    }

    // --- TESZT 2: Van-e adat a MAI napra? ---
    echo "<h2>Adatok ellenőrzése a mai napra</h2>";
    $today_date = date('Y-m-d');
    $sql_today = "SELECT COUNT(*) as darab, SUM(rendelesek_szama) as osszes_rendeles FROM kiszedesek WHERE DATE(kezdes_idopont) = ?";
    $stmt_today = mysqli_prepare($conn, $sql_today);
    mysqli_stmt_bind_param($stmt_today, "s", $today_date);
    mysqli_stmt_execute($stmt_today);
    $result_today = mysqli_stmt_get_result($stmt_today)->fetch_assoc();
    mysqli_stmt_close($stmt_today);
    echo "<p>A mai napra (<strong>$today_date</strong>) a lekérdezés eredménye: <strong>" . $result_today['darab'] . "</strong> adatsor, ami összesen <strong>" . $result_today['osszes_rendeles'] . "</strong> rendelést tartalmaz.</p>";

    // --- TESZT 3: Nézzünk bele az 5 legutóbbi adatba ---
    echo "<h2>5 legutóbbi adat a `kiszedesek` táblából</h2>";
    $sql_sample = "SELECT * FROM `kiszedesek` ORDER BY `id` DESC LIMIT 5";
    $result_sample = mysqli_query($conn, $sql_sample);
    echo "<table><thead><tr><th>ID</th><th>Azonosító</th><th>Munkatárs</th><th>Kezdés</th><th>Befejezés</th><th>Időtartam(mp)</th><th>Rendelések</th><th>Termékek</th><th>Terv</th></tr></thead><tbody>";
    while($row = mysqli_fetch_assoc($result_sample)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kiszedes_azonosito']) . "</td>";
        echo "<td>" . htmlspecialchars($row['munkatars']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kezdes_idopont']) . "</td>";
        echo "<td>" . htmlspecialchars($row['befejezes_idopont']) . "</td>";
        echo "<td>" . htmlspecialchars($row['idotartam_mp']) . "</td>";
        echo "<td>" . htmlspecialchars($row['rendelesek_szama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tenyleges_termek_db']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tervezett_termek_db']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";

    mysqli_close($conn);

} catch (Exception $e) {
    echo "<h2 style='color:red;'>KRITIKUS HIBA</h2><p>" . $e->getMessage() . "</p>";
}
echo "</body></html>";
?>