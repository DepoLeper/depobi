<?php
// Fájl helye: management/import_kiszedesek.php
// Feladat: A dedikált 'Kiszedések' feed adatainak importálása a `kiszedesek` táblába.
// Végleges, javított verzió.

set_time_limit(600);
ini_set('display_errors', 1); error_reporting(E_ALL);
date_default_timezone_set('Europe/Budapest');

echo "<h1>Kiszedések Adatimportáló Szkript</h1><p>Szkript indítva: " . date("Y-m-d H:i:s") . "</p><hr>";

$feed_url = 'https://app.clouderp.hu/api/1/automatism/file-share/?s=Z0FBQUFBQm9SZUNLX2dFbEIyTXB3SFhNaGEyYW42cFVvcVVsWUZaUi1SaUQyQlFMN2REVzJSU0oxTWVpdjVEejl6U1BQamgxQWJjUlVJREI5VDVEZ3pBSEV5dzltRDdZbzh1Vi1nT3pxRUZtT1hfZFNRbHphRGs9';
$db_host = 'localhost'; $db_name = 'tdepo_vezetoi_dash'; $db_user = 'tdepo_dash_admin'; $db_pass = 'Hammer11!';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("DB HIBA: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");
echo "<p>Adatbázis-kapcsolat OK.</p>";

$ch = curl_init(); curl_setopt_array($ch, [ CURLOPT_URL => $feed_url, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 120, ]); $csv_data = curl_exec($ch);
if (curl_errno($ch)) { die("cURL HIBA: " . curl_error($ch)); } curl_close($ch);
echo "<p>CSV Letöltés OK.</p>";

$lines = explode("\n", trim($csv_data));
$sql = "INSERT IGNORE INTO `kiszedesek` (`kiszedes_azonosito`, `munkatars`, `kezdes_idopont`, `befejezes_idopont`, `idotartam_mp`, `rendelesek_szama`, `tenyleges_termek_db`, `tervezett_termek_db`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception(mysqli_error($conn)); }
    $inserted_rows = 0; $skipped_rows = 0;
    for ($i = 1; $i < count($lines); $i++) {
        $line = trim($lines[$i]); if (empty($line)) continue;
        $values = str_getcsv($line);
        if (count($values) < 8 || empty($values[0])) { $skipped_rows++; continue; }
        $kezdes_dt = new DateTime($values[2]);
        $befejezes_dt = new DateTime($values[3]);

        $azonosito = $values[0]; $munkatars = $values[1];
        $kezdes_db_format = $kezdes_dt->format('Y-m-d H:i:s');
        $befejezes_db_format = $befejezes_dt->format('Y-m-d H:i:s');
        $idotartam = (int)($values[4] ?? 0); $rendelesek = (int)($values[5] ?? 0);
        $tenyleges_db = (int)($values[6] ?? 0); $tervezett_db = (int)($values[7] ?? 0);
        
        mysqli_stmt_bind_param($stmt, "ssssiiis", $azonosito, $munkatars, $kezdes_db_format, $befejezes_db_format, $idotartam, $rendelesek, $tenyleges_db, $tervezett_db);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_affected_rows($stmt) > 0) { $inserted_rows++; }
    }
    mysqli_commit($conn); mysqli_stmt_close($stmt);
} catch(Exception $e) { mysqli_rollback($conn); die("HIBA: " . $e->getMessage()); }
mysqli_close($conn);
echo "<h2>Kész!</h2><p>Beillesztve: $inserted_rows</p><p>Kihagyva: $skipped_rows</p>";
?>